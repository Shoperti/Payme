<?php

namespace Shoperti\PayMe\Contracts;

interface Factory
{
    /**
     * Get a Gateway implementation.
     *
     * @param string $driver
     *
     * @return \Shoperti\PayMe\Contracts\Gateway
     */
    public function driver($driver = null);
}
