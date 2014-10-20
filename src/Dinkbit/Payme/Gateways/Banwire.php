<?php namespace Dinkbit\Payme\Gateways;

use Dinkbit\Payme\Contracts\CreditCard;
use Dinkbit\Payme\Contracts\Gateway as GatewayInterface;

class Banwire extends AbstractGateway implements GatewayInterface {

	protected $liveEndpoint = 'https://banwiresecure.com/Recurrentes2013/recurrente';
	protected $defaultCurrency = 'MXN';

	/**
	 * @param $config
	 */
	public function __construct($config)
	{
		$this->requires($config, ['merchant', 'mail']);
	}

	/**
	 * {@inheritdoc}
	 */
	public function charge($amount, $payment, $options = array())
	{
		$url = $this->buildUrlFromString($this->recurrentEndPoint, 'ejecuta_pago_ondemand');

		$params['usr_banwire'] = 'desarrollo';
		$params['email'] = 'jruiz@banwire.com';
		$params['id_tarjeta'] = '1111';
		$params['card_name'] = 'Hugo A.';
		$params['card_lastname'] = 'Castrejon G';
		$params['cliente_id'] = '3';
		$params['monto'] = '10.00';
		$params['token'] = '8305ab68d4acf7dc650364d3f31a7318';

		$response = $this->getHttpClient()->post($url, [
			'headers' => ['Accept-Charset' => 'utf-8,*'],
			'timeout' => 30,
			'body' => $params
		]);

		return $this->respond($response);

		$name = $data['params']['name'];
		list($fname, $lname) = explode(' ', "$name ", 2);
		$params = array(
			'usr_banwire'	=> $data['config']['merchant'],
			'email' 		=> $data['config']['mail'],
			"id_tarjeta"	=> $data['params']['id'],
			"token"			=> $data['params']['token'],
			"cliente_id"	=> $data['params']['user_id'],
			"card_name"		=> $fname,
			"card_lastname"	=> $lname,
			"monto"			=> $data['params']['amount'],
		);
	}


	public function save()
	{
		$url = $this->buildUrlFromString($this->recurrentEndPoint, 'guarda_tarjeta');

		$params = [
			'usr_banwire'	=> $this->config['merchant'],
			'email'			=> $this->config['mail'],
			'cliente_id'	=> $this->card->getUserId(),
			'card_num'		=> $this->card->getCardNumber(),
			'card_type'		=> $this->card->getCardBrand(),
			'card_exp_yy'	=> substr($this->card->getExpiryYear(), -2),
			'card_exp_mm'	=> $this->card->getExpiryMonth(),
			'card_ccv2'		=> $this->card->getCardCvv(),
			'card_name'		=> $this->card->getCardFirstName(),
			'card_lastname' => $this->card->getCardLastName(),
			'calle'			=> $this->cleanAccents($this->card->getBillingAddress1() . ' ' . $this->card->getBillingAddress2()),
			'codigo_postal' => $this->card->getBillingPostCode(),
			'telefono'		=> $this->card->getBillingPhone()
		];

		var_dump($params);

		$response = $this->getHttpClient()->post($url, [
			'headers' => ['Accept-Charset' => 'utf-8,*'],
			'timeout' => 30,
			'body' => $params
		]);

		dd($this->parseResponse($response->getBody()));

		$card = $this->card->fill($this->parseResponse($response->getBody()));

		return $card;

		$name = $data['params']['name'];
		list($fname, $lname) = explode(' ', "$name ", 2);

		$params = array(
			'card_type'		=> $data['params']['type'],
			'card_num'		=> $data['params']['number'],
			'card_name'		=> $fname,
			'card_lastname' => $lname,
			'card_exp_mm'	=> $data['params']['month'],
			'card_exp_yy'	=> substr($data['params']['year'], -2),
			'card_ccv2'		=> $data['params']['ccv2'],
			'cliente_id'	=> $data['params']['user_id'],
			'calle'			=> $this->cleanAccents($data['params']['address']),
			'codigo_postal' => $data['params']['ccv2'],
			'telefono'		=> $data['params']['phone']
		);
	}

	public function update()
	{
//		$url = $this->buildUrlFromString($this->recurrentEndPoint, 'update_tarjeta');
//
//		$params = [
//
//		];
//
//		$response = $this->getHttpClient()->post($url, [
//			'headers' => ['Accept-Charset' => 'utf-8,*'],
//			'timeout' => 30,
//			'body' => $params
//		]);
//
//		return $this->respond($response);
//
//
//		// Sanitize data
//		$data = $this->data;
//
//		$card_name = $data['params']['old_name'];
//		list($cfname, $clname) = explode(' ', "$card_name ", 2);
//
//		$name = $data['params']['name'];
//		list($fname, $lname) = explode(' ', "$name ", 2);
//
//		$params = array(
//			'usr_banwire'		=> $data['config']['merchant'],
//			'email'				=> $data['config']['mail'],
//			"card_num"			=> $data['params']['number'],
//			"card_name_new" 	=> $fname,
//			"card_lastname_new" => $lname,
//			"card_type"			=> $data['params']['type'],
//			"card_exp_mm"		=> str_replace('0', '', $data['params']['month']),
//			"card_exp_yy"		=> $data['params']['year'],
//			"card_ccv2"			=> $data['params']['ccv2'],
//			"calle"				=> $this->cleanAccents($data['params']['address']),
//			"codigo_postal"		=> $data['params']['postcode'],
//			"telefono"			=> $data['params']['phone'],
//			"card_name"			=> $cfname,
//			"card_lastname"		=> $clname,
//			"token"				=> $data['params']['token'],
//			"cliente_id"		=> $data['params']['user_id'],
//			"id_tarjeta"		=> $data['params']['id']
//		);
	}


	protected function respond($response)
	{
		$output = $this->parseResponse($response->getBody());

		return $this->mapTransactionToObject($output);
	}

	/**
	 * @param $response
	 * @return mixed
	 */
	protected function parseResponse($response)
	{
		parse_str($response, $output);

		return $output;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function mapTransactionToObject(array $transaction)
	{
		return (new Transaction)->setRaw($transaction)->map([
			'isSuccessful' => $this->getTransactionStatus($transaction),
			'isRedirect' => false,
			'code' => isset($transaction['code_auth']) ? $transaction['code_auth'] : null,
			'message' => $this->getTransactionMessage($transaction['code'], $transaction['message']),
		]);
	}

	/**
	 * @param $transaction
	 * @return bool
	 */
	protected function getTransactionStatus($transaction)
	{
		if (isset($transaction['response']) and $transaction['response'] == 'ok')
		{
			return true;
		}

		foreach ($transaction as $key => $value)
		{
			if ($value == 'ok')
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param $code
	 * @param string $message
	 * @return string
	 */
	protected function getTransactionMessage($code, $message = 'Lo sentimos ocurrió un error.')
	{
		switch ($code)
		{
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

		$responseMessage .= " Si crees que esto es un error contáctanos a soporte@lasampleria.com";

		return $responseMessage;
	}

	/**
	 * @param $success
	 * @param $response
	 * @return mixed
	 */
	public function mapResponseToTransaction($success, $response)
	{
		// TODO: Implement mapResponseToTransaction() method.
	}

	/**
	 * @return mixed
	 */
	protected function getRequestUrl()
	{
		// TODO: Implement getRequestUrl() method.
	}
}
