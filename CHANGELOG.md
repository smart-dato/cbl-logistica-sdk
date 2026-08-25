# Changelog

All notable changes to `cbl-logistica-sdk` will be documented in this file.

## 0.0.1 - unreleased

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
