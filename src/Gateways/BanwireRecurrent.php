<?php

namespace Shoperti\PayMe\Gateways;

use Shoperti\PayMe\Contracts\Charge;
use Shoperti\PayMe\Status;
use Shoperti\PayMe\Support\Arr;
use Shoperti\PayMe\Transaction;

class BanwireRecurrent extends AbstractGateway implements Charge
{
    /**
     * Gateway API endpoint.
     *
     * @var string
     */
    protected $endpoint = 'https://banwiresecure.com/Recurrentes2013/recurrente';

    /**
     * Gateway display name.
     *
     * @var string
     */
    protected $displayName = 'banwirerecurrent';

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
     * Inject the configuration for a Gateway.
     *
     * @param $config
     */
    public function __construct($config)
    {
        Arr::requires($config, ['merchant', 'email']);

        $this->config = $config;
    }

    /**
     * Charge the credit card.
     *
     * @param $amount
     * @param $payment
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Transaction
     */
    public function charge($amount, $payment, $options = [])
    {
        $params = [];

        $params = $this->addOrder($params, $amount, $options);
        $params = $this->addPayMent($params, $payment, $options);
        $params = $this->addUser($params, $options);

        return $this->commit('post', $this->buildUrlFromString('ejecuta_pago_ondemand'), $params);
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
        $params['monto'] = $this->amount($money);

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
    protected function addPayMent(array $params, $payment, array $options)
    {
        $params['token'] = $payment;
        $params['id_tarjeta'] = Arr::get($options, 'card_id');

        $name = Arr::get($options, 'card_name');
        list($cardName, $cardLastName) = explode(' ', "$name ", 2);

        $params['card_name'] = $cardName;
        $params['card_lastname'] = $cardLastName;

        return $params;
    }

    /**
     * Add user to request.
     *
     * @param $params[]
     * @param $options[]
     *
     * @return array
     */
    protected function addUser(array $params, array $options)
    {
        $params['cliente_id'] = Arr::get($options, 'customer');

        return $params;
    }

    /**
     * Map transaction code to message.
     *
     * @param string $code
     * @param string $message
     *
     * @return string
     */
    protected function getTransactionMessage($code, $message = 'Lo sentimos ocurrió un error.')
    {
        switch ($code) {
            case 'N/A':
            case '404':
                $responseMessage = 'Tarjeta denegada, por favor revisa tu información e intenta de nuevo.';
                break;
            case '405':
                $responseMessage = 'Los datos de facturación de American Express no son los mismos que en el estado de cuenta.';
                break;
            case '406':
                $responseMessage = 'Lo sentimos, esta tarjeta no puede ser procesada por seguridad.';
                break;
            case '403':
            case '250':
            case '700':
                $responseMessage = 'Los datos de la tarjeta no son correctos, por favor revisa tu información e intenta de nuevo.';
                break;
            case '100':
                $responseMessage = 'La dirección y código postal de la tarjeta de crédito no coinciden.';
                break;
            case '212':
            case '213':
            case '216':
                $responseMessage = $message;
                break;
            default:
                $responseMessage = 'Por el momento no es posible procesar la transacción, favor de intentar mas tarde.';
                break;
        }

        return $responseMessage;
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
        $params['usr_banwire'] = $this->config['merchant'];
        $params['email'] = $this->config['email'];

        $rawResponse = $this->getHttpClient()->{$method}($url, [
            'exceptions'      => false,
            'timeout'         => '80',
            'connect_timeout' => '30',
            'body'            => $params,
        ]);

        $response = $this->parseResponse($rawResponse->getBody());

        $success = $this->getSuccess($response);

        if (!$success) {
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
            'message'         => $success ? 'Transacción aprobada' : $this->getTransactionMessage(Arr::get($response, 'code_auth'), Arr::get($response, 'message')),
            'test'            => false,
            'authorization'   => Arr::get($response, 'code_auth', false),
            'status'          => $success ? Arr::get($response, 'message', true) : new Status('failed'),
            'reference'       => Arr::get($response, 'status', false),
            'code'            => Arr::get($response, 'code_auth', false),
        ]);
    }

    /**
     * Is Banwire response success?
     *
     * @param $transaction
     *
     * @return bool
     */
    protected function getSuccess($transaction)
    {
        if (null === $transaction) {
            return false;
        }

        if (isset($transaction['response']) and $transaction['response'] == 'ok') {
            return true;
        }

        foreach ($transaction as $key => $value) {
            if ($value == 'ok') {
                return true;
            }
        }

        return false;
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
        return $this->endpoint;
    }
}
