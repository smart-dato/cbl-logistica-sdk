# Recorded CBL payloads

Every file here was **recorded from the live CBL test account** (client code
`000000311`) by the integration suite, so they carry the API's real quirks:

- the daily token is a plain 64-hex string, not the base64 blob the manual shows;
- `pending-shipments` returns `numPackages` and `weight` as **strings** and
  `createDate` as `d/m/Y`, unlike every other date in the API;
- `status` and `pod` timestamps arrive with **no timezone offset**, which
  laravel-data's default date format rejects;
- `create-shipment-ok` holds genuine ZPL, and `PrintShipmentPackages` answers with
  the identical envelope but a null `carrierReference` and `clientReference`.

`create-shipment-partial`, `delete-shipment-packages` and
`pending-shipments-incomplete` record the incremental-package flow: registering
part of a declared shipment, then deleting one package by SSCC, which moves the
shipment from `closed` back to `pending`.

`create-shipment-error` and `status-clamped` are the only constructed payloads —
the test account produced no failing shipment or over-wide window to record. Their
field names follow the manual's samples.

Re-record after a carrier change with:

```bash
CBL_LOGISTICA_INTEGRATION=1 vendor/bin/pest --group=integration
```
