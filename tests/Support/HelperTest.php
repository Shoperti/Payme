<?php

namespace Shoperti\Test\PayMe\Support;

use Shoperti\PayMe\Support\Helper;

class HelperTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_cleans_accents_from_string()
    {
        $string = 'Cómo tü te vá';

        $str = Helper::cleanAccents($string);

        $this->assertEquals('Como tu te va', $str);
    }

    /** @test */
    public function it_converts_string_to_camelcase()
    {
        $this->assertEquals('paymePHPPay', Helper::camelCase('payme_p_h_p_pay'));
        $this->assertEquals('paymePhpPay', Helper::camelCase('payme_php_pay'));
        $this->assertEquals('paymePhPPay', Helper::camelCase('payme-phP-pay'));
        $this->assertEquals('paymePhpPay', Helper::camelCase('payme  -_-  php   -_-   pay   '));
    }
}
