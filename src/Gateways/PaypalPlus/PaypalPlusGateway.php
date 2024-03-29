<?php

namespace Shoperti\PayMe\Gateways\PaypalPlus;

use Exception;
use Shoperti\PayMe\ErrorCode;
use Shoperti\PayMe\Gateways\AbstractGateway;
use Shoperti\PayMe\Response;
use Shoperti\PayMe\ResponseException;
use Shoperti\PayMe\Status;
use Shoperti\PayMe\Support\Arr;

/**
 * This is the PayPal plus gateway class.
 *
 * @author Arturo Rodríguez <arturo.rodriguez@dinkbit.com>
 */
class PaypalPlusGateway extends AbstractGateway
{
    /**
     * Gateway API endpoint.
     *
     * @var string
     */
    protected $endpoint = 'https://api.paypal.com';

    /**
     * Gateway API test endpoint.
     *
     * @var string
     */
    protected $testEndpoint = 'https://api.sandbox.paypal.com';

    /**
     * Gateway display name.
     *
     * @var string
     */
    protected $displayName = 'paypal_plus';

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
     * PayPal Plus API version.
     *
     * @var string
     */
    protected $apiVersion = 'v1';

    /**
     * The requests template.
     *
     * @var array
     */
    protected $requestTemplate;

    /**
     * The statuses considered as a success on payment responses.
     *
     * @var array
     */
    protected $successPaymentStatuses = [
        'completed',
        'pending',
        'created',
        'refunded',
        'partially_refunded',
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
        Arr::requires($config, ['client_id', 'client_secret']);

        $config['version'] = $this->apiVersion;
        $config['test'] = (bool) Arr::get($config, 'test', false);

        parent::__construct($config);

        $this->requestTemplate = [
            'exceptions'      => false,
            'timeout'         => '80',
            'connect_timeout' => '30',
            'headers'         => [
                'Content-Type' => 'application/json',
                'User-Agent'   => "PayPalPlus/{$this->apiVersion} PayMeBindings/{$this->config['version']}",
            ],
        ];
    }

    /**
     * Generate an access token to be used on subsequent requests.
     *
     * @return array
     */
    public function token()
    {
        $payload = [
            'auth'        => [$this->config['client_id'], $this->config['client_secret']],
            'form_params' => ['grant_type' => 'client_credentials'],
        ];

        $request = array_merge($this->requestTemplate, $payload);

        $response = $this->performRequest('post', $this->buildUrlFromString('oauth2/token'), $request)['body'];

        return [
            'token'  => Arr::get($response, 'access_token'),
            'type'   => Arr::get($response, 'token_type'),
            'scope'  => Arr::get($response, 'scope'),
            'expiry' => isset($response['expires_in'])
                ? time() + Arr::get($response, 'expires_in')
                : null,
        ];
    }

    /**
     * Commit an HTTP request.
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
        $request = $this->requestTemplate;

        $token = Arr::get($options, 'token');

        if (!$token) {
            // there are some methods that do not have $options argument
            $token = Arr::get($params, 'token');
            if ($token) {
                unset($params['token']);
            }
        }

        // on IPN we need to send content as form params
        $payloadKey = $method === 'post'
            ? (strpos($url, 'https://ipnpb.') === 0 ? 'form_params' : 'json')
            : 'query';

        $request[$payloadKey] = $params;

        $request['headers']['Authorization'] = "Bearer {$token}";

        if (isset($options['application'])) {
            $request['headers']['PayPal-Partner-Attribution-Id'] = $options['application'];
        }

        $response = $this->performRequest($method, $url, $request);

        try {
            return $this->respond($response['body'], [
                'request'    => $params,
                'options'    => $options,
                'statusCode' => $response['code'],
            ]);
        } catch (Exception $e) {
            throw new ResponseException($e, $response);
        }
    }

    /**
     * Perform the request and return the parsed response and http code.
     *
     * @param string $method
     * @param string $url
     * @param array  $payload
     *
     * @return array ['code' => http code, 'body' => [the response]]
     */
    protected function performRequest($method, $url, $payload)
    {
        [$rawResponse, $code, $body] = $this->makeRequest($method, $url, $payload);

        if ($code === 204) {
            $response = '';
        } else {
            $response = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $response = ['body' => trim($body)];
            }
        }

        $data = 200 <= $code && $code <= 299
            ? $response
            : ($response ?: $this->jsonError($rawResponse));

        $data = $data ?: [];

        return [
            'code' => $code,
            'body' => $data,
        ];
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
        $statusCode = $params['statusCode'];

        if (array_key_exists('webhooks', $response)) {
            $results = [];
            foreach ($response['webhooks'] as $result) {
                $results[] = $this->mapResponse($result, $statusCode);
            }

            return $results;
        }

        // IPN response
        if (in_array(Arr::get($response, 'body'), ['VERIFIED', 'INVALID'])) {
            $response = array_merge($request, $response);
        }
        // charge request
        elseif (array_key_exists('continue_url', $options)) {
            $response['continue_url'] = $options['continue_url'];
            $response['is_redirect'] = true;
        }

        return $this->mapResponse($response, $statusCode);
    }

    /**
     * Map the HTTP response to a response object.
     *
     * @param array $response
     * @param int   $statusCode
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    protected function mapResponse($response, $statusCode)
    {
        // IPN response
        if (in_array(Arr::get($response, 'body'), ['VERIFIED', 'INVALID'])) {
            $success = $response['body'] === 'VERIFIED';

            return (new Response())->setRaw($response)->map([
                'isRedirect'      => false,
                'success'         => $success,
                'reference'       => $success ? Arr::get($response, 'invoice') : false,
                'message'         => $response['body'],
                'test'            => $this->config['test'],
                'authorization'   => $success ? Arr::get($response, 'txn_id') : '',
                'status'          => $success ?
                    $this->getPaymentStatus(Arr::get($response, 'payment_status'))
                    : new Status('failed'),
                'errorCode'       => null,
                'type'            => $success ? Arr::get($response, 'txn_type') : null,
            ]);
        }

        $isRedirect = isset($response['is_redirect']) ? $response['is_redirect'] : false;
        if ($isRedirect) {
            unset($response['is_redirect']);
        }

        // Payment request/execute/get response
        $type = $this->getType($response);
        $state = $this->getSuccessAndStatus($response, $statusCode, $type, $isRedirect);
        $success = $state['success'];
        $status = $state['status'];
        $reference = $state['reference'];
        $authorization = $state['authorization'];
        $message = $success ? 'Transaction approved' : Arr::get($response, 'message', '');

        return (new Response())->setRaw($response)->map([
            'isRedirect'    => $isRedirect,
            'success'       => $success && !$isRedirect,
            'reference'     => $reference,
            'message'       => $message,
            'test'          => $this->config['test'],
            'authorization' => $authorization,
            'status'        => $status,
            'errorCode'     => $success ? null : $this->getErrorCode(Arr::get($response, 'name')),
            'type'          => $type,
        ]);
    }

    /**
     * Get the transaction type.
     *
     * @param array $response
     *
     * @return string|null
     */
    protected function getType($response)
    {
        if (!$response) {
            return;
        }

        if (array_key_exists('intent', $response)) {
            // 'sale' for payment request/execute/get
            return $response['intent'];
        }

        if (array_key_exists('url', $response) && array_key_exists('event_types', $response)) {
            return 'webhook';
        }
    }

    /**
     * Get the success status along with the mapped status.
     *
     * @param mixed  $response
     * @param int    $code
     * @param string $type
     * @param bool   $isRedirect
     *
     * @return array
     */
    private function getSuccessAndStatus($response, $code, $type, $isRedirect)
    {
        if (!in_array($code, [200, 201, 204])) {
            return [
                'success'       => false,
                'status'        => new Status('failed'),
                'reference'     => Arr::get($response, 'debug_id'),
                'authorization' => null,
            ];
        }

        $success = true;
        $status = null;
        $reference = null;
        $authorization = null;

        if ($type === 'sale') {
            $transaction = Arr::last($response['transactions']);
            $resources = Arr::get($transaction, 'related_resources', [[]]);
            $sale = Arr::get($resources[0], 'sale');

            $reference = $sale ? $sale['id'] : $response['id'];
            $authorization = $sale ? $response['id'] : null;
            $state = $sale ? Arr::get($sale, 'state') : Arr::get($response, 'state');

            $success = in_array($state, $this->successPaymentStatuses);
            $status = $this->getPaymentStatus($state);
        }

        if ($isRedirect) {
            $approvalUrl = null;
            foreach ($response['links'] as $link) {
                if ($link['rel'] === 'approval_url') {
                    $approvalUrl = rawurlencode($link['href']);
                    break;
                }
            }
            $authorization = "{$response['continue_url']}/?approval_url={$approvalUrl}&reference={$reference}";
        }

        return [
            'success'       => $success,
            'status'        => $status,
            'reference'     => $reference,
            'authorization' => $authorization,
        ];
    }

    /**
     * Map PayPal payment response to status object.
     *
     * @param string $status
     *
     * @return \Shoperti\PayMe\Status
     */
    protected function getPaymentStatus($status)
    {
        switch ($status) {
            // https://developer.paypal.com/docs/api/payments/v1/
            // sale status
            case 'completed':
                return new Status('paid');
            case 'pending':
                return new Status('pending');
            case 'partially_refunded':
                return new Status('partially_refunded');
            case 'refunded':
                return new Status('refunded');
            case 'denied':
                return new Status('declined');
                // payment general status
            case 'created':
            case 'approved':
                return new Status('pending');
            case 'failed':
                return new Status('failed');
                // IPN status
            case 'Completed':
            case 'Processed':
                return new Status('paid');
            case 'Pending':
            case 'In-Progress':
                return new Status('pending');
            case 'Canceled_Reversal':
                return new Status('canceled');
            case 'Failed':
                return new Status('failed');
            case 'Declined':
                return new Status('declined');
            case 'Expired':
                return new Status('expired');
            case 'Refunded':
                return new Status('refunded');
            case 'Partially_Refunded':
                return new Status('partially_refunded');
            case 'Reversed':
                return new Status('charged_back');
            case 'Voided':
                return new Status('voided');

            default:
                return new Status('failed');
        }
    }

    /**
     * Map the response to error code object.
     *
     * @param string|null $error
     *
     * @return \Shoperti\PayMe\ErrorCode
     */
    protected function getErrorCode($error)
    {
        switch ($error) {
            // Call the API again with the same parameters and values
            case 'INTERNAL_SERVICE_ERROR':
                return new ErrorCode('processing_error');

                // Bank decline, payment no approved
            case 'INSTRUMENT_DECLINED':
                return new ErrorCode('card_declined');

                // PayPal risk decline, payment not approved
            case 'CREDIT_CARD_REFUSED':
            case 'TRANSACTION_REFUSED_BY_PAYPAL_RISK':
                return new ErrorCode('suspected_fraud');
            case 'PAYER_CANNOT_PAY':
            case 'PAYER_ACCOUNT_RESTRICTED':
            case 'PAYER_ACCOUNT_LOCKED_OR_CLOSED':
            case 'PAYEE_ACCOUNT_RESTRICTED':
            case 'TRANSACTION_REFUSED':
                return new ErrorCode('card_declined');

            default:
                return new ErrorCode('config_error');
        }
    }

    /**
     * Get the request url.
     *
     * @return string
     */
    protected function getRequestUrl()
    {
        return ($this->config['test'] ? $this->testEndpoint : $this->endpoint)."/{$this->apiVersion}";
    }
}
