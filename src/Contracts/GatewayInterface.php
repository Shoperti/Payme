<?php

namespace Shoperti\PayMe\Contracts;

/**
 * This is the gateway interface.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
interface GatewayInterface
{
    /**
     * Sets the current config array.
     *
     * @param string[]
     *
     * @return $this
     */
    public function getConfig();

    /**
     * Sets the current config array.
     *
     * @param string[]
     *
     * @return $this
     */
    public function setConfig($config);

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
     * Get the amount.
     *
     * @param int|string $amount
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function getAmount($amount);

    /**
     * Get the amount converted to integer.
     *
     * @param int|string $amount
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
     * @param int|string $amount
     *
     * @return string
     */
    public function formatCurrency($amount);
}
