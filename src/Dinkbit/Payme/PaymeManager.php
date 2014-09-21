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

	protected function createBanwireDriver()
	{
		return
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
