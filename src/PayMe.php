<?php

namespace Shoperti\PayMe;

use BadMethodCallException;
use InvalidArgumentException;
use Shoperti\PayMe\Support\Helper;

/**
 * This is the payme class.
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
     * The current factories instances.
     *
     * @return array
     */
    protected $factories = [];

    /**
     * The current driver name.
     *
     * @return string
     */
    protected $driver;

    /**
     * The current instatiated gateway.
     *
     * @return \Shoperti\PayMe\Contracts\Gateway
     */
    protected $gateway;

    /**
     * Create a new PayMe instance.
     *
     * @param string[] $config
     *
     * @return void
     */
    public function __construct($config)
    {
        if (!isset($config['driver'])) {
            throw new InvalidArgumentException('A gateway must be specified.');
        }

        $this->driver = $config['driver'];

        if (isset($this->factories[$this->driver])) {
            return $this->gateway = $this->factories[$this->driver];
        }

        $gateway = Helper::className($this->driver);
        $class = "\\Shoperti\\PayMe\\Gateways\\{$gateway}\\{$gateway}Gateway";

        if (class_exists($class)) {
            return $this->gateway = $this->factories[$this->driver] = new $class($config);
        }

        throw new InvalidArgumentException("Unsupported gateway [$this->driver].");
    }

    /**
     * Get the current gateway.
     *
     * @return \Shoperti\PayMe\Contracts\Gateway
     */
    public function getGateway()
    {
        return $this->gateway;
    }

    /**
     * Create a new PayMe instance.
     *
     * @param string[] $config
     *
     * @return \Shoperti\PayMe\Payme
     */
    public static function make($config)
    {
        return new static($config);
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
     * Returns the Api class instance for the given method.
     *
     * @param string $method
     *
     * @throws \BadMethodCallException
     *
     * @return \Shoperti\PayMe\Contracts\ApiInterface
     */
    protected function getApiInstance($method)
    {
        $gateway = Helper::className($this->driver);

        $class = "\\Shoperti\\PayMe\\Gateways\\{$gateway}\\".Helper::className($method);

        if (class_exists($class)) {
            return new $class($this->gateway);
        }

        throw new BadMethodCallException("Undefined method [{$method}] called.");
    }
}
