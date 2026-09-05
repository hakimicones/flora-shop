<div class="flora-shop-wrap">
    <div class="flora-shop-header-actions">
        <a href="<?php echo esc_url( get_permalink( FLORA_SHOP_PAGE_ID ) ); ?>" class="flora-header-cart-link">
            <span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Retour à la boutique', 'flora-shop' ); ?>
        </a>
        <a href="<?php echo esc_url( get_permalink( FLORA_CART_PAGE_ID ) ); ?>" class="flora-header-cart-link">
            <span class="dashicons dashicons-cart"></span> <?php esc_html_e( 'Mon panier', 'flora-shop' ); ?>
        </a>
    </div>

    <?php if ( $pack ) : ?>
        <div class="flora-product-detail flora-pack-detail" data-pack-id="<?php echo esc_attr( $pack->id ); ?>">
            <div class="flora-product-detail-image">
                <?php if ( $pack->image_url ) : ?>
                    <img src="<?php echo esc_url( $pack->image_url ); ?>" alt="<?php echo esc_attr( $pack->name ); ?>">
                <?php else : ?>
                    <div class="flora-product-placeholder">
                        <span class="dashicons dashicons-category"></span>
                    </div>
                <?php endif; ?>
                <span class="flora-badge-pack"><?php esc_html_e( 'PACK', 'flora-shop' ); ?></span>
            </div>

            <div class="flora-product-detail-info">
                <h1><?php echo esc_html( $pack->name ); ?></h1>
                <p class="flora-product-price"><?php echo esc_html( Flora_Helpers::format_price( $pack->pack_price ) ); ?></p>

                <?php if ( $pack->description ) : ?>
                    <div class="flora-product-detail-desc">
                        <?php echo wp_kses_post( wpautop( $pack->description ) ); ?>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $pack_products ) ) : ?>
                    <h3 class="flora-pack-detail-title"><?php esc_html_e( 'Contenu du pack', 'flora-shop' ); ?></h3>
                    <div class="flora-pack-accordion">
                        <?php foreach ( $pack_products as $pp ) :
                            $pp_prod = $db->get_product( $pp->product_id );
                            if ( ! $pp_prod ) {
                                continue;
                            }
                        ?>
                            <div class="flora-acc-item">
                                <button type="button" class="flora-acc-header" aria-expanded="false">
                                    <span class="flora-acc-header-media">
                                        <?php if ( $pp_prod->image_url ) : ?>
                                            <img src="<?php echo esc_url( $pp_prod->image_url ); ?>" alt="">
                                        <?php endif; ?>
                                    </span>
                                    <span class="flora-acc-header-name"><?php echo esc_html( $pp_prod->name ); ?></span>
                                    <span class="flora-acc-header-qty">× <?php echo esc_html( $pp->quantity ); ?></span>
                                    <span class="dashicons dashicons-arrow-down-alt2 flora-acc-icon"></span>
                                </button>
                                <div class="flora-acc-body" hidden>
                                    <div class="flora-acc-media">
                                        <?php if ( $pp_prod->image_url ) : ?>
                                            <img src="<?php echo esc_url( $pp_prod->image_url ); ?>" alt="<?php echo esc_attr( $pp_prod->name ); ?>">
                                        <?php else : ?>
                                            <div class="flora-product-placeholder">
                                                <span class="dashicons dashicons-products"></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flora-acc-desc">
                                        <?php echo wp_kses_post( wpautop( $pp_prod->description ) ); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $pack_promotions ) ) : ?>
                    <h3 class="flora-pack-detail-title flora-pack-promo-title"><?php esc_html_e( 'Promotions', 'flora-shop' ); ?></h3>
                    <ul class="flora-pack-promotions">
                        <?php foreach ( $pack_promotions as $promo ) : ?>
                            <li class="<?php echo $promo->is_free ? 'flora-promo-free' : ''; ?>">
                                <span class="dashicons <?php echo $promo->is_free ? 'dashicons-gift' : 'dashicons-megaphone'; ?>"></span>
                                <span><?php echo esc_html( $promo->title ); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <div class="flora-pack-recap" id="flora-recap">
                    <h3 class="flora-pack-detail-title"><?php esc_html_e( 'Récapitulatif des prix', 'flora-shop' ); ?></h3>

                    <div class="flora-product-actions">
                        <div class="flora-qty-control">
                            <button type="button" class="flora-qty-minus" data-index="pack-<?php echo esc_attr( $pack->id ); ?>">−</button>
                            <input type="number" class="flora-qty-input" id="qty-pack-<?php echo esc_attr( $pack->id ); ?>" value="1" min="1">
                            <button type="button" class="flora-qty-plus" data-index="pack-<?php echo esc_attr( $pack->id ); ?>">+</button>
                        </div>
                        <button type="button" class="button button-primary flora-add-to-cart"
                            data-type="pack"
                            data-id="<?php echo esc_attr( $pack->id ); ?>"
                            data-qty-input="qty-pack-<?php echo esc_attr( $pack->id ); ?>">
                            <?php esc_html_e( 'Ajouter au panier', 'flora-shop' ); ?>
                        </button>
                    </div>

                    <table class="flora-recap-table">
                        <tr>
                            <td><?php esc_html_e( 'Prix unitaire', 'flora-shop' ); ?></td>
                            <td data-cell="unit" class="flora-recap-unit"><?php echo esc_html( Flora_Helpers::format_price( $pack->pack_price ) ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Sous-total', 'flora-shop' ); ?></td>
                            <td data-cell="subtotal" class="flora-recap-subtotal"><?php echo esc_html( Flora_Helpers::format_price( $pack->pack_price ) ); ?></td>
                        </tr>
                        <tbody data-cell="promos"></tbody>
                        <tr class="flora-recap-total-row">
                            <td><strong><?php esc_html_e( 'Total', 'flora-shop' ); ?></strong></td>
                            <td data-cell="total" class="flora-recap-total"><strong><?php echo esc_html( Flora_Helpers::format_price( $pack->pack_price ) ); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <script type="application/json" id="flora-recap-data"><?php echo wp_json_encode( array(
            'unit_price' => (float) $pack->pack_price,
            'promotions' => $promo_config,
        ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE ); ?></script>
    <?php else : ?>
        <p class="flora-product-not-found"><?php esc_html_e( 'Pack introuvable.', 'flora-shop' ); ?></p>
    <?php endif; ?>

    <div id="flora-cart-notification" class="flora-notification" style="display:none;"></div>
</div>