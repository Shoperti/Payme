<?php

namespace Shoperti\PayMe\Gateways\MercadoPagoBasic;

use Exception;
use Shoperti\PayMe\Gateways\MercadoPago\MercadoPagoGateway;
use Shoperti\PayMe\Response;
use Shoperti\PayMe\ResponseException;
use Shoperti\PayMe\Status;
use Shoperti\PayMe\Support\Arr;

/**
 * This is the Mercado Pago gateway class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
class MercadoPagoBasicGateway extends MercadoPagoGateway
{
    /**
     * Gateway API endpoint.
     *
     * @var string
     */
    protected $endpoint = 'https://api.mercadopago.com';

    /**
     * Gateway display name.
     *
     * @var string
     */
    protected $displayName = 'mercadopago_basic';

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
    protected $moneyFormat = 'dollars';

    /**
     * MercadoPagoBasic API version.
     *
     * @var string
     */
    protected $apiVersion = 'v1';

    /**
     * MercadoPagoBasic OAuth token.
     *
     * @var null|string
     */
    protected $oauthToken = null;

    /**
     * Inject the configuration for a Gateway.
     *
     * @param string[] $config
     *
     * @return void
     */
    public function __construct(array $config)
    {
        Arr::requires($config, ['client_id', 'client_secret']);

        $config['version'] = $this->apiVersion;
        $config['test'] = (bool) Arr::get($config, 'test', false);

        $this->setConfig($config);
    }

    /**
     * Commit a HTTP request.
     *
     * @param string   $method
     * @param string   $url
     * @param string[] $params
     * @param string[] $options
     * @param string[] $customHeaders
     *
     * @return array|\Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function commit($method, $url, $params = [], $options = [], $customHeaders = [])
    {
        $request = [
            'exceptions'      => false,
            'timeout'         => '80',
            'connect_timeout' => '30',
            'headers'         => [
                'Content-Type'  => 'application/json',
                'User-Agent'    => 'MercadoPago PayMeBindings/'.$this->config['version'],
            ],
        ];

        if (!empty($params)) {
            $request[$method === 'get' ? 'query' : 'json'] = $params;
        }

        $oauthUrl = $this->buildUrlFromString('oauth/token');

        $response = $this->performRequest('post', $oauthUrl, [
            'json' => [
                'client_id'     => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'grant_type'    => 'client_credentials',
            ],
        ]);

        if (!$this->oauthToken) {
            $authResponse = $response['body'];

            $this->oauthToken = Arr::get($authResponse, 'access_token');
        }

        $authUrl = sprintf('%s?access_token=%s', $url, $this->oauthToken);

        $response = $this->performRequest($method, $authUrl, $request);

        $response['body']['isRedirect'] = Arr::get($options, 'isRedirect', false);
        $response['body']['topic'] = Arr::get($options, 'topic');

        try {
            return $this->respond($response['body'], ['code' => $response['code']]);
        } catch (Exception $e) {
            throw new ResponseException($e, $response);
        }
    }

    /**
     * Map HTTP response to transaction object.
     *
     * @param bool  $success
     * @param array $response
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    protected function mapResponse($success, $response)
    {
        if (array_key_exists('collection', $response)) {
            $response = array_merge($response, $response['collection']);

            unset($response['collection']);
        }

        $rawResponse = $response;
        $test = $this->config['test'];

        unset($rawResponse['isRedirect']);
        unset($rawResponse['topic']);

        if ($response['isRedirect']) {
            return (new Response())->setRaw($rawResponse)->map([
                'isRedirect'      => $response['isRedirect'],
                'success'         => $response['isRedirect'] ? false : $success,
                'reference'       => $success ? Arr::get($response, 'id') : null,
                'message'         => 'Redirect',
                'test'            => $test,
                'authorization'   => $success ? Arr::get($response, $test ? 'sandbox_init_point' : 'init_point') : null,
                'status'          => $success ? new Status('pending') : new Status('failed'),
                'errorCode'       => $success ? null : $this->getErrorCode($response),
                'type'            => null,
            ]);
        }

        return (new Response())->setRaw($rawResponse)->map([
            'isRedirect'      => false,
            'success'         => $success,
            'reference'       => $success ? $this->getReference($response) : null,
            'message'         => $success ? 'Transaction approved' : $this->getMessage($rawResponse),
            'test'            => $test,
            'authorization'   => $success ? Arr::get($response, 'id') : null,
            'status'          => $this->getStatus($response),
            'errorCode'       => $success ? null : $this->getErrorCode($response),
            'type'            => Arr::get($response, 'topic'),
        ]);
    }

    /**
     * Check if it's a successful response.
     *
     * @param array $response
     * @param int   $code
     *
     * @return bool
     */
    protected function isSuccess($response, $code)
    {
        if ($code !== 200 && $code !== 201) {
            return false;
        }

        if (isset($response['error'])) {
            return false;
        }

        if (!empty(Arr::get($response, 'payments'))) {
            return in_array($this->getPaymentStatus($response), $this->successPaymentStatuses);
        }

        return true;
    }

    /**
     * Get the status from the last payment.
     *
     * @param array $response
     *
     * @return string
     */
    protected function getPaymentStatus($response)
    {
        $lastPayment = Arr::last($response['payments']);

        // approved, in_process, pending, rejected, cancelled
        // on testing at least, payments may be empty
        return $lastPayment ? Arr::get($lastPayment, 'status', 'other') : 'no_payment';
    }

    /**
     * Get the message from the response.
     *
     * @param array $rawResponse
     *
     * @return string|null
     */
    protected function getMessage($rawResponse)
    {
        if ($message = Arr::get($rawResponse, 'message')) {
            return $message;
        }

        if (!empty(Arr::get($rawResponse, 'payments'))) {
            if ($message = $this->getPaymentStatus($rawResponse)) {
                return $message;
            }
        }
    }

    /**
     * Get MercadoPago authorization.
     *
     * @param array $response
     *
     * @return string|null
     */
    protected function getReference(array $response)
    {
        $payments = Arr::get($response, 'payments');

        if (!$payments) {
            return Arr::get($response, 'preference_id');
        }

        $lastPayment = Arr::last($payments);

        return Arr::get($lastPayment, 'id');
    }

    /**
     * Map MercadoPago response to status object.
     *
     * @param array $response
     *
     * @return \Shoperti\PayMe\Status|null
     */
    protected function getStatus(array $response)
    {
        $payments = Arr::get($response, 'payments');

        if (empty($payments)) {
            return new Status('pending');
        }

        $newResponse = $response;

        if (count($payments) > 1) {
            $totalPaid = 0;
            $totalRefund = 0;
            $total = $newResponse['shipping_cost'] + $newResponse['total_amount'];

            foreach ($payments as $payment) {
                if ($payment['status'] === 'approved') {
                    // Get the total paid amount, considering only approved incomings.
                    $totalPaid += $payment['total_paid_amount'] - $payment['amount_refunded'];
                } elseif ($payment['status'] === 'refunded') {
                    // Get the total refunded amount.
                    $totalRefund += $payment['amount_refunded'];
                }
            }

            if ($totalPaid >= $total) {
                $newResponse['status'] = 'approved';
            } elseif ($totalRefund >= $total) {
                $newResponse['status'] = 'refunded';
            } elseif ($totalRefund > 0) {
                $newResponse['status'] = 'partially_refunded';
            } else {
                $newResponse['status'] = 'pending';
            }

            return parent::getStatus($newResponse);
        }

        $newResponse['status'] = $payments[0]['amount_refunded'] > 0 ? 'partially_refunded' : $payments[0]['status'];

        return parent::getStatus($newResponse);
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
