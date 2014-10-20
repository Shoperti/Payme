<?php namespace Dinkbit\Payme\Contracts;

interface Charge {

	/**
	 * Charge the credit card.
	 *
	 * @param $amount
	 * @param $payment
	 * @param array $options
	 * @return
	 */
	public function charge($amount, $payment, $options = []);

}
