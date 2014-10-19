<?php namespace Dinkbit\Payme\Gateways;

use Dinkbit\Payme\Transaction;

class ConektaBank extends Conekta {

	/**
	 * {@inheritdoc}
	 */
	public function charge($amount, $payment, $options = array())
	{
		$params = [];

		$params['bank']['type'] = $payment;

		$params = $this->addOrder($params, $amount, $options);

		return $this->commit('post', $this->buildUrlFromString('charges'), $params);
	}

	/**
	 * {@inheritdoc}
	 */
	public function mapResponseToTransaction($success, $response)
	{
		return (new Transaction)->setRaw($response)->map([
			'isRedirect' 	=> false,
			'success'	 	=> $success,
			'message' 		=> $success ? $response['payment_method']['service_number'] : $response['message_to_purchaser'],
			'test' 			=> array_key_exists('livemode', $response) ? $response["livemode"] : false,
			'authorization' => $success ? $response['id'] : $response['type'],
			'status'		=> $success ? $response['status'] : false,
			'reference' 	=> $success ? $response['id'] : false,
			'code' 			=> $success ? $response['payment_method']['reference'] : $response['code'],
		]);
	}

}
