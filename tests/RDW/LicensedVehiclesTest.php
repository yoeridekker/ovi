<?php

namespace Ovi\Tests\RDW;

use Ovi\RDW\LicensedVehicles;
use PHPUnit\Framework\TestCase;

// Named stub for enrichDataWith flow
class DummyEndpointForEnrich
{
    public function setQueryArgs(array $params) { return $this; }
    public function getRequestUrl() { return $this; }
    public function doRequest() { return $this; }
    public function getBody() { return [['foo' => 'bar']]; }
    public function getIdentifier(): string { return 'dummyendpoint'; }
}

// Named stub for get() helper test
class DummyEndpointForGet
{
    public function setQueryArgs(array $params) { return $this; }
    public function getRequestUrl() { return $this; }
    public function doRequest() { return $this; }
    public function enrichData() { return $this; }
    public function getBody() { return [ ['id' => 1], ['id' => 2] ]; }
}

class LicensedVehiclesTest extends TestCase
{
    public function testMapFieldsRecursivelyAddsMissingKeys()
    {
        $lv = new LicensedVehicles();
        // Start with empty fields registry
        $lv->fields = [];
        // Simulate a nested response structure
        $lv->response = [
            [
                'kenteken' => 'ab-12-cd',
                'nested' => [
                    'inner_key' => 123,
                    'deep' => [
                        'far' => true,
                    ],
                ],
            ],
        ];

        // Act
        $lv->mapFieldsRecursively($lv->response);

        // Assert top-level key discovered
        $this->assertArrayHasKey('kenteken', $lv->fields);
        $this->assertSame(['label' => '', 'format' => ''], $lv->fields['nested']);
        // Assert nested keys discovered as well
        $this->assertSame(['label' => '', 'format' => ''], $lv->fields['inner_key']);
        $this->assertSame(['label' => '', 'format' => ''], $lv->fields['deep']);
        $this->assertSame(['label' => '', 'format' => ''], $lv->fields['far']);
    }

    public function testFormatDataUsesLabelsAndFormatters()
    {
        $lv = new LicensedVehicles();
        // Prepare fields meta to control label and format
        $lv->fields = [
            'kenteken' => ['label' => 'Kenteken', 'format' => 'sanitizeString'],
            'number'   => ['label' => 'Number',   'format' => 'sanitizeInt'],
            // unknown will fallback to default label (field name) and no formatting
        ];

        $lv->response = [
            [
                'kenteken' => 'ab-12-cd',
                'number'   => '42',
                'unknown'  => 'x',
            ],
        ];

        $lv->formatData();

        $item = $lv->response[0];
        $this->assertSame([
            'value' => 'ab-12-cd',
            'name'  => 'kenteken',
            'label' => 'Kenteken',
        ], $item['kenteken']);

        $this->assertSame([
            'value' => 42,
            'name'  => 'number',
            'label' => 'Number',
        ], $item['number']);

        $this->assertSame([
            'value' => 'x',
            'name'  => 'unknown',
            'label' => 'unknown',
        ], $item['unknown']);
    }

    public function testEnrichDataWithAddsDataWhenSingleVehicleAndKeysPresent()
    {
        $lv = new LicensedVehicles();
        $lv->response = [
            [
                'k1' => 'v1',
                'k2' => 'v2',
            ],
        ];

        // Use a named stub that LicensedVehicles can instantiate
        $class = DummyEndpointForEnrich::class;
        $lv->enrichDataWith($class, ['k1', 'k2']);

        $this->assertArrayHasKey('dummyendpoint', $lv->response[0]);
        $this->assertSame([
            ['foo' => 'bar']
        ], $lv->response[0]['dummyendpoint']);
    }

    public function testGetReturnsFirstItemWhenMultipleFalse()
    {
        $lv = new LicensedVehicles();
        $endpoint = new DummyEndpointForGet();
        $result = $lv->get($endpoint, ['irrelevant' => 'x'], false);
        $this->assertSame(['id' => 1], $result);
    }
}
