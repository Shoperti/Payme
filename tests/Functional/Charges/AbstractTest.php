<?php

namespace Shoperti\Tests\PayMe\Functional\Charges;

use Shoperti\Tests\PayMe\Functional\AbstractFunctionalTestCase;

abstract class AbstractTest extends AbstractFunctionalTestCase
{
    /**
     * @param mixed      $token
     * @param null|mixed $amount
     * @param mixed      $payload
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface $response
     */
    protected function successfulChargeRequest($token, $amount = null, $payload = [])
    {
        [$amount, $payload] = $this->fixOrderData($amount, $payload);

        $isRedirect = array_key_exists('isRedirect', $this->gatewayData)
            ? $this->gatewayData['isRedirect']
            : false;

        $charge = $this->chargeRequest($token, $amount, $payload);

        $this->assertSame($isRedirect, $charge->isRedirect());

        $this->assertSame(!$isRedirect, $charge->success());

        return $charge;
    }

    /**
     * This method automates a charge generation.
     *
     * if amount / payload params are null, they will be replaced by the order data stub.
     *
     * @param mixed      $token
     * @param null|mixed $amount
     * @param mixed      $payload
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface $response
     */
    protected function chargeRequest($token, $amount = null, $payload = [])
    {
        [$amount, $payload] = $this->fixOrderData($amount, $payload);

        return $this->getPayMe()->charges()->create($amount, $token, $payload);
    }

    private function fixOrderData($amount, $payload)
    {
        $order = !is_null($amount) || is_array($payload) ? $this->getOrderData($payload ?: []) : null;

        return[
            !is_null($amount) ? $amount : $order['total'],
            is_array($payload) ? $order['payload'] : [],
        ];
    }
}
