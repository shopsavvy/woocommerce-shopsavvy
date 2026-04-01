# ShopSavvy for WooCommerce

Monitor competitor prices and show real-time price comparisons on your WooCommerce product pages.

## Features

- **Automatic Price Comparisons** — Displays competitor prices on product pages using barcode, UPC, EAN, ASIN, or SKU
- **Admin Dashboard** — Configure settings, validate your API key, monitor API usage, and manage cache
- **Shortcode Support** — Embed price comparisons anywhere with `[shopsavvy_compare]`
- **Smart Caching** — WordPress Transients cache reduces API calls and improves page load
- **Responsive Design** — Looks great on desktop and mobile
- **Customizable Position** — After price, after add-to-cart, after meta, or after product tabs

## Requirements

- WordPress 5.0+
- WooCommerce 7.0+
- PHP 8.0+
- ShopSavvy Data API key ([shopsavvy.com/data](https://shopsavvy.com/data))

## Installation

1. Upload the `woocommerce-shopsavvy` folder to `/wp-content/plugins/`
2. Activate the plugin in WordPress
3. Go to **WooCommerce > ShopSavvy**
4. Enter your API key from [shopsavvy.com/data](https://shopsavvy.com/data)
5. Configure widget settings and save

## Configuration

| Setting | Description | Default |
|---------|-------------|---------|
| API Key | Your ShopSavvy Data API key | — |
| Widget Enabled | Show/hide the comparison widget | Enabled |
| Widget Position | Where on the product page to display | After price |
| Max Retailers | Maximum retailer prices to show (1-50) | 10 |
| Cache Duration | How long to cache price data | 1 hour |

## Shortcode

Use the `[shopsavvy_compare]` shortcode to embed price comparisons in any post, page, or widget area:

```
[shopsavvy_compare identifier="B08N5WRWNW"]
[shopsavvy_compare identifier="B08N5WRWNW" max="5"]
[shopsavvy_compare identifier="194252828526" title="AirPods Pro" max="10"]
```

### Shortcode Attributes

| Attribute | Description | Default |
|-----------|-------------|---------|
| `identifier` | Product identifier (UPC, ASIN, EAN, ISBN, URL, model number, MPN) | Required |
| `max` | Maximum number of offers to display | 10 |
| `title` | Custom product name to display | Auto-detected |

## Product Identifiers

The plugin automatically reads product identifiers from WooCommerce product meta. It checks these fields in order:

1. `_barcode`, `_upc`, `_ean`, `_isbn`, `_gtin`, `_global_unique_id` (WooCommerce core 8.x+)
2. Plugin-specific fields: `_wpm_gtin_code`, `_alg_ean`, `hwp_var_gtin`
3. ASIN fields: `_asin`, `asin`, `_amazon_asin`
4. Product SKU (fallback)

## Template Override

Copy `templates/widget-comparison.php` to `yourtheme/shopsavvy/widget-comparison.php` to customize the widget markup.

## API

This plugin uses the [ShopSavvy Data API](https://shopsavvy.com/data).

## License

GPL v2 or later. See [LICENSE](LICENSE) for details.
