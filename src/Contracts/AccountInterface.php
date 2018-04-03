<?php

namespace Shoperti\PayMe\Contracts;

/**
 * This is the account interface.
 *
 * @author Arturo Rodríguez <arturo.rodriguez@dinkbit.com>
 */
interface AccountInterface
{
    /**
     * Get account info.
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function info();
}
