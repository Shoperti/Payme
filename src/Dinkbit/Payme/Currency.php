<?php 

namespace Dinkbit\Payme;

class Currency
{
    /**
     * @var
     */
    private $code;

    /**
     * @var
     */
    private $numeric;

    /**
     * @var
     */
    private $decimals;

    /**
     * Create a new Currency object.
     *
     * @param $code
     * @param $numeric
     * @param $decimals
     */
    private function __construct($code, $numeric, $decimals)
    {
        $this->code = $code;
        $this->numeric = $numeric;
        $this->decimals = $decimals;
    }

    /**
     * Get the three letter code for the currency.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Get the numeric code for this currency.
     *
     * @return string
     */
    public function getNumeric()
    {
        return $this->numeric;
    }

    /**
     * Get the number of decimal places for this currency.
     *
     * @return int
     */
    public function getDecimals()
    {
        return $this->decimals;
    }

    /**
     * Find a specific currency.
     *
     * @param string $code The three letter currency code
     *
     * @return mixed A Currency object, or null if no currency was found
     */
    public static function find($code)
    {
        $code = strtoupper($code);
        $currencies = static::all();

        if (isset($currencies[$code])) {
            return new static($code, $currencies[$code]['numeric'], $currencies[$code]['decimals']);
        }
    }

    /**
     * Get an array of all supported currencies.
     *
     * @return array
     */
    public static function all()
    {
        return [
            'ARS' => ['numeric' => '032', 'decimals' => 2],
            'AUD' => ['numeric' => '036', 'decimals' => 2],
            'BOB' => ['numeric' => '068', 'decimals' => 2],
            'BRL' => ['numeric' => '986', 'decimals' => 2],
            'BTC' => ['numeric' => null, 'decimals' => 8],
            'CAD' => ['numeric' => '124', 'decimals' => 2],
            'CHF' => ['numeric' => '756', 'decimals' => 2],
            'CLP' => ['numeric' => '152', 'decimals' => 0],
            'CNY' => ['numeric' => '156', 'decimals' => 2],
            'COP' => ['numeric' => '170', 'decimals' => 2],
            'CRC' => ['numeric' => '188', 'decimals' => 2],
            'CZK' => ['numeric' => '203', 'decimals' => 2],
            'DKK' => ['numeric' => '208', 'decimals' => 2],
            'DOP' => ['numeric' => '214', 'decimals' => 2],
            'EUR' => ['numeric' => '978', 'decimals' => 2],
            'FJD' => ['numeric' => '242', 'decimals' => 2],
            'GBP' => ['numeric' => '826', 'decimals' => 2],
            'GTQ' => ['numeric' => '320', 'decimals' => 2],
            'HKD' => ['numeric' => '344', 'decimals' => 2],
            'HUF' => ['numeric' => '348', 'decimals' => 2],
            'ILS' => ['numeric' => '376', 'decimals' => 2],
            'INR' => ['numeric' => '356', 'decimals' => 2],
            'JPY' => ['numeric' => '392', 'decimals' => 0],
            'KRW' => ['numeric' => '410', 'decimals' => 0],
            'LAK' => ['numeric' => '418', 'decimals' => 0],
            'MXN' => ['numeric' => '484', 'decimals' => 2],
            'MYR' => ['numeric' => '458', 'decimals' => 2],
            'NOK' => ['numeric' => '578', 'decimals' => 2],
            'NZD' => ['numeric' => '554', 'decimals' => 2],
            'PEN' => ['numeric' => '604', 'decimals' => 2],
            'PGK' => ['numeric' => '598', 'decimals' => 2],
            'PHP' => ['numeric' => '608', 'decimals' => 2],
            'PLN' => ['numeric' => '985', 'decimals' => 2],
            'PYG' => ['numeric' => '600', 'decimals' => 0],
            'SBD' => ['numeric' => '090', 'decimals' => 2],
            'SEK' => ['numeric' => '752', 'decimals' => 2],
            'SGD' => ['numeric' => '702', 'decimals' => 2],
            'THB' => ['numeric' => '764', 'decimals' => 2],
            'TOP' => ['numeric' => '776', 'decimals' => 2],
            'TRY' => ['numeric' => '949', 'decimals' => 2],
            'TWD' => ['numeric' => '901', 'decimals' => 2],
            'USD' => ['numeric' => '840', 'decimals' => 2],
            'UYU' => ['numeric' => '858', 'decimals' => 2],
            'VEF' => ['numeric' => '937', 'decimals' => 2],
            'VND' => ['numeric' => '704', 'decimals' => 0],
            'VUV' => ['numeric' => '548', 'decimals' => 0],
            'WST' => ['numeric' => '882', 'decimals' => 2],
            'ZAR' => ['numeric' => '710', 'decimals' => 2],
        ];
    }
}
