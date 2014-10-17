<?php  namespace Dinkbit\Payme\Contracts;

interface Gateway {

	/**
	 * Charge the credit card.
	 *
	 * @param $amount
	 * @param $reference
	 * @param array $options
	 */
	public function charge($amount, $reference, $options = array());

}
