<?php
/**
 * ShopSavvy Cache
 *
 * Wraps the WordPress Transients API for caching offer data.
 *
 * @package ShopSavvy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ShopSavvy_Cache {

    /**
     * Cache key prefix.
     */
    private const PREFIX = 'shopsavvy_offers_';

    /**
     * Get cached offers for an identifier.
     *
     * @param string $identifier Product identifier (UPC, ASIN, URL, etc.).
     * @return array|false Cached data or false if not found/expired.
     */
    public static function get( string $identifier ): array|false {
        $key  = self::build_key( $identifier );
        $data = get_transient( $key );

        if ( false === $data ) {
            return false;
        }

        return $data;
    }

    /**
     * Set cached offers for an identifier.
     *
     * @param string $identifier Product identifier.
     * @param array  $data       Offer data to cache.
     * @param int    $ttl        Time to live in seconds. 0 = use plugin setting.
     * @return bool True if cached successfully.
     */
    public static function set( string $identifier, array $data, int $ttl = 0 ): bool {
        $key = self::build_key( $identifier );

        if ( 0 === $ttl ) {
            $ttl = self::get_ttl();
        }

        return set_transient( $key, $data, $ttl );
    }

    /**
     * Delete cached offers for an identifier.
     *
     * @param string $identifier Product identifier.
     * @return bool True if deleted.
     */
    public static function delete( string $identifier ): bool {
        $key = self::build_key( $identifier );
        return delete_transient( $key );
    }

    /**
     * Flush all ShopSavvy transients.
     *
     * @return int Number of transients deleted.
     */
    public static function flush_all(): int {
        global $wpdb;

        $count = $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_shopsavvy_%' OR option_name LIKE '_transient_timeout_shopsavvy_%'"
        );

        return (int) $count;
    }

    /**
     * Build a transient key from an identifier.
     *
     * @param string $identifier Product identifier.
     * @return string Transient key (max 172 chars for transients).
     */
    private static function build_key( string $identifier ): string {
        return self::PREFIX . md5( $identifier );
    }

    /**
     * Get the configured TTL from plugin settings.
     *
     * @return int TTL in seconds.
     */
    private static function get_ttl(): int {
        $duration = get_option( 'shopsavvy_cache_duration', 3600 );
        return max( 60, (int) $duration );
    }
}
