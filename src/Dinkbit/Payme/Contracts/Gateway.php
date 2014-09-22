<?php  namespace Dinkbit\Payme\Contracts;

interface Gateway {

	public function charge($amount, $reference, $options = array());

	public function save();

	public function delete();

	public function update();

	public function refund();

	public function subscribe();

}
