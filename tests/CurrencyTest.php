<?php

use Dinkbit\PayMe\Currency;

class CurrencyTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_find_currency_by_code()
    {
        $currency = Currency::find('MXN');

        $this->assertEquals($currency->getCode(), 'MXN');
        $this->assertEquals($currency->getDecimals(), 2);
        $this->assertEquals($currency->getNumeric(), '484');
    }

    /** @test */
    public function it_returns_null_if_not_found()
    {
        $currency = Currency::find('FOO');

        $this->assertNull($currency);
    }
}
