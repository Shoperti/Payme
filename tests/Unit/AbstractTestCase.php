<?php

namespace Shoperti\Tests\PayMe\Unit;

abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * The gateways credentials.
     *
     * @var array
     */
    protected $credentials;

    public function setUp()
    {
        $this->credentials = require dirname(__DIR__).'/stubs/credentials.php';
    }
}
