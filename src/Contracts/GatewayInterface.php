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
     * Create a new gateway with the specified configuration.
     *
     * @param string[] $config
     *
     * @return void
     */
    public function __construct(array $config);

    /**
     * Set the current config array.
     *
     * @param string[]
     *
     * @return $this
     */
    public function getConfig();

    /**
     * Set the current config array.
     *
     * @param string[]
     *
     * @return $this
     */
    public function setConfig($config);

    /**
     * Commit a HTTP request.
     *
     * @param string   $method
     * @param string   $url
     * @param string[] $params
     * @param string[] $options
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function commit($method, $url, $params = [], $options = []);

    /**
     * Respond with an array of responses or a single response.
     *
     * @param array $response
     * @param mixed $more
     *
     * @return array|\Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function respond($response, $more = null);

    /**
     * Map an HTTP response to transaction object.
     *
     * @param bool  $success
     * @param array $response
     *
     * @return \Shoperti\PayMe\Contracts\ResponseInterface
     */
    public function mapResponse($success, $response);

    /**
     * Build request url from string.
     *
     * @param string $endpoint
     *
     * @return string
     */
    public function buildUrlFromString($endpoint);

    /**
     * Get gateway display name.
     *
     * @return string
     */
    public function getDisplayName();

    /**
     * Accept the amount of money in base unit and returns cants or base unit.
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
     * Format an amount to the current currency.
     *
     * @param int|string $amount
     *
     * @return string
     */
    public function formatCurrency($amount);
}
