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
     * Inject the Gateway for to use on an Api instance.
     *
     * @param \Shoperti\PayMe\Contracts\GatewayInterface
     *
     * @return void
     */
    public function __construct($gateway)
    {
        $this->gateway = $gateway;
    }
}
