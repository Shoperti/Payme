<?php

namespace Shoperti\PayMe\Gateways;

use Shoperti\PayMe\Contracts\ApiInterface;

/**
 * This is the abstract api class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
abstract class AbstractApi implements ApiInterface
{
    /**
     * The current Gateway instance.
     *
     * @var \Shoperti\PayMe\Contracts\GatewayInterface
     */
    protected $gateway;

    /**
     * Create a new API object with the specified configuration.
     *
     * @param \Shoperti\PayMe\Contracts\GatewayInterface $gateway
     *
     * @return void
     */
    public function __construct($gateway)
    {
        $this->gateway = $gateway;
    }
}
