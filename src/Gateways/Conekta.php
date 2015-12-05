<?php

namespace Shoperti\PayMe\Gateways;

use Shoperti\PayMe\Contracts\Charge;
use Shoperti\PayMe\Contracts\Store;
use Shoperti\PayMe\Status;
use Shoperti\PayMe\Support\Arr;
use Shoperti\PayMe\Support\Helper;
use Shoperti\PayMe\Transaction;

class Conekta extends AbstractGateway implements Charge, Store
{
    /**
     * Gateway API endpoint.
     *
     * @var string
     */
    protected $endpoint = 'https://api.conekta.io';

    /**
     * Gateway display name.
     *
     * @var string
     */
    protected $displayName = 'conekta';

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
    protected $moneyFormat = 'cents';

    /**
     * Conekta API version.
     *
     * @var string
     */
    protected $apiVersion = '0.3.0';

    /**
     * Conekta API locale.
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
     * Charge the credit card.
     *
     * @param int      $amount
     * @param mixed    $payment
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Transaction
     */
    public function charge($amount, $payment, $options = [])
    {
        $params = [];

        $params = $this->addOrder($params, $amount, $options);
        $params = $this->addPayMentMethod($params, $payment, $options);
        $params = $this->addOrderDetails($params, $options);

        return $this->commit('post', $this->buildUrlFromString('charges'), $params);
    }

    /**
     * Stores a credit card.
     *
     * @param string   $creditcard
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Transaction
     */
    public function store($creditcard, $options = [])
    {
        if (isset($options['customer'])) {
            $params['token'] = $creditcard;

            return $this->commit('post', $this->buildUrlFromString('customers/'.$options['customer'].'/cards'), $params);
        } else {
            $params['email'] = Arr::get($options, 'email');
            $params['name'] = Arr::get($options, 'name');
            $params['cards'] = [$creditcard];

            return $this->commit('post', $this->buildUrlFromString('customers'), $params);
        }
    }

    /**
     * Unstores a credit card.
     *
     * @param string   $reference
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Transaction
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
     * @param string[] $params
     * @param int      $money
     * @param string[] $options
     *
     * @return array
     */
    protected function addOrder(array $params, $money, array $options)
    {
        $params['description'] = Helper::cleanAccents(Arr::get($options, 'description', 'PayMe Purchase'));
        $params['reference_id'] = Arr::get($options, 'order_id');
        $params['currency'] = Arr::get($options, 'currency', $this->getCurrency());
        $params['amount'] = $this->amount($money);

        return $params;
    }

    /**
     * Add order details params.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addOrderDetails(array $params, array $options)
    {
        if (isset($options['name'])) {
            $params['details']['name'] = Arr::get($options, 'name', '');
        }

        if (isset($options['email'])) {
            $params['details']['email'] = Arr::get($options, 'email', '');
        }

        if (isset($options['phone'])) {
            $params['details']['phone'] = Arr::get($options, 'phone', '');
        }

        $params = $this->addCustomer($params, $options);
        $params = $this->addLineItems($params, $options);
        $params = $this->addBillingAddress($params, $options);
        $params = $this->addShippingAddress($params, $options);

        return $params;
    }

    /**
     * Add payment method to request.
     *
     * @param string[] $params
     * @param mixed    $payment
     * @param string[] $options
     *
     * @return array
     */
    protected function addPayMentMethod(array $params, $payment, array $options)
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
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addAddress(array $params, array $options)
    {
        if ($address = Arr::get($options, 'address') || Arr::get($options, 'billing_address')) {
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
     * @param string[] $params
     * @param string   $creditcard
     * @param string[] $options
     *
     * @return array
     */
    protected function addCustomer(array $params, array $options)
    {
        if ($customer = Arr::get($options, 'customer')) {
            $params['details']['customer'] = [];
            $params['details']['customer']['logged_in'] = Arr::get($customer, 'logged_in');
            $params['details']['customer']['successful_purchases'] = Arr::get($customer, 'successful_purchases');
            $params['details']['customer']['created_at'] = Arr::get($customer, 'created_at');
            $params['details']['customer']['updated_at'] = Arr::get($customer, 'updated_at');
            $params['details']['customer']['offline_payments'] = Arr::get($customer, 'offline_payments');
            $params['details']['customer']['score'] = Arr::get($customer, 'score');
        }

        return $params;
    }

    /**
     * Add order line items param.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addLineItems(array $params, array $options)
    {
        $params['details']['line_items'] = [];

        if (isset($options['line_items']) && is_array($options['line_items'])) {
            foreach ($options['line_items'] as $line_item) {
                $params['details']['line_items'][] = [
                    'name'        => Arr::get($line_item, 'name'),
                    'description' => Arr::get($line_item, 'description'),
                    'unit_price'  => $this->amount(Arr::get($line_item, 'unit_price')),
                    'quantity'    => Arr::get($line_item, 'quantity', 1),
                    'sku'         => Arr::get($line_item, 'sku'),
                    'category'    => Arr::get($line_item, 'category'),
                ];
            }
        }

        return $params;
    }

    /**
     * Add Billing address to request.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addBillingAddress(array $params, array $options)
    {
        if ($address = Arr::get($options, 'billing_address')) {
            $params['details']['billing_address'] = [];
            $params['details']['billing_address']['street1'] = Arr::get($address, 'address1');
            $params['details']['billing_address']['street2'] = Arr::get($address, 'address2');
            $params['details']['billing_address']['street3'] = Arr::get($address, 'address3');
            $params['details']['billing_address']['city'] = Arr::get($address, 'city');
            $params['details']['billing_address']['country'] = Arr::get($address, 'country');
            $params['details']['billing_address']['state'] = Arr::get($address, 'state');
            $params['details']['billing_address']['zip'] = Arr::get($address, 'zip');
            $params['details']['billing_address']['tax_id'] = Arr::get($address, 'tax_id');
            $params['details']['billing_address']['company_name'] = Arr::get($address, 'company_name');
            $params['details']['billing_address']['phone'] = Arr::get($address, 'phone');
            $params['details']['billing_address']['email'] = Arr::get($address, 'email');
        }

        return $params;
    }

    /**
     * Add Shipping address to request.
     *
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addShippingAddress(array $params, array $options)
    {
        if ($address = Arr::get($options, 'shipping_address')) {
            $params['details']['shipment'] = [];
            $params['details']['shipment']['carrier'] = Arr::get($address, 'carrier');
            $params['details']['shipment']['service'] = Arr::get($address, 'service');
            $params['details']['shipment']['price'] = Arr::get($address, 'price');
            $params['details']['shipment']['address']['street1'] = Arr::get($address, 'address1');
            $params['details']['shipment']['address']['street2'] = Arr::get($address, 'address2');
            $params['details']['shipment']['address']['street3'] = Arr::get($address, 'address3');
            $params['details']['shipment']['address']['city'] = Arr::get($address, 'city');
            $params['details']['shipment']['address']['state'] = Arr::get($address, 'state');
            $params['details']['shipment']['address']['zip'] = Arr::get($address, 'zip');
            $params['details']['shipment']['address']['country'] = Arr::get($address, 'country');
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
        $user_agent = [
            'bindings_version' => $this->config['version'],
            'lang'             => 'php',
            'lang_version'     => phpversion(),
            'publisher'        => 'conekta',
            'uname'            => php_uname(),
        ];

        $success = false;

        $rawResponse = $this->getHttpClient()->{$method}($url, [
            'exceptions'      => false,
            'timeout'         => '80',
            'connect_timeout' => '30',
            'headers'         => [
                'Accept'                      => "application/vnd.conekta-v{$this->config['version']}+json",
                'Accept-Language'             => $this->config['locale'],
                'Authorization'               => 'Basic '.base64_encode($this->config['private_key'].':'),
                'Content-Type'                => 'application/json',
                'RaiseHtmlError'              => 'false',
                'X-Conekta-Client-User-Agent' => json_encode($user_agent),
                'User-Agent'                  => 'Conekta PayMeBindings/'.$this->config['version'],
            ],
            'json' => $params,
        ]);

        if ($rawResponse->getStatusCode() == 200) {
            $response = $this->parseResponse($rawResponse->getBody());
            $success = !(Arr::get($response, 'object', 'error') == 'error');
        } else {
            $response = $this->responseError($rawResponse->getBody());
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
            'isRedirect'    => false,
            'success'       => $success,
            'message'       => $success ? 'Transacción aprobada' : $response['message_to_purchaser'],
            'test'          => array_key_exists('livemode', $response) ? $response['livemode'] : false,
            'authorization' => $success ? $response['id'] : $response['type'],
            'status'        => $success ? $this->getStatus(Arr::get($response, 'status', 'paid')) : new Status('failed'),
            'reference'     => $success ? $this->getReference($response) : false,
            'code'          => $success ? false : $response['code'],
        ]);
    }

    /**
     * Map reference to response.
     *
     * @param array $response
     *
     * @return string|null
     */
    protected function getReference($response)
    {
        $object = Arr::get($response, 'object');

        if ($object == 'customer') {
            return Arr::get($response, 'default_card_id');
        } elseif ($object == 'card') {
            return Arr::get($response, 'customer_id');
        } elseif ($object == 'payee') {
            return Arr::get($response, 'id');
        } elseif ($object == 'transfer') {
            return Arr::get($response, 'id');
        }

        return $response['payment_method']['auth_code'];
    }

    /**
     * Map Conekta response to status object.
     *
     * @param string $status
     *
     * @return \Shoperti\PayMe\Status
     */
    protected function getStatus($status)
    {
        switch ($status) {
            case 'pending_payment':
                return new Status('pending');
                break;
            case 'paid':
            case 'refunded':
            case 'paused':
            case 'active':
            case 'canceled':
                return new Status($status);
                break;
            case 'in_trial':
                return new Status('trial');
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
            'message_to_purchaser' => $msg,
        ];
    }

    /**
     * Get the request url.
     *
     * @return string
     */
    protected function getRequestUrl()
    {
        return $this->endpoint;
    }
}
