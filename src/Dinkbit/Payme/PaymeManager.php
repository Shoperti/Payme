<?php  namespace Dinkbit\Payme;

use Illuminate\Support\Manager;

class PaymeManager extends Manager implements Contracts\Factory {

	/**
	 * Get a driver instance.
	 *
	 * @param  string  $driver
	 * @return mixed
	 */
	public function with($driver)
	{
		return $this->driver($driver);
	}

	/**
	 * Create an instance of the specified driver.
	 *
	 * @return Gateways\Banwire
	 */
	protected function createBanwireDriver()
	{
		$config = $this->app['config']['services.banwire'];

		return new \Dinkbit\Payme\Gateways\Banwire($config);
	}

	/**
	 * Create an instance of the specified driver.
	 *
	 * @return Gateways\Conekta
	 */
	protected function createConektaDriver()
	{
		$config = $this->app['config']['services.conekta'];

		return new \Dinkbit\Payme\Gateways\Conekta($config);
	}

	/**
	 * Create an instance of the specified driver.
	 *
	 * @return Gateways\ConektaBank
	 */
	protected function createConektaBankDriver()
	{
		$config = $this->app['config']['services.conekta'];

		return new \Dinkbit\Payme\Gateways\ConektaBank($config);
	}

	/**
	 * Create an instance of the specified driver.
	 *
	 * @return Gateways\ConektaOxxo
	 */
	protected function createConektaOxxoDriver()
	{
		$config = $this->app['config']['services.conekta'];

		return new \Dinkbit\Payme\Gateways\ConektaOxxo($config);
	}

	/**
	 * Create an instance of the specified driver.
	 * 
	 * @return Gateways\PaypalStandard
	 */
	protected function createPaypalStandardDriver()
	{
		$config = $this->app['config']['services.paypal'];

		return new \Dinkbit\Payme\Gateways\PaypalStandard();
	}

	/**
	 * Get the default driver name.
	 *
	 * @return string
	 */
	public function getDefaultDriver()
	{
		throw new \InvalidArgumentException("No Payme driver was specified.");
	}
}
