<?php

namespace Dinkbit\PayMe\Gateways;

use Dinkbit\PayMe\Contracts\Charge;
use Dinkbit\PayMe\Contracts\Store;
use Dinkbit\PayMe\Status;
use Dinkbit\PayMe\Transaction;

class Conekta extends AbstractGateway implements Charge, Store
{
    protected $liveEndpoint = 'https://api.conekta.io';
    protected $defaultCurrency = 'MXN';
    protected $displayName = 'conekta';
    protected $moneyFormat = 'cents';

    protected $apiVersion = "0.3.0";
    protected $locale = 'es';

    /**
     * @param $config
     */
    public function __construct($config)
    {
        $this->requires($config, ['private_key']);

        $config['version'] = $this->apiVersion;
        $config['locale'] = $this->locale;

        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function charge($amount, $payment, $options = [])
    {
        $params = [];

        $params = $this->addOrder($params, $amount, $options);
        $params = $this->addPayMentMethod($params, $payment, $options);

        return $this->commit('post', $this->buildUrlFromString('charges'), $params);
    }

    /**
     * {@inheritdoc}
     */
    public function store($creditcard, $options = [])
    {
        if (isset($options['customer'])) {
            $params['token'] = $creditcard;

            return $this->commit('post', $this->buildUrlFromString('customers/'.$options['customer'].'/cards'), $params);
        } else {
            $params['email'] = $this->array_get($options, 'email');
            $params['name'] = $this->array_get($options, 'name');
            $params['cards'] = [$creditcard];

            return $this->commit('post', $this->buildUrlFromString('customers'), $params);
        }
    }

    /**
     * {@inheritdoc}
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
     * @param $params
     * @param $money
     * @param $options
     *
     * @return mixed
     */
    protected function addOrder($params, $money, $options)
    {
        $params['description'] = $this->array_get($options, 'description', "PayMe Purchase");
        $params['reference_id'] = $this->array_get($options, 'order_id');
        $params['currency'] = $this->array_get($options, 'currency', $this->getCurrency());
        $params['amount'] = $this->amount($money);

        return $params;
    }

    /**
     * @param $params
     * @param $payment
     * @param $options
     *
     * @return mixed
     */
    protected function addPayMentMethod($params, $payment, $options)
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
     * @param $params
     * @param $options
     *
     * @return mixed
     */
    protected function addAddress($params, $options)
    {
        if ($address = $this->array_get($options, 'address') or $this->array_get($options, 'billing_address')) {
            $params['address'] = [];
            $params['address']['street1'] = $this->array_get($address, 'address1');
            $params['address']['street2'] = $this->array_get($address, 'address2');
            $params['address']['street3'] = $this->array_get($address, 'address3');
            $params['address']['city'] = $this->array_get($address, 'city');
            $params['address']['country'] = $this->array_get($address, 'country');
            $params['address']['state'] = $this->array_get($address, 'state');
            $params['address']['zip'] = $this->array_get($address, 'zip');

            return $params;
        }
    }

    /**
     * @param $params
     * @param $creditcard
     * @param $options
     *
     * @return string
     */
    protected function addCustomer($params, $creditcard, $options)
    {
        return $params['customer'] = array_key_exists('customer', $options) ? $options['customer'] : '';
    }

    /**
     * {@inheritdoc}
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
            'json' => $params
        ]);

        if ($rawResponse->getStatusCode() == 200) {
            $response = $this->parseResponse($rawResponse->getBody());
            $success = ! ($this->array_get($response, 'object', 'error') == 'error');
        } else {
            $response = $this->responseError($rawResponse);
        }

        return $this->mapResponseToTransaction($success, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function mapResponseToTransaction($success, $response)
    {
        return (new Transaction())->setRaw($response)->map([
            'isRedirect'      => false,
            'success'         => $success,
            'message'         => $success ? 'Transacción aprobada' : $response['message_to_purchaser'],
            'test'            => array_key_exists('livemode', $response) ? $response["livemode"] : false,
            'authorization'   => $success ? $response['id'] : $response['type'],
            'status'          => $success ? $this->getStatus($this->array_get($response, 'status', 'paid')) : new Status('failed'),
            'reference'       => $success ? $this->getReference($response) : false,
            'code'            => $success ? false : $response['code'],
        ]);
    }

    /**
     * @param $response
     *
     * @return null
     */
    protected function getReference($response)
    {
        $object = $this->array_get($response, 'object');

        if ($object == 'customer') {
            return $this->array_get($response, 'default_card_id');
        } elseif ($object == 'card') {
            return $this->array_get($response, 'customer_id');
        }

        return $response['payment_method']['auth_code'];
    }

    /**
     * @param $status
     *
     * @return Status
     */
    protected function getStatus($status)
    {
        switch ($status) {
            case 'pending_payment';

                return new Status('pending');
                break;
            case 'paid':
            case 'refunded':
            case 'paused':
            case 'active':
            case 'canceled':
                return new Status($status);
                break;
            case 'in_trial';

                return new Status('trial');
                break;
        }
    }

    /**
     * @param $body
     *
     * @return array
     */
    protected function parseResponse($body)
    {
        return json_decode($body, true);
    }

    /**
     * @param $rawResponse
     *
     * @return array
     */
    protected function responseError($rawResponse)
    {
        if (! $this->isJson($rawResponse->getBody())) {
            return $this->jsonError($rawResponse);
        }

        return $this->parseResponse($rawResponse->getBody());
    }

    /**
     * @param $rawResponse
     *
     * @return array
     */
    public function jsonError($rawResponse)
    {
        $msg = 'Respuesta no válida recibida de la API de Conekta. Por favor, póngase en contacto con contacto@conekta.com si sigues recibiendo este mensaje.';
        $msg .= " (Respuesta en bruto devuelto por el API {$rawResponse->getBody()})";

        return [
            'message_to_purchaser' => $msg
        ];
    }

    /**
     * @param $string
     *
     * @return bool
     */
    protected function isJson($string)
    {
        json_decode($string);

        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * @return string
     */
    protected function getRequestUrl()
    {
        return $this->liveEndpoint;
    }
}
