<!--
    Fiche produit (shortcode [flora_product]).
    Affiche l'image, les informations, les promotions et le récapitulatif de
    prix du produit demandé via le paramètre « flora_product ». Le récapitulatif
    est mis à jour par cart.js à partir du JSON #flora-recap-data.
-->
<div class="flora-shop-wrap">
    <!-- En-tête : retour à la boutique et lien vers le panier. -->
    <div class="flora-shop-header-actions">
        <a href="<?php echo esc_url( Flora_Helpers::get_page_url( 'shop' ) ); ?>" class="flora-header-cart-link">
            <span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Retour à la boutique', 'flora-shop' ); ?>
        </a>
        <a href="<?php echo esc_url( Flora_Helpers::get_page_url( 'cart' ) ); ?>" class="flora-header-cart-link flora-cart-open">
            <span class="dashicons dashicons-cart"></span> <?php echo esc_html( Flora_Helpers::cart_button_label() ); ?>
            <span class="flora-cart-count" style="display:none;">0</span>
        </a>
    </div>

    <?php if ( $product ) : ?>
        <!-- Galerie / image du produit, avec placeholder et badge de rupture de stock le cas échéant. -->
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

            <!-- Informations : nom, prix, description, disponibilité et commande (quantité + ajout au panier). -->
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
                            <?php echo esc_html( Flora_Helpers::add_to_cart_label() ); ?>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Promotions actives appliquées à ce produit (configurées en back-office). -->
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

                <!-- Récapitulatif des prix : unitaire, sous-total, promotions et total (recalculé en JS). -->
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

        <!-- Données JSON de calcul (prix unitaire + config des promotions) consommées par cart.js pour la mise à jour du récapitulatif. -->
        <script type="application/json" id="flora-recap-data"><?php echo wp_json_encode( array(
            'unit_price' => (float) $product->price,
            'promotions' => $promo_config,
        ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE ); ?></script>
    <?php else : ?>
        <p class="flora-product-not-found"><?php esc_html_e( 'Produit introuvable.', 'flora-shop' ); ?></p>
    <?php endif; ?>

    <div id="flora-cart-notification" class="flora-notification" style="display:none;"></div>
</div>