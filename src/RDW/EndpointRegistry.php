<?php

namespace Ovi\RDW;

class EndpointRegistry
{
    private static $endpoints = [
        // Voertuigen
        'm9d7-ebf2' => ['class' => GekentekendVoertuigen::class, 'link' => ['kenteken']],
        '8ys7-d773' => ['class' => Brandstof::class, 'link' => ['kenteken']],
        'vezc-m2t6' => ['class' => Carrosserie::class, 'link' => ['kenteken']],
        'jhie-znh9' => ['class' => CarrosserieSpecificatie::class, 'link' => ['kenteken']],
        'kmfi-hrps' => ['class' => Voertuigklasse::class, 'link' => ['kenteken']],
        '3huj-srit' => ['class' => Assen::class, 'link' => ['kenteken']],
        '2ba7-embk' => ['class' => SubcategorieVoertuig::class, 'link' => ['kenteken']],
        '7ug8-2dtt' => ['class' => Bijzonderheden::class, 'link' => ['kenteken']],
        '3xwf-ince' => ['class' => Rupsbanden::class, 'link' => ['kenteken']],
        'jqs4-4kvw' => ['class' => TellerstandoordeelTrendToelichting::class, 'link' => ['code_toelichting_tellerstandoordeel']],
        // Terugroepacties
        'j9yg-7rg9' => ['class' => TerugroepActie::class, 'link' => ['referentiecode_rdw']],
        't49b-isb7' => ['class' => TerugroepActieStatus::class, 'link' => ['referentiecode_rdw']],
        'mu2x-mu5e' => ['class' => TerugroepVoertuigMerkType::class, 'link' => ['referentiecode_rdw']],
        '9ihi-jgpf' => ['class' => TerugroepActieRisico::class, 'link' => ['referentiecode_rdw']],
        'mh8w-8cup' => ['class' => TerugroepInformerenEigenaar::class, 'link' => ['referentiecode_rdw']],
        // Keuringen
        'sgfe-77wx' => ['class' => MeldingenKeuringsinstantie::class, 'link' => ['kenteken']],
        'a34c-vvps' => ['class' => GeconstateerdeGebreken::class, 'link' => ['kenteken']],
        'vkij-7mwc' => ['class' => Keuringen::class, 'link' => ['kenteken']],
        'hx2c-gt7k' => ['class' => Gebreken::class, 'link' => ['gebrek_identificatie']],
        'sghb-dzxx' => ['class' => ToegevoegdeObjecten::class, 'link' => ['kenteken']],
    ];

    public static function resolveFromUrl(string $url): ?array
    {
        if (preg_match('/resource\/([a-z0-9]{4}-[a-z0-9]{4})\.json/', $url, $matches)) {
            return self::$endpoints[$matches[1]] ?? null;
        }
        return null;
    }
}
