<?php namespace Dinkbit\Payme\Gateways;

use Dinkbit\Payme\Contracts\Gateway as GatewayInterface;
use Dinkbit\Payme\Transaction;

class Conekta extends AbstractGateway implements GatewayInterface {

	protected $liveEndpoint = 'https://api.conekta.io';
	protected $defaultCurrency = 'MXN';

	protected $apiVersion = "0.3.0";
	protected $locale = 'es';

	/**
	 * @param $config
	 */
	public function __construct($config)
	{
		$this->requires($config, ['private_key']);

		$config['version'] = $this->apiVersion;

		$this->config = $config;
	}

	/**
	 * {@inheritdoc}
	 */
	public function charge($amount, $payment, $options = array())
	{
		$params = [];

		$params = $this->addOrder($params, $amount, $options);
		$params = $this->addPaymentMethod($params, $payment, $options);

		return $this->commit('post', $this->buildUrlFromString('charges'), $params);
	}

	public function save()
	{
		$customer = $this->findOrCreate($this->customer->getBillableId());

		$response = $this->commit('post', $this->buildUrlFromString('customers/' . $customer . '/cards'), [
			'token' => $this->card->getCardToken()
		]);

		$card = $this->parseResponse($response->getBody());

		$this->card->setCardToken($card['id']);
		$this->card->setCardBrand($card['brand']);
		$this->card->setCardName($card['name']);
		$this->card->setExpiryMonth($card['exp_month']);
		$this->card->setExpiryYear($card['exp_year']);
		$this->card->setLastFour($card['last4']);

		return $this->card;
	}

	public function update()
	{
	}

	public function findOrCreate($id)
	{
		$customer = $this->getCustomer($id);

		if ($customer)
		{
			return $id;
		}

		return $this->createCustomer();
	}

	public function createCustomer()
	{
		$url = $this->buildUrlFromString('customers');

		$request = $this->commit('post', $url, [
			'name' => $this->customer->getName(),
			'email' => $this->customer->getEmail()
		]);

		return $this->parseId($request->getBody());
	}

	public function getCustomer($id)
	{
		if (! $id) return false;

		$customer_id = $this->parseId($this->request('get', $this->buildUrlFromString('customers' . '/' . $id))->getBody());

		if ($customer_id == $id)
		{
			return true;
		}

		return false;
	}

	protected function addOrder($params, $money, $options)
	{
		$params['description'] = $this->array_get($options, 'description', "Payme Purchase");
        $params['reference_id'] = $this->array_get($options, 'order_id');
        $params['currency'] = $this->array_get($options, 'currency', $this->getCurrency());
        $params['amount'] = $this->getAmountInteger($money);

		return $params;
	}

	protected function addPaymentMethod($params, $payment, $options)
	{
		if (is_string($payment))
		{
			$params['card'] = $payment;
		}
		else if ($payment instanceof \Dinkbit\Payme\CreditCard)
		{
			$params['card'] = [];
			$params['card']['name'] = $payment->name;
			$params['card']['cvc'] = $payment->cvc;
			$params['card']['number'] = $payment->number;
			$params['card']['exp_month'] = $payment->exp_month;
			$params['card']['exp_year'] = $payment->exp_year;
			$params['card'] = $this->addAddress($params['card'], $options);
		}

		return $params;
	}

	protected function addAddress($params, $options)
	{
		if ($address = $this->array_get($options, 'address') or $this->array_get($options, 'billing_address'))
		{
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

	protected function addCustomer($params, $creditcard, $options)
	{
		return $params['customer'] = array_key_exists('customer', $options) ? $options['customer'] : '';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function commit($method = 'post', $url, $params = [], $options = [])
	{
		$success = false;
		$rawResponse = $this->getHttpClient()->{$method}($url, [
			'exceptions' => false,
			'headers' => [
				'Accept' => "application/vnd.conekta-v{$this->config['version']}+json",
				'Accept-Language' => $this->locale,
				'Authorization' => 'Basic ' . base64_encode($this->config['private_key'] . ':' ),
				'RaiseHtmlError' => 'false'
			],
			'body' => $params
		]);

		if ($rawResponse->getStatusCode() == 200)
		{
			$response = $this->parseResponse($rawResponse->getBody());
			$success = ! ($this->array_get($response, 'object', 'error') == 'error');
		}
		else
		{
			$response = $this->responseError($rawResponse);
		}

		return $this->mapResponseToTransaction($success, $response);
	}

	/**
	 * {@inheritdoc}
	 */
	public function mapResponseToTransaction($success, $response)
	{
		return (new Transaction)->setRaw($response)->map([
			'isRedirect' 	=> false,
			'success'	 	=> $success,
			'message' 		=> $success ? 'Transacción aprovada' : $response['message_to_purchaser'],
			'test' 			=> array_key_exists('livemode', $response) ? $response["livemode"] : false,
			'authorization' => $success ? $response['id'] : $response['type'],
			'status'		=> $success ? $response['status'] : false,
			'reference' 	=> $success ? $response['id'] : false,
			'code' 			=> $success ? $response['payment_method']['auth_code'] : $response['code'],
		]);
	}

	/**
	 * @param $body
	 * @return array
	 */
	protected function parseResponse($body)
	{
		return json_decode($body, true);
	}

	/**
	 * @param $rawResponse
	 * @return array
	 */
	protected function responseError($rawResponse)
	{
		if ( ! $this->isJson($rawResponse->getBody()))
		{
			return $this->jsonError($rawResponse);
		}

		return $this->parseResponse($rawResponse->getBody());
	}

	/**
	 * @param $rawResponse
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
