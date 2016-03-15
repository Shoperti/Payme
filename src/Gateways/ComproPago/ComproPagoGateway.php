<?php

namespace Shoperti\PayMe\Gateways\ComproPago;

use Shoperti\PayMe\ErrorCode;
use Shoperti\PayMe\Gateways\AbstractGateway;
use Shoperti\PayMe\Response;
use Shoperti\PayMe\Status;
use Shoperti\PayMe\Support\Arr;

/**
 * This is the conekta gateway class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
class ComproPagoGateway extends AbstractGateway
{
    /**
     * Gateway API endpoint.
     *
     * @var string
     */
    protected $endpoint = 'https://api.compropago.com';

    /**
     * Gateway display name.
     *
     * @var string
     */
    protected $displayName = 'compropago';

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
     * ComproPago API version.
     *
     * @var string
     */
    protected $apiVersion = 'v1';

    /**
     * ComproPago API locale.
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
    public function commit($method, $url, $params = [], $options = [])
    {
        $success = false;

        $request = [
            'exceptions'      => false,
            'timeout'         => '80',
            'connect_timeout' => '30',
            'headers'         => [
                'Accept'        => 'application/compropago',
                'Authorization' => 'Basic '.base64_encode($this->config['private_key'].':'),
                'Content-Type'  => 'application/json',
                'User-Agent'    => 'ComproPago PayMeBindings/'.$this->config['version'].' (PHP '.phpversion().';)',
            ],
        ];

        if (!empty($params) && $method !== 'get') {
            $request['json'] = $params;
        }

        $rawResponse = $this->getHttpClient()->{$method}($url, $request);

        if ($rawResponse->getStatusCode() == 200) {
            $response = $this->parseResponse($rawResponse->getBody());
        } else {
            $response = $this->responseError($rawResponse->getBody());
        }

        return $this->respond($success, $response);
    }

    /**
     * Respond with an array of responses or a single response.
     *
     * @param bool  $success
     * @param array $response
     *
     * @return array|\Shoperti\PayMe\Contracts\ResponseInterface
     */
    protected function respond($success, $response)
    {
        $success = !(Arr::get($response, 'object', 'error') == 'error');

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
            'reference'     => $success ? $response['id'] : null,
            'message'       => $success ? 'Transaction approved' : $response['message'],
            'test'          => array_key_exists('livemode', $response) ? $response['livemode'] : false,
            'authorization' => $success ? $this->getAuthorization($response) : false,
            'status'        => $success ? $this->getStatus($response) : new Status('failed'),
            'errorCode'     => $success ? null : $this->getErrorCode($response['code']),
            'type'          => array_key_exists('type', $response) ? $response['type'] : Arr::get($response, 'object'),
        ]);
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
        return 'https://www.compropago.com/comprobante/?confirmation_id='.$response['id'];
    }

    /**
     * Map ComproPago response to status object.
     *
     * @param array $response
     *
     * @return \Shoperti\PayMe\Status
     */
    protected function getStatus($response)
    {
        if (isset($response['refunded']) && $response['refunded'] == true) {
            return new Status('refunded');
        }

        $status = isset($response['status']) ? $response['status'] : $response['type'];

        switch ($status) {
            case 'charge.pending':
                return new Status('pending');
                break;
            case 'charge.success':
                return new Status('paid');
            case 'charge.declined':
                return new Status('failed');
            case 'charge.deleted':
                return new Status('canceled');
            case 'charge.expired':
                return new Status('voided');
                break;
        }
    }

    /**
     * Map ComproPago response to error code object.
     *
     * @param string $code
     *
     * @return \Shoperti\PayMe\ErrorCode
     */
    protected function getErrorCode($code)
    {
        switch ($code) {
            case 'invalid_expiry_month':
            case 'invalid_expiry_year':
                return new ErrorCode('invalid_expiry_date');
                break;
            case 'invalid_number':
            case 'invalid_cvc':
            case 'card_declined':
            case 'processing_error':
            case 'expired_card':
            case 'insufficient_funds':
            case 'suspected_fraud':
                return new ErrorCode($code);
                break;
            case 'invalid_amount':
            case 'invalid_payment_type':
            case 'unsupported_currency':
            case 'missing_description':
                return new ErrorCode('config_error');
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
            'message' => $msg,
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
