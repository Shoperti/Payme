<?php

namespace Shoperti\PayMe\Gateways\Bogus;

use Shoperti\PayMe\Contracts\ChargeInterface;
use Shoperti\PayMe\Gateways\AbstractApi;

/**
 * This is the bogus charges class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
class Charges extends AbstractApi implements ChargeInterface
{
    /**
     * Create a charge.
     *
     * @param int|float $amount
     * @param mixed     $payment
     * @param string[]  $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
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
