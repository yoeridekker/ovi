# OVI - RDW Open Data API Client

PHP client for the Dutch RDW (Rijksdienst voor het Wegverkeer) Open Data API. Provides typed classes for all 20 datasets in the RDW catalog across three categories: Voertuigen, Terugroepacties, and Keuringen.

## Requirements

- PHP ^7.4 || ^8.0
- ext-json
- Guzzle ^7.0

## Installation

```bash
composer require yoeridekker/ovi
```

## Quick start

```php
use Ovi\Vehicles;

$ovi = new Vehicles();

// Full vehicle profile with all enrichments
$vehicle = $ovi->get(['kenteken' => '62PTX5']);

// Raw response (no field formatting)
$raw = $ovi->get_raw(['kenteken' => '62PTX5']);
```

The `Vehicles` facade queries the primary `Gekentekende voertuigen` dataset and automatically enriches the result with data from 13 related datasets (fuel, axles, body, inspections, recalls, etc.).

## Architecture

```
Ovi\Vehicles                         # Facade — simple get() / get_raw() API
  └── Ovi\RDW\GekentekendVoertuigen  # Primary endpoint (m9d7-ebf2)
        ├── ApiTrait                  # HTTP client, query building, api_* enrichment
        ├── SanitizationTrait         # Input sanitization (kenteken, int, string, date)
        └── EndpointRegistry          # Maps resource IDs → classes for api_* resolution
```

### Request chain

Every endpoint supports a fluent interface:

```php
$endpoint->setQueryArgs($params)  // Set query parameters (auto-sanitized)
    ->getRequestUrl()             // Build the request URL
    ->doRequest()                 // Execute HTTP GET via Guzzle
    ->enrichData()                // Resolve api_* links + fetch related datasets
    ->getBody();                  // Return decoded response array
```

## Datasets

### Voertuigen (10 datasets)

| Class | Resource ID | Description | Link field |
|-------|-------------|-------------|------------|
| `GekentekendVoertuigen` | m9d7-ebf2 | Licensed vehicles (primary) | kenteken |
| `Brandstof` | 8ys7-d773 | Fuel information | kenteken |
| `Carrosserie` | vezc-m2t6 | Body type | kenteken |
| `CarrosserieSpecificatie` | jhie-znh9 | Body specifications | kenteken |
| `Voertuigklasse` | kmfi-hrps | Vehicle class | kenteken |
| `Assen` | 3huj-srit | Axle information | kenteken |
| `SubcategorieVoertuig` | 2ba7-embk | Vehicle subcategory | kenteken |
| `Bijzonderheden` | 7ug8-2dtt | Special features | kenteken |
| `Rupsbanden` | 3xwf-ince | Tracks/caterpillar treads | kenteken |
| `TellerstandoordeelTrendToelichting` | jqs4-4kvw | Odometer trend explanation | code_toelichting_tellerstandoordeel |

### Terugroepacties (5 datasets)

| Class | Resource ID | Description | Link field |
|-------|-------------|-------------|------------|
| `TerugroepActie` | j9yg-7rg9 | Recall actions | referentiecode_rdw |
| `TerugroepActieStatus` | t49b-isb7 | Recall status | referentiecode_rdw |
| `TerugroepVoertuigMerkType` | mu2x-mu5e | Recall vehicle brand/type | referentiecode_rdw |
| `TerugroepActieRisico` | 9ihi-jgpf | Recall risk assessment | referentiecode_rdw |
| `TerugroepInformerenEigenaar` | mh8w-8cup | Owner notification status | referentiecode_rdw |

### Keuringen (5 datasets)

| Class | Resource ID | Description | Link field |
|-------|-------------|-------------|------------|
| `MeldingenKeuringsinstantie` | sgfe-77wx | Inspection authority reports | kenteken |
| `GeconstateerdeGebreken` | a34c-vvps | Detected defects | kenteken |
| `Keuringen` | vkij-7mwc | Inspections (APK) | kenteken |
| `Gebreken` | hx2c-gt7k | Defect descriptions (lookup) | gebrek_identificatie |
| `ToegevoegdeObjecten` | sghb-dzxx | Added objects | kenteken |

## Usage

### Full enrichment via the Vehicles facade

```php
use Ovi\Vehicles;

$ovi = new Vehicles();
$vehicle = $ovi->get_raw(['kenteken' => '62PTX5']);

// Result contains:
// - All scalar vehicle fields (merk, kleur, cilinderinhoud, etc.)
// - api_* resolved: brandstof, assen, carrosserie, carrosserie_specificatie, voertuigklasse
// - kenteken-linked: keuringen, meldingen, gebreken, terugroepactiestatus, etc.
```

The `get()` method returns formatted output where each field is structured as:

```php
[
    'value' => 'TOYOTA',
    'name'  => 'merk',
    'label' => 'Merk'
]
```

Use `get_raw()` for the plain key-value response from the API.

### Selective enrichment

Use `enrichDataWith()` to enrich with only the datasets you need — avoids the overhead of fetching all 13 related datasets:

```php
use Ovi\RDW\GekentekendVoertuigen;
use Ovi\RDW\Brandstof;
use Ovi\RDW\Keuringen;

$gv = new GekentekendVoertuigen();
$result = $gv->setQueryArgs(['kenteken' => '62PTX5'])
    ->getRequestUrl()
    ->doRequest()
    ->enrichDataWith(Brandstof::class, ['kenteken'])   // only fuel
    ->enrichDataWith(Keuringen::class, ['kenteken'])    // only inspections
    ->getBody(true);

// $result['brandstof'] => fuel data
// $result['keuringen'] => inspection data
// No other enrichments performed
```

The second argument specifies which fields from the vehicle record to pass as query parameters to the related endpoint.

### Using endpoint classes directly

Every dataset has its own class that can be used standalone:

```php
use Ovi\RDW\Brandstof;

$fuel = new Brandstof();
$data = $fuel->setQueryArgs(['kenteken' => '62PTX5'])
    ->getRequestUrl()
    ->doRequest()
    ->getBody();

// Returns: [['kenteken' => '62PTX5', 'brandstof_omschrijving' => 'Benzine', ...]]
```

```php
use Ovi\RDW\TerugroepActie;

$recall = new TerugroepActie();
$data = $recall->setQueryArgs(['referentiecode_rdw' => 'MGP150162'])
    ->getRequestUrl()
    ->doRequest()
    ->enrichData()  // resolves api_* links to status, risk, brand/type, owner notification
    ->getBody();
```

### Configuring Guzzle options

```php
use Ovi\Vehicles;

$ovi = new Vehicles();

// Set Guzzle options via dot-notation
$ovi->set_option(false, 'guzzle_options.verify');      // disable SSL verification
$ovi->set_option(60, 'guzzle_options.timeout');         // custom timeout (default: 30s)
$ovi->set_option('your-token', 'guzzle_options.headers.X-App-Token');

$result = $ovi->get(['kenteken' => 'AB12CD']);
```

## How enrichment works

### api_* enrichment (ApiTrait)

The RDW API embeds `api_*` fields in responses that link to related datasets. For example, `Gekentekende voertuigen` contains:

```
api_gekentekende_voertuigen_brandstof → https://opendata.rdw.nl/resource/8ys7-d773.json
api_gekentekende_voertuigen_assen     → https://opendata.rdw.nl/resource/3huj-srit.json
```

The `ApiTrait::enrichData()` method automatically:

1. Detects any field starting with `api_`
2. Resolves the URL to a class via `EndpointRegistry`
3. Looks up the linking fields (e.g., `kenteken`) from the registry
4. Extracts those values from the current record
5. Queries the related endpoint with proper filtering
6. Replaces the `api_*` field with the actual data (key stripped of `api_` prefix)

This is recursive: if the sub-endpoint also has `api_*` fields, they are resolved too. The dataset graph is a DAG (no circular links), so there is no recursion risk.

### Kenteken-linked enrichment (GekentekendVoertuigen)

Beyond `api_*` fields, `GekentekendVoertuigen::enrichData()` also fetches 8 additional datasets by `kenteken` that are not linked via `api_*` fields: subcategorie, bijzonderheden, rupsbanden, keuringen, meldingen, geconstateerde gebreken, terugroepactiestatus, and toegevoegde objecten.

This only runs for single-vehicle lookups (`count($response) === 1`) to avoid excessive HTTP calls.

### Dataset relationships

```
GekentekendVoertuigen (m9d7-ebf2)
  ├── api_* ──> Brandstof (8ys7-d773)
  ├── api_* ──> Voertuigklasse (kmfi-hrps)
  ├── api_* ──> Assen (3huj-srit)
  ├── api_* ──> Carrosserie (vezc-m2t6)
  ├── api_* ──> CarrosserieSpecificatie (jhie-znh9)
  ├── kenteken ──> SubcategorieVoertuig (2ba7-embk)
  ├── kenteken ──> Bijzonderheden (7ug8-2dtt)
  ├── kenteken ──> Rupsbanden (3xwf-ince)
  ├── kenteken ──> Keuringen (vkij-7mwc)
  ├── kenteken ──> MeldingenKeuringsinstantie (sgfe-77wx)
  ├── kenteken ──> GeconstateerdeGebreken (a34c-vvps)
  ├── kenteken ──> TerugroepActieStatus (t49b-isb7)
  └── kenteken ──> ToegevoegdeObjecten (sghb-dzxx)

TerugroepActie (j9yg-7rg9)
  ├── api_* ──> TerugroepActieStatus (t49b-isb7)
  ├── api_* ──> TerugroepVoertuigMerkType (mu2x-mu5e)
  ├── api_* ──> TerugroepActieRisico (9ihi-jgpf)
  └── api_* ──> TerugroepInformerenEigenaar (mh8w-8cup)

MeldingenKeuringsinstantie (sgfe-77wx)
  ├── api_* ──> GeconstateerdeGebreken (a34c-vvps)
  └── api_* ──> Gebreken (hx2c-gt7k)
```

## Adding a new endpoint class

All endpoint classes follow the same template:

```php
<?php

namespace Ovi\RDW;

use Ovi\Interfaces\ApiInterface;
use Ovi\Traits\SanitizationTrait;
use Ovi\Traits\ApiTrait;

class MyEndpoint implements ApiInterface
{
    use SanitizationTrait;
    use ApiTrait;

    private $api_base = 'https://opendata.rdw.nl';
    private $api_path = 'resource/xxxx-xxxx.json';  // Socrata resource ID

    // Only needed if the endpoint accepts kenteken as a filter:
    private $sanitizers = [
        'kenteken' => 'sanitizeLicenseplate',
    ];

    public $request_url = '';
    public $query_vars = [];
}
```

Then register it in `EndpointRegistry` if it should be resolved from `api_*` URLs:

```php
'xxxx-xxxx' => ['class' => MyEndpoint::class, 'link' => ['kenteken']],
```

The `link` array defines which fields from a parent record are passed as query parameters when this endpoint is reached via an `api_*` URL.

## Testing

```bash
# Unit tests only (default — excludes integration tests)
composer test

# Include integration tests (hits live RDW API)
php vendor/bin/phpunit --group integration

# All tests
php vendor/bin/phpunit --group integration,default
```

## Project structure

```
src/
├── Vehicles.php                          # Facade
├── Interfaces/
│   ├── ApiInterface.php                  # Contract for all endpoint classes
│   └── VehiclesInterface.php             # Contract for the Vehicles facade
├── Traits/
│   ├── ApiTrait.php                      # HTTP client, enrichment, query building
│   └── SanitizationTrait.php             # Input sanitizers
├── Helpers/
│   ├── Helper.php                        # Array dot-notation utilities
│   └── Log.php                           # File logger
└── RDW/
    ├── EndpointRegistry.php              # Resource ID → class mapping
    ├── GekentekendVoertuigen.php         # Primary vehicle endpoint
    ├── Brandstof.php                     # Fuel
    ├── Carrosserie.php                   # Body type
    ├── CarrosserieSpecificatie.php        # Body specifications
    ├── Voertuigklasse.php                # Vehicle class
    ├── Assen.php                         # Axles
    ├── SubcategorieVoertuig.php          # Vehicle subcategory
    ├── Bijzonderheden.php                # Special features
    ├── Rupsbanden.php                    # Tracks
    ├── TellerstandoordeelTrendToelichting.php  # Odometer explanation
    ├── TerugroepActie.php                # Recall actions
    ├── TerugroepActieStatus.php          # Recall status
    ├── TerugroepVoertuigMerkType.php     # Recall brand/type
    ├── TerugroepActieRisico.php          # Recall risk
    ├── TerugroepInformerenEigenaar.php   # Owner notification
    ├── MeldingenKeuringsinstantie.php    # Inspection reports
    ├── GeconstateerdeGebreken.php        # Detected defects
    ├── Keuringen.php                     # Inspections (APK)
    ├── Gebreken.php                      # Defect descriptions
    ├── ToegevoegdeObjecten.php           # Added objects
    └── data/
        └── fields.json                   # Field label/format metadata
```

## License

MIT
