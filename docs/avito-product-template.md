# Avito product draft generation

`AvitoProductTemplate` selects the product group from its category hierarchy,
falling back to the product name. Descriptions and attribute labels are excluded:
the tyre attribute “Посадочный диаметр на диск” must not select a rim template.
The existing `Avito::generatedProductType()` method delegates to this rule so
both structured fields and generated descriptions agree.

Generated titles preserve the full normalized product name. `ensureTitleStorage`
widens legacy VARCHAR title columns to 255 characters; the original 50-character
column otherwise silently truncated drafts even after the generator was fixed.
Already wider or text columns remain unchanged.

The existing application feed limit stays at 50 characters. This is deliberately
not raised to 90 based on an illustrative counter. Official Avito documentation
could not be independently retrieved on 2026-10-22 (HTTP 429 / browser access
blocked), so category-specific platform limits remain to be confirmed. Change
the shared `TITLE_LIMIT` only after confirmation.

The editor shows Unicode character count including spaces, preserves overlength
input and reports overflow rather than silently trimming. Add/edit actions reject
overlength titles before storing the ad or triggering autoload. Readiness also
marks an overlength draft as requiring correction. A generated long-title draft
remains archived pending review.

Checks:

- `php tests/AvitoProductTemplateTest.php`
- `node tests/AvitoTitleCounterTest.js`

Production verification covered an actual tyre misclassified as a rim, generation
of the tyre description, full title storage, and rendering its 70/50 counter.

## Warehouse address

`AvitoWarehouse::ADDRESS` is the shared warehouse location for every product and
complete-set advertisement. Configuration, draft defaults, imports, editor saves
and both XML exporters use it. The editor displays this common address read-only.
When correcting a different address, stale coordinates and SellerAddressId are
cleared so they cannot override the warehouse text. References already attached
to the correct warehouse address are preserved. Legal company details elsewhere
in the site are independent of the Avito pickup location.

Run `php tests/AvitoWarehouseTest.php` to check normalization of existing product
and set ads, empty/new drafts, stale references and idempotent migration.
