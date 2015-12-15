<?php

namespace Shoperti\PayMe\Gateways\Stripe;

use Shoperti\PayMe\Gateways\AbstractGateway;
use Shoperti\PayMe\Response;
use Shoperti\PayMe\Status;
use Shoperti\PayMe\Support\Arr;

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
     * @param $config
     */
    public function __construct($config)
    {
        Arr::requires($config, ['private_key']);

        $config['version'] = $this->apiVersion;

        $this->config = $config;
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
    public function commit($method = 'post', $url, $params = [], $options = [])
    {
        $userAgent = [
            'bindings_version' => $this->config['version'],
            'lang'             => 'php',
            'lang_version'     => phpversion(),
            'publisher'        => 'stripe',
            'uname'            => php_uname(),
        ];

        $success = false;

        $rawResponse = $this->getHttpClient()->{$method}($url, [
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
            'form_params'     => $params,
        ]);

        if ($rawResponse->getStatusCode() == 200) {
            $response = $this->parseResponse($rawResponse->getBody());
            $success = (!array_key_exists('error', $response));
        } else {
            $response = $this->responseError($rawResponse->getBody());
        }

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
        return (new Response())->setRaw($response)->map([
            'isRedirect'      => false,
            'success'         => $success,
            'message'         => $success ? 'Transaction approved' : $response['error']['message'],
            'test'            => array_key_exists('livemode', $response) ? $response['livemode'] : false,
            'authorization'   => $success ? $response['id'] : Arr::get($response['error'], 'charge', 'error'),
            'status'          => $success ? $this->getStatus(Arr::get($response, 'paid', true)) : new Status('failed'),
            'reference'       => $success ? Arr::get($response, 'balance_transaction', '') : false,
            'code'            => $success ? false : $response['error']['type'],
        ]);
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
        return $status ? new Status('paid') : new Status('pending');
    }

    /**
     * Parse JSON response to array.
     *
     * @param string $body
     *
     * @return array
     */
    protected function parseResponse($body)
    {
        return json_decode($body, true);
    }

    /**
     * Get error response from server or fallback to general error.
     *
     * @param string $responseBody
     *
     * @return array
     */
    protected function responseError($responseBody)
    {
        return $this->parseResponse($responseBody) ?: $this->jsonError($responseBody);
    }

    /**
     * Default JSON response.
     *
     * @param string $rawResponse
     *
     * @return array
     */
    public function jsonError($rawResponse)
    {
        $msg = 'API Response not valid.';
        $msg .= " (Raw response API {$rawResponse})";

        return [
            'error' => ['message' => $msg],
        ];
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
