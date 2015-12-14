<?php

namespace Shoperti\PayMe\Contracts;

interface GatewayInterface
{
    /**
     * Map HTTP response to transaction object.
     *
     * @param bool  $success
     * @param array $response
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function mapResponse($success, $response);

    /**
     * Get gateway display name.
     *
     * @return string
     */
    public function getDisplayName();

    /**
     * Accepts the amount of money in base unit and returns cants or base unit.
     *
     * @param int|float $money
     *
     * @throws \InvalidArgumentException
     *
     * @return int|float
     */
    public function amount($money);

    /**
     * @param $amount
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function getAmount($amount);

    /**
     * Get the amount converted to integer.
     *
     * @param $amount
     *
     * @return int
     */
    public function getAmountInteger($amount);

    /**
     * Get the gateway currency.
     *
     * @return string
     */
    public function getCurrency();

    /**
     * Get the gateway currency numeric representation.
     *
     * @return int
     */
    public function getCurrencyNumeric();

    /**
     * Get the currency decimal places.
     *
     * @return int
     */
    public function getCurrencyDecimalPlaces();

    /**
     * Format amount to the current currency.
     *
     * @param $amount
     *
     * @return string
     */
    public function formatCurrency($amount);
}
