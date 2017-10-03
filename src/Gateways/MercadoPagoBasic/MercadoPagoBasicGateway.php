<?php

namespace Shoperti\PayMe\Gateways\MercadoPagoBasic;

use Shoperti\PayMe\ErrorCode;
use Shoperti\PayMe\Gateways\MercadoPago\MercadoPagoGateway;
use Shoperti\PayMe\Response;
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
     *
     * @return array|\Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function commit($method, $url, $params = [], $options = [])
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

        $authRawResponse = $this->getHttpClient()->post($oauthUrl, [
            'json' => [
                'client_id'     => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'grant_type'    => 'client_credentials',
            ],
        ]);

        if (!$this->oauthToken) {
            $authResponse = $this->parseResponse($authRawResponse);

            $this->oauthToken = Arr::get($authResponse, 'access_token');
        }

        $authUrl = sprintf('%s?access_token=%s', $url, $this->oauthToken);

        $rawResponse = $this->getHttpClient()->{$method}($authUrl, $request);

        $response = $this->parseResponse($rawResponse);

        $response['isRedirect'] = Arr::get($options, 'isRedirect', false);
        $response['topic'] = Arr::get($options, 'topic');

        return $this->respond($response);
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
        if (array_key_exists('collection', $response)) {
            $response = array_merge($response, $response['collection']);

            unset($response['collection']);
        }

        $rawResponse = $response;

        unset($rawResponse['isRedirect']);
        unset($rawResponse['topic']);

        if ($response['isRedirect']) {
            return (new Response())->setRaw($rawResponse)->map([
                'isRedirect'      => $response['isRedirect'],
                'success'         => $response['isRedirect'] ? false : $success,
                'reference'       => $success ? Arr::get($response, 'id') : null,
                'message'         => $success ? 'Transaction approved' : 'Redirect',
                'test'            => $this->config['test'],
                'authorization'   => $success ? Arr::get($response, 'init_point') : null,
                'status'          => $success ? new Status('pending') : new Status('failed'),
                'errorCode'       => $success ? null : $this->getErrorCode($response),
                'type'            => null,
            ]);
        }

        return (new Response())->setRaw($rawResponse)->map([
            'isRedirect'      => false,
            'success'         => $success,
            'reference'       => $success ? Arr::get($response, 'id') : null,
            'message'         => $success ? 'Transaction approved' : null,
            'test'            => $this->config['test'],
            'authorization'   => $success ? Arr::get($response, 'preference_id') : null,
            'status'          => $success ? $this->getStatus($response) : new Status('failed'),
            'errorCode'       => $success ? null : $this->getErrorCode($response),
            'type'            => Arr::get($response, 'topic'),
        ]);
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
