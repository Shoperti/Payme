<?php

namespace Shoperti\PayMe\Contracts;

interface Factory
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
