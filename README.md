# CBL Logistica SDK

Fluent PHP SDK for the [CBL Logistica](https://www.cbl-logistica.com) carrier web
service, built on [Saloon](https://docs.saloon.dev) and
[spatie/laravel-data](https://spatie.be/docs/laravel-data): shipment registration
with ZPL labels, day confirmation, deletion, tracking and proof of delivery.

## Coverage

| Operation | Endpoint | Status |
| --- | --- | --- |
| Daily token | `TokenAuth/Get` | ✅ since 0.0.1 (handled for you) |
| Create shipment | `ShipmentRegistry/CreateShipment` | ✅ since 0.0.1 |
| Confirm day | `ShipmentRegistry/ConfirmDayShipments` | ✅ since 0.0.1 |
| Pending shipments | `ShipmentRegistry/GetPendingShipments` | ✅ since 0.0.1 |
| Delete pending | `ShipmentRegistry/DeletePendingShipments` | ✅ since 0.0.1 |
| Delete confirmed | `ShipmentRegistry/DeleteConfirmedShipments` | ✅ since 0.0.1 |
| Delete packages | `ShipmentRegistry/DeletShipmentPackages` | ✅ since 0.0.1 |
| Reprint labels | `ShipmentRegistry/PrintShipmentPackages` | ✅ since 0.0.1 |
| Tracking | `ShipmentStatus/RequestStatusBy{Reference,DateRange}` | ✅ since 0.0.1 |
| Proof of delivery | `ShipmentPod/RequestPodBy{Reference,DateRange}` | ✅ since 0.0.1 |

`ShipmentStatus/Get` and `ShipmentPod/Get` are not modelled: despite their names
they return a daily token, exactly like `TokenAuth/Get`.

## Requirements

- PHP `^8.4`
- Laravel `^11.0 || ^12.0 || ^13.0`

## Installation

```bash
composer require smart-dato/cbl-logistica-sdk
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag="cbl-logistica-sdk-config"
```

It holds endpoints, HTTP options and the token cache store. Credentials are passed
per call (see below), so the `credentials` block is only a single-account
convenience for standalone use.

## Getting started

Credentials are per call, and the package holds no account state — one application
can serve any number of CBL accounts, including several accounts of the same
carrier:

```php
use SmartDato\CblLogistica\Data\Credentials;
use SmartDato\CblLogistica\Facades\CblLogistica;

$cbl = CblLogistica::withCredentials(new Credentials(
    username: 'YourUser',       // HTTP basic auth
    password: 'YourPassword',
    clientToken: 'a1b2c3…',     // buys the daily token
    clientCode: '000000311',    // identifies the account in the request body
));
```

`withCredentials()` returns a configured clone, so the container singleton is never
mutated and two accounts never share a connector, a resource or a cached token:

```php
$base = app(\SmartDato\CblLogistica\CblLogistica::class);   // holds no credentials

$omest = $base->withCredentials($omestCredentials);
$other = $base->withCredentials($otherCredentials);
```

You never deal with the daily token. CBL wants it in the body of every request; the
package fetches it on first use, caches it until midnight under a key derived from
the whole credential set, and injects it into each payload.

Outside Laravel, `new \SmartDato\CblLogistica\CblLogistica()` works the same way.

## Create a shipment

```php
use SmartDato\CblLogistica\Data\Shipments\AddressData;
use SmartDato\CblLogistica\Data\Shipments\PackageData;
use SmartDato\CblLogistica\Data\Shipments\ShipmentData;

$result = $cbl->shipments()->create(new ShipmentData(
    clientReference: 'ORDER-4242',              // max 20 characters, see below
    sender: new AddressData(
        name: 'OMEST SRL',
        street: 'Via L. Negrelli 15',
        postalCode: '39100',
        city: 'BOLZANO',
        country: 'IT',
        province: 'BZ',
    ),
    receiver: new AddressData(
        name: 'Josép Peñá',
        street: 'Calle Mayor 1',
        postalCode: '08029',
        city: 'BARCELONA',
        country: 'ES',
        province: 'BARCELONA',
        phone: '111222444',
        nif: '123456789B',
        email: 'jose@example.com',
    ),
    numPackages: 2,
    weight: 2.0,
    volume: 0.02,
    packages: [
        new PackageData(packageNumber: 1, width: 0.2, height: 0.2, depth: 0.2, weight: 1.0),
        new PackageData(packageNumber: 2, width: 0.2, height: 0.2, depth: 0.2, weight: 1.0),
    ],
    observations1: 'Call before delivery',
    carrier: 'SALVAT',                          // required for international shipments
));
```

The result carries the labels and the errors together — CBL reports business
failures inside an HTTP 200 response, so nothing is thrown for a rejected shipment:

```php
$result->succeeded();        // the status field, OK vs ERROR
$result->carrierReference;   // CBL's shipment number
$result->labels();           // [1 => '^XA…^XZ', 2 => '^XA…^XZ'] — raw ZPL per package
$result->ssccs();            // [1 => '00000000000000254823', …] — 20-digit SSCC per package
$result->errorMessages();    // ['Wrong Postal Code']
$result->warningMessages();  // ['Magnitude weight in field package 1 is too big']
```

Labels are returned untouched. Rendering them is the caller's job — pair this with
`smart-dato/zpl` or `smart-dato/labelary`.

### Units and limits

- Weight in kilograms (max 999999), lengths in metres (max 999.99), volume in cubic
  metres (max 99.99). Exceeding one is a warning (code 105), not an error.
- Omit the shipment `weight`/`volume` totals and CBL derives them from the packages.
- Packages must be numbered. Send fewer than `numPackages` declares and an account
  with day confirmation leaves the shipment pending until the rest arrive, while an
  account without it returns a package-count error.

### `clientReference` is capped at 20 characters

CBL stores only the first 20 characters, does not document this, and does not
complain — a longer reference is truncated silently and still answers `status: OK`,
so two references sharing a 20-character prefix collapse into one shipment. The
package refuses the call instead:

```php
$cbl->shipments()->create($data);   // ValidationException when clientReference is longer
```

## Confirm the day

Accounts configured for day confirmation leave newly created shipments *registered
but not handed over* — `create()` alone is not enough. Confirmation is a separate
call, mirroring the API:

```php
$pending = $cbl->shipments()->pending();

$pending->shipments();                          // every pending entry
$pending->closed();                             // those whose package count is complete
$pending->findByClientReference('ORDER-4242');

$confirmation = $cbl->shipments()->confirm(['ORDER-4242']);
$confirmation->generatedShipments;              // 1
```

`confirm()` authenticates as one account and only confirms that account's own
references, so a batch job serving several accounts must group references by
account.

## Delete and reprint

```php
$cbl->shipments()->deletePending(['ORDER-4242'])->deletedShipments;
$cbl->shipments()->deleteConfirmed(['ORDER-4242']);   // marks only — call your CBL branch
$cbl->shipments()->deletePackages(['00000000000000254823']);

$reprint = $cbl->shipments()->reprint('ORDER-4242', [1, 2]);
$reprint->labels();                                   // same envelope as create()
```

## Track a shipment

```php
$result = $cbl->status()->byReference('999935360776');   // client reference or carrier number

foreach ($result->events() as $event) {
    $event->statusDate;          // CarbonImmutable
    $event->statusCode;          // e.g. 'ALTA'
    $event->statusDescription;
    $event->carrierNumber;
    $event->clientReference;
}

$result = $cbl->status()->byDateRange($from, $to);       // clamped to 30 days
```

## Proof of delivery

```php
$result = $cbl->pod()->byReference('104932869');

$document = $result->first();
$document?->decoded();          // raw bytes, base64 already undone
$document?->fileExtension();    // 'pdf'
$document?->uploadDate;         // CarbonImmutable

$result = $cbl->pod()->byDateRange($from, $to);          // clamped to 7 days
```

Both date-range endpoints cap their window — 30 days for status, 7 for proof of
delivery. CBL silently narrows a wider request and attaches warning 300; the package
clamps client-side too, so the window you get is the window you asked for.

## Error handling

CBL answers business failures with HTTP 200 and a populated `errorList`, so results
are always returned and inspected. The package throws only for problems above that
layer:

| Exception | Thrown when |
| --- | --- |
| `Exceptions\ValidationException` | the call cannot be made as given — no credentials, an empty reference list, an over-long `clientReference` |
| `Exceptions\CblLogisticaApiException` | an HTTP failure. A rejected credential set yields a bare `401` with an empty body, which this reports as a readable message rather than a JSON parse error |

Every response object exposes `hasErrors()`, `errors()`, `errorMessages()`,
`hasWarnings()`, `warnings()` and `warningMessages()`.

## Auditing the raw exchange

Resources retain the last exchange, for applications that journal carrier traffic:

```php
$shipments = $cbl->shipments();
$shipments->create($data);

$shipments->lastRawRequest();    // the exact JSON sent
$shipments->lastRawResponse();   // the exact body received
```

## Character encoding

CBL renders labels with `^CI10`, so anything present in CP850 survives — `Müller &
Söhne`, `Josép Peñá Ürüñ`, `Città àèìòù` and `früh` all print correctly. Characters
outside it do not: a typographic em dash (`—`) prints as `ÔÇö`. Prefer ASCII
punctuation in `observations1`/`observations2`.

## Faking in your tests

Saloon's `MockClient` intercepts everything the package sends, the daily-token call
included — queue that response first:

```php
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

MockClient::global([
    MockResponse::make(['dailyToken' => 'test-token']),
    MockResponse::make(['carrierReference' => '12527', 'status' => 'OK', 'packagesTags' => []]),
]);

// exercise your code, then assert on the outgoing payload:
MockClient::getGlobal()->getLastPendingRequest()->body()->all();

MockClient::destroyGlobal();
```

## Development

```bash
composer test      # pest
composer analyse   # phpstan, level 8
composer format    # pint
```

The live carrier tests are excluded from `composer test` and gated twice — by group
and by environment variable. They create, confirm and delete real shipments on the
test account:

```bash
CBL_LOGISTICA_INTEGRATION=1 \
CBL_LOGISTICA_USERNAME=… CBL_LOGISTICA_PASSWORD=… \
CBL_LOGISTICA_CLIENT_TOKEN=… CBL_LOGISTICA_CLIENT_CODE=… \
vendor/bin/pest --group=integration
```

The fixtures in `tests/Fixtures/responses` were recorded from that account; see the
README there for the quirks they preserve.

## Credits

- [SmartDato](https://github.com/smart-dato)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
