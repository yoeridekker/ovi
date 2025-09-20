<?php

namespace Ovi\Tests\Traits;

use Ovi\Traits\ApiTrait;
use PHPUnit\Framework\TestCase;

class DummyNoIdentifier
{
    use ApiTrait;
}

class DummyWithIdentifier
{
    use ApiTrait;
    public $identifier = 'custom';
}

class ApiTraitIdentifierTest extends TestCase
{
    public function testGetIdentifierDefaultsToLowercasedShortClassName()
    {
        $obj = new DummyNoIdentifier();
        // Expected: lowercase of the short class name
        $this->assertSame('dummynoidentifier', $obj->getIdentifier());
    }

    public function testGetIdentifierRespectsIdentifierProperty()
    {
        $obj = new DummyWithIdentifier();
        $this->assertSame('custom', $obj->getIdentifier());
    }
}
