<?php

namespace Shoperti\PayMe\Gateways\Bogus;

use Shoperti\PayMe\Gateways\AbstractApi;
use Shoperti\PayMe\Contracts\ChargeInterface;

/**
 * This is the bogus charges class.
 *
 * @author joseph.cohen@dinkbit.com
 */
class Charges extends AbstractApi implements ChargeInterface
{
    /**
     * Charge the credit card.
     *
     * @param int      $amount
     * @param mixed    $payment
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\ResponseInterface
     */
    public function create($amount, $payment, $options = [])
    {
        $params = [];

        $params['transaction'] = 'fail';

        if ($payment === 'success') {
            $params['transaction'] = 'success';
        }

        return $this->gateway->commit('post', 'charges', $params);
    }
}
