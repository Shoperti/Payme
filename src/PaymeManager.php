<?php

namespace Dinkbit\PayMe;

use Dinkbit\PayMe\Gateways\BanwireRecurrent;
use Dinkbit\PayMe\Gateways\Bogus;
use Dinkbit\PayMe\Gateways\Conekta;
use Dinkbit\PayMe\Gateways\ConektaBank;
use Dinkbit\PayMe\Gateways\ConektaOxxo;
use Dinkbit\PayMe\Gateways\PaypalExpress;
use Illuminate\Support\Manager;
use InvalidArgumentException;

class PaymeManager extends Manager implements Contracts\Factory
{
    /**
     * Get a driver instance.
     *
     * @param string $driver
     *
     * @return \Dinkbit\PayMe\Contracts\Gateway
     */
    public function with($driver)
    {
        return $this->driver($driver);
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return \Dinkbit\PayMe\Gateways\Bogus
     */
    protected function createBogusDriver()
    {
        $config = $this->app['config']['services.bogus'];

        return new Bogus($config);
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return \Dinkbit\PayMe\Gateways\BanwireRecurrent
     */
    protected function createBanwireRecurrentDriver()
    {
        $config = $this->app['config']['services.banwire'];

        return new BanwireRecurrent($config);
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return \Dinkbit\PayMe\Gateways\Conekta
     */
    protected function createConektaDriver()
    {
        $config = $this->app['config']['services.conekta'];

        return new Conekta($config);
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return \Dinkbit\PayMe\Gateways\ConektaBank
     */
    protected function createConektaBankDriver()
    {
        $config = $this->app['config']['services.conekta'];

        return new ConektaBank($config);
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return \Dinkbit\PayMe\Gateways\ConektaOxxo
     */
    protected function createConektaOxxoDriver()
    {
        $config = $this->app['config']['services.conekta'];

        return new ConektaOxxo($config);
    }

    /**
     * Create an instance of the specified driver.
     *
     * @return \Dinkbit\PayMe\Gateways\PaypalExpress
     */
    protected function createPaypalExpressDriver()
    {
        $config = $this->app['config']['services.paypal'];

        return new PaypalExpress($config);
    }

    /**
     * Get the default driver name.
     *
     * @throws \InvalidArgumentException
     */
    public function getDefaultDriver()
    {
        throw new InvalidArgumentException("No PayMe driver was specified.");
    }
}
