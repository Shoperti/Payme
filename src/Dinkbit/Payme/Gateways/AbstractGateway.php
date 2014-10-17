<?php namespace Dinkbit\Payme\Gateways;

use Dinkbit\Payme\Contracts\CreditCard;
use Dinkbit\Payme\Contracts\Customer;
use Dinkbit\Payme\Contracts\Gateway as GatewayInterface;
use Dinkbit\Payme\Currency;

abstract class AbstractGateway implements GatewayInterface {

	/**
	 * @var
	 */
	protected $config;

	/**
	 * @var
	 */
	protected $card;

	/**
	 * @var
	 */
	protected $customer;

	/**
	 * @param $config
	 */
	public function __construct($config)
	{
		$this->config = $config;
	}


	/**
	 * @param $success
	 * @param $response
	 * @return mixed
	 */
	abstract public function mapResponseToTransaction($success, $response);

	/**
	 * @return mixed
	 */
	abstract protected function getDefaultCurrency();

	/**
	 * @return mixed
	 */
	abstract protected function getRequestUrl();

	/**
	 * @param CreditCard $card
	 * @return $this
	 */
	public function setCard(CreditCard $card)
	{
		$this->card = $card;

		return $this;
	}

	/**
	 * @param $customer
	 * @return $this
	 */
	public function setCustomer(Customer $customer)
	{
		$this->customer = $customer;

		return $this;
	}

	/**
	 * Get a fresh instance of the Guzzle HTTP client.
	 *
	 * @return \GuzzleHttp\Client
	 */
	protected function getHttpClient()
	{
		return new \GuzzleHttp\Client;
	}

	/**
	 * @param $endpoint
	 * @return string
	 */
	protected function buildUrlFromString($endpoint)
	{
		return $this->getRequestUrl() . '/' . $endpoint;
	}

	/**
	 * @param $amount
	 * @return string
	 * @throws InvalidRequestException
	 */
	public function getAmount($amount)
	{
		if ( ! is_float($amount) &&
			$this->getCurrencyDecimalPlaces() > 0 &&
			false === strpos((string) $amount, '.')) {
			throw new InvalidRequestException(
				'Please specify amount as a string or float, ' .
				'with decimal places (e.g. \'10.00\' to represent $10.00).'
			);
		}

		return $this->formatCurrency($amount);
	}

	/**
	 * @param $amount
	 * @return int
	 * @throws InvalidRequestException
	 */
	public function getAmountInteger($amount)
	{
		return (int) round($this->getAmount($amount) * $this->getCurrencyDecimalFactor());
	}

	/**
	 * @return mixed
	 */
	public function getCurrency()
	{
		return $this->getDefaultCurrency();
	}

	/**
	 * @return mixed
	 */
	public function getCurrencyNumeric()
	{
		if ($currency = Currency::find($this->getCurrency())) {
			return $currency->getNumeric();
		}
	}

	/**
	 * @return int
	 */
	public function getCurrencyDecimalPlaces()
	{
		if ($currency = Currency::find($this->getCurrency())) {
			return $currency->getDecimals();
		}

		return 2;
	}

	/**
	 * @return number
	 */
	private function getCurrencyDecimalFactor()
	{
		return pow(10, $this->getCurrencyDecimalPlaces());
	}

	/**
	 * @param $amount
	 * @return string
	 */
	public function formatCurrency($amount)
	{
		return number_format(
			$amount,
			$this->getCurrencyDecimalPlaces(),
			'.',
			''
		);
	}

	/**
	 * Remove all accents from string
	 *
	 * @var string
	 * @return mixed
	 */
	protected function cleanAccents($string) {
		$notAllowed = array("á","é","í","ó","ú","Á","É","Í","Ó","Ú","ñ","À","Ã","Ì","Ò","Ù","Ã™","Ã ","Ã¨","Ã¬","Ã²","Ã¹","ç","Ç","Ã¢","ê","Ã®","Ã´","Ã»","Ã‚","ÃŠ","ÃŽ","Ã”","Ã›","ü","Ã¶","Ã–","Ã¯","Ã¤","«","Ò","Ã","Ã„","Ã‹");
		$allowed = array("a","e","i","o","u","A","E","I","O","U","n","N","A","E","I","O","U","a","e","i","o","u","c","C","a","e","i","o","u","A","E","I","O","U","u","o","O","i","a","e","U","I","A","E");
		$text = str_replace($notAllowed, $allowed ,$string);

		return $text;
	}

	/**
	 * @param $options
	 * @param array $required
	 */
	protected function requires($options, array $required = [])
	{
		foreach ($options as $key => $option)
		{
			if ( ! in_array($key, $required))
			{
				throw new \InvalidArgumentException("Missing required parameter: {$key}");
			}
		}
	}
}
