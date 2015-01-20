<?php

namespace Dinkbit\Payme;

use Illuminate\Support\Manager;

class PaymeManager extends Manager implements Contracts\Factory
{
    /**
     * Get a driver instance.
     *
     * @param string $driver
     *
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
    protected function createBanwireRecurrentDriver()
    {
        $config = $this->app['config']['services.banwire'];

        return new \Dinkbit\PayMe\Gateways\BanwireRecurrent($config);
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return Gateways\Conekta
     */
    protected function createConektaDriver()
    {
        $config = $this->app['config']['services.conekta'];

        return new \Dinkbit\PayMe\Gateways\Conekta($config);
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return Gateways\ConektaBank
     */
    protected function createConektaBankDriver()
    {
        $config = $this->app['config']['services.conekta'];

        return new \Dinkbit\PayMe\Gateways\ConektaBank($config);
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return Gateways\ConektaOxxo
     */
    protected function createConektaOxxoDriver()
    {
        $config = $this->app['config']['services.conekta'];

        return new \Dinkbit\PayMe\Gateways\ConektaOxxo($config);
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return Gateways\PaypalExpress
     */
    protected function createPaypalExpressDriver()
    {
        $config = $this->app['config']['services.paypal'];

        return new \Dinkbit\PayMe\Gateways\PaypalExpress($config);
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        throw new \InvalidArgumentException("No PayMe driver was specified.");
    }
}
