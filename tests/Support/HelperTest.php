<?php

use Dinkbit\PayMe\Support\Helper;

class HelperTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_cleans_accents_from_string()
    {
        $string = 'Cómo tü te vá';

        $str = Helper::cleanAccents($string);

        $this->assertEquals('Como tu te va', $str);
    }
}
