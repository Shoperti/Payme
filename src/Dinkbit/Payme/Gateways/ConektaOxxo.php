<?php namespace Dinkbit\Payme\Gateways;

class ConektaOxxo extends Conekta {

	/**
	 * {@inheritdoc}
	 */
	public function charge($amount, $reference, $options = array())
	{
		$options['description'] = 'Cargo';
		$options['cash'] = ['type' => 'oxxo'];

		$params = array_merge($options, [
			'amount' => $this->getAmountInteger($amount),
			'reference_id' => $reference,
			'currency' => $this->getCurrency()
		]);

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
			'message' 		=> $success ? $response['payment_method']['barcode'] : $response['message_to_purchaser'],
			'test' 			=> array_key_exists('livemode', $response) ? $response["livemode"] : false,
			'authorization' => $success ? $response['id'] : $response['type'],
			'status'		=> $success ? $response['status'] : false,
			'reference' 	=> $success ? $response['id'] : false,
			'code' 			=> $success ? $response['payment_method']['barcode_url'] : $response['code'],
		]);
	}

}
