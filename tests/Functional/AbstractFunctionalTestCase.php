<?php

namespace Shoperti\Tests\PayMe\Functional;

abstract class AbstractFunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    protected $credentials;

    public function setUp()
    {
        $this->credentials = require(__DIR__.'/stubs/credentials.php');
    }
}
