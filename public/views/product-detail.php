<div class="flora-shop-wrap">
    <div class="flora-shop-header-actions">
        <a href="<?php echo esc_url( get_permalink( FLORA_SHOP_PAGE_ID ) ); ?>" class="flora-header-cart-link">
            <span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Retour à la boutique', 'flora-shop' ); ?>
        </a>
        <a href="<?php echo esc_url( get_permalink( FLORA_CART_PAGE_ID ) ); ?>" class="flora-header-cart-link">
            <span class="dashicons dashicons-cart"></span> <?php esc_html_e( 'Mon panier', 'flora-shop' ); ?>
        </a>
    </div>

    <?php if ( $product ) : ?>
        <div class="flora-product-detail" data-product-id="<?php echo esc_attr( $product->id ); ?>">
            <div class="flora-product-detail-image">
                <?php if ( $product->image_url ) : ?>
                    <img src="<?php echo esc_url( $product->image_url ); ?>" alt="<?php echo esc_attr( $product->name ); ?>">
                <?php else : ?>
                    <div class="flora-product-placeholder">
                        <span class="dashicons dashicons-format-image"></span>
                    </div>
                <?php endif; ?>
                <?php if ( $product->stock_status === 'outofstock' ) : ?>
                    <span class="flora-badge-outofstock"><?php esc_html_e( 'Rupture de stock', 'flora-shop' ); ?></span>
                <?php endif; ?>
            </div>

            <div class="flora-product-detail-info">
                <h1><?php echo esc_html( $product->name ); ?></h1>
                <p class="flora-product-price"><?php echo esc_html( Flora_Helpers::format_price( $product->price ) ); ?></p>

                <?php if ( $product->description ) : ?>
                    <div class="flora-product-detail-desc">
                        <?php echo wp_kses_post( wpautop( $product->description ) ); ?>
                    </div>
                <?php endif; ?>

                <?php if ( $product->stock_qty > 0 && $product->stock_status !== 'outofstock' ) : ?>
                    <p class="flora-stock-info"><?php echo esc_html( sprintf( __( 'En stock : %d', 'flora-shop' ), $product->stock_qty ) ); ?></p>
                <?php elseif ( $product->stock_status !== 'outofstock' ) : ?>
                    <p class="flora-stock-info"><?php esc_html_e( 'Disponible', 'flora-shop' ); ?></p>
                <?php endif; ?>

                <?php if ( $product->stock_status !== 'outofstock' ) : ?>
                    <div class="flora-product-actions">
                        <div class="flora-qty-control">
                            <button type="button" class="flora-qty-minus" data-index="product-<?php echo esc_attr( $product->id ); ?>">−</button>
                            <input type="number" class="flora-qty-input" id="qty-product-<?php echo esc_attr( $product->id ); ?>" value="1" min="1" max="<?php echo esc_attr( $product->stock_qty ); ?>">
                            <button type="button" class="flora-qty-plus" data-index="product-<?php echo esc_attr( $product->id ); ?>">+</button>
                        </div>
                        <button type="button" class="button button-primary flora-add-to-cart"
                            data-type="product"
                            data-id="<?php echo esc_attr( $product->id ); ?>"
                            data-qty-input="qty-product-<?php echo esc_attr( $product->id ); ?>">
                            <?php esc_html_e( 'Ajouter au panier', 'flora-shop' ); ?>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $product_promotions ) ) : ?>
                    <h3 class="flora-pack-detail-title flora-pack-promo-title"><?php esc_html_e( 'Promotions', 'flora-shop' ); ?></h3>
                    <ul class="flora-pack-promotions">
                        <?php foreach ( $product_promotions as $promo ) : ?>
                            <li class="<?php echo $promo->is_free ? 'flora-promo-free' : ''; ?>">
                                <span class="dashicons <?php echo $promo->is_free ? 'dashicons-gift' : 'dashicons-megaphone'; ?>"></span>
                                <span><?php echo esc_html( $promo->title ); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if ( $product->stock_status !== 'outofstock' ) : ?>
                    <div class="flora-pack-recap" id="flora-recap">
                        <h3 class="flora-pack-detail-title"><?php esc_html_e( 'Récapitulatif des prix', 'flora-shop' ); ?></h3>

                        <table class="flora-recap-table">
                            <tr>
                                <td><?php esc_html_e( 'Prix unitaire', 'flora-shop' ); ?></td>
                                <td data-cell="unit" class="flora-recap-unit"><?php echo esc_html( Flora_Helpers::format_price( $product->price ) ); ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e( 'Sous-total', 'flora-shop' ); ?></td>
                                <td data-cell="subtotal" class="flora-recap-subtotal"><?php echo esc_html( Flora_Helpers::format_price( $product->price ) ); ?></td>
                            </tr>
                            <tbody data-cell="promos"></tbody>
                            <tr class="flora-recap-total-row">
                                <td><strong><?php esc_html_e( 'Total', 'flora-shop' ); ?></strong></td>
                                <td data-cell="total" class="flora-recap-total"><strong><?php echo esc_html( Flora_Helpers::format_price( $product->price ) ); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script type="application/json" id="flora-recap-data"><?php echo wp_json_encode( array(
            'unit_price' => (float) $product->price,
            'promotions' => $promo_config,
        ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE ); ?></script>
    <?php else : ?>
        <p class="flora-product-not-found"><?php esc_html_e( 'Produit introuvable.', 'flora-shop' ); ?></p>
    <?php endif; ?>

    <div id="flora-cart-notification" class="flora-notification" style="display:none;"></div>
</div>