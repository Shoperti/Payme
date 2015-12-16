<?php

namespace Shoperti\PayMe\Contracts;

/**
 * This is the factory interface.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
interface FactoryInterface
{
    /**
     * Create a new gateway instance..
     *
     * @param string[] $driver
     *
     * @return \Shoperti\PayMe\Contracts\Gateway
     */
    public function make(array $config);
}
