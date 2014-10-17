<?php namespace Dinkbit\Payme\Gateways;

use Dinkbit\Payme\Contracts\Gateway as GatewayInterface;

class Conekta extends AbstractGateway implements GatewayInterface {

	protected $apiVersion = "0.3.0";
	protected $locale = 'es';

	/**
	 * {@inheritdoc}
	 */
	public function charge($amount, $reference, $options = array())
	{
		$options['description'] = 'Cargo';

		$params = array_merge($options, [
			'amount' => $this->getAmountInteger($amount),
			'reference_id' => $reference,
			'currency' => $this->getCurrency()
		]);

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

	protected function commit($method = 'post', $url, $params = [], $options = [])
	{
		$success = false;
		$rawResponse = $this->getHttpClient()->{$method}($url, [
			'exceptions' => false,
			'headers' => [
				'Accept' => "application/vnd.conekta-v{$this->apiVersion}+json",
				'Accept-Language' => $this->locale,
				'Authorization' => 'Basic ' . base64_encode($this->config['private_key'] . ':' )
			],
			'body' => $params
		]);

		if ($rawResponse->getStatusCode() == 200)
		{
			$response = $this->parseResponse($rawResponse->getBody());
			$success = ! array_key_exists('error', $response);
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

	protected function responseError($rawResponse)
	{
		if ( ! $this->isJson($rawResponse->getBody()))
		{
			return $this->jsonError($rawResponse);
		}

		return $this->parseResponse($rawResponse->getBody());
	}

	public function jsonError($rawResponse)
	{
		$msg = 'Respuesta no válida recibida de la API de Conekta. Por favor, póngase en contacto con contacto@conekta.com si sigues recibiendo este mensaje.';
		$msg .= " (Respuesta en bruto devuelto por el API {$rawResponse->getBody()})";

		return [
			'message_to_purchaser' => $msg
		];
	}

	protected function isJson($string)
	{
		json_decode($string);

		return (json_last_error() == JSON_ERROR_NONE);
	}

	protected function getDefaultCurrency()
	{
		return 'MXN';
	}

	protected function getRequestUrl()
	{
		return 'https://api.conekta.io';
	}
}
