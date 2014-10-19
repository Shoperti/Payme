<?php  namespace Dinkbit\Payme\Contracts;

interface Gateway {

	/**
	 * Charge the credit card.
	 *
	 * @param $amount
	 * @param $payment
	 * @param array $options
	 * @return
	 */
	public function charge($amount, $payment, $options = array());

}
