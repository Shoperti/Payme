<?php

namespace Shoperti\PayMe\Gateways\Conekta;

use Shoperti\PayMe\Gateways\AbstractGateway;
use Shoperti\PayMe\Response;
use Shoperti\PayMe\Status;
use Shoperti\PayMe\Support\Arr;

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
    protected $apiVersion = '0.3.0';

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
    public function __construct($config)
    {
        Arr::requires($config, ['private_key']);

        $config['version'] = $this->apiVersion;
        $config['locale'] = $this->locale;

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
     * @return mixed
     */
    public function commit($method = 'post', $url, $params = [], $options = [])
    {
        $user_agent = [
            'bindings_version' => $this->config['version'],
            'lang'             => 'php',
            'lang_version'     => phpversion(),
            'publisher'        => 'conekta',
            'uname'            => php_uname(),
        ];

        $success = false;

        $rawResponse = $this->getHttpClient()->{$method}($url, [
            'exceptions'      => false,
            'timeout'         => '80',
            'connect_timeout' => '30',
            'headers'         => [
                'Accept'                      => "application/vnd.conekta-v{$this->config['version']}+json",
                'Accept-Language'             => $this->config['locale'],
                'Authorization'               => 'Basic '.base64_encode($this->config['private_key'].':'),
                'Content-Type'                => 'application/json',
                'RaiseHtmlError'              => 'false',
                'X-Conekta-Client-User-Agent' => json_encode($user_agent),
                'User-Agent'                  => 'Conekta PayMeBindings/'.$this->config['version'],
            ],
            'json' => $params,
        ]);

        if ($rawResponse->getStatusCode() == 200) {
            $response = $this->parseResponse($rawResponse->getBody());
            $success = !(Arr::get($response, 'object', 'error') == 'error');
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
            'isRedirect'    => false,
            'success'       => $success,
            'message'       => $success ? 'Transacción aprobada' : $response['message_to_purchaser'],
            'test'          => array_key_exists('livemode', $response) ? $response['livemode'] : false,
            'authorization' => $success ? $response['id'] : $response['type'],
            'status'        => $success ? $this->getStatus(Arr::get($response, 'status', 'paid')) : new Status('failed'),
            'reference'     => $success ? $this->getReference($response) : false,
            'code'          => $success ? false : $response['code'],
        ]);
    }

    /**
     * Map reference to response.
     *
     * @param array $response
     *
     * @return string|null
     */
    protected function getReference($response)
    {
        $object = Arr::get($response, 'object');

        if ($object == 'customer') {
            return Arr::get($response, 'default_card_id');
        } elseif ($object == 'card') {
            return Arr::get($response, 'customer_id');
        } elseif ($object == 'payee') {
            return Arr::get($response, 'id');
        } elseif ($object == 'transfer') {
            return Arr::get($response, 'id');
        }

        return $response['payment_method']['auth_code'];
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
                break;
            case 'paid':
            case 'refunded':
            case 'paused':
            case 'active':
            case 'canceled':
                return new Status($status);
                break;
            case 'in_trial':
                return new Status('trial');
                break;
        }
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
     * @param string $parseResponse
     *
     * @return array
     */
    protected function responseError($parseResponse)
    {
        return $this->parseResponse($parseResponse) ?: $this->jsonError($parseResponse);
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
