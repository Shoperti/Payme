<?php

namespace Shoperti\PayMe;

use InvalidArgumentException;
use Shoperti\PayMe\Contracts\FactoryInterface;
use Shoperti\PayMe\Support\Helper;

/**
 * This is the payme factory class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
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

        return $this->factories[$name] = $this->get($name, $config);
    }

    /**
     * Attempt to get the gateway from the local cache.
     *
     * @param  string   $name
     * @param  string[] $config
     *
     * @return \Shoperti\PayMe\Contracts\Gateway
     *
     * @throws \InvalidArgumentException
     */
    protected function get($name, $config)
    {
        if (isset($this->factories[$name]) && $this->factories[$name]->getConfig() === $config) {
            return $this->factories[$name];
        }

        return PayMe::make($config);
    }
}
