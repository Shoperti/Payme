<?php

namespace Dinkbit\Payme\Gateways;

use Dinkbit\PayMe\Currency;

abstract class AbstractGateway
{
    /**
     * @var
     */
    protected $config;

    /**
     * @param $config
     */
    abstract public function __construct($config);

    /**
     * @param string $method
     * @param $url
     * @param array  $params
     * @param array  $options
     *
     * @return mixed
     */
    abstract protected function commit($method = 'post', $url, $params = [], $options = []);

    /**
     * @param $success
     * @param $response
     *
     * @return mixed
     */
    abstract public function mapResponseToTransaction($success, $response);

    /**
     * @return mixed
     */
    abstract protected function getRequestUrl();

    /**
     * @return string
     */
    public function displayName()
    {
        return $this->displayName;
    }

    /**
     * @return string
     */
    protected function getDefaultCurrency()
    {
        return $this->defaultCurrency;
    }

    /**
     * @return string
     */
    protected function getMoneyFormat()
    {
        return $this->moneyFormat;
    }

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
     * @param $endpoint
     *
     * @return string
     */
    protected function buildUrlFromString($endpoint)
    {
        return $this->getRequestUrl().'/'.$endpoint;
    }

    /**
     * Accepts the anount of money in base unit and returns cants or base unit
     * amount according to the @see $money_format propery.
     *
     *
     * @param  $money The amount of money in base unit, not in cents.
     *
     * @throws \InvalidArgumentException
     * @access public
     *
     * @return integer|float
     */
    public function amount($money)
    {
        if (null === $money) {
            return;
        }

        if (is_string($money) or $money < 0) {
            throw new \InvalidArgumentException('Money amount must be a positive number.');
        }

        if ($this->getMoneyFormat() == 'cents') {
            return number_format($money, 0, '', '');
        }

        return sprintf("%.2f", number_format($money, 2, '.', '') / 100);
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
        if (! is_float($amount) &&
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

    /**
     * Remove all accents from string.
     *
     * @var string
     *
     * @return mixed
     */
    protected function cleanAccents($string)
    {
        $notAllowed = ["á","é","í","ó","ú","Á","É","Í","Ó","Ú","ñ","À","Ã","Ì","Ò","Ù","Ã™","Ã ","Ã¨","Ã¬","Ã²","Ã¹","ç","Ç","Ã¢","ê","Ã®","Ã´","Ã»","Ã‚","ÃŠ","ÃŽ","Ã”","Ã›","ü","Ã¶","Ã–","Ã¯","Ã¤","«","Ò","Ã","Ã„","Ã‹"];
        $allowed = ["a","e","i","o","u","A","E","I","O","U","n","N","A","E","I","O","U","a","e","i","o","u","c","C","a","e","i","o","u","A","E","I","O","U","u","o","O","i","a","e","U","I","A","E"];
        $text = str_replace($notAllowed, $allowed, $string);

        return $text;
    }

    /**
     * @param $array
     * @param $key
     * @param null $default
     *
     * @return null
     */
    public function array_get($array, $key, $default = null)
    {
        return isset($array[$key]) ? $array[$key] : $default;
    }

    /**
     * @param $options
     * @param array $required
     *
     * @return bool
     */
    protected function requires($options, array $required = [])
    {
        foreach ($required as $key) {
            if (! array_key_exists(trim($key), $options)) {
                throw new \InvalidArgumentException("Missing required parameter: {$key}");
                break;

                return false;
            }
        }

        return true;
    }
}
