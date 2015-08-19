<?php

namespace Shoperti\PayMe\Gateways;

use Shoperti\PayMe\Currency;
use InvalidArgumentException;

abstract class AbstractGateway
{
    /**
     * Configuration options.
     *
     * @var string[]
     */
    protected $config;

    /**
     * Inject the configuration for a Gateway.
     *
     * @param $config
     */
    abstract public function __construct($config);

    /**
     * Commit a HTTP request.
     *
     * @param string   $method
     * @param string   $url
     * @param string[] $params
     * @param string[] $options
     *
     * @return mixed
     */
    abstract protected function commit($method = 'post', $url, $params = [], $options = []);

    /**
     * Map HTTP response to transaction object.
     *
     * @param bool  $success
     * @param array $response
     *
     * @return \Shoperti\PayMe\Transaction
     */
    abstract public function mapTransaction($success, $response);

    /**
     * Get the gateway request url.
     *
     * @return mixed
     */
    abstract protected function getRequestUrl();

    /**
     * Get a fresh instance of the Guzzle HTTP client.
     *
     * @return \GuzzleHttp\Client
     */
    protected function getHttpClient()
    {
        return new \GuzzleHttp\Client();
    }

    /**
     * Build request url from string.
     *
     * @param string $endpoint
     *
     * @return string
     */
    protected function buildUrlFromString($endpoint)
    {
        return $this->getRequestUrl().'/'.$endpoint;
    }

    /**
     * Get gateway display name.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return property_exists($this, 'displayName') ? $this->displayName : '';
    }

    /**
     * Get gateway default currency.
     *
     * @return string
     */
    protected function getDefaultCurrency()
    {
        return property_exists($this, 'defaultCurrency') ? $this->defaultCurrency : '';
    }

    /**
     * Get gateway money format.
     *
     * @return string
     */
    protected function getMoneyFormat()
    {
        return property_exists($this, 'moneyFormat') ? $this->moneyFormat : '';
    }

    /**
     * Accepts the amount of money in base unit and returns cants or base unit
     * amount according to the @see $money_format propery.
     *
     * @param  $money
     *
     * @throws \InvalidArgumentException
     *
     * @return int|float
     */
    public function amount($money)
    {
        if (null === $money) {
            return;
        }

        if (is_string($money) or $money < 0) {
            throw new InvalidArgumentException('Money amount must be a positive number.');
        }

        if ($this->getMoneyFormat() == 'cents') {
            return number_format($money, 0, '', '');
        }

        return sprintf('%.2f', number_format($money, 2, '.', '') / 100);
    }

    /**
     * @param $amount
     *
     * @throws InvalidRequestException
     *
     * @return string
     */
    public function getAmount($amount)
    {
        if (!is_float($amount) &&
            $this->getCurrencyDecimalPlaces() > 0 &&
            false === strpos((string) $amount, '.')) {
            throw new InvalidRequestException(
                'Please specify amount as a string or float, '.
                'with decimal places (e.g. \'10.00\' to represent $10.00).'
            );
        }

        return $this->formatCurrency($amount);
    }

    /**
     * @param $amount
     *
     * @throws InvalidRequestException
     *
     * @return int
     */
    public function getAmountInteger($amount)
    {
        return (int) round($this->getAmount($amount) * $this->getCurrencyDecimalFactor());
    }

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->getDefaultCurrency();
    }

    /**
     * @return mixed
     */
    public function getCurrencyNumeric()
    {
        if ($currency = Currency::find($this->getCurrency())) {
            return $currency->getNumeric();
        }
    }

    /**
     * @return int
     */
    public function getCurrencyDecimalPlaces()
    {
        if ($currency = Currency::find($this->getCurrency())) {
            return $currency->getDecimals();
        }

        return 2;
    }

    /**
     * @return number
     */
    private function getCurrencyDecimalFactor()
    {
        return pow(10, $this->getCurrencyDecimalPlaces());
    }

    /**
     * @param $amount
     *
     * @return string
     */
    public function formatCurrency($amount)
    {
        return number_format(
            $amount,
            $this->getCurrencyDecimalPlaces(),
            '.',
            ''
        );
    }
}
