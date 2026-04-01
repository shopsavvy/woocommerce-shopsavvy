<?php
/**
 * ShopSavvy Comparison Widget Template
 *
 * Available variables:
 * - $offers       (array)  List of offer arrays with retailer/price data.
 * - $product_name (string) Product name.
 * - $store_price  (float)  The WooCommerce store's price (0 if from shortcode).
 * - $is_cached    (bool)   Whether the data was served from cache.
 *
 * Override this template by copying it to:
 *   yourtheme/shopsavvy/widget-comparison.php
 *
 * @package ShopSavvy
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'shopsavvy_format_price' ) ) {
    /**
     * Format a price with currency symbol.
     */
    function shopsavvy_format_price( float $price, string $currency = 'USD' ): string {
        $symbols = [
            'USD' => '$', 'EUR' => "\u{20AC}", 'GBP' => "\u{00A3}", 'JPY' => "\u{00A5}",
            'CAD' => 'CA$', 'AUD' => 'A$', 'INR' => "\u{20B9}", 'BRL' => 'R$',
            'MXN' => 'MX$', 'KRW' => "\u{20A9}", 'CNY' => "\u{00A5}", 'RUB' => "\u{20BD}",
        ];

        $symbol   = $symbols[ strtoupper( $currency ) ] ?? $currency . ' ';
        $decimals = in_array( strtoupper( $currency ), [ 'JPY', 'KRW' ], true ) ? 0 : 2;

        return $symbol . number_format( $price, $decimals );
    }
}
?>

<div class="shopsavvy-comparison" data-cached="<?php echo esc_attr( $is_cached ? 'true' : 'false' ); ?>">
    <div class="shopsavvy-comparison-header">
        <h3 class="shopsavvy-comparison-title">
            <?php esc_html_e( 'Compare Prices', 'shopsavvy' ); ?>
        </h3>
        <?php if ( ! empty( $product_name ) ) : ?>
            <span class="shopsavvy-product-name"><?php echo esc_html( $product_name ); ?></span>
        <?php endif; ?>
    </div>

    <table class="shopsavvy-comparison-table">
        <thead>
            <tr>
                <th class="shopsavvy-col-retailer"><?php esc_html_e( 'Retailer', 'shopsavvy' ); ?></th>
                <th class="shopsavvy-col-price"><?php esc_html_e( 'Price', 'shopsavvy' ); ?></th>
                <th class="shopsavvy-col-availability"><?php esc_html_e( 'Availability', 'shopsavvy' ); ?></th>
                <th class="shopsavvy-col-action"></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $lowest_price = null;

            foreach ( $offers as $index => $offer ) :
                $price          = (float) ( $offer['price'] ?? $offer['price_retailer'] ?? 0 );
                $retailer_name  = $offer['retailer']['name'] ?? $offer['retailer_name'] ?? $offer['store_name'] ?? __( 'Unknown', 'shopsavvy' );
                $currency       = $offer['currency'] ?? $offer['price_currency'] ?? 'USD';
                $url            = $offer['url'] ?? $offer['offer_url'] ?? '#';
                $in_stock       = $offer['in_stock'] ?? $offer['availability'] ?? true;
                $condition      = $offer['condition'] ?? 'new';
                $shipping       = $offer['shipping'] ?? $offer['shipping_cost'] ?? null;

                if ( null === $lowest_price || $price < $lowest_price ) {
                    $lowest_price = $price;
                }

                $is_lowest   = ( $price === $lowest_price && 0 === $index );
                $row_classes = [ 'shopsavvy-offer-row' ];

                if ( $is_lowest ) {
                    $row_classes[] = 'shopsavvy-lowest-price';
                }

                if ( 'used' === strtolower( $condition ) || 'refurbished' === strtolower( $condition ) ) {
                    $row_classes[] = 'shopsavvy-condition-' . strtolower( $condition );
                }
            ?>
                <tr class="<?php echo esc_attr( implode( ' ', $row_classes ) ); ?>">
                    <td class="shopsavvy-col-retailer">
                        <span class="shopsavvy-retailer-name"><?php echo esc_html( $retailer_name ); ?></span>
                        <?php if ( 'new' !== strtolower( $condition ) ) : ?>
                            <span class="shopsavvy-condition"><?php echo esc_html( ucfirst( $condition ) ); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="shopsavvy-col-price">
                        <span class="shopsavvy-price">
                            <?php echo esc_html( shopsavvy_format_price( $price, $currency ) ); ?>
                        </span>
                        <?php if ( $is_lowest ) : ?>
                            <span class="shopsavvy-badge-lowest"><?php esc_html_e( 'Lowest', 'shopsavvy' ); ?></span>
                        <?php endif; ?>
                        <?php if ( $store_price > 0 && $price < $store_price ) : ?>
                            <span class="shopsavvy-savings">
                                <?php
                                $savings_pct = round( ( ( $store_price - $price ) / $store_price ) * 100 );
                                printf(
                                    /* translators: %d: savings percentage */
                                    esc_html__( '%d%% less', 'shopsavvy' ),
                                    $savings_pct
                                );
                                ?>
                            </span>
                        <?php endif; ?>
                        <?php if ( null !== $shipping ) : ?>
                            <span class="shopsavvy-shipping">
                                <?php
                                if ( 0 === (int) $shipping || 'free' === strtolower( (string) $shipping ) ) {
                                    esc_html_e( 'Free shipping', 'shopsavvy' );
                                } else {
                                    printf(
                                        /* translators: %s: shipping cost */
                                        esc_html__( '+%s shipping', 'shopsavvy' ),
                                        esc_html( shopsavvy_format_price( (float) $shipping, $currency ) )
                                    );
                                }
                                ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="shopsavvy-col-availability">
                        <?php if ( $in_stock ) : ?>
                            <span class="shopsavvy-in-stock"><?php esc_html_e( 'In Stock', 'shopsavvy' ); ?></span>
                        <?php else : ?>
                            <span class="shopsavvy-out-of-stock"><?php esc_html_e( 'Out of Stock', 'shopsavvy' ); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="shopsavvy-col-action">
                        <?php if ( '#' !== $url ) : ?>
                            <a href="<?php echo esc_url( $url ); ?>"
                               class="shopsavvy-view-btn"
                               target="_blank"
                               rel="noopener noreferrer nofollow">
                                <?php esc_html_e( 'View', 'shopsavvy' ); ?>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="shopsavvy-comparison-footer">
        <span class="shopsavvy-powered-by">
            <?php
            printf(
                /* translators: %s: ShopSavvy link */
                esc_html__( 'Prices by %s', 'shopsavvy' ),
                '<a href="https://shopsavvy.com" target="_blank" rel="noopener noreferrer">ShopSavvy</a>'
            );
            ?>
        </span>
        <?php if ( count( $offers ) > 0 ) : ?>
            <span class="shopsavvy-offer-count">
                <?php
                printf(
                    /* translators: %d: number of offers */
                    esc_html( _n( '%d offer', '%d offers', count( $offers ), 'shopsavvy' ) ),
                    count( $offers )
                );
                ?>
            </span>
        <?php endif; ?>
    </div>
</div>
