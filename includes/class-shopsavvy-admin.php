<?php
/**
 * ShopSavvy Admin Settings
 *
 * Provides the WooCommerce settings page for configuring the ShopSavvy plugin.
 *
 * @package ShopSavvy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ShopSavvy_Admin {

    /**
     * Initialize admin hooks.
     */
    public static function init(): void {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu_page' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_styles' ] );
        add_action( 'wp_ajax_shopsavvy_validate_key', [ __CLASS__, 'ajax_validate_key' ] );
        add_action( 'wp_ajax_shopsavvy_clear_cache', [ __CLASS__, 'ajax_clear_cache' ] );
    }

    /**
     * Add settings page under WooCommerce menu.
     */
    public static function add_menu_page(): void {
        add_submenu_page(
            'woocommerce',
            __( 'ShopSavvy Settings', 'shopsavvy' ),
            __( 'ShopSavvy', 'shopsavvy' ),
            'manage_woocommerce',
            'shopsavvy-settings',
            [ __CLASS__, 'render_settings_page' ]
        );
    }

    /**
     * Register plugin settings.
     */
    public static function register_settings(): void {
        // API Settings section.
        add_settings_section(
            'shopsavvy_api_section',
            __( 'API Configuration', 'shopsavvy' ),
            [ __CLASS__, 'render_api_section' ],
            'shopsavvy-settings'
        );

        register_setting( 'shopsavvy_settings', 'shopsavvy_api_key', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ] );

        add_settings_field(
            'shopsavvy_api_key',
            __( 'API Key', 'shopsavvy' ),
            [ __CLASS__, 'render_api_key_field' ],
            'shopsavvy-settings',
            'shopsavvy_api_section'
        );

        // Widget Settings section.
        add_settings_section(
            'shopsavvy_widget_section',
            __( 'Widget Settings', 'shopsavvy' ),
            [ __CLASS__, 'render_widget_section' ],
            'shopsavvy-settings'
        );

        register_setting( 'shopsavvy_settings', 'shopsavvy_widget_enabled', [
            'type'              => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default'           => true,
        ] );

        add_settings_field(
            'shopsavvy_widget_enabled',
            __( 'Enable Widget', 'shopsavvy' ),
            [ __CLASS__, 'render_widget_enabled_field' ],
            'shopsavvy-settings',
            'shopsavvy_widget_section'
        );

        register_setting( 'shopsavvy_settings', 'shopsavvy_widget_position', [
            'type'              => 'string',
            'sanitize_callback' => [ __CLASS__, 'sanitize_widget_position' ],
            'default'           => 'after_price',
        ] );

        add_settings_field(
            'shopsavvy_widget_position',
            __( 'Widget Position', 'shopsavvy' ),
            [ __CLASS__, 'render_widget_position_field' ],
            'shopsavvy-settings',
            'shopsavvy_widget_section'
        );

        register_setting( 'shopsavvy_settings', 'shopsavvy_max_retailers', [
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 10,
        ] );

        add_settings_field(
            'shopsavvy_max_retailers',
            __( 'Max Retailers', 'shopsavvy' ),
            [ __CLASS__, 'render_max_retailers_field' ],
            'shopsavvy-settings',
            'shopsavvy_widget_section'
        );

        // Cache Settings section.
        add_settings_section(
            'shopsavvy_cache_section',
            __( 'Cache Settings', 'shopsavvy' ),
            [ __CLASS__, 'render_cache_section' ],
            'shopsavvy-settings'
        );

        register_setting( 'shopsavvy_settings', 'shopsavvy_cache_duration', [
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 3600,
        ] );

        add_settings_field(
            'shopsavvy_cache_duration',
            __( 'Cache Duration', 'shopsavvy' ),
            [ __CLASS__, 'render_cache_duration_field' ],
            'shopsavvy-settings',
            'shopsavvy_cache_section'
        );
    }

    /**
     * Enqueue admin CSS.
     */
    public static function enqueue_styles( string $hook ): void {
        if ( 'woocommerce_page_shopsavvy-settings' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'shopsavvy-admin',
            SHOPSAVVY_PLUGIN_URL . 'assets/css/shopsavvy-admin.css',
            [],
            SHOPSAVVY_VERSION
        );
    }

    /**
     * Render the settings page.
     */
    public static function render_settings_page(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        // Show success message after settings save.
        if ( isset( $_GET['settings-updated'] ) ) {
            add_settings_error(
                'shopsavvy_messages',
                'shopsavvy_updated',
                __( 'Settings saved.', 'shopsavvy' ),
                'updated'
            );
        }

        ?>
        <div class="wrap shopsavvy-settings-wrap">
            <h1>
                <span class="shopsavvy-logo">&#128722;</span>
                <?php esc_html_e( 'ShopSavvy for WooCommerce', 'shopsavvy' ); ?>
            </h1>

            <?php settings_errors( 'shopsavvy_messages' ); ?>

            <?php self::render_usage_card(); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'shopsavvy_settings' );
                do_settings_sections( 'shopsavvy-settings' );
                submit_button( __( 'Save Settings', 'shopsavvy' ) );
                ?>
            </form>

            <div class="shopsavvy-cache-actions">
                <h2><?php esc_html_e( 'Cache Management', 'shopsavvy' ); ?></h2>
                <p><?php esc_html_e( 'Clear all cached price data to force fresh lookups.', 'shopsavvy' ); ?></p>
                <button type="button" class="button" id="shopsavvy-clear-cache">
                    <?php esc_html_e( 'Clear Cache', 'shopsavvy' ); ?>
                </button>
                <span id="shopsavvy-cache-status"></span>
            </div>

            <div class="shopsavvy-footer">
                <p>
                    <?php
                    printf(
                        /* translators: %s: link to ShopSavvy Data API */
                        esc_html__( 'Get your API key at %s', 'shopsavvy' ),
                        '<a href="https://shopsavvy.com/data" target="_blank">shopsavvy.com/data</a>'
                    );
                    ?>
                </p>
            </div>
        </div>

        <script>
        (function() {
            // Validate API key button.
            const validateBtn = document.getElementById('shopsavvy-validate-key');
            if (validateBtn) {
                validateBtn.addEventListener('click', function() {
                    const status = document.getElementById('shopsavvy-key-status');
                    const keyField = document.getElementById('shopsavvy_api_key');
                    status.textContent = '<?php echo esc_js( __( 'Validating...', 'shopsavvy' ) ); ?>';
                    status.className = 'shopsavvy-status shopsavvy-status-pending';

                    const formData = new FormData();
                    formData.append('action', 'shopsavvy_validate_key');
                    formData.append('api_key', keyField.value);
                    formData.append('_wpnonce', '<?php echo esc_js( wp_create_nonce( 'shopsavvy_validate_key' ) ); ?>');

                    fetch(ajaxurl, { method: 'POST', body: formData })
                        .then(r => r.json())
                        .then(data => {
                            status.textContent = data.data.message;
                            status.className = 'shopsavvy-status ' + (data.success ? 'shopsavvy-status-valid' : 'shopsavvy-status-invalid');
                        })
                        .catch(() => {
                            status.textContent = '<?php echo esc_js( __( 'Validation request failed.', 'shopsavvy' ) ); ?>';
                            status.className = 'shopsavvy-status shopsavvy-status-invalid';
                        });
                });
            }

            // Clear cache button.
            const clearBtn = document.getElementById('shopsavvy-clear-cache');
            if (clearBtn) {
                clearBtn.addEventListener('click', function() {
                    const status = document.getElementById('shopsavvy-cache-status');
                    status.textContent = '<?php echo esc_js( __( 'Clearing...', 'shopsavvy' ) ); ?>';

                    const formData = new FormData();
                    formData.append('action', 'shopsavvy_clear_cache');
                    formData.append('_wpnonce', '<?php echo esc_js( wp_create_nonce( 'shopsavvy_clear_cache' ) ); ?>');

                    fetch(ajaxurl, { method: 'POST', body: formData })
                        .then(r => r.json())
                        .then(data => {
                            status.textContent = data.data.message;
                            status.className = 'shopsavvy-status shopsavvy-status-valid';
                        })
                        .catch(() => {
                            status.textContent = '<?php echo esc_js( __( 'Clear request failed.', 'shopsavvy' ) ); ?>';
                            status.className = 'shopsavvy-status shopsavvy-status-invalid';
                        });
                });
            }
        })();
        </script>
        <?php
    }

    /**
     * Render API usage card if API key is configured.
     */
    private static function render_usage_card(): void {
        $api_key = get_option( 'shopsavvy_api_key', '' );

        if ( empty( $api_key ) ) {
            return;
        }

        $usage = ShopSavvy_Client::get_usage();

        if ( ! $usage['success'] ) {
            return;
        }

        $data = $usage['data'] ?? [];
        $credits_used  = $data['credits_used'] ?? 0;
        $credits_total = $data['credits_total'] ?? 0;
        $plan_name     = $data['plan'] ?? __( 'Unknown', 'shopsavvy' );
        $percent_used  = $credits_total > 0 ? round( ( $credits_used / $credits_total ) * 100 ) : 0;

        ?>
        <div class="shopsavvy-usage-card">
            <h2><?php esc_html_e( 'API Usage', 'shopsavvy' ); ?></h2>
            <div class="shopsavvy-usage-grid">
                <div class="shopsavvy-usage-stat">
                    <span class="shopsavvy-usage-label"><?php esc_html_e( 'API Usage', 'shopsavvy' ); ?></span>
                    <span class="shopsavvy-usage-value">
                        <?php echo esc_html( number_format( $credits_used ) ); ?> / <?php echo esc_html( number_format( $credits_total ) ); ?>
                    </span>
                </div>
                <div class="shopsavvy-usage-stat">
                    <span class="shopsavvy-usage-label"><?php esc_html_e( 'Usage', 'shopsavvy' ); ?></span>
                    <div class="shopsavvy-progress-bar">
                        <div class="shopsavvy-progress-fill" style="width: <?php echo esc_attr( $percent_used ); ?>%"></div>
                    </div>
                    <span class="shopsavvy-usage-percent"><?php echo esc_html( $percent_used ); ?>%</span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Section descriptions.
     */
    public static function render_api_section(): void {
        printf(
            '<p>%s <a href="https://shopsavvy.com/data" target="_blank">shopsavvy.com/data</a>.</p>',
            esc_html__( 'Enter your ShopSavvy Data API key. Get one at', 'shopsavvy' )
        );
    }

    public static function render_widget_section(): void {
        echo '<p>' . esc_html__( 'Configure how the price comparison widget appears on product pages.', 'shopsavvy' ) . '</p>';
    }

    public static function render_cache_section(): void {
        echo '<p>' . esc_html__( 'Control how long price data is cached to balance freshness with API usage.', 'shopsavvy' ) . '</p>';
    }

    /**
     * Field renderers.
     */
    public static function render_api_key_field(): void {
        $value = get_option( 'shopsavvy_api_key', '' );
        ?>
        <input type="password"
               id="shopsavvy_api_key"
               name="shopsavvy_api_key"
               value="<?php echo esc_attr( $value ); ?>"
               class="regular-text"
               autocomplete="off"
        />
        <button type="button" class="button button-secondary" id="shopsavvy-validate-key">
            <?php esc_html_e( 'Validate Key', 'shopsavvy' ); ?>
        </button>
        <span id="shopsavvy-key-status" class="shopsavvy-status"></span>
        <p class="description">
            <?php esc_html_e( 'Your ShopSavvy Data API key. This is used to authenticate requests to the ShopSavvy API.', 'shopsavvy' ); ?>
        </p>
        <?php
    }

    public static function render_widget_enabled_field(): void {
        $value = get_option( 'shopsavvy_widget_enabled', true );
        ?>
        <label>
            <input type="checkbox"
                   name="shopsavvy_widget_enabled"
                   value="1"
                   <?php checked( $value ); ?>
            />
            <?php esc_html_e( 'Show price comparison widget on product pages', 'shopsavvy' ); ?>
        </label>
        <?php
    }

    public static function render_widget_position_field(): void {
        $value = get_option( 'shopsavvy_widget_position', 'after_price' );
        $options = [
            'after_price'       => __( 'After price', 'shopsavvy' ),
            'after_add_to_cart' => __( 'After add to cart button', 'shopsavvy' ),
            'after_meta'        => __( 'After product meta', 'shopsavvy' ),
            'after_tabs'        => __( 'After product tabs', 'shopsavvy' ),
        ];
        ?>
        <select name="shopsavvy_widget_position" id="shopsavvy_widget_position">
            <?php foreach ( $options as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e( 'Choose where the price comparison widget appears on product pages.', 'shopsavvy' ); ?>
        </p>
        <?php
    }

    public static function render_max_retailers_field(): void {
        $value = get_option( 'shopsavvy_max_retailers', 10 );
        ?>
        <input type="number"
               name="shopsavvy_max_retailers"
               value="<?php echo esc_attr( $value ); ?>"
               min="1"
               max="50"
               class="small-text"
        />
        <p class="description">
            <?php esc_html_e( 'Maximum number of retailer prices to display (1-50).', 'shopsavvy' ); ?>
        </p>
        <?php
    }

    public static function render_cache_duration_field(): void {
        $value = get_option( 'shopsavvy_cache_duration', 3600 );
        $options = [
            300   => __( '5 minutes', 'shopsavvy' ),
            900   => __( '15 minutes', 'shopsavvy' ),
            1800  => __( '30 minutes', 'shopsavvy' ),
            3600  => __( '1 hour', 'shopsavvy' ),
            7200  => __( '2 hours', 'shopsavvy' ),
            14400 => __( '4 hours', 'shopsavvy' ),
            43200 => __( '12 hours', 'shopsavvy' ),
            86400 => __( '24 hours', 'shopsavvy' ),
        ];
        ?>
        <select name="shopsavvy_cache_duration" id="shopsavvy_cache_duration">
            <?php foreach ( $options as $seconds => $label ) : ?>
                <option value="<?php echo esc_attr( $seconds ); ?>" <?php selected( (int) $value, $seconds ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e( 'How long to cache price data before fetching fresh results. Longer durations make fewer API calls.', 'shopsavvy' ); ?>
        </p>
        <?php
    }

    /**
     * Sanitize widget position value.
     */
    public static function sanitize_widget_position( string $value ): string {
        $valid = [ 'after_price', 'after_add_to_cart', 'after_meta', 'after_tabs' ];
        return in_array( $value, $valid, true ) ? $value : 'after_price';
    }

    /**
     * AJAX: Validate API key.
     */
    public static function ajax_validate_key(): void {
        check_ajax_referer( 'shopsavvy_validate_key' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'shopsavvy' ) ] );
        }

        $api_key = sanitize_text_field( $_POST['api_key'] ?? '' );

        if ( empty( $api_key ) ) {
            wp_send_json_error( [ 'message' => __( 'Please enter an API key.', 'shopsavvy' ) ] );
        }

        $result = ShopSavvy_Client::validate_api_key( $api_key );

        if ( $result['valid'] ) {
            wp_send_json_success( [ 'message' => $result['message'] ] );
        } else {
            wp_send_json_error( [ 'message' => $result['message'] ] );
        }
    }

    /**
     * AJAX: Clear cache.
     */
    public static function ajax_clear_cache(): void {
        check_ajax_referer( 'shopsavvy_clear_cache' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'shopsavvy' ) ] );
        }

        $count = ShopSavvy_Cache::flush_all();

        wp_send_json_success( [
            'message' => sprintf(
                /* translators: %d: number of cache entries cleared */
                __( 'Cache cleared. %d entries removed.', 'shopsavvy' ),
                $count
            ),
        ] );
    }
}
