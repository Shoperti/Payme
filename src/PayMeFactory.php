<?php

namespace Shoperti\PayMe;

use InvalidArgumentException;
use Shoperti\PayMe\Contracts\FactoryInterface;

/**
 * This is the PayMe factory class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
class PayMeFactory implements FactoryInterface
{
    /**
     * The instantiated PayMe objects.
     *
     * @var \Shoperti\PayMe\PayMe[]
     */
    protected $instances = [];

    /**
     * Create a new gateway instance.
     *
     * @param string[] $config
     *
     * @throws \InvalidArgumentException
     *
     * @return \Shoperti\PayMe\PayMe
     */
    public function make(array $config)
    {
        if (!isset($config['driver'])) {
            throw new InvalidArgumentException('A gateway must be specified.');
        }

        return $this->resolve($config);
    }

    /**
     * Obtain or generate a PayMe instance with the specified configuration.
     *
     * @param string[] $config
     *
     * @throws \InvalidArgumentException
     *
     * @return \Shoperti\PayMe\PayMe
     */
    protected function resolve($config)
    {
        $name = $config['driver'];

        if (isset($this->instances[$name]) && $this->instances[$name]->getConfig() === $config) {
            return $this->instances[$name];
        }

        return $this->instances[$name] = PayMe::make($config);
    }
}
