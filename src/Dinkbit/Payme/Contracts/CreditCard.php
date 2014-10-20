<?php namespace Dinkbit\Payme\Contracts;

interface CreditCard {

	public function getUserId();

	public function getCardToken();

	public function setCardToken($token);

	public function getCardName();

	public function setCardName($name);

	public function getCardNumber();

	public function getLastFour();

	public function setLastFour($last4);

	public function getCardBrand();

	public function setCardBrand($brand);

	public function getCardCvv();

	public function getExpiryMonth();

	public function setExpiryMonth($expMoth);

	public function getExpiryYear();

	public function setExpiryYear($expYear);

	public function getCardFirstName();

	public function getCardLastName();

	public function getBillingAddress1();

	public function getBillingAddress2();

	public function getBillingCity();

	public function getBillingState();

	public function getBillingCountry();

	public function getBillingPostCode();

	public function getBillingPhone();

}
