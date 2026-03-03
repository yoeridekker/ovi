<?php

namespace Ovi\Tests\RDW;

use Ovi\Vehicles;
use PHPUnit\Framework\TestCase;

/**
 * @group integration
 */
class VehiclesIntegrationTest extends TestCase
{
    public function testQueryKenteken62PTX5()
    {
        $vehicles = new Vehicles();
        $result = $vehicles->get_raw(['kenteken' => '62PTX5']);

        // Basic vehicle info
        $this->assertSame('62PTX5', $result['kenteken']);
        $this->assertSame('Personenauto', $result['voertuigsoort']);
        $this->assertSame('TOYOTA', $result['merk']);
        $this->assertSame('TOYOTA RAV4', $result['handelsbenaming']);
        $this->assertSame('GRIJS', $result['eerste_kleur']);
        $this->assertSame('5', $result['aantal_zitplaatsen']);
        $this->assertSame('4', $result['aantal_cilinders']);
        $this->assertSame('1998', $result['cilinderinhoud']);

        // api_* enriched data (resolved via EndpointRegistry)
        $this->assertArrayHasKey('gekentekende_voertuigen_brandstof', $result);
        $this->assertArrayHasKey('gekentekende_voertuigen_assen', $result);
        $this->assertArrayHasKey('gekentekende_voertuigen_carrosserie', $result);

        // api_* fields should be removed after enrichment
        $this->assertArrayNotHasKey('api_gekentekende_voertuigen_brandstof', $result);
        $this->assertArrayNotHasKey('api_gekentekende_voertuigen_assen', $result);
        $this->assertArrayNotHasKey('api_gekentekende_voertuigen_carrosserie', $result);

        // Kenteken-linked enrichment
        $this->assertArrayHasKey('keuringen', $result);
        $this->assertArrayHasKey('meldingenkeuringsinstantie', $result);
        $this->assertArrayHasKey('geconstateerdegebreken', $result);
        $this->assertArrayHasKey('terugroepactiestatus', $result);
    }
}
