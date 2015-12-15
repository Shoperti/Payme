<?php

namespace Shoperti\PayMe;

use InvalidArgumentException;
use Shoperti\PayMe\Contracts\FactoryInterface;
use Shoperti\PayMe\Support\Helper;

class PayMeFactory implements FactoryInterface
{
    /**
     * The current factory instances.
     *
     * @var \Shoperti\PayMe\Contracts\Factory[]
     */
    protected $factories = [];

    /**
     * Create a new gateway instance.
     *
     * @param string[] $config
     *
     * @throws \InvalidArgumentException
     *
     * @return \Shoperti\PayMe\Contracts\GatewayInterface
     */
    public function make(array $config)
    {
        if (!isset($config['driver'])) {
            throw new InvalidArgumentException('A gateway must be specified.');
        }

        return $this->factory($config);
    }

    /**
     * Get a factory instance by name.
     *
     * @param string $config
     *
     * @return \Shoperti\PayMe\Contracts\FactoryInterface
     */
    public function factory($config)
    {
        $name = $config['driver'];

        if (isset($this->factories[$name])) {
            return $this->factories[$name];
        }

        $gateway = Helper::className($name);
        $class = "Shoperti\\PayMe\\Gateways\\{$gateway}\\{$gateway}Gateway";

        if (class_exists($class)) {
            return $this->factories[$name] = new $class($config);
        }

        throw new InvalidArgumentException("Unsupported factory [$name].");
    }
}
