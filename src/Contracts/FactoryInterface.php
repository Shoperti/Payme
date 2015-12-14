<?php

namespace Shoperti\PayMe\Contracts;

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
