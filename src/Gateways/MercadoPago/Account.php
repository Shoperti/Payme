<?php

namespace Shoperti\PayMe\Gateways\MercadoPago;

use Shoperti\PayMe\Contracts\AccountInterface;
use Shoperti\PayMe\Gateways\AbstractApi;

/**
 * This is the MercadoPago account class.
 *
 * @author Arturo Rodríguez <arturo.rodriguez@dinkbit.com>
 */
class Account extends AbstractApi implements AccountInterface
{
    /**
     * Get account info.
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function info()
    {
        return $this->gateway->commit('get', dirname($this->gateway->buildUrlFromString('')).'/users/me');
    }
}
