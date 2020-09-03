<?php

namespace Shoperti\PayMe\Gateways;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Shoperti\PayMe\Contracts\GatewayInterface;
use Shoperti\PayMe\Currency;

/**
 * This is the abstract gateway class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
abstract class AbstractGateway implements GatewayInterface
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
     * @param string[] $config
     *
     * @return void
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get the gateway request url.
     *
     * @return mixed
     */
    abstract protected function getRequestUrl();

    /**
     * Return the current config array.
     *
     * @return string[]
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set the current config array.
     *
     * @param string[]
     *
     * @return $this
     */
    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Get a fresh instance of the Guzzle HTTP client.
     *
     * @return \GuzzleHttp\Client
     */
    protected function getHttpClient()
    {
        $stack = HandlerStack::create();

        $stack->push(Middleware::retry(function ($retries, RequestInterface $request, ResponseInterface $response = null, TransferException $exception = null) {
            return $retries < 3 && ($exception instanceof ConnectException || ($response && $response->getStatusCode() >= 500));
        }, function ($retries) {
            return (int) pow(2, $retries) * 1000;
        }));

        return new GuzzleClient(['handler' => $stack]);
    }

    /**
     * Perform the request and return the response & the http code.
     *
     * @param string $method
     * @param string $url
     * @param array  $payload
     *
     * @return array [0 => the response string, 1 => the http code int, 2 => the raw response object]
     */
    protected function makeRequest($method, $url, $payload)
    {
        /** @var \GuzzleHttp\Message\Response|\GuzzleHttp\Psr7\Response $rawResponse */
        $rawResponse = $this->getHttpClient()->{$method}($url, $payload);

        return [(string) $rawResponse->getBody(), $rawResponse->getStatusCode(), $rawResponse];
    }

    /**
     * Build request url from string.
     *
     * @param string $endpoint
     *
     * @return string
     */
    public function buildUrlFromString($endpoint)
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
     * Accept the amount of money in base unit and returns cents or base unit.
     *
     * @param int|float $money
     *
     * @throws \InvalidArgumentException
     *
     * @return string|null
     */
    public function amount($money)
    {
        if (null === $money) {
            return;
        }

        if (is_string($money) || $money < 0) {
            throw new InvalidArgumentException('Money amount must be a positive number.');
        }

        if ($this->getMoneyFormat() === 'cents') {
            return number_format($money, 0, '', '');
        }

        return sprintf('%.2f', number_format($money, 2, '.', '') / 100);
    }

    /**
     * Parse the amount to pay to the currency format.
     *
     * @param int|string $amount
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function getAmount($amount)
    {
        if (!is_float($amount) &&
            $this->getCurrencyDecimalPlaces() > 0 &&
            false === strpos((string) $amount, '.')) {
            throw new InvalidArgumentException(
                'Please specify amount as a string or float, '.
                'with decimal places (e.g. \'10.00\' to represent $10.00).'
            );
        }

        return $this->formatCurrency($amount);
    }

    /**
     * Get the amount converted to integer.
     *
     * @param int|string $amount
     *
     * @return int
     */
    public function getAmountInteger($amount)
    {
        return (int) round($this->getAmount($amount) * $this->getCurrencyDecimalFactor());
    }

    /**
     * Get the gateway currency.
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->getDefaultCurrency();
    }

    /**
     * Get the gateway currency numeric representation.
     *
     * @return int|null
     */
    public function getCurrencyNumeric()
    {
        $currency = Currency::find($this->getCurrency());

        return $currency ? $currency->getNumeric() : null;
    }

    /**
     * Get the currency decimal places.
     *
     * @return int
     */
    public function getCurrencyDecimalPlaces()
    {
        $currency = Currency::find($this->getCurrency());

        return $currency ? $currency->getDecimals() : 2;
    }

    /**
     * Get the currency decimal factor.
     *
     * @return number
     */
    private function getCurrencyDecimalFactor()
    {
        return pow(10, $this->getCurrencyDecimalPlaces());
    }

    /**
     * Format amount to the current currency.
     *
     * @param int|string $amount
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
