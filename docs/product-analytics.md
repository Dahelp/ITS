# Product analytics

`/admin/product` shows clickable counters for product availability, Avito coverage,
SEO field coverage and product/content relationships. Category and badge filters
scope both counters and table results. Selecting a counter resets the previous
table search and pagination; searches entered afterwards further narrow results.

`ProductAnalytics` supplies the same SQL predicates for summaries and table
filters. Preaggregated joins avoid counting a product twice when it has several
ads or links. Stock is available quantity minus reserved quantity, floored at zero.

Avito coverage uses product articles and excludes ads belonging to complete sets.
An ad awaiting deletion still counts as existing to avoid creating duplicates.
Published counts use the last stored API publication status. The preparation
filter selects products without an ad which are active, have a price and have
free stock; advertisement-specific required fields must still be filled in.

Own SEO completeness measures title, description, keywords, content and main
image fields. It is not a ranking score. Effective missing metadata also checks
the first active product InSEO template for that category. Duplicate titles
refer to nonempty own titles across the catalogue. Image checks inspect the
stored field and known placeholders, not filesystem existence.

Link counters exclude nonexistent targets and self-links. Incoming links include
related products, similar products and existing content materials. Groups can
overlap. The isolated filter requires no outgoing or incoming links.

The table exposes seven hidden numeric columns for ordering by stock, own SEO,
InSEO and each relationship count. All existing editing/advertisement actions
remain in the filtered table.

Run `php tests/ProductAnalyticsTest.php` for fixture-based counter/filter parity,
reserved stock, duplicate/set/pending ads, templates and invalid link checks.

The product list uses five composite columns (product identity/media, price and
stock, quality/links, management, Avito). All twenty server-side data columns are
retained for searching and numeric ordering. The previous independent columns
are combined, not discarded. Exports include identifiers and diagnostic details.
On narrow screens each row becomes a labelled vertical card without a horizontal
scroll container. Analytics panels use AdminLTE CardWidget collapse and remember
their state per browser. Existing filters and badge actions remain available.
