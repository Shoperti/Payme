<?php

namespace Dinkbit\PayMe\Gateways;

use Dinkbit\PayMe\Contracts\Charge;
use Dinkbit\PayMe\Status;
use Dinkbit\PayMe\Transaction;

class BanwireRecurrent extends AbstractGateway implements Charge
{
    protected $liveEndpoint = 'https://banwiresecure.com/Recurrentes2013/recurrente';
    protected $defaultCurrency = 'MXN';
    protected $displayName = 'banwirerecurrent';
    protected $moneyFormat = 'dollars';

    /**
     * @param $config
     */
    public function __construct($config)
    {
        $this->requires($config, ['merchant', 'email']);

        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function charge($amount, $payment, $options = [])
    {
        $params = [];

        $params = $this->addOrder($params, $amount);
        $params = $this->addPayMent($params, $payment, $options);
        $params = $this->addUser($params, $options);

        return $this->commit('post', $this->buildUrlFromString('ejecuta_pago_ondemand'), $params);
    }

    /**
     * @param $params
     * @param $money
     *
     * @throws InvalidRequestException
     *
     * @return mixed
     */
    protected function addOrder($params, $money)
    {
        $params['monto'] = $this->amount($money);

        return $params;
    }

    /**
     * @param $params
     * @param $payment
     * @param array $options
     */
    protected function addPayMent($params, $payment, $options = [])
    {
        $params['token'] = $payment;
        $params['id_tarjeta'] = $this->array_get($options, 'card_id');

        $name = $this->array_get($options, 'card_name');
        list($cardName, $cardLastName) = explode(' ', "$name ", 2);

        $params['card_name'] = $cardName;
        $params['card_lastname'] = $cardLastName;

        return $params;
    }

    /**
     * @param $params
     * @param array $options
     */
    protected function addUser($params, $options = [])
    {
        $params['cliente_id'] = $this->array_get($options, 'customer');

        return $params;
    }

    /**
     * @param $code
     * @param string $message
     *
     * @return string
     */
    protected function getTransactionMessage($code, $message = 'Lo sentimos ocurrió un error.')
    {
        switch ($code) {
            case 'N/A':
            case '404':
                $responseMessage = "Tarjeta denegada, por favor revisa tu información e intenta de nuevo.";
                break;
            case '405':
                $responseMessage = "Los datos de facturación de American Express no son los mismos que en el estado de cuenta.";
                break;
            case '406':
                $responseMessage = "Lo sentimos, esta tarjeta no puede ser procesada por seguridad.";
                break;
            case '403':
            case '250':
            case '700':
                $responseMessage = "Los datos de la tarjeta no son correctos, por favor revisa tu información e intenta de nuevo.";
                break;
            case '100':
                $responseMessage = "La dirección y código postal de la tarjeta de crédito no coinciden.";
                break;
            case '212':
            case '213':
            case '216':
                $responseMessage = $message;
                break;
            default:
                $responseMessage = "Por el momento no es posible procesar la transacción, favor de intentar mas tarde.";
                break;
        }

        return $responseMessage;
    }

    /**
     * {@inheritdoc}
     */
    protected function commit($method = 'post', $url, $params = [], $options = [])
    {
        $params['usr_banwire'] = $this->config['merchant'];
        $params['email'] = $this->config['email'];

        $rawResponse = $this->getHttpClient()->{$method}($url, [
            'exceptions'      => false,
            'timeout'         => '80',
            'connect_timeout' => '30',
            'body'            => $params
        ]);

        $response = $this->parseResponse($rawResponse->getBody());

        $success = $this->getSuccess($response);

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
            'message'         => $success ? 'Transacción aprobada' : $this->getTransactionMessage($this->array_get($response, 'code_auth'), $this->array_get($response, 'message')),
            'test'            => false, //array_key_exists('livemode', $response) ? $response["livemode"] : false,
            'authorization'   => $this->array_get($response, 'code_auth', false),
            'status'          => $success ? $this->array_get($response, 'message', true) : new Status('failed'),
            'reference'       => $this->array_get($response, 'status', false),
            'code'            => $this->array_get($response, 'code_auth', false),
        ]);
    }

    /**
     * @param $transaction
     *
     * @return bool
     */
    protected function getSuccess($transaction)
    {
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
     * @param $body
     *
     * @return array
     */
    protected function parseResponse($body)
    {
        parse_str($body, $output);

        return $output;
    }

    /**
     * @return string
     */
    protected function getRequestUrl()
    {
        return $this->liveEndpoint;
    }
}
