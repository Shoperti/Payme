<?php namespace Dinkbit\Payme\Gateways;

use Dinkbit\Payme\Transaction;

class ConektaOxxo extends Conekta {

	/**
	 * {@inheritdoc}
	 */
	public function charge($amount, $payment, $options = [])
	{
		$params = [];

		$params['cash']['type'] = $payment;

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
			'message' 		=> $success ? $response['payment_method']['barcode_url'] : $response['message_to_purchaser'],
			'test' 			=> array_key_exists('livemode', $response) ? $response["livemode"] : false,
			'authorization' => $success ? $response['id'] : $response['type'],
			'status'		=> $success ? $response['status'] : false,
			'reference' 	=> $success ? $response['payment_method']['barcode_url'] : false,
			'code' 			=> $success ? $response['payment_method']['barcode'] : $response['code'],
		]);
	}

}
