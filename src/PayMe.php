<?php

namespace Shoperti\PayMe;

use BadMethodCallException;
use InvalidArgumentException;
use Shoperti\PayMe\Support\Helper;

/**
 * This is the PayMe class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
class PayMe
{
    /**
     * The package version.
     *
     * @var string
     */
    const VERSION = '2.0.0';

    /**
     * The current driver name.
     *
     * @return string
     */
    protected $driver;

    /**
     * The current driver config.
     *
     * @return string[]
     */
    protected $config;

    /**
     * The current instantiated gateway.
     *
     * @return \Shoperti\PayMe\Contracts\GatewayInterface
     */
    protected $gateway;

    /**
     * Create a new PayMe instance.
     *
     * @param string[] $config
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function __construct($config)
    {
        if (!isset($config['driver'])) {
            throw new InvalidArgumentException('A gateway must be specified.');
        }

        $this->config = $config;

        $gateway = Helper::className($this->getDriver());

        $class = "\\Shoperti\\PayMe\\Gateways\\{$gateway}\\{$gateway}Gateway";

        if (!class_exists($class)) {
            throw new InvalidArgumentException('Unsupported gateway ['.$this->getDriver().'].');
        }

        $this->gateway = new $class($config);
    }

    /**
     * Create a new PayMe instance.
     *
     * @param string[] $config
     *
     * @throws \InvalidArgumentException
     *
     * @return \Shoperti\PayMe\PayMe
     */
    public static function make($config)
    {
        return new static($config);
    }

    /**
     * Get the current package version.
     *
     * @return string
     */
    public static function getVersion()
    {
        return self::VERSION;
    }

    /**
     * Get the current gateway.
     *
     * @return \Shoperti\PayMe\Contracts\GatewayInterface
     */
    public function getGateway()
    {
        return $this->gateway;
    }

    /**
     * Get the current config array.
     *
     * @return string[]
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set the current config array.
     *
     * @param string[]
     *
     * @return $this
     */
    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Get the driver name.
     *
     * @return string
     */
    public function getDriver()
    {
        return isset($this->config['driver']) ? $this->config['driver'] : null;
    }

    /**
     * Dynamically handle missing methods.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return \Shoperti\PayMe\Contracts\ApiInterface
     */
    public function __call($method, array $parameters = [])
    {
        return $this->getApiInstance($method);
    }

    /**
     * Return the Api class instance for the given method.
     *
     * @param string $method
     *
     * @throws \BadMethodCallException
     *
     * @return \Shoperti\PayMe\Contracts\ApiInterface
     */
    protected function getApiInstance($method)
    {
        $gateway = Helper::className($this->getDriver());

        $class = "\\Shoperti\\PayMe\\Gateways\\{$gateway}\\".Helper::className($method);

        if (!class_exists($class)) {
            throw new BadMethodCallException("Undefined method [{$method}] called.");
        }

        return new $class($this->gateway);
    }
}
