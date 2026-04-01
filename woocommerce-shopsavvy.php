<?php
/**
 * Plugin Name: ShopSavvy for WooCommerce
 * Plugin URI: https://shopsavvy.com/data
 * Description: Compare your product prices against competitors across thousands of retailers.
 * Version: 1.0.0
 * Author: ShopSavvy
 * Author URI: https://shopsavvy.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: shopsavvy
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 8.0
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SHOPSAVVY_VERSION', '1.0.0' );
define( 'SHOPSAVVY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SHOPSAVVY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SHOPSAVVY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check if WooCommerce is active before initializing.
 */
function shopsavvy_check_woocommerce(): bool {
    return in_array(
        'woocommerce/woocommerce.php',
        apply_filters( 'active_plugins', get_option( 'active_plugins' ) ),
        true
    );
}

/**
 * Display admin notice if WooCommerce is not active.
 */
function shopsavvy_woocommerce_missing_notice(): void {
    ?>
    <div class="notice notice-error">
        <p>
            <strong><?php esc_html_e( 'ShopSavvy for WooCommerce', 'shopsavvy' ); ?></strong>
            <?php esc_html_e( 'requires WooCommerce to be installed and active.', 'shopsavvy' ); ?>
        </p>
    </div>
    <?php
}

/**
 * Load plugin classes and initialize.
 */
function shopsavvy_init(): void {
    if ( ! shopsavvy_check_woocommerce() ) {
        add_action( 'admin_notices', 'shopsavvy_woocommerce_missing_notice' );
        return;
    }

    require_once SHOPSAVVY_PLUGIN_DIR . 'includes/class-shopsavvy-cache.php';
    require_once SHOPSAVVY_PLUGIN_DIR . 'includes/class-shopsavvy-client.php';
    require_once SHOPSAVVY_PLUGIN_DIR . 'includes/class-shopsavvy-widget.php';
    require_once SHOPSAVVY_PLUGIN_DIR . 'includes/class-shopsavvy-admin.php';
    require_once SHOPSAVVY_PLUGIN_DIR . 'includes/class-shopsavvy-shortcode.php';

    ShopSavvy_Admin::init();
    ShopSavvy_Widget::init();
    ShopSavvy_Shortcode::init();
}

add_action( 'plugins_loaded', 'shopsavvy_init' );

/**
 * Register activation hook to set default options.
 */
function shopsavvy_activate(): void {
    add_option( 'shopsavvy_api_key', '' );
    add_option( 'shopsavvy_cache_duration', 3600 );
    add_option( 'shopsavvy_widget_enabled', true );
    add_option( 'shopsavvy_widget_position', 'after_price' );
    add_option( 'shopsavvy_max_retailers', 10 );
}

register_activation_hook( __FILE__, 'shopsavvy_activate' );

/**
 * Register deactivation hook to clean up transients.
 */
function shopsavvy_deactivate(): void {
    global $wpdb;

    // Clean up all ShopSavvy transients.
    $wpdb->query(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_shopsavvy_%' OR option_name LIKE '_transient_timeout_shopsavvy_%'"
    );
}

register_deactivation_hook( __FILE__, 'shopsavvy_deactivate' );

/**
 * Add settings link to plugins page.
 */
function shopsavvy_plugin_action_links( array $links ): array {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        admin_url( 'admin.php?page=shopsavvy-settings' ),
        esc_html__( 'Settings', 'shopsavvy' )
    );

    array_unshift( $links, $settings_link );

    return $links;
}

add_filter( 'plugin_action_links_' . SHOPSAVVY_PLUGIN_BASENAME, 'shopsavvy_plugin_action_links' );
