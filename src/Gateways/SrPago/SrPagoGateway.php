<?php

namespace Shoperti\PayMe\Gateways\SrPago;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Shoperti\PayMe\ErrorCode;
use Shoperti\PayMe\Gateways\AbstractGateway;
use Shoperti\PayMe\Response;
use Shoperti\PayMe\Status;
use Shoperti\PayMe\Support\Arr;

class SrPagoGateway extends AbstractGateway
{
    /**
     * Production endpoint.
     *
     * @var string
     */
    protected $endpoint = 'https://api.srpago.com/v1';

    /**
     * Sandbox endpoint.
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
     * API connection token.
     *
     * @var string
     */
    protected $connectionToken;

    /**
     * The date time connection token expiration.
     *
     * @var string
     */
    protected $tokenExpiration;

    /**
     * Sr Pago application key.
     *
     * @var string
     */
    protected $applicationKey;

    /**
     * Sr Pago secret key.
     *
     * @var string
     */
    protected $applicationSecret;

    /**
     * Whether requests are test.
     *
     * @var bool
     */
    protected $isTest;

    /**
     * Map of possible error codes.
     *
     * @var array
     */
    protected $errorCodeMap = [
        'InvalidParamException'       => 'config_error',        // Malformed JSON, invalid fields, not required fields
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

    public function __construct(array $config)
    {
        Arr::requires($config, ['private_key']);
        Arr::requires($config, ['secret_key']);

        $this->applicationKey = Arr::get($config, 'private_key');
        $this->applicationSecret = Arr::get($config, 'secret_key');
        $this->isTest = Arr::get($config, 'test', false);

        parent::__construct($config);
    }

    /**
     * Get a fresh instance of the Guzzle HTTP client.
     *
     * @return \GuzzleHttp\Client
     */
    protected function getHttpClient()
    {
        $stack = HandlerStack::create();

        $stack->push(Middleware::retry(function ($retries, RequestInterface $request, ResponseInterface $response = null, TransferException $exception = null) {
            $parsed = $this->parseResponse($response->getBody());

            // Be aware that this gateway validation error responses may be an HTTP 500 code.
            return $retries < 3 && (
                $exception instanceof ConnectException ||
                (
                    $response->getStatusCode() >= 500 &&
                    isset($parsed['error']['code']) &&
                    !in_array($parsed['error']['code'], array_keys($this->errorCodeMap))
                )
            );
        }, function ($retries) {
            return (int) pow(2, $retries) * 1000;
        }));

        return new GuzzleClient(['handler' => $stack]);
    }

    /**
     * Log in with the application.
     *
     * @return string
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
            ],
        ];

        $url = $this->getRequestUrl().'/auth/login/application';

        $response = $this->getHttpClient()->post($url, $request);
        $response = json_decode($response->getBody());

        $this->connectionToken = $response->connection->token;

        return $this->connectionToken;
    }

    /**
     * Commit an http request.
     *
     * @param string $method
     * @param string $url
     * @param array  $params
     * @param array  $options
     *
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
            'headers'         => [
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

    /**
     * Get the request url.
     *
     * @return string
     */
    protected function getRequestUrl()
    {
        return $this->isTest ? $this->sandboxEndpoint : $this->endpoint;
    }

    /**
     * Get auth connection token.
     *
     * @return string
     */
    public function getConnectionToken()
    {
        return $this->connectionToken;
    }

    /**
     * Get the key for the application.
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
        $authorization = Arr::get($response['result']['recipe'], 'authorization_code');
        $type = $this->getType($response);
        $transaction = Arr::get($response['result'], 'transaction');

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
            'test'          => $this->isTest,
            'authorization' => $authorization,
            'status'        => $success ? new Status('authorized') : new Status('failed'),
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
        if (!isset($this->errorCodeMap[$code])) {
            $code = 'PaymentException';
        }

        return new ErrorCode($this->errorCodeMap[$code]);
    }
}
