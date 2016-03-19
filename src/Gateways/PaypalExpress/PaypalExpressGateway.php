<?php

namespace Shoperti\PayMe\Gateways\PaypalExpress;

use GuzzleHttp\ClientInterface;
use Shoperti\PayMe\ErrorCode;
use Shoperti\PayMe\Gateways\AbstractGateway;
use Shoperti\PayMe\Response;
use Shoperti\PayMe\Status;
use Shoperti\PayMe\Support\Arr;

/**
 * This is the paypal express gateway class.
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
        CURLOPT_SSLVERSION      => 6,
        CURLOPT_CONNECTTIMEOUT  => 10,
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_TIMEOUT         => 60,    // maximum number of seconds to allow cURL functions to execute
        CURLOPT_SSL_VERIFYHOST  => 2,
        CURLOPT_SSL_VERIFYPEER  => 1,
        CURLOPT_SSL_CIPHER_LIST => 'TLSv1',
        //Allowing TLSv1 cipher list.
        //Adding it like this for backward compatibility with older versions of curl
    ];

    /**
     * Inject the configuration for a Gateway.
     *
     * @param string[] $config
     *
     * @return void
     */
    public function __construct($config)
    {
        Arr::requires($config, ['username', 'password', 'signature']);

        $config['version'] = $this->apiVersion;
        $config['test'] = Arr::get($config, 'test', false);

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
    public function commit($method, $url, $params = [], $options = [])
    {
        $params['VERSION'] = $this->config['version'];
        $params['USER'] = $this->config['username'];
        $params['PWD'] = $this->config['password'];
        $params['SIGNATURE'] = $this->config['signature'];

        $success = false;

        $request = [
            'verify'          => false,
            'exceptions'      => false,
            'timeout'         => '80',
            'connect_timeout' => '30',
            'headers'         => [
                'User-Agent' => 'PaypalExpress/v1 PayMeBindings/'.$this->config['version'],
            ],
            'debug' => true,
        ];

        if (version_compare(ClientInterface::VERSION, '6') === 1) {
            $request['curl'] = static::$defaultCurlOptions;
            $request['form_params'] = $params;
        } else {
            $request['config']['curl'] = static::$defaultCurlOptions;
            $request['body'] = $params;
        }

        $rawResponse = $this->getHttpClient()->{$method}($url, $request);

        if ($rawResponse->getStatusCode() == 200) {
            $response = $this->parseResponse((string) $rawResponse->getBody());
        } else {
            $response = $this->responseError((string) $rawResponse->getBody());
        }

        return $this->respond($success, $response, $options);
    }

    /**
     * Respond with an array of responses or a single response.
     *
     * @param bool  $success
     * @param array $response
     * @param array $options
     *
     * @return array|\Shoperti\PayMe\Contracts\ResponseInterface
     */
    protected function respond($success, $response, $options)
    {
        $success = isset($response['ACK']) && in_array($response['ACK'], ['Success', 'SuccessWithWarning']);

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
    public function mapResponse($success, $response)
    {
        return (new Response())->setRaw($response)->map([
            'isRedirect'      => $response['isRedirect'],
            'success'         => $response['isRedirect'] ? false : $success,
            'reference'       => $this->getReference($response, $success, $response['isRedirect']),
            'message'         => $success ? 'Transaction approved' : $response['L_LONGMESSAGE0'],
            'test'            => $this->config['test'],
            'authorization'   => $success ? Arr::get($response, 'CORRELATIONID', '') : false,
            'status'          => $success ? $this->getStatus(Arr::get($response, 'paid', false)) : new Status('failed'),
            'errorCode'       => $success ? null : $this->getErrorCode($response['L_ERRORCODE0']),
            'type'            => false,
        ]);
    }

    /**
     * Map paypal response to status object.
     *
     * @param array $response
     * @param bool  $success
     * @param bool  $isRedirect
     *
     * @return string
     */
    protected function getReference($response, $success, $isRedirect)
    {
        if (!$success) {
            return '';
        }

        if (!$isRedirect) {
            return $response['TOKEN'];
        }

        return $this->getCheckoutUrl().'?'.http_build_query([
            'cmd'   => '_express-checkout',
            'token' => $response['TOKEN'],
        ], '', '&');
    }

    /**
     * Map paypal response to status object.
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
     * Map PaypalExpress response to error code object.
     *
     * @param array $error
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
                break;
            case '15004':
                return new ErrorCode('incorrect_cvc');
                break;
            case '10762':
                return new ErrorCode('invalid_cvc');
                break;
            default:
                return new ErrorCode('config_error');
                break;
        }
    }

    /**
     * Parse body response to array.
     *
     * @param string $body
     *
     * @return array
     */
    protected function parseResponse($body)
    {
        $response = [];

        parse_str($body, $response);

        return $response;
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
