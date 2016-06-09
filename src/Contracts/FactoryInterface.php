<?php

namespace Shoperti\PayMe\Contracts;

/**
 * This is the factories interface.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
interface FactoryInterface
{
    /**
     * Create a new gateway instance.
     *
     * @param string[] $config
     *
     * @throws \InvalidArgumentException
     *
     * @return \Shoperti\PayMe\Contracts\GatewayInterface
     */
    public function make(array $config);
}
