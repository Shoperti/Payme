<?php

namespace Dinkbit\Payme\Contracts;

interface Factory
{
    /**
     * Get a Gateway implementation.
     *
     * @param string $driver
     *
     * @return \Dinkbit\Payme\Contracts\Gateway
     */
    public function driver($driver = null);
}
