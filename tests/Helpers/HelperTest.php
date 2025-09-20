<?php

namespace Ovi\Tests\Helpers;

use Ovi\Helpers\Helper;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    public function testSetWithDotNotationCreatesNestedArrays()
    {
        $arr = [];
        Helper::set($arr, 'a.b.c', 123);
        $this->assertArrayHasKey('a', $arr);
        $this->assertArrayHasKey('b', $arr['a']);
        $this->assertSame(123, $arr['a']['b']['c']);
    }

    public function testSetWithNullKeyReplacesArray()
    {
        $arr = ['x' => 1];
        Helper::set($arr, null, ['y' => 2]);
        $this->assertSame(['y' => 2], $arr);
    }

    public function testGetWithExistingKeyReturnsValue()
    {
        $arr = ['foo' => 'bar'];
        $this->assertSame('bar', Helper::get($arr, 'foo'));
    }

    public function testGetWithDotNotationReturnsNestedValue()
    {
        $arr = ['a' => ['b' => ['c' => 'ok']]];
        $this->assertSame('ok', Helper::get($arr, 'a.b.c'));
    }

    public function testGetWithMissingKeyReturnsDefault()
    {
        $arr = [];
        $this->assertSame('default', Helper::get($arr, 'missing', 'default'));
    }

    public function testGetWithMissingKeyReturnsCallableDefaultResult()
    {
        $arr = [];
        $default = function () { return 'computed'; };
        $this->assertSame('computed', Helper::get($arr, 'missing', $default));
    }

    public function testGetWithNullKeyReturnsWholeArray()
    {
        $arr = ['a' => 1];
        $this->assertSame($arr, Helper::get($arr, null));
    }
}
