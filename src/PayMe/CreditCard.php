<?php

namespace Dinkbit\PayMe;

use Dinkbit\PayMe\Exceptions\InvalidCreditCardException;

class CreditCard
{
    use CreditCardTrait;

    public function displayNumber()
    {
        return self::mask($this->number);
    }

    public function lastDigits()
    {
        return self::getLastDigits($this->number);
    }

    private static $CARD_COMPANIES = [
        'visa'               => '/^4\d{12}(\d{3})?$/',
        'master'             => '/^(5[1-5]\d{4}|677189)\d{10}$/',
        'discover'           => '/^(6011|65\d{2})\d{12}$/',
        'american_express'   => '/^3[47]\d{13}$/',
        'diners_club'        => '/^3(0[0-5]|[68]\d)\d{11}$/',
        'jcb'                => '/^35(28|29|[3-8]\d)\d{12}$/',
        'switch'             => '/^6759\d{12}(\d{2,3})?$/',
        'solo'               => '/^6767\d{12}(\d{2,3})?$/',
        'dankort'            => '/^5019\d{12}$/',
        'maestro'            => '/^(5[06-8]|6\d)\d{10,17}$/',
        'forbrugsforeningen' => '/^600722\d{10}$/',
        'laser'              => '/^(6304|6706|6771|6709)\d{8}(\d{4}|\d{6,7})?$/', ];

    /**
     * All known/supported card brands, and a regular expression to match them.
     *
     * The order of the card brands is important, as some of the regular expressions overlap.
     *
     * Note: The fact that this class knows about a particular card brand does not imply
     * that your gateway supports it.
     *
     * @return array
     *
     * @link https://github.com/Shopify/active_merchant/blob/master/lib/active_merchant/billing/credit_card_methods.rb
     */
    public function getSupportedBrands()
    {
        return [
            static::$BRAND_VISA               => '/^4\d{12}(\d{3})?$/',
            static::$BRAND_MASTERCARD         => '/^(5[1-5]\d{4}|677189)\d{10}$/',
            static::$BRAND_DISCOVER           => '/^(6011|65\d{2}|64[4-9]\d)\d{12}|(62\d{14})$/',
            static::$BRAND_AMEX               => '/^3[47]\d{13}$/',
            static::$BRAND_DINERS_CLUB        => '/^3(0[0-5]|[68]\d)\d{11}$/',
            static::$BRAND_JCB                => '/^35(28|29|[3-8]\d)\d{12}$/',
            static::$BRAND_SWITCH             => '/^6759\d{12}(\d{2,3})?$/',
            static::$BRAND_SOLO               => '/^6767\d{12}(\d{2,3})?$/',
            static::$BRAND_DANKORT            => '/^5019\d{12}$/',
            static::$BRAND_MAESTRO            => '/^(5[06-8]|6\d)\d{10,17}$/',
            static::$BRAND_FORBRUGSFORENINGEN => '/^600722\d{10}$/',
            static::$BRAND_LASER              => '/^(6304|6706|6709|6771(?!89))\d{8}(\d{4}|\d{6,7})?$/',
        ];
    }

    public function name()
    {
    }

    public function brand()
    {
    }

    public function firstDigits()
    {
    }

    public function lastFour()
    {
    }

    public function mask()
    {
    }

    /**
     * @param $value
     *
     * @return int|null
     */
    protected function cleanYear($value)
    {
        // normalize year to four digits
        if (null === $value || '' === $value) {
            $value = null;
        } else {
            $value = (int) gmdate('Y', gmmktime(0, 0, 0, 1, 1, (int) $value));
        }

        return $value;
    }

    /**
     * Split first name and last name from composed name.
     *
     * @param $name
     *
     * @return array
     */
    public function splitName($name)
    {
        $name = trim($name);

        $names = explode(' ', $name, 2);

        return [
            'first_name' => $names[0],
            'last_name'  => isset($names[1]) ? $names[1] : null
        ];
    }

    /**
     * Strip non-numeric characters.
     *
     * @param $value
     *
     * @return mixed
     */
    public function cleanNumber($value)
    {
        return preg_replace('/\D/', '', $value);
    }

    /**
     * Validate this credit card. If the card is invalid, InvalidCreditCardException is thrown.
     *
     * This method is called internally by gateways to avoid wasting time with an API call
     * when the credit card is clearly invalid.
     *
     * Generally if you want to validate the credit card yourself with custom error
     * messages, you should use your framework's validation library, not this method.
     */
    public function validate()
    {
        foreach (['number', 'exp_month', 'exp_year'] as $key) {
            if (! $this->getAttribute($key)) {
                throw new InvalidCreditCardException("The $key parameter is required");
            }
        }

        if ($this->getExpiryDate('Ym') < gmdate('Ym')) {
            throw new InvalidCreditCardException('Card has expired');
        }

        if (! $this->validateLuhn($this->getCardNumber())) {
            throw new InvalidCreditCardException('Card number is invalid');
        }
    }

    /**
     * @return null
     */
    public function getNumberLastFour()
    {
        return substr($this->getCardNumber(), -4, 4) ?: null;
    }

    /**
     * Returns a masked credit card number with only the last 4 chars visible.
     *
     * @param string $mask Character to use in place of numbers
     *
     * @return string
     */
    public function getNumberMasked($mask = 'x')
    {
        $maskLength = strlen($this->getCardNumber()) - 4;

        return str_repeat($mask, $maskLength).$this->getNumberLastFour();
    }

    /**
     * Credit Card Brand
     * Iterates through known/supported card brands to determine the brand of this card.
     *
     * @return string
     */
    public function matchCardBrand()
    {
        foreach ($this->getSupportedBrands() as $brand => $val) {
            if (preg_match($val, $this->getCardNumber())) {
                return $brand;
            }
        }
    }

    /**
     * Get the card expiry date, using the specified date format string.
     *
     * @param string $format
     *
     * @return string
     */
    public function getExpiryDate($format)
    {
        return gmdate($format, gmmktime(0, 0, 0, $this->getExpiryMonth(), 1, $this->getExpiryYear()));
    }

    /**
     * Get the card start date, using the specified date format string.
     *
     * @param string $format
     *
     * @return string
     */
    public function getStartDate($format)
    {
        return gmdate($format, gmmktime(0, 0, 0, $this->getStartMonth(), 1, $this->getStartYear()));
    }

    /**
     * Validate using Luhn algo.
     *
     * @param $number
     *
     * @return bool
     */
    protected function validateLuhn($number)
    {
        $str = '';

        foreach (array_reverse(str_split($number)) as $i => $c) {
            $str .= $i % 2 ? $c * 2 : $c;
        }

        return array_sum(str_split($str)) % 10 === 0;
    }
}
