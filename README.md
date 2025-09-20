# ovi

RDW OVI

This package provides a simple facade around the Dutch RDW Open Data (OVI) endpoints.

Recent updates:
- Added PHPDoc docblocks for all classes and methods in src/RDW for improved IDE autocompletion and readability.
- Added PHPDoc to core Helpers, Traits, and Interfaces.
- Composer updated for PHP 8 support (php ^7.4 || ^8.0) and Guzzle ^7 compatibility.
- Minor PHP 8 compatibility fixes in core helpers and traits.
- Added PHPUnit setup and initial unit tests (Helpers and ApiTrait). Run with `composer test`.

Basic usage example:

```php
use Ovi\Vehicles;

$ovi = new Vehicles();

// Example: fetch formatted data for a license plate
$result = $ovi
    ->set_option(['verify' => true], 'guzzle_options') // example of setting options using dot-notation
    ->get(['kenteken' => 'AB12CD']);

print_r($result);
```
