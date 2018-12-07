<?php

namespace Shoperti\PayMe\Gateways\SrPago;

use Shoperti\PayMe\Gateways\AbstractGateway;
use Shoperti\PayMe\Support\Arr;

class SrPagoGateway extends AbstractGateway
{
    /**
     * Production endpoint
     *
     * @var string
     */
    protected $endpoint  = 'https://api.srpago.com'; 

    /**
     * Sandbox endpoint
     *
     * @var string
     */
    protected $sandboxEndpoint = 'https://sandbox-api.srpago.com';

    /**
     * Gateway display name.
     *
     * @var string
     */
    protected $displayName = 'srpago';

    /**
     * API connection token
     *
     * @var string
     */
    protected $connectionToken;

    /**
     * The  date time connection expiration
     *
     * @var string
     */
    protected $connectionTokenExpiration;

    /**
     * Undocumented function
     *
     * @return void
     */
    public function __construct(array $config)
    {
        Arr::requires($config, ['private_key']);

        parent::__construct($config);
    }

    /**
     * Logs with the application
     *
     * @return void
     */
    public function loginApplication()
    {
        $request = [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => $this->applicationKey.':'.$this->applicationSecret,
            ],
        ];

        $raw = $this->getHttpClient()->post($this->getRequestUrl().'/v1/auth/login/application', $request);
        
        print_r($raw);
        exit();
    }

    public function commit($method, $url, $params = [], $options = [])
    {
        $request = [
            'headers'   => [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$this->connectionToken,
            ],
        ];

        $raw = $this->getHttpClient()->{$method}($url, $request);
        $statusCode = $raw->getStatusCode();

        $response = $statusCode == 200
            ? $this->parseResponse($rawResponse->getBody())
            : $this->responseError($rawResponse->getBody(), $statusCode);

        return $this->respond($response);
    }

    protected function getRequestUrl()
    {   
        return $this->sandboxEndpoint;
    }

    /**
     * Map an HTTP response to transaction object.
     *
     * @param bool  $success
     * @param array $response
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function mapResponse($success, $response)
    {
        return;
    }

    public function getConnectionToken()
    {
        return $this->connectionToken;
    }
}