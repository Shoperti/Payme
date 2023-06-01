<?php

namespace Shoperti\PayMe\Gateways\OpenPay;

use Exception;
use Shoperti\PayMe\ErrorCode;
use Shoperti\PayMe\Gateways\AbstractGateway;
use Shoperti\PayMe\Response;
use Shoperti\PayMe\ResponseException;
use Shoperti\PayMe\Status;
use Shoperti\PayMe\Support\Arr;

/**
 * This is the OpenPay gateway class.
 *
 * @author Arturo Rodríguez <arturo.rodriguez@dinkbit.com>
 */
class OpenPayGateway extends AbstractGateway
{
    /**
     * Gateway API endpoint.
     *
     * @var string
     */
    protected $endpoint = 'https://api.openpay.mx/v1';

    /**
     * Gateway test API endpoint.
     *
     * @var string
     */
    protected $testEendpoint = 'https://sandbox-api.openpay.mx/v1';

    /**
     * Gateway display name.
     *
     * @var string
     */
    protected $displayName = 'openpay';

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
     * OpenPay API version.
     *
     * @var string
     */
    protected $apiVersion = '1';

    /**
     * OpenPay API locale.
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
     * @return array|\Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function commit($method, $url, $params = [], $options = [])
    {
        $userAgent = [
            'bindings_version' => $this->config['version'],
            'lang'             => 'php',
            'lang_version'     => phpversion(),
            'publisher'        => 'openpay',
            'uname'            => php_uname(),
        ];

        $request = [
            'exceptions'      => false,
            'timeout'         => '80',
            'connect_timeout' => '30',
            'headers'         => [
                'Authorization'               => 'Basic '.base64_encode($this->config['private_key'].':'),
                'Content-Type'                => 'application/json',
                'X-OpenPay-Client-User-Agent' => json_encode($userAgent),
                'User-Agent'                  => 'OpenPay PayMeBindings/'.$this->config['version'],
            ],
        ];

        if (!empty($params)) {
            $request[$method === 'get' ? 'query' : 'json'] = $params;
        }

        $response = $this->performRequest($method, $url, $request);

        try {
            return $this->respond($response['body']);
        } catch (Exception $e) {
            throw new ResponseException($e, $response);
        }
    }

    /**
     * Check if response from performed request is valid.
     *
     * @param int $code
     *
     * @return bool
     */
    protected function isValidResponse($code)
    {
        return 200 <= $code && $code <= 299;
    }

    /**
     * Respond with an array of responses or a single response.
     *
     * @param array $response
     * @param array $_
     *
     * @return array|\Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function respond($response, $_ = [])
    {
        if ($response === null) {
            return $this->mapResponse(true, []);
        }

        if (!empty($response) && !isset($response[0])) {
            $success = null === Arr::get($response, 'error_code');

            return $this->mapResponse($success, $response);
        }

        $responses = [];

        foreach ($response as $responseItem) {
            $success = null === Arr::get($responseItem, 'error_code');

            $responses[] = $this->mapResponse($success, $responseItem);
        }

        return $responses;
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
        $type = $this->getType($response);

        [$reference, $authorization] = $success
            ? $this->getReferences($response, $type)
            : [Arr::get($response, 'request_id'), null];

        $message = $success
            ? 'Transaction approved'
            : Arr::get($response, 'description', '');

        return (new Response())->setRaw($response)->map([
            'isRedirect'    => false,
            'success'       => $success,
            'reference'     => $reference,
            'message'       => $message,
            'test'          => array_key_exists('livemode', $response) ? !$response['livemode'] : false,
            'authorization' => $authorization,
            'status'        => $success ? $this->getStatus($response) : new Status('failed'),
            'errorCode'     => $success ? null : $this->getErrorCode($response),
            'type'          => $type,
        ]);
    }

    /**
     * Get the transaction type.
     *
     * @param array $rawResponse
     *
     * @return string|null
     */
    protected function getType($rawResponse)
    {
        if (array_key_exists('refund', $rawResponse)) {
            return 'refund';
        }

        return Arr::get($rawResponse, 'transaction_type');
    }

    /**
     * Get the transaction reference and auth code.
     *
     * @param array       $response
     * @param string|null $type
     *
     * @return array
     */
    protected function getReferences($response, $type)
    {
        if ($type === 'refund') {
            $response = $response['refund'];
        }

        $id = Arr::get($response, 'id');

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
        $method = Arr::get($response, 'method');

        if ('card' == $method) {
            return Arr::get($response, 'authorization');
        }

        if ('bank_account' == $method) {
            $payment = Arr::get($response, 'payment_method', []);

            return Arr::get($payment, 'clabe');
        }

        if ('store' == $method) {
            $payment = Arr::get($response, 'payment_method', []);

            return Arr::get($payment, 'barcode_url');
        }
    }

    /**
     * Map OpenPay response to status object.
     *
     * @param array $response
     *
     * @return \Shoperti\PayMe\Status|null
     */
    protected function getStatus(array $response)
    {
        $status = Arr::get($response, 'status', 'paid');

        switch ($status) {
            case 'active':
            case 'trial':
            case 'unpaid':
            case 'failed':
                return new Status($status);
            case 'completed':
                return new Status('paid');
            case 'in_progress':
                return new Status('pending');
            case 'cancelled':
                return new Status('canceled');
            case 'verified':
                return new Status('active');
            case 'past_due':
                return new Status('failed');
            case 'deleted':
                return new Status('canceled');
        }
    }

    /**
     * Map OpenPay response to error code object.
     *
     * @param array $response
     *
     * @return \Shoperti\PayMe\ErrorCode
     * @return \Shoperti\PayMe\ErrorCode
     */
    protected function getErrorCode(array $response)
    {
        $code = Arr::get($response, 'error_code', 1001);

        $codeMap = [
            1000 => 'processing_error',     // OpenPay internal server error
            1001 => 'config_error',         // Malformed JSON, invalid fields, not required fields
            1002 => 'config_error',         // Invalid or not authenticated request
            1003 => 'config_error',         // Unable to complete as at least one of the params is incorrect
            1004 => 'processing_error',     // A required service for processing the transaction isn't available
            1005 => 'config_error',         // One of the required resources does not exist
            1006 => 'processing_error',     // There's already a transaction with the same order id
            1007 => 'card_declined',        // Transfer between bank account and OpenPay was rejected
            1008 => 'config_error',         // One of the accounts required in the request is deactivated
            1009 => 'processing_error',     // The request body is too large
            1010 => 'config_error',         // Using wrong key (private when should be public and vs)
            1012 => 'processing_error',     // The transaction amount exceeds your allowed transaction limit
            2001 => 'config_error',         // The bank account with this CLABE is already registered on the customer
            2002 => 'config_error',         // The card with this number is already registered on the customer
            2003 => 'config_error',         // Customer with this external identifier (External ID) already exists
            2004 => 'incorrect_number',     // The check digit card number is invalid according to the Luhn algorithm
            2005 => 'invalid_expiry_date',  // The expiration date of the card is prior to the current date
            2006 => 'incorrect_cvc',        // Security code card (CVV2) was not provided
            2007 => 'invalid_number',       // The card number is a test number and can only be used in Sandbox
            2008 => 'card_declined',        // The consulted card is not valid for points
            3001 => 'card_declined',        // The card was declined
            3002 => 'invalid_expiry_date',  // The card has expired
            3003 => 'insufficient_funds',   // The card has insufficient funds
            3004 => 'suspected_fraud',      // The card has been identified as a stolen card
            3005 => 'suspected_fraud',      // The card has been identified as a fraudulent card
            3006 => 'card_declined',        // The operation is not allowed for this customer or this transaction
            3008 => 'card_declined',        // The card is not supported in online transactions
            3009 => 'card_declined',        // The card was reported missing
            3010 => 'call_issuer',          // The bank has restricted the card
            3011 => 'call_issuer',          // The bank has requested that the card is retained. Contact the bank
            3012 => 'card_declined',        // A bank authorization is required to make this payment
            4001 => 'insufficient_funds',   // The Openpay account has not enough
        ];
        
        if (!isset($codeMap[$code])) {
            $code = 1001;
        }

        return new ErrorCode($codeMap[$code]);
    }

    /**
     * Get the request url.
     *
     * @return string
     */
    protected function getRequestUrl()
    {
        $isTest = !array_key_exists('test', $this->getConfig()) || ((bool) $this->getConfig()['test']);

        return ($isTest ? $this->testEendpoint : $this->endpoint)."/{$this->getConfig()['id']}";
    }
}
