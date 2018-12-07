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
    protected $endpoint  = 'https://api.srpago.com/v1'; 

    /**
     * Sandbox endpoint
     *
     * @var string
     */
    protected $sandboxEndpoint = 'https://sandbox-api.srpago.com/v1';

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

    protected $applicationKey;
    protected $applicationSecret;

    /**
     * Undocumented function
     *
     * @return void
     */
    public function __construct(array $config)
    {
        Arr::requires($config, ['private_key']);
        Arr::requires($config, ['secret_key']);

        $this->applicationKey = Arr::get($config, 'private_key');

        $this->applicationSecret = Arr::get($config, 'secret_key');

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
            'auth' => [
                $this->applicationKey,
                $this->applicationSecret,
            ],
            'headers' => [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'json'  => [
                'application_bundle' => '',
            ]
        ];

        $url = $this->getRequestUrl().'/auth/login/application';

        $response = $this->getHttpClient()->post($url, $request);
        $response = json_decode($response->getBody());

        $this->connectionToken = $response->connection->token;
        
        return $response;
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
            ? $this->parseResponse($raw->getBody())
            : $this->responseError($raw->getBody(), $statusCode);

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