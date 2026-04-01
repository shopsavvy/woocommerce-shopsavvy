=== ShopSavvy for WooCommerce ===
Contributors: shopsavvy
Tags: price comparison, competitor monitoring, woocommerce, pricing, price tracking
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.0.0
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Monitor competitor prices and show real-time price comparisons on your WooCommerce product pages.

== Description ==

**ShopSavvy for WooCommerce** brings real-time competitor price monitoring directly to your WooCommerce store. Automatically display price comparisons from thousands of retailers on your product pages, helping your customers see that you offer competitive pricing.

= Features =

* **Automatic Price Comparisons** — Displays competitor prices on product pages using barcode, UPC, EAN, ASIN, or SKU.
* **Admin Dashboard** — Configure settings, validate your API key, monitor API usage, and manage cache from WooCommerce settings.
* **Shortcode Support** — Embed price comparisons anywhere with `[shopsavvy_compare identifier="B08N5WRWNW"]`.
* **Smart Caching** — Uses WordPress Transients to cache price data, reducing API calls and improving page load times.
* **Responsive Design** — The comparison widget looks great on desktop and mobile.
* **Customizable Position** — Choose where the widget appears: after price, after add-to-cart, after meta, or after tabs.
* **Lowest Price Highlighting** — Automatically highlights the lowest available price.
* **Condition Labels** — Shows new, used, and refurbished conditions.
* **Shipping Information** — Displays shipping costs when available.

= How It Works =

1. The plugin reads your product's barcode, UPC, EAN, ASIN, or SKU from WooCommerce product data.
2. It queries the ShopSavvy Data API for current prices across thousands of retailers.
3. Results are cached and displayed in a clean comparison table on your product page.

= Supported Identifiers =

ShopSavvy can look up products by:

* UPC / EAN / ISBN barcodes
* Amazon ASIN
* Model numbers
* Manufacturer Part Numbers (MPN)
* Product URLs
* SKU (as fallback)

The plugin checks common meta fields used by popular barcode plugins (WooCommerce Product Manager, EAN for WooCommerce, Germanized, and more).

= API Key Required =

This plugin requires a ShopSavvy Data API key. Get yours at [shopsavvy.com/data](https://shopsavvy.com/data).

== Installation ==

1. Upload the `woocommerce-shopsavvy` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **WooCommerce > ShopSavvy** in your admin panel.
4. Enter your API key from [shopsavvy.com/data](https://shopsavvy.com/data).
5. Click "Validate Key" to confirm it works.
6. Configure widget position, cache duration, and max retailers.
7. Save settings. Price comparisons will now appear on product pages that have barcode/identifier data.

= Adding Product Identifiers =

For the widget to appear on a product page, the product needs a barcode or identifier. You can add these via:

* **WooCommerce core** (8.x+): Use the built-in GTIN field.
* **Custom fields**: Add `_barcode`, `_upc`, `_ean`, `_asin`, or `_isbn` as product meta.
* **Barcode plugins**: Use any popular WooCommerce barcode plugin — ShopSavvy reads their meta fields automatically.
* **SKU**: If no barcode is found, the product SKU is used as a fallback identifier.

== Frequently Asked Questions ==

= Do I need an API key? =

Yes. The plugin requires a ShopSavvy Data API key to fetch competitor prices. Get one at [shopsavvy.com/data](https://shopsavvy.com/data).

= How often are prices updated? =

By default, prices are cached for 1 hour. You can configure this from 5 minutes to 24 hours in the plugin settings.

= What if a product doesn't have a barcode? =

The plugin falls back to the product SKU. For best results, add UPC, EAN, or ASIN data to your products.

= Can I customize the widget appearance? =

Yes. You can override the template by copying `templates/widget-comparison.php` to `yourtheme/shopsavvy/widget-comparison.php`. The CSS can also be overridden in your theme.

= Does this work with variable products? =

The widget displays on the main product page. It uses the parent product's identifier data.

== Screenshots ==

1. Price comparison widget on a product page.
2. Admin settings page with API key validation.
3. Shortcode usage in a post or page.

== Changelog ==

= 1.0.0 =
* Initial release.
* Product page price comparison widget.
* Admin settings with API key validation and usage display.
* Shortcode support for embedding comparisons anywhere.
* WordPress Transients caching with configurable duration.
* Responsive design for mobile and desktop.

== Upgrade Notice ==

= 1.0.0 =
Initial release of ShopSavvy for WooCommerce.
