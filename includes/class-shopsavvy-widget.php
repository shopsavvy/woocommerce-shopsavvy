<?php
/**
 * ShopSavvy Product Page Widget
 *
 * Displays competitor price comparisons on WooCommerce product pages.
 *
 * @package ShopSavvy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ShopSavvy_Widget {

    /**
     * Hook position mapping.
     */
    private const POSITIONS = [
        'after_price'       => [ 'hook' => 'woocommerce_single_product_summary', 'priority' => 15 ],
        'after_add_to_cart' => [ 'hook' => 'woocommerce_single_product_summary', 'priority' => 35 ],
        'after_meta'        => [ 'hook' => 'woocommerce_single_product_summary', 'priority' => 45 ],
        'after_tabs'        => [ 'hook' => 'woocommerce_after_single_product_summary', 'priority' => 15 ],
    ];

    /**
     * Initialize the widget hooks.
     */
    public static function init(): void {
        if ( ! self::is_enabled() ) {
            return;
        }

        $position = get_option( 'shopsavvy_widget_position', 'after_price' );
        $config   = self::POSITIONS[ $position ] ?? self::POSITIONS['after_price'];

        add_action( $config['hook'], [ __CLASS__, 'render' ], $config['priority'] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_styles' ] );
    }

    /**
     * Check if the widget is enabled.
     */
    private static function is_enabled(): bool {
        $enabled = get_option( 'shopsavvy_widget_enabled', true );
        return filter_var( $enabled, FILTER_VALIDATE_BOOLEAN );
    }

    /**
     * Enqueue frontend CSS.
     */
    public static function enqueue_styles(): void {
        if ( ! is_product() ) {
            return;
        }

        wp_enqueue_style(
            'shopsavvy-widget',
            SHOPSAVVY_PLUGIN_URL . 'assets/css/shopsavvy-widget.css',
            [],
            SHOPSAVVY_VERSION
        );
    }

    /**
     * Render the comparison widget on the product page.
     */
    public static function render(): void {
        global $product;

        if ( ! $product instanceof WC_Product ) {
            return;
        }

        $identifier = self::get_product_identifier( $product );

        if ( empty( $identifier ) ) {
            return;
        }

        $max_retailers = (int) get_option( 'shopsavvy_max_retailers', 10 );
        $result        = ShopSavvy_Client::get_current_offers( $identifier, $max_retailers );

        if ( ! $result['success'] || empty( $result['data'] ) ) {
            return;
        }

        $offers       = $result['data']['offers'] ?? $result['data'] ?? [];
        $product_name = $result['data']['product']['name'] ?? $product->get_name();
        $is_cached    = $result['cached'] ?? false;

        if ( empty( $offers ) || ! is_array( $offers ) ) {
            return;
        }

        // Sort by price ascending.
        usort( $offers, function ( $a, $b ) {
            $price_a = (float) ( $a['price'] ?? $a['price_retailer'] ?? PHP_FLOAT_MAX );
            $price_b = (float) ( $b['price'] ?? $b['price_retailer'] ?? PHP_FLOAT_MAX );
            return $price_a <=> $price_b;
        } );

        // Limit to max retailers.
        $offers = array_slice( $offers, 0, $max_retailers );

        // Get the store's price for comparison.
        $store_price = (float) $product->get_price();

        // Load the template.
        $template_path = SHOPSAVVY_PLUGIN_DIR . 'templates/widget-comparison.php';

        if ( file_exists( $template_path ) ) {
            include $template_path;
        }
    }

    /**
     * Extract a product identifier from WooCommerce product data.
     *
     * Checks in order: barcode/UPC custom field, GTIN, EAN, ISBN, SKU, ASIN custom field.
     *
     * @param WC_Product $product WooCommerce product.
     * @return string Product identifier or empty string.
     */
    private static function get_product_identifier( WC_Product $product ): string {
        // Common barcode/UPC meta keys used by popular plugins.
        $barcode_keys = [
            '_barcode',
            '_upc',
            '_ean',
            '_isbn',
            '_gtin',
            '_global_unique_id',      // WooCommerce core (8.x+).
            'barcode',
            'upc',
            'ean',
            'isbn',
            'gtin',
            '_wpm_gtin_code',         // WooCommerce Product Manager.
            '_alg_ean',               // EAN for WooCommerce.
            'hwp_var_gtin',           // Flavor/JEGS.
            '_wpm_gtin_code_pre',     // Germanized.
        ];

        foreach ( $barcode_keys as $key ) {
            $value = $product->get_meta( $key );
            if ( ! empty( $value ) && is_string( $value ) ) {
                return trim( $value );
            }
        }

        // Try ASIN (commonly stored for Amazon-linked products).
        $asin_keys = [ '_asin', 'asin', '_amazon_asin' ];
        foreach ( $asin_keys as $key ) {
            $value = $product->get_meta( $key );
            if ( ! empty( $value ) && is_string( $value ) ) {
                return trim( $value );
            }
        }

        // Fall back to SKU.
        $sku = $product->get_sku();
        if ( ! empty( $sku ) ) {
            return $sku;
        }

        return '';
    }
}
