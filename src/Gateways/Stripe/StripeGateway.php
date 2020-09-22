<?php

namespace Shoperti\PayMe\Gateways\Stripe;

use Exception;
use GuzzleHttp\ClientInterface;
use Shoperti\PayMe\ErrorCode;
use Shoperti\PayMe\Gateways\AbstractGateway;
use Shoperti\PayMe\Response;
use Shoperti\PayMe\ResponseException;
use Shoperti\PayMe\Status;
use Shoperti\PayMe\Support\Arr;

/**
 * This is the stripe gateway class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 * @author Arturo Rodríguez <arturo.rodriguez@dinkbit.com>
 */
class StripeGateway extends AbstractGateway
{
    /**
     * Gateway API endpoint.
     *
     * @var string
     */
    protected $endpoint = 'https://api.stripe.com';

    /**
     * Gateway display name.
     *
     * @var string
     */
    protected $displayName = 'stripe';

    /**
     * Gateway default currency.
     *
     * @var string
     */
    protected $defaultCurrency = 'USD';

    /**
     * Gateway money format.
     *
     * @var string
     */
    protected $moneyFormat = 'cents';

    /**
     * Stripe API version.
     *
     * @var string
     */
    protected $apiVersion = 'v1';

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
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function commit($method, $url, $params = [], $options = [])
    {
        $userAgent = [
            'bindings_version' => $this->config['version'],
            'lang'             => 'php',
            'lang_version'     => phpversion(),
            'publisher'        => 'stripe',
            'uname'            => php_uname(),
        ];

        $request = [
            'exceptions'      => false,
            'timeout'         => '80',
            'connect_timeout' => '30',
            'headers'         => [
                'Authorization'              => 'Basic '.base64_encode($this->config['private_key'].':'),
                'Content-Type'               => 'application/x-www-form-urlencoded',
                'RaiseHtmlError'             => 'false',
                'User-Agent'                 => 'Stripe/v1 PayMeBindings/'.$this->config['version'],
                'X-Stripe-Client-User-Agent' => json_encode($userAgent),
            ],
        ];

        if (version_compare(ClientInterface::VERSION, '6') === 1) {
            $request['form_params'] = $params;
        } else {
            $request['body'] = $params;
        }

        $response = $this->performRequest($method, $url, $request);

        try {
            return $this->respond($response['body'], $options);
        } catch (Exception $e) {
            throw new ResponseException($e, $response);
        }
    }

    /**
     * Respond with an array of responses or a single response.
     *
     * @param array $response
     * @param array $request
     *
     * @return array|\Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function respond($response, $request = null)
    {
        if (Arr::get($response, 'object') === 'list') {
            $responses = [];

            foreach ($response['data'] as $subResponse) {
                $responses[] = $this->mapResponse($subResponse, $request);
            }

            return $responses;
        }

        return $this->mapResponse($response, $request);
    }

    /**
     * Map HTTP response to transaction object.
     *
     * @param array $rawResponse
     * @param array $request
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function mapResponse($rawResponse, $request)
    {
        // if there's an inner object (e.g. when getting an event) use it as data source
        $response = array_key_exists('type', $rawResponse) && isset($rawResponse['data']['object'])
            ? $rawResponse['data']['object']
            : $rawResponse;

        $type = Arr::get($rawResponse, 'type') ?: Arr::get($response, 'object');
        $isTest = array_key_exists('livemode', $response) ? !$response['livemode'] : false;
        $success = !array_key_exists('error', $response);

        $status = $this->getStatus($response);
        $isCharge = $type === 'payment_intent';
        $isRedirect = $isCharge && (string) $status === 'pending';

        $result = [
            // when mapping a payment intent, success must be false if redirect is needed
            'success' => $success && !$isRedirect,
            'test'    => $isTest,
            'type'    => $type,
            'status'  => $status,
        ];

        $result = array_merge($result, $success ? [
            'isRedirect'    => $isRedirect,
            'errorCode'     => null,
            'reference'     => Arr::get($response, 'id'), // may not exist, e.g. balance objects
            'message'       => $isCharge ? 'Transaction approved' : 'success',
            'authorization' => $isCharge
                ? Arr::get($request, 'continue_url')."?reference={$response['client_secret']}"
                : null,
        ] : [
            'isRedirect'    => false,
            'errorCode'     => $this->getErrorCode($response['error']),
            'reference'     => Arr::get($response['error'], 'charge'),
            'message'       => Arr::get($response['error'], 'message'),
            'authorization' => null,
        ]);

        return (new Response())->setRaw($rawResponse)->map($result);
    }

    /**
     * Map Stripe response to status object.
     *
     * @param array $response
     *
     * @return \Shoperti\PayMe\Status
     */
    protected function getStatus(array $response)
    {
        if (isset($response['error'])) {
            return new Status('declined');
        }

        $type = Arr::get($response, 'object');

        if ($type === 'payment_intent') {
            switch (Arr::get($response, 'status', null)) {
                case 'canceled':
                    return new Status('canceled');

                case 'processing':
                case 'requires_action':
                case 'requires_capture':
                case 'requires_confirmation':
                    return new Status('pending');

                // When the PaymentIntent is created or if the payment attempt fails
                case 'requires_payment_method':
                    $isRecent = $response['last_payment_error'] === null;

                    return $isRecent ? new Status('pending') : new Status('declined');

                case 'succeeded':
                    return $response['amount_received'] >= $response['amount']
                        ? new Status('paid')
                        : new Status('partially_paid');
            }

            return new Status('pending');
        }

        if ($type === 'webhook_endpoint') {
            return Arr::get($response, 'deleted')
                ? new Status('canceled')
                : (Arr::get($response, 'status') === 'enabled'
                    ? new Status('authorized')
                    : new Status('pending'));
        }

        return new Status('pending');
    }

    /**
     * Map Stripe response to error code object.
     *
     * @param array $error
     *
     * @return \Shoperti\PayMe\ErrorCode
     */
    protected function getErrorCode($error)
    {
        $code = Arr::get($error, 'code', $error['type']);

        switch ($code) {
            case 'invalid_expiry_month':
            case 'invalid_expiry_year':
                return new ErrorCode('invalid_expiry_date');

            case 'card_declined':
            case 'expired_card':
            case 'incorrect_address':
            case 'incorrect_cvc':
            case 'incorrect_number':
            case 'incorrect_zip':
            case 'invalid_cvc':
            case 'invalid_number':
            case 'processing_error':
                return new ErrorCode($code);

            case 'amount_too_large':
            case 'amount_too_small':
            case 'invalid_charge_amount':
                return new ErrorCode('invalid_amount');

            case 'balance_insufficient':
                return new ErrorCode('insufficient_funds');

            case 'missing':
                return new ErrorCode('invalid_state');
        }

        return new ErrorCode('config_error');
    }

    /**
     * Get the request url.
     *
     * @return string
     */
    protected function getRequestUrl()
    {
        return $this->endpoint.'/'.$this->apiVersion;
    }
}
