<?php

namespace Shoperti\Tests\PayMe\Support;

use Shoperti\PayMe\Support\Arr;

class ArrTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function testArrGet()
    {
        $array = [
            'foo' => 'bar',
        ];

        $a1 = Arr::get($array, 'foo');
        $a2 = Arr::get($array, 'baz', 'default');

        $this->assertEquals('bar', $a1);
        $this->assertEquals('default', $a2);
    }

    /**
     * @test
     *
     * @expectedException InvalidArgumentException
     */
    public function it_validates_array_requires()
    {
        $array = [
            'foo' => 'bar',
        ];

        Arr::requires($array, ['baz']);
    }

    /** @test */
    public function testArrFilter()
    {
        $array = [
            'foo' => 'bar',
            'baz' => null,
        ];

        $a1 = Arr::filters($array);

        $this->assertEquals(['foo' => 'bar'], $a1);
    }
}
