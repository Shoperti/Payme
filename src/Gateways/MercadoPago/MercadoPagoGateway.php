<?php

namespace Shoperti\PayMe\Gateways\MercadoPago;

use Exception;
use Shoperti\PayMe\ErrorCode;
use Shoperti\PayMe\Gateways\AbstractGateway;
use Shoperti\PayMe\Response;
use Shoperti\PayMe\ResponseException;
use Shoperti\PayMe\Status;
use Shoperti\PayMe\Support\Arr;

/**
 * This is the Mercado Pago gateway class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
class MercadoPagoGateway extends AbstractGateway
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
    protected $displayName = 'mercadopago';

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
     * MercadoPago API version.
     *
     * @var string
     */
    protected $apiVersion = 'v1';

    /**
     * The statuses considered as a success on payment responses.
     *
     * @var array
     */
    protected $successPaymentStatuses = [
        'pending',  // atm
        'in_process',
        'in_meditation',
        'approved',
        'authorized',
        'refunded',
        'charged_back',
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
        Arr::requires($config, ['private_key']);

        $config['version'] = $this->apiVersion;

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

        if ($method === 'post') {
            $request['headers']['X-Idempotency-Key'] = Arr::get($params, 'x-idempotency-key', $this->generateUUID());
        }

        $authUrl = sprintf('%s?access_token=%s', $url, $this->config['private_key']);

        $response = $this->performRequest($method, $authUrl, $request);

        try {
            return $this->respond($response['body'], ['code' => $response['code']]);
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
        return 200 <= $code && $code <= 499;
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
        if (isset($response[0])) {
            foreach ($response as $responseItem) {
                $responses[] = $this->respond($responseItem, $params['code']);
            }

            return $responses;
        }

        return $this->mapResponse($this->isSuccess($response, $params['code']), $response);
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
        $type = Arr::get($response, 'operation_type');

        [$reference, $authorization] = $success
            ? [Arr::get($response, 'id'), Arr::get($response, 'authorization_code')]
            : [Arr::get($response, 'id'), null];

        $message = $this->getClientMessage($response);

        return (new Response())->setRaw($response)->map([
            'isRedirect'    => false,
            'success'       => $success,
            'reference'     => $reference,
            'message'       => $message,
            'test'          => array_key_exists('live_mode', $response) ? !$response['live_mode'] : false,
            'authorization' => $authorization,
            'status'        => $this->getStatus($response),
            'errorCode'     => $success ? null : $this->getErrorCode($response),
            'type'          => $type,
        ]);
    }

    /**
     * Check if it's a successful response.
     *
     * @param array $response
     * @param int   $code
     *
     * @return bool
     */
    protected function isSuccess($response, $code)
    {
        if (in_array(Arr::get($response, 'status'), $this->successPaymentStatuses)) {
            return true;
        }

        // Account::info() case
        if (isset($response['site_id'])) {
            return true;
        }

        $success = false;

        // Checking refund, docs say 200 but 201 is currently returned
        // https://www.mercadopago.com.mx/developers/en/solutions/payments/basic-checkout/refund-cancel/
        if ($code === 200 || $code === 201) {
            $success = isset($response['id'])
                && isset($response['payment_id'])
                && isset($response['amount'])
                && isset($response['metadata'])
                && isset($response['source']);
        }

        return $success;
    }

    /**
     * Map MercadoPago response to status object.
     *
     * @param array $response
     *
     * @return \Shoperti\PayMe\Status|null
     */
    protected function getStatus(array $response)
    {
        // https://www.mercadopago.com.mx/developers/en/reference/payments/resource/
        switch ($status = Arr::get($response, 'status', '')) {
            case 'authorized':
            case 'refunded':
            case 'partially_refunded':
            case 'charged_back':
                return new Status($status);
            case 'approved':
                return new Status('paid');
            case 'pending':
            case 'in_process':
            case 'in_mediation':
                return new Status('pending');
            case 'cancelled':
                return new Status('canceled');
            case 'rejected':
                return new Status('declined');
            default:
                return new Status('pending');
        }
    }

    /**
     * Map MercadoPago response to error code object.
     *
     * @param array $response
     *
     * @return \Shoperti\PayMe\ErrorCode
     * @return \Shoperti\PayMe\ErrorCode
     */
    protected function getErrorCode(array $response)
    {
        $code = Arr::get($response, 'status', 1);

        if (!isset($codeMap[$code])) {
            $code = 1;
        }

        $codeMap = [
            1    => 'config_error', // Params Error
            3    => 'config_error', // Token must be for test
            5    => 'config_error', // Must provide your access_token to proceed
            1000 => 'processing_error', // Number of rows exceeded the limits
            1001 => 'invalid_expiry_date', // Date format must be yyyy-MM-dd'T'HH:mm:ss.SSSZ
            2001 => 'processing_error', // Already posted the same request in the last minute
            2004 => 'processing_error', // POST to Gateway Transactions API fail
            2002 => 'processing_error', // Customer not found
            2006 => 'incorrect_number', // Card Token not found
            2007 => 'processing_error', // Connection to Card Token API fail
            2009 => 'config_error', // Card token issuer can't be null
            2010 => 'config_error', // Card not found
            2013 => 'config_error', // Invalid profileId
            2014 => 'config_error', // Invalid reference_id
            2015 => 'config_error', // Invalid scope
            2016 => 'config_error', // Invalid status for update
            2017 => 'config_error', // Invalid transaction_amount for update
            2018 => 'processing_error', // The action requested is not valid for the current payment state
            2020 => 'config_error', // Customer not allowed to operate
            2021 => 'config_error', // Collector not allowed to operate
            2022 => 'processing_error', // You have exceeded the max number of refunds for this payment
            2024 => 'processing_error', // Payment too old to be refunded
            2025 => 'processing_error', // Operation type not allowed to be refunded
            2027 => 'processing_error', // The action requested is not valid for the current payment method type
            2029 => 'processing_error', // Payment without movements
            2030 => 'insufficient_funds', // Collector hasn't enough money
            2031 => 'insufficient_funds', // Collector hasn't enough available money
            2034 => 'config_error', // Invalid users involved
            2035 => 'config_error', // Invalid params for preference Api
            2036 => 'config_error', // Invalid context
            2038 => 'config_error', // Invalid campaign_id
            2039 => 'config_error', // Invalid coupon_code
            2040 => 'config_error', // User email doesn't exist
            2060 => 'config_error', // The customer can't be equal to the collector
            3000 => 'config_error', // You must provide your cardholder_name with your card data
            3001 => 'config_error', // You must provide your cardissuer_id with your card data
            3003 => 'config_error', // Invalid card_token_id
            3004 => 'config_error', // Invalid parameter site_id
            3005 => 'config_error', // Not valid action, the resource is in a state that does not allow this operation. For more information see the state that has the resource.
            3006 => 'config_error', // Invalid parameter cardtoken_id
            3007 => 'config_error', // The parameter client_id can not be null or empty
            3008 => 'config_error', // Not found Cardtoken
            3009 => 'config_error', // unauthorized client_id
            3010 => 'config_error', // Not found card on whitelist
            3011 => 'config_error', // Not found payment_method
            3012 => 'incorrect_cvc', // Invalid parameter security_code_length
            3013 => 'incorrect_cvc', // The parameter security_code is a required field can not be null or empty
            3014 => 'config_error', // Invalid parameter payment_method
            3015 => 'incorrect_number', // Invalid parameter card_number_length
            3016 => 'incorrect_number', // Invalid parameter card_number
            3017 => 'config_error', // The parameter card_number_id can not be null or empty
            3018 => 'invalid_expiry_date', // The parameter expiration_month can not be null or empty
            3019 => 'invalid_expiry_date', // The parameter expiration_year can not be null or empty
            3020 => 'config_error', // The parameter cardholder.name can not be null or empty
            3021 => 'config_error', // The parameter cardholder.document.number can not be null or empty
            3022 => 'config_error', // The parameter cardholder.document.type can not be null or empty
            3023 => 'config_error', // The parameter cardholder.document.subtype can not be null or empty
            3024 => 'config_error', // Not valid action - partial refund unsupported for this transaction
            3025 => 'config_error', // Invalid Auth Code
            3026 => 'config_error', // Invalid card_id for this payment_method_id
            3027 => 'config_error', // Invalid payment_type_id
            3028 => 'config_error', // Invalid payment_method_id
            3029 => 'invalid_expiry_date', // Invalid card expiration month
            3030 => 'invalid_expiry_date', // Invalid card expiration year
            4000 => 'invalid_number', // card attribute can't be null
            4001 => 'config_error', // payment_method_id attribute can't be null
            4002 => 'config_error', // transaction_amount attribute can't be null
            4003 => 'invalid_number', // transaction_amount attribute must be numeric
            4004 => 'config_error', // installments attribute can't be null
            4005 => 'config_error', // installments attribute must be numeric
            4006 => 'config_error', // payer attribute is malformed
            4007 => 'config_error', // site_id attribute can't be null
            4012 => 'config_error', // payer.id attribute can't be null
            4013 => 'config_error', // payer.type attribute can't be null
            4015 => 'config_error', // payment_method_reference_id attribute can't be null
            4016 => 'config_error', // payment_method_reference_id attribute must be numeric
            4017 => 'config_error', // status attribute can't be null
            4018 => 'config_error', // payment_id attribute can't be null
            4019 => 'config_error', // payment_id attribute must be numeric
            4020 => 'config_error', // notification_url attribute must be url valid
            4021 => 'config_error', // notification_url attribute must be shorter than 500 character
            4022 => 'config_error', // metadata attribute must be a valid JSON
            4023 => 'config_error', // transaction_amount attribute can't be null
            4024 => 'config_error', // transaction_amount attribute must be numeric
            4025 => 'config_error', // refund_id can't be null
            4026 => 'config_error', // Invalid coupon_amount
            4027 => 'config_error', // campaign_id attribute must be numeric
            4028 => 'config_error', // coupon_amount attribute must be numeric
            4029 => 'config_error', // Invalid payer type
            4037 => 'config_error', // Invalid transaction_amount
            4038 => 'config_error', // application_fee cannot be bigger than transaction_amount
            4039 => 'config_error', // application_fee cannot be a negative value
            4050 => 'config_error', // payer.email must be a valid email
            4051 => 'config_error', // payer.email must be shorter than 254 characters
        ];

        return new ErrorCode($codeMap[$code]);
    }

    /**
     * Gets the message to present to the client.
     *
     * @param array $response
     *
     * @see https://www.mercadopago.com.mx/developers/en/api-docs/custom-checkout/create-payments/
     * @see https://www.mercadopago.com.mx/developers/en/guides/payments/api/handling-responses/
     *
     * @return string
     */
    protected function getClientMessage(array $response)
    {
        if (isset($response['message'])) {
            return $response['message'];
        }

        $codeMap = [
            // status_detail messages
            'accredited'                           => 'Done, your payment was approved! You will see the amount charged in your bill as statement_descriptor.',
            'pending_contingency'                  => 'We are processing the payment. In less than an hour we will e-mail you the results.',
            'pending_review_manual'                => 'We are processing the payment. In less than 2 business days we will tell you by e-mail whether it was approved or if we need more information.',
            'cc_rejected_bad_filled_card_number'   => 'Check the card number.',
            'cc_rejected_bad_filled_date'          => 'Check the expiration date.',
            'cc_rejected_bad_filled_other'         => 'Check the information.',
            'cc_rejected_bad_filled_security_code' => 'Check the security code.',
            'cc_rejected_blacklist'                => 'We could not process your payment.',
            'cc_rejected_call_for_authorize'       => 'You must authorize payment_method_id to pay the amount to MercadoPago',
            'cc_rejected_card_disabled'            => 'Call payment_method_id to activate your card. The phone number is on the back of your card.',
            'cc_rejected_card_error'               => 'We could not process your payment.',
            'cc_rejected_duplicated_payment'       => 'You already made a payment for that amount. If you need to repay, use another card or other payment method.',
            'cc_rejected_high_risk'                => 'Your payment was declined. Choose another payment method.',
            'cc_rejected_insufficient_amount'      => 'Your payment_method_id do not have sufficient funds.',
            'cc_rejected_invalid_installments'     => 'payment_method_id does not process payments in installments.',
            'cc_rejected_max_attempts'             => 'You have reached the limit of allowed attempts. Choose another card or another payment method.',
            'cc_rejected_other_reason'             => 'payment_method_id did not process the payment.',
            // undocumented status_detail
            'pending_waiting_payment'              => 'Please perform your payment using the given information.',
            // status without status_detail docs
            'pending'                              => 'Your payment is pending.',
            'authorized'                           => 'Your payment was authorized but not approved yet.',
        ];

        $values = [
            'payment_method_id'    => Arr::get($response, 'payment_method_id', 'card issuer'),
            'statement_descriptor' => Arr::get($response, 'statement_descriptor'),
        ];

        $code = Arr::get($response, 'status_detail', Arr::get($response, 'status'));

        // when requesting Account::info() status is an array
        if (is_array($code)) {
            $code = '';
        }

        $message = $code === 'accredited'
            ? !empty($values['statement_descriptor']) ? Arr::get($codeMap, $code) : 'Transaction approved'
            : Arr::get($codeMap, $code);

        $message = $message
            ? str_replace(array_keys($values), array_values($values), $message)
            : str_replace('_', ' ', $code);

        return ucfirst($message);
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

    /**
     * Generate UUID for Idempotency-Key header.
     *
     * @return string
     */
    protected function generateUUID()
    {
        $data = random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0F | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3F | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
