<?php
/**
 * ShopSavvy API Client
 *
 * Communicates with the ShopSavvy Data API using wp_remote_get/wp_remote_post.
 *
 * @package ShopSavvy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ShopSavvy_Client {

    /**
     * Base URL for the ShopSavvy Data API.
     */
    private const API_BASE = 'https://api.shopsavvy.com/v1';

    /**
     * Default request timeout in seconds.
     */
    private const TIMEOUT = 15;

    /**
     * Search for products by query string.
     *
     * @param string $query   Search query (product name, barcode, ASIN, URL, etc.).
     * @param int    $limit   Maximum number of results (1-50).
     * @return array{success: bool, data?: array, error?: string}
     */
    public static function search_products( string $query, int $limit = 10 ): array {
        return self::request( '/products/search', [
            'q'     => $query,
            'limit' => min( 50, max( 1, $limit ) ),
        ] );
    }

    /**
     * Get current offers/prices for a product.
     *
     * @param string $identifier Product identifier (UPC, EAN, ISBN, ASIN, URL, model number, or MPN).
     * @param int    $limit      Maximum number of offers to return.
     * @return array{success: bool, data?: array, error?: string}
     */
    public static function get_current_offers( string $identifier, int $limit = 20 ): array {
        // Check cache first.
        $cached = ShopSavvy_Cache::get( $identifier );
        if ( false !== $cached ) {
            return [
                'success' => true,
                'data'    => $cached,
                'cached'  => true,
            ];
        }

        $result = self::request( '/products/offers', [
            'identifier' => $identifier,
            'limit'      => min( 50, max( 1, $limit ) ),
        ] );

        // Cache successful responses.
        if ( $result['success'] && ! empty( $result['data'] ) ) {
            ShopSavvy_Cache::set( $identifier, $result['data'] );
        }

        return $result;
    }

    /**
     * Get price history for a product.
     *
     * @param string $identifier Product identifier.
     * @param string $period     Time period: '30d', '90d', '1y', 'all'.
     * @return array{success: bool, data?: array, error?: string}
     */
    public static function get_price_history( string $identifier, string $period = '90d' ): array {
        return self::request( '/products/history', [
            'identifier' => $identifier,
            'period'     => $period,
        ] );
    }

    /**
     * Get API usage and credit information.
     *
     * @return array{success: bool, data?: array, error?: string}
     */
    public static function get_usage(): array {
        return self::request( '/account/usage' );
    }

    /**
     * Validate the API key by making a usage request.
     *
     * @param string $api_key API key to validate.
     * @return array{valid: bool, message: string, data?: array}
     */
    public static function validate_api_key( string $api_key ): array {
        $result = self::request( '/account/usage', [], $api_key );

        if ( $result['success'] ) {
            return [
                'valid'   => true,
                'message' => __( 'API key is valid.', 'shopsavvy' ),
                'data'    => $result['data'] ?? [],
            ];
        }

        return [
            'valid'   => false,
            'message' => $result['error'] ?? __( 'Invalid API key.', 'shopsavvy' ),
        ];
    }

    /**
     * Make a GET request to the ShopSavvy API.
     *
     * @param string      $endpoint API endpoint path.
     * @param array       $params   Query parameters.
     * @param string|null $api_key  Override API key (for validation).
     * @return array{success: bool, data?: array, error?: string}
     */
    private static function request( string $endpoint, array $params = [], ?string $api_key = null ): array {
        if ( null === $api_key ) {
            $api_key = get_option( 'shopsavvy_api_key', '' );
        }

        if ( empty( $api_key ) ) {
            return [
                'success' => false,
                'error'   => __( 'ShopSavvy API key is not configured.', 'shopsavvy' ),
            ];
        }

        $url = self::API_BASE . $endpoint;

        if ( ! empty( $params ) ) {
            $url = add_query_arg( $params, $url );
        }

        $response = wp_remote_get( $url, [
            'timeout' => self::TIMEOUT,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Accept'        => 'application/json',
                'User-Agent'    => 'ShopSavvy-WooCommerce/' . SHOPSAVVY_VERSION,
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'error'   => $response->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( 200 !== $code ) {
            $error_message = $data['message'] ?? $data['error'] ?? sprintf(
                /* translators: %d: HTTP status code */
                __( 'API request failed with status %d.', 'shopsavvy' ),
                $code
            );

            return [
                'success' => false,
                'error'   => $error_message,
                'code'    => $code,
            ];
        }

        return [
            'success' => true,
            'data'    => $data,
        ];
    }
}
