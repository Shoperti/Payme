<?php

namespace Shoperti\PayMe\Gateways\Conekta;

use Exception;
use Shoperti\PayMe\ErrorCode;
use Shoperti\PayMe\Gateways\AbstractGateway;
use Shoperti\PayMe\Response;
use Shoperti\PayMe\ResponseException;
use Shoperti\PayMe\Status;
use Shoperti\PayMe\Support\Arr;

/**
 * This is the Conekta gateway class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 * @author Arturo Rodríguez <arturo.rodriguez@dinkbit.com>
 */
class ConektaGateway extends AbstractGateway
{
    /**
     * Gateway API endpoint.
     *
     * @var string
     */
    protected $endpoint = 'https://api.conekta.io';

    /**
     * Gateway display name.
     *
     * @var string
     */
    protected $displayName = 'conekta';

    /**
     * Gateway default currency.
     *
     * @var string
     */
    protected $defaultCurrency = 'MXN';

    /**
     * Gateway money format.
     *
     * @var string
     */
    protected $moneyFormat = 'cents';

    /**
     * Conekta API version.
     *
     * @var string
     */
    protected $apiVersion = '2.0.0';

    /**
     * Conekta API locale.
     *
     * @var string
     */
    protected $locale = 'es';

    /**
     * Inject the configuration for a Gateway.
     *
     * @param string[] $config
     *
     * @return void
     */
    public function __construct(array $config)
    {
        Arr::requires($config, ['private_key']);

        $config['version'] = $this->apiVersion;
        $config['locale'] = $this->locale;

        parent::__construct($config);
    }

    /**
     * Commit a HTTP request.
     *
     * @param string   $method
     * @param string   $url
     * @param string[] $params
     * @param string[] $options
     *
     * @return mixed
     */
    public function commit($method, $url, $params = [], $options = [])
    {
        $userAgent = [
            'bindings_version' => $this->config['version'],
            'lang'             => 'php',
            'lang_version'     => phpversion(),
            'publisher'        => 'conekta',
            'uname'            => php_uname(),
        ];

        $request = [
            'exceptions'      => false,
            'timeout'         => '80',
            'connect_timeout' => '30',
            'headers'         => [
                'Accept'                      => "application/vnd.conekta-v{$this->config['version']}+json",
                'Accept-Language'             => $this->config['locale'],
                'Authorization'               => 'Basic '.base64_encode($this->config['private_key'].':'),
                'Content-Type'                => 'application/json',
                'RaiseHtmlError'              => 'false',
                'X-Conekta-Client-User-Agent' => json_encode($userAgent),
                'User-Agent'                  => 'Conekta PayMeBindings/'.$this->config['version'],
            ],
        ];

        if (!empty($params)) {
            $request[$method === 'get' ? 'query' : 'json'] = $params;
        }

        $rawResponse = $this->getHttpClient()->{$method}($url, $request);
        $statusCode = $rawResponse->getStatusCode();

        $response = $statusCode == 200
            ? $this->parseResponse($rawResponse->getBody())
            : $this->responseError($rawResponse->getBody(), $statusCode);

        try {
            return $this->respond($response);
        } catch (Exception $e) {
            throw new ResponseException($e, $response);
        }
    }

    /**
     * Respond with an array of responses or a single response.
     *
     * @param array $response
     *
     * @return array|\Shoperti\PayMe\Contracts\ResponseInterface
     */
    protected function respond($response)
    {
        if (Arr::get($response, 'object') === 'list') {
            if (!empty($response['data'])) {
                foreach ($response['data'] as $responseItem) {
                    $success = Arr::get($responseItem, 'object', 'error') !== 'error';

                    $responses[] = $this->mapResponse($success, $responseItem);
                }

                return $responses;
            } else {
                $response = $response['data'];
            }
        }

        $success = Arr::get($response, 'object', 'error') !== 'error';

        return $this->mapResponse($success, $response);
    }

    /**
     * Map HTTP response to transaction object.
     *
     * @param bool  $success
     * @param array $response
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function mapResponse($success, $response)
    {
        $rawResponse = $response;

        $object = Arr::get($response, 'object');

        if ($object !== 'error' && array_key_exists('type', $response) && isset($response['data']['object'])) {
            $response = $response['data']['object'];
        }

        $type = $this->getType($rawResponse);
        list($reference, $authorization) = $success ? $this->getReferences($response, $type) : [null, null];

        $message = '';

        if ($success) {
            $message = 'Transaction approved';
        } elseif ($object === 'error') {
            foreach (Arr::get($response, 'details') as $detail) {
                $message .= ' '.Arr::get($detail, 'message', '');
            }
            $message = ltrim($message);
        } else {
            $message = Arr::get($response, 'message_to_purchaser') ?: Arr::get($response, 'message', '');
        }

        $isTest = $object === 'error' && isset($response['data'])
            ? !(Arr::get($response['data'], 'livemode', true))
            : !(Arr::get($response, 'livemode', true));

        if ($type === 'refund') {
            $rawResponse['amount_refunded'] = $this->getRefundAmount($rawResponse);
        }

        return (new Response())->setRaw($rawResponse)->map([
            'isRedirect'    => false,
            'success'       => $success,
            'reference'     => $reference,
            'message'       => $message,
            'test'          => $isTest,
            'authorization' => $authorization,
            'status'        => $success ? $this->getStatus(Arr::get($response, 'payment_status', 'paid')) : new Status('failed'),
            'errorCode'     => $success ? null : $this->getErrorCode($response),
            'type'          => $type,
        ]);
    }

    /**
     * Get the transaction type.
     *
     * @param array $rawResponse
     *
     * @return string
     */
    protected function getType($rawResponse)
    {
        if ($type = Arr::get($rawResponse, 'type')) {
            return $type;
        }

        switch (Arr::get($rawResponse, 'payment_status')) {
            case 'partially_refunded':
            case 'refunded':
                return 'refund';
        }

        return Arr::get($rawResponse, 'object');
    }

    /**
     * Get the last refund amount.
     *
     * @param array $response
     *
     * @return int
     */
    protected function getRefundAmount($response)
    {
        $lastCharge = Arr::last($response['charges']['data']);
        $lastRefund = Arr::last($lastCharge['refunds']['data']);

        return abs($lastRefund['amount']);
    }

    /**
     * Get the transaction reference and auth code.
     *
     * @param array  $response
     * @param string $type
     *
     * @return array
     */
    protected function getReferences($response, $type)
    {
        if (in_array($type, ['order', 'refund'])) {
            $charges = $response['charges']['data'];
            $charge = Arr::last($charges);

            if ($type === 'refund') {
                $refunds = $charge['refunds']['data'];
                $refund = Arr::last($refunds);

                return [$refund['id'], $refund['auth_code']];
            }

            $id = $charge['id'];
        } else {
            $id = $response['id'];
        }

        return [$id, $this->getAuthorization($response)];
    }

    /**
     * Map reference to response.
     *
     * @param array $response
     *
     * @return string|null
     */
    protected function getAuthorization($response)
    {
        $object = Arr::get($response, 'object');

        if ($object == 'customer') {
            return Arr::get($response, 'default_payment_source_id');
        } elseif ($object == 'payment_source') {
            return Arr::get($response, 'parent_id');
        } elseif ($object == 'payee') {
            return Arr::get($response, 'id');
        } elseif ($object == 'transfer') {
            return Arr::get($response, 'id');
        } elseif ($object == 'event') {
            return Arr::get($response, 'id');
        }

        if (isset($response['charges'])) {
            $charges = $response['charges']['data'];
            $charge = Arr::last($charges);
            $paymentMethod = $charge['payment_method'];

            if (isset($paymentMethod['auth_code'])) {
                return $paymentMethod['auth_code'];
            } elseif (isset($paymentMethod['reference'])) {
                return $paymentMethod['reference'];
            } elseif (isset($paymentMethod['clabe'])) {
                return $paymentMethod['clabe'];
            }
        }
    }

    /**
     * Map Conekta response to status object.
     *
     * @param string $status
     *
     * @return \Shoperti\PayMe\Status
     */
    protected function getStatus($status)
    {
        switch ($status) {
            case 'pending_payment':
                return new Status('pending');
            case 'paid':
            case 'refunded':
            case 'partially_refunded':
            case 'paused':
            case 'active':
            case 'canceled':
                return new Status($status);
            case 'in_trial':
                return new Status('trial');
        }
    }

    /**
     * Map Conekta response to error code object.
     *
     * @param array $response
     *
     * @return \Shoperti\PayMe\ErrorCode
     */
    protected function getErrorCode($response)
    {
        $code = isset($response['details']) ? $response['details'][0]['code'] : null;

        switch ($code) {
            case 'conekta.errors.processing.bank.declined':
            case 'conekta.errors.processing.bank_bindings.declined':
                return new ErrorCode('card_declined');

            case 'conekta.errors.processing.bank.insufficient_funds':
            case 'conekta.errors.processing.bank_bindings.insufficient_funds':
                return new ErrorCode('insufficient_funds');

            case 'conekta.errors.processing.charge.card_payment.suspicious_behaviour':
                return new ErrorCode('suspected_fraud');

            case 'conekta.errors.parameter_validation.expiration_date.expired':
                return new ErrorCode('invalid_expiry_date');

            case 'conekta.errors.parameter_validation.card.number':
                return new ErrorCode('invalid_number');

            case 'conekta.errors.parameter_validation.card.cvc':
                return new ErrorCode('invalid_cvc');
        }

        $code = Arr::get($response, 'type');

        return new ErrorCode($code === 'processing_error' ? $code : 'config_error');
    }

    /**
     * Parse JSON response to array.
     *
     * @param string $body
     *
     * @return array|null
     */
    protected function parseResponse($body)
    {
        return json_decode($body, true);
    }

    /**
     * Get error response from server or fallback to general error.
     *
     * @param string $body
     * @param int    $httpCode
     *
     * @return array
     */
    protected function responseError($body, $httpCode)
    {
        return $this->parseResponse($body) ?: $this->jsonError($body, $httpCode);
    }

    /**
     * Default JSON response.
     *
     * @param string $rawResponse
     * @param int    $httpCode
     *
     * @return array
     */
    public function jsonError($rawResponse, $httpCode)
    {
        $msg = 'API Response not valid.';
        $msg .= " (Raw response: '{$rawResponse}', HTTP code: {$httpCode})";

        return [
            'message_to_purchaser' => $msg,
        ];
    }

    /**
     * Get the request url.
     *
     * @return string
     */
    protected function getRequestUrl()
    {
        return $this->endpoint;
    }
}
