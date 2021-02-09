<?php

namespace Shoperti\PayMe\Gateways\PaypalExpress;

use Exception;
use GuzzleHttp\ClientInterface;
use InvalidArgumentException;
use Shoperti\PayMe\ErrorCode;
use Shoperti\PayMe\Gateways\AbstractGateway;
use Shoperti\PayMe\Response;
use Shoperti\PayMe\ResponseException;
use Shoperti\PayMe\Status;
use Shoperti\PayMe\Support\Arr;

/**
 * This is the PayPal express gateway class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
class PaypalExpressGateway extends AbstractGateway
{
    /**
     * Gateway API endpoint.
     *
     * @var string
     */
    protected $endpoint = 'https://api-3t.paypal.com/nvp';

    /**
     * Gateway API test endpoint.
     *
     * @var string
     */
    protected $testEndpoint = 'https://api-3t.sandbox.paypal.com/nvp';

    /**
     * Gateway checkout endpoint.
     *
     * @var string
     */
    protected $checkoutEndpoint = 'https://www.paypal.com/cgi-bin/webscr';

    /**
     * Gateway checkout test endpoint.
     *
     * @var string
     */
    protected $testCheckoutEndpoint = 'https://www.sandbox.paypal.com/cgi-bin/webscr';

    /**
     * Gateway display name.
     *
     * @var string
     */
    protected $displayName = 'paypal_express';

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
    protected $moneyFormat = 'dollars';

    /**
     * PaypalExpress API version.
     *
     * @var string
     */
    protected $apiVersion = '119.0';

    /**
     * Some default options for curl.
     *
     * @var array
     */
    public static $defaultCurlOptions = [
        CURLOPT_SSLVERSION      => 1,
        CURLOPT_SSL_CIPHER_LIST => 'TLSv1',
    ];

    /**
     * Inject the configuration for a Gateway.
     *
     * @param string[] $config
     *
     * @return void
     */
    public function __construct(array $config)
    {
        Arr::requires($config, ['username', 'password', 'signature']);

        $config['version'] = $this->apiVersion;
        $config['test'] = (bool) Arr::get($config, 'test', false);

        parent::__construct($config);
    }

    /**
     * Accept the amount of money in base unit and returns cants or base unit.
     *
     * @param int|float $money
     * @param bool      $validateNegative
     *
     * @throws \InvalidArgumentException
     *
     * @return string|null
     */
    public function amount($money, $validateNegative = true)
    {
        if (null === $money) {
            return;
        }

        if (is_string($money) || ($validateNegative && $money < 0)) {
            throw new InvalidArgumentException('Money amount must be a positive number.');
        }

        if ($this->getMoneyFormat() == 'cents') {
            return number_format($money, 0, '', '');
        }

        return sprintf('%.2f', number_format($money, 2, '.', '') / 100);
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
        $params['VERSION'] = $this->config['version'];
        $params['USER'] = $this->config['username'];
        $params['PWD'] = $this->config['password'];
        $params['SIGNATURE'] = $this->config['signature'];

        $request = [
            'exceptions'      => false,
            'timeout'         => '60',
            'connect_timeout' => '10',
            'headers'         => [
                'User-Agent' => 'PaypalExpress/v1 PayMeBindings/'.$this->config['version'],
            ],
        ];

        if (isset($options['partner'])) {
            $request['headers']['PayPal-Partner-Attribution-Id'] = $options['partner'];
        }

        if (version_compare(ClientInterface::VERSION, '6') === 1) {
            $request['curl'] = static::$defaultCurlOptions;
            $request['form_params'] = $params;
        } else {
            $request['config']['curl'] = static::$defaultCurlOptions;
            $request['body'] = $params;
        }

        $response = $this->performRequest($method, $url, $request);

        try {
            return $this->respond($response['body'], ['request' => $params, 'options' => $options]);
        } catch (Exception $e) {
            throw new ResponseException($e, $response);
        }
    }

    /**
     * Respond with an array of responses or a single response.
     *
     * @param array $response
     * @param array $params
     *
     * @return array|\Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function respond($response, $params = [])
    {
        $request = $params['request'];
        $options = $params['options'];

        $success = isset($response['ACK']) && in_array($response['ACK'], ['Success', 'SuccessWithWarning']);

        if (array_key_exists('INVALID', $response) || array_key_exists('VERIFIED', $response)) {
            $response = array_merge($response, $request);
        }

        $response['isRedirect'] = Arr::get($options, 'isRedirect', false);

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
    protected function mapResponse($success, $response)
    {
        $rawResponse = $response;

        unset($rawResponse['isRedirect']);

        if (array_key_exists('INVALID', $response) || array_key_exists('VERIFIED', $response)) {
            $success = array_key_exists('VERIFIED', $response);

            return (new Response())->setRaw($rawResponse)->map([
                'isRedirect'      => false,
                'success'         => $success,
                'reference'       => $success ? Arr::get($response, 'invoice') : false,
                'message'         => $success ? 'VERIFIED' : 'INVALID',
                'test'            => $this->config['test'],
                'authorization'   => $success ? Arr::get($response, 'txn_id') : '',
                'status'          => $success ? $this->getPaymentStatus($response) : new Status('failed'),
                'errorCode'       => null,
                'type'            => $success ? Arr::get($response, 'txn_type') : null,
            ]);
        }

        $error = Arr::get($response, 'L_ERRORCODE0');
        $message = $success
            ? Arr::get($response, 'ACK', 'Transaction approved')
            : Arr::get($response, 'L_LONGMESSAGE0', $error ?: 'Misc. error');

        return (new Response())->setRaw($rawResponse)->map([
            'isRedirect'      => $response['isRedirect'],
            'success'         => $response['isRedirect'] ? false : $success,
            'reference'       => $success ? $this->getReference($response, $response['isRedirect']) : false,
            'message'         => $message,
            'test'            => $this->config['test'],
            'authorization'   => $this->getAuthorization($response, $success, $response['isRedirect']),
            'status'          => $success ? $this->getStatus($response, $response['isRedirect']) : new Status('failed'),
            'errorCode'       => $success ? null : $this->getErrorCode($error),
            'type'            => null,
        ]);
    }

    /**
     * Map PayPal response to reference.
     *
     * @param array $response
     * @param bool  $isRedirect
     *
     * @return string
     */
    public function getReference($response, $isRedirect)
    {
        if ($isRedirect) {
            return Arr::get($response, 'TOKEN', '');
        }

        foreach (['REFUNDTRANSACTIONID',
            'TRANSACTIONID',
            'PAYMENTINFO_0_TRANSACTIONID',
            'AUTHORIZATIONID', ] as $key) {
            if (isset($response[$key])) {
                return $response[$key];
            }
        }
    }

    /**
     * Map PayPal response to authorization.
     *
     * @param array $response
     * @param bool  $success
     * @param bool  $isRedirect
     *
     * @return string
     */
    protected function getAuthorization($response, $success, $isRedirect)
    {
        if (!$success) {
            return '';
        }

        if (!$isRedirect) {
            return $response['CORRELATIONID'];
        }

        return $this->getCheckoutUrl().'?'.http_build_query([
            'cmd'   => '_express-checkout',
            'token' => $response['TOKEN'],
        ], '', '&');
    }

    /**
     * Map PayPal response to status object.
     *
     * @param array $response
     * @param bool  $isRedirect
     *
     * @return \Shoperti\PayMe\Status
     */
    protected function getStatus($response, $isRedirect)
    {
        if ($isRedirect) {
            return new Status('pending');
        }

        if (isset($response['PAYMENTINFO_0_PAYMENTSTATUS'])
            && $response['PAYMENTINFO_0_PAYMENTSTATUS'] == 'Pending') {
            return new Status('pending');
        }

        return new Status('paid');
    }

    /**
     * Map PayPal payment response to status object.
     *
     * @param array $response
     *
     * @return \Shoperti\PayMe\Status
     */
    protected function getPaymentStatus($response)
    {
        switch ($status = Arr::get($response, 'payment_status', 'paid')) {
            case 'Completed':
            case 'Processed':
                return new Status('paid');
            case 'Created':
            case 'Pending':
                return new Status('pending');
            case 'Canceled_Reversal':
                return new Status('canceled');
            case 'Failed':
            case 'Denied':
                return new Status('failed');
            case 'Declined':
                return new Status('declined');
            case 'Expired':
                return new Status('expired');
            case 'Refunded':
                return new Status('refunded');
            case 'Reversed':
                return new Status('charged_back');
            case 'Voided':
                return new Status('voided');
        }
    }

    /**
     * Map PayPalExpress response to error code object.
     *
     * @param string|null $error
     *
     * @return \Shoperti\PayMe\ErrorCode
     */
    protected function getErrorCode($error)
    {
        switch ($error) {
            case '15005':
            case '10754':
            case '10752':
            case '10759':
            case '10761':
            case '15002':
            case '11084':
                return new ErrorCode('card_declined');

            case '15004':
                return new ErrorCode('incorrect_cvc');

            case '10762':
                return new ErrorCode('invalid_cvc');

            default:
                return new ErrorCode('config_error');
        }
    }

    /**
     * Parse the response to an array.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return array
     */
    protected function parseResponse($response)
    {
        $result = [];

        parse_str((string) $response->getBody(), $result);

        return $result;
    }

    /**
     * Get the request url.
     *
     * @return string
     */
    protected function getRequestUrl()
    {
        return $this->config['test'] ? $this->testEndpoint : $this->endpoint;
    }

    /**
     * Get the request url.
     *
     * @return string
     */
    protected function getCheckoutUrl()
    {
        return $this->config['test'] ? $this->testCheckoutEndpoint : $this->checkoutEndpoint;
    }
}
