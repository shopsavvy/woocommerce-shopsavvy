<?php
/**
 * ShopSavvy Shortcode
 *
 * Provides the [shopsavvy_compare] shortcode for embedding price comparisons anywhere.
 *
 * @package ShopSavvy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ShopSavvy_Shortcode {

    /**
     * Initialize shortcode registration.
     */
    public static function init(): void {
        add_shortcode( 'shopsavvy_compare', [ __CLASS__, 'render' ] );
    }

    /**
     * Render the [shopsavvy_compare] shortcode.
     *
     * Usage:
     *   [shopsavvy_compare identifier="B08N5WRWNW"]
     *   [shopsavvy_compare identifier="B08N5WRWNW" max="5"]
     *   [shopsavvy_compare identifier="194252828526" title="AirPods Pro"]
     *
     * @param array|string $atts Shortcode attributes.
     * @return string HTML output.
     */
    public static function render( array|string $atts ): string {
        $atts = shortcode_atts( [
            'identifier' => '',
            'max'        => 10,
            'title'      => '',
            'show_price' => 'yes',
        ], $atts, 'shopsavvy_compare' );

        $identifier = sanitize_text_field( $atts['identifier'] );

        if ( empty( $identifier ) ) {
            if ( current_user_can( 'manage_woocommerce' ) ) {
                return '<p class="shopsavvy-error">'
                    . esc_html__( 'ShopSavvy: Missing "identifier" attribute. Example: [shopsavvy_compare identifier="B08N5WRWNW"]', 'shopsavvy' )
                    . '</p>';
            }
            return '';
        }

        $max_retailers = max( 1, min( 50, (int) $atts['max'] ) );
        $result        = ShopSavvy_Client::get_current_offers( $identifier, $max_retailers );

        if ( ! $result['success'] ) {
            if ( current_user_can( 'manage_woocommerce' ) ) {
                return '<p class="shopsavvy-error">'
                    . esc_html( sprintf(
                        /* translators: %s: error message */
                        __( 'ShopSavvy: %s', 'shopsavvy' ),
                        $result['error'] ?? __( 'Unknown error.', 'shopsavvy' )
                    ) )
                    . '</p>';
            }
            return '';
        }

        $offers = $result['data']['offers'] ?? $result['data'] ?? [];

        if ( empty( $offers ) || ! is_array( $offers ) ) {
            return '';
        }

        // Sort by price ascending.
        usort( $offers, function ( $a, $b ) {
            $price_a = (float) ( $a['price'] ?? $a['price_retailer'] ?? PHP_FLOAT_MAX );
            $price_b = (float) ( $b['price'] ?? $b['price_retailer'] ?? PHP_FLOAT_MAX );
            return $price_a <=> $price_b;
        } );

        $offers = array_slice( $offers, 0, $max_retailers );

        $product_name = ! empty( $atts['title'] )
            ? sanitize_text_field( $atts['title'] )
            : ( $result['data']['product']['name'] ?? '' );

        $store_price = 0;
        $is_cached   = $result['cached'] ?? false;

        // Enqueue widget styles.
        wp_enqueue_style(
            'shopsavvy-widget',
            SHOPSAVVY_PLUGIN_URL . 'assets/css/shopsavvy-widget.css',
            [],
            SHOPSAVVY_VERSION
        );

        // Capture template output.
        ob_start();
        $template_path = SHOPSAVVY_PLUGIN_DIR . 'templates/widget-comparison.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        }
        return ob_get_clean();
    }
}
