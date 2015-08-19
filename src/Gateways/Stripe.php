<?php

namespace Shoperti\PayMe\Gateways;

use Shoperti\PayMe\Contracts\Charge;
use Shoperti\PayMe\Contracts\Store;
use Shoperti\PayMe\Status;
use Shoperti\PayMe\Support\Arr;
use Shoperti\PayMe\Support\Helper;
use Shoperti\PayMe\Transaction;

class Stripe extends AbstractGateway implements Charge, Store
{
    /**
     * Gateway API endpoint.
     *
     * @var string
     */
    protected $endpoint = 'https://api.stripe.com';

    /**
     * Gateway display name.
     *
     * @var string
     */
    protected $displayName = 'stripe';

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
    protected $moneyFormat = 'cents';

    /**
     * Stripe API version.
     *
     * @var string
     */
    protected $apiVersion = 'v1';

    /**
     * Inject the configuration for a Gateway.
     *
     * @param $config
     */
    public function __construct($config)
    {
        Arr::requires($config, ['secret']);

        $config['version'] = $this->apiVersion;

        $this->config = $config;
    }

    /**
     * Charge the credit card.
     *
     * @param $amount
     * @param $payment
     * @param string[] $options
     *
     * @return \Shoperti\Payme\Transaction
     */
    public function charge($amount, $payment, $options = [])
    {
        $params = [];

        $params = $this->addOrder($params, $amount, $options);
        $params = $this->addCard($params, $payment, $options);
        $params = $this->addCustomer($params, $payment, $options);

        return $this->commit('post', $this->buildUrlFromString('charges'), $params);
    }

    /**
     * Stores a credit card.
     *
     * @param $creditcard
     * @param string[] $options
     *
     * @return mixed
     */
    public function store($creditcard, $options = [])
    {
        if (isset($options['customer'])) {
            $params['card'] = $creditcard;

            return $this->commit('post', $this->buildUrlFromString('customers/'.$options['customer'].'/cards'), $params);
        } else {
            $params['email'] = Arr::get($options, 'email');
            $params['description'] = Arr::get($options, 'name');
            $params['card'] = $creditcard;

            return $this->commit('post', $this->buildUrlFromString('customers'), $params);
        }
    }

    /**
     * Unstores a credit card.
     *
     * @param $reference
     * @param string[] $options
     *
     * @return mixed
     */
    public function unstore($reference, $options = [])
    {
        if (isset($options['card_id'])) {
            return $this->commit('delete', $this->buildUrlFromString('customers/'.$reference.'/cards/'.$options['card_id']));
        } else {
            return $this->commit('delete', $this->buildUrlFromString('customers/'.$reference));
        }
    }

    /**
     * Add order params to request.
     *
     * @param $params[]
     * @param $money
     * @param $options[]
     *
     * @return array
     */
    protected function addOrder(array $params, $money, array $options)
    {
        $params['description'] = Helper::cleanAccents(Arr::get($options, 'description', 'PayMe Purchase'));
        $params['currency'] = Arr::get($options, 'currency', $this->getCurrency());
        $params['amount'] = $this->amount($money);

        return $params;
    }

    /**
     * Add payment method to request.
     *
     * @param $params[]
     * @param $payment
     * @param $options[]
     *
     * @return array
     */
    protected function addCard(array $params, $payment, array $options)
    {
        if (is_string($payment)) {
            $params['card'] = $payment;
        } elseif ($payment instanceof CreditCard) {
            $params['card'] = [];
            $params['card']['name'] = $payment->getName();
            $params['card']['cvc'] = $payment->getCvv();
            $params['card']['number'] = $payment->getNumber();
            $params['card']['exp_month'] = $payment->getExpiryMonth();
            $params['card']['exp_year'] = $payment->getExpiryYear();
            $params['card'] = $this->addAddress($params['card'], $options);
        }

        return $params;
    }

    /**
     * Add address to request.
     *
     * @param $params[]
     * @param $options[]
     *
     * @return array
     */
    protected function addAddress(array $params, array $options)
    {
        if ($address = Arr::get($options, 'address') or Arr::get($options, 'billing_address')) {
            $params['address'] = [];
            $params['address']['street1'] = Arr::get($address, 'address1');
            $params['address']['street2'] = Arr::get($address, 'address2');
            $params['address']['street3'] = Arr::get($address, 'address3');
            $params['address']['city'] = Arr::get($address, 'city');
            $params['address']['country'] = Arr::get($address, 'country');
            $params['address']['state'] = Arr::get($address, 'state');
            $params['address']['zip'] = Arr::get($address, 'zip');

            return $params;
        }
    }

    /**
     * Add customer to request.
     *
     * @param $params[]
     * @param $creditcard
     * @param $options[]
     *
     * @return array
     */
    protected function addCustomer(array $params, $creditcard, array $options)
    {
        if (array_key_exists('customer', $options)) {
            $params['customer'] = $options['customer'];
        }

        return $params;
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
    protected function commit($method = 'post', $url, $params = [], $options = [])
    {
        $userAgent = [
            'bindings_version' => $this->config['version'],
            'lang'             => 'php',
            'lang_version'     => phpversion(),
            'publisher'        => 'stripe',
            'uname'            => php_uname(),
        ];

        $success = false;

        $rawResponse = $this->getHttpClient()->{$method}($url, [
            'exceptions'      => false,
            'timeout'         => '80',
            'connect_timeout' => '30',
            'headers'         => [
                'Authorization'              => 'Basic '.base64_encode($this->config['secret'].':'),
                'Content-Type'               => 'application/x-www-form-urlencoded',
                'RaiseHtmlError'             => 'false',
                'User-Agent'                 => 'Stripe/v1 PayMeBindings/'.$this->config['version'],
                'X-Stripe-Client-User-Agent' => json_encode($userAgent),
            ],
            'body' => $params,
        ]);

        if ($rawResponse->getStatusCode() == 200) {
            $response = $this->parseResponse($rawResponse->getBody());
            $success = (!array_key_exists('error', $response));
        } else {
            $response = $this->responseError($rawResponse);
        }

        return $this->mapTransaction($success, $response);
    }

    /**
     * Map HTTP response to transaction object.
     *
     * @param bool  $success
     * @param array $response
     *
     * @return \Shoperti\PayMe\Transaction
     */
    public function mapTransaction($success, $response)
    {
        return (new Transaction())->setRaw($response)->map([
            'isRedirect'      => false,
            'success'         => $success,
            'message'         => $success ? 'Transaction approved' : $response['error']['message'],
            'test'            => array_key_exists('livemode', $response) ? $response['livemode'] : false,
            'authorization'   => $success ? $response['id'] : Arr::get($response['error'], 'charge', 'error'),
            'status'          => $success ? $this->getStatus(Arr::get($response, 'paid', true)) : new Status('failed'),
            'reference'       => $success ? Arr::get($response, 'balance_transaction', '') : false,
            'code'            => $success ? false : $response['error']['type'],
        ]);
    }

    /**
     * Map Conekta response to status object.
     *
     * @param $status
     *
     * @return \Shoperti\PayMe\Status
     */
    protected function getStatus($status)
    {
        return $status ? new Status('paid') : new Status('pending');
    }

    /**
     * Parse JSON response to array.
     *
     * @param  $body
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
     * @param string $rawResponse
     *
     * @return array
     */
    protected function responseError($rawResponse)
    {
        return $this->parseResponse($rawResponse->getBody()) ?: $this->jsonError($rawResponse);
    }

    /**
     * Default JSON response.
     *
     * @param $rawResponse
     *
     * @return array
     */
    public function jsonError($rawResponse)
    {
        $msg = 'API Response not valid.';
        $msg .= " (Raw response API {$rawResponse->getBody()})";

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
        return $this->endpoint.'/'.$this->apiVersion;
    }
}
