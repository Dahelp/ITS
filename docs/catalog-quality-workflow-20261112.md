# Quality workflow — 12 November 2026
Published two files to /home/s/shinaspec/quality-workflow-20261112 with baseline SHA256 checks, backups and atomic replacement. No database writes. Prepared from actual live baseline, preserving other changes.

Added specific filters for missing source H1/Title/meta description, empty product description, unset main image, missing/unreadable image files, and missing brand. Clickable totals show catalogue-wide counts and preserve current search criteria. Public product links and existing editor links included. Image containment checks prohibit traversal and inspect existing baseimg/unload files; no images or facts are changed. Metadata coverage remains a source-field check, not final InSEO audit.

Production read-only preflight: 1184 products, 880 saved profiles, 304 proposals, 598 with positive stock. 804 products have at least one content/source-field issue; 762 blank source title and description, 715 empty product descriptions, 6 products with bad image files, 1 with unset main image, 55 missing brand. Counts overlap.

Validation: PHP lint, CatalogQualityTest, CatalogQualityContentTest (real valid/broken/missing images, path containment, filters/counts), CatalogQualityViewTest (escaping/rendering) passed; production schema/read-only preflight passed. Receipt outputs/quality-workflow-publication.json.

Live authenticated UI check pending sign-in: tab21 redirected to login. User asked to sign in, no password requested. Cron schedule still deferred until Beget restoration, no duplicate scheduler installed.

Authenticated live UI verified after user sign-in: photo_file filter shows6 rows; combining stock=positive via select and submit shows3 camera products IT000012450, IT000012452, IT000012448. Counters and edit/public links rendered correctly. Evidence outputs/quality-workflow-live-verified.png. No product data changed.
