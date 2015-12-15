<?php

namespace Shoperti\Tests\PayMe\Support;

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
        $this->assertEquals('PaymePHPPay', Helper::className('payme_p_h_p_pay'));
        $this->assertEquals('PaymePhpPay', Helper::className('payme_php_pay'));
        $this->assertEquals('PaymePhPPay', Helper::className('payme-phP-pay'));
        $this->assertEquals('PaymePhpPay', Helper::className('payme  -_-  php   -_-   pay   '));
    }

    /** @test */
    public function it_checks_if_a_string_starts_with()
    {
        $this->assertTrue(Helper::startsWith('php is awesome', 'php'));
        $this->assertTrue(Helper::startsWith('php_is_awesome', 'php'));
        $this->assertTrue(Helper::startsWith('php_is_awesome', ['ph', 'php']));
    }
}
