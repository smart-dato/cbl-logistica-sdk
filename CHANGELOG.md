# Changelog

All notable changes to `cbl-logistica-sdk` will be documented in this file.

## 0.0.1 - 2026-08-25

Initial release. Covers the CBL Logistica web service: shipment registration with
ZPL labels, day confirmation, pending lookup, the three delete operations, label
reprinting, tracking and proof of delivery.

Notes on the carrier, all verified against the test account rather than taken from
the manual:

- The daily token is handled for you. CBL wants it in the body of every request, so
  the authenticator injects it there and caches it until midnight, keyed on the
  whole credential set. Several accounts of one carrier never share a token.
- `clientReference` is capped at 20 characters. CBL truncates silently and still
  answers `status: OK`, which merges two shipments whose references share a prefix,
  so the package refuses an over-long reference instead.
- Business failures arrive as HTTP 200 with a populated `errorList` and are returned,
  not thrown. Only transport and authentication failures raise an exception.
- `ShipmentStatus/Get` and `ShipmentPod/Get` are not modelled: despite the names they
  return a daily token.
- `PrintShipmentPackages` answers with the same envelope as `CreateShipment`, so
  `reprint()` hands back labels.
- Date windows are clamped client-side: 30 days for status, 7 for proof of delivery.
- `DeletShipmentPackages` really is spelled that way. The manual's
  `DeleteShipmentPackages` returns 404; the typo is part of the route.
- Packages may be registered across several `create()` calls that share one
  `clientReference`. Each call returns labels only for the packages it registered,
  and the shipment moves between `pending` and `closed` as the declared count is
  reached — deleting a package moves it back.
- A reference or SSCC CBL does not recognise is a silent no-op: the count is 0 with
  an empty `errorList`, so callers must check the count.
- Beyond the package count, a shipment cannot be modified; correcting one means
  deleting and recreating it.
