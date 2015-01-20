<?php

namespace Dinkbit\PayMe\Contracts;

interface Factory
{
    /**
     * Get a Gateway implementation.
     *
     * @param string $driver
     *
     * @return \Dinkbit\PayMe\Contracts\Gateway
     */
    public function driver($driver = null);
}
