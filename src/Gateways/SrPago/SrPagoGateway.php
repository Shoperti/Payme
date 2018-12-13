<?php

namespace Shoperti\PayMe\Gateways\SrPago;

use Shoperti\PayMe\Gateways\AbstractGateway;
use Shoperti\PayMe\Support\Arr;
use Shoperti\PayMe\Response;
use Shoperti\PayMe\Status;
use Shoperti\PayMe\ErrorCode;

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
     * The  date time connection token expiration
     *
     * @var string
     */
    protected $tokenExpiration;

    /**
     * Sr Pago application key
     *
     * @var string
     */
    protected $applicationKey;

    /**
     * Sr Pago secret key
     *
     * @var string
     */
    protected $applicationSecret;

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
    public function loginApplication($applicationBundle = '')
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
                'application_bundle' => $applicationBundle,
            ]
        ];

        $url = $this->getRequestUrl().'/auth/login/application';

        $response = $this->getHttpClient()->post($url, $request);
        $response = json_decode($response->getBody());

        $this->connectionToken = $response->connection->token;
        
        return $response;
    }

    /**
     * Commit an http request
     *
     * @param string $method
     * @param string $url
     * @param array $params
     * @param array $options
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function commit($method, $url, $params = [], $options = [])
    {
        if (empty($this->connectionToken)) {
            $this->loginApplication();
        }
        
        $request = [
            'exceptions'      => false,
            'timeout'         => '80',
            'connect_timeout' => '30',
            'headers'   => [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$this->connectionToken,
            ],
        ];

        if (!empty($params)) {
            $request[$method === 'get' ? 'query' : 'json'] = $params;
        }

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
     * Get auth connection token 
     *
     * @return string
     */
    public function getConnectionToken()
    {
        return $this->connectionToken;
    }

    /**
     * Get the key for the application
     *
     * @return string
     */
    public function getApplicationKey()
    {
        return $this->applicationKey;
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
     * Single response.
     *
     * @param array $response
     *
     * @return array|\Shoperti\PayMe\Contracts\ResponseInterface
     */
    protected function respond($response)
    {
        $success = Arr::get($response, 'success');

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
        $authorization = Arr::get($response['result'], 'autorization_code');
        $type          = $this->getType($response);
        $transaction   = Arr::get($response['result'], 'transaction');

        if ($success) {
            $message = 'Transaction approved';
        } else {
            $message = Arr::get($response['error'], 'message');
        }

        return (new Response())->setRaw($response)->map([
            'isRedirect'    => false,
            'success'       => $success,
            'reference'     => $transaction,
            'message'       => $message,
            'test'          => false,
            'authorization' => $authorization,
            'status'        => $success ? $this->getStatus(Arr::get($response['result'], 'status', 'N')) : new Status('failed'),
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
        return Arr::get($rawResponse['result'], 'method');
    }

    /**
     * Map SrPago response to error code object.
     *
     * @param array $response
     *
     * @return \Shoperti\PayMe\ErrorCode
     */
    protected function getErrorCode($response)
    {
        $code = Arr::get($response['error'], 'code', 'PaymentException');
        if (!isset($codeMap[$code])) {
            $code = 'PaymentException';
        }

        $codeMap = [
            'InvalidParamException'       => 'invalid_param',        // Malformed JSON, invalid fields, not required fields
            'InvalidEncryptionException'  => 'invalid_encryption',   // Incorrect data encryption  
            'PaymentFilterException'      => 'processing_error',     // System detected supicious elements
            'SuspectedFraudException'     => 'suspected_fraud',      // System detected transaction as fraud
            'InvalidTransactionException' => 'processing_error',     // Transaction started but not processed due to internal rules
            'PaymentException'            => 'card_declined',        // Transaction was rejected by bank
            'SwitchException'             => 'processing_error',     // There's already a transaction with the same order id
            'InternalErrorException'      => 'card_declined',        // Sr pago is not available to process transactions
            'InvalidCardException'        => 'card_declined',        // Card already exists 
            'TokenAlreadyUsedException'   => 'card_declined',        // Token has already been used
        ];

        return new ErrorCode($codeMap[$code]);
    }

    /**
     * Map SrPago response to status object.
     *
     * @param string $status
     *
     * @return \Shoperti\PayMe\Status
     */
    protected function getStatus($status)
    {
        switch (strtolower($status)) {
            case 'n':
                return new Status('pending');
            case 'c':
                return new Status('authorized');
        }
    }

    /**
     * Return a valid token used for testing
     *
     * @return string
     */
    public function getValidTestToken($attributes = [])
    {
        $card = array_merge([
            'cardholder_name' => 'FSMO',
            'number'          => '4242424242424242',
            'cvv'             => '123',
            'expiration'      => (new \DateTime('+1 year'))->format('ym'),
        ], $attributes);

        $params = Encryption::encryptParametersWithString($card);

        if (empty($this->connectionToken)) {
            $this->loginApplication();
        }

        $raw = $this->getHttpClient()->post($this->getRequestUrl().'/token', [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$this->connectionToken,
            ],
            'json' => $params,
        ]);

        $response = $this->parseResponse($raw->getBody());

        return $response['result']['token'];
    }
}