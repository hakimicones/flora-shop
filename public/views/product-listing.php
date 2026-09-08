<!--
    Grille de la boutique (shortcode [flora_products]).
    Affiche les produits puis les packs sous forme de cartes : image, nom,
    prix, courte description, contenu (packs) et ajout direct au panier.
-->
<div class="flora-shop-wrap">
    <!-- En-tête : titre de la section et lien vers le panier. -->
    <h2><?php esc_html_e( 'Nos Produits', 'flora-shop' ); ?></h2>

    <div class="flora-shop-header-actions">
        <a href="<?php echo esc_url( Flora_Helpers::get_page_url( 'cart' ) ); ?>" class="flora-header-cart-link flora-cart-open">
            <span class="dashicons dashicons-cart"></span> Mon panier
            <span class="flora-cart-count" style="display:none;">0</span>
        </a>
    </div>

    <!-- Grille des produits : chaque carte pointe vers la fiche produit et permet l'ajout au panier. -->
    <?php if ( ! empty( $products ) ) : ?>
        <div class="flora-products-grid">
            <?php foreach ( $products as $p ) :
                    $product_detail_url = add_query_arg( 'flora_product', $p->slug, Flora_Helpers::get_page_url( 'product' ) );
                ?>
                <div class="flora-product-card" data-product-id="<?php echo esc_attr( $p->id ); ?>">
                    <a href="<?php echo esc_url( $product_detail_url ); ?>" class="flora-product-image-link">
                        <div class="flora-product-image">
                            <?php if ( $p->image_url ) : ?>
                                <img src="<?php echo esc_url( $p->image_url ); ?>" alt="<?php echo esc_attr( $p->name ); ?>">
                            <?php else : ?>
                                <div class="flora-product-placeholder">
                                    <span class="dashicons dashicons-format-image"></span>
                                </div>
                            <?php endif; ?>
                            <?php if ( $p->stock_status === 'outofstock' ) : ?>
                                <span class="flora-badge-outofstock"><?php esc_html_e( 'Rupture de stock', 'flora-shop' ); ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="flora-product-info">
                        <h3><a href="<?php echo esc_url( $product_detail_url ); ?>" class="flora-product-title-link"><?php echo esc_html( $p->name ); ?></a></h3>
                        <p class="flora-product-price"><?php echo esc_html( Flora_Helpers::format_price( $p->price ) ); ?></p>
                        <?php if ( $p->description ) : ?>
                            <p class="flora-product-desc"><?php echo esc_html( wp_trim_words( $p->description, 20 ) ); ?></p>
                        <?php endif; ?>
                        <?php if ( $p->stock_status !== 'outofstock' ) : ?>
                            <div class="flora-product-actions">
                                <div class="flora-qty-control">
                                    <button type="button" class="flora-qty-minus" data-index="product-<?php echo esc_attr( $p->id ); ?>">−</button>
                                    <input type="number" class="flora-qty-input" id="qty-product-<?php echo esc_attr( $p->id ); ?>" value="1" min="1" max="<?php echo esc_attr( $p->stock_qty ); ?>">
                                    <button type="button" class="flora-qty-plus" data-index="product-<?php echo esc_attr( $p->id ); ?>">+</button>
                                </div>
                                <button type="button" class="button button-primary flora-add-to-cart"
                                    data-type="product"
                                    data-id="<?php echo esc_attr( $p->id ); ?>"
                                    data-qty-input="qty-product-<?php echo esc_attr( $p->id ); ?>">
                                    <?php esc_html_e( 'Ajouter au panier', 'flora-shop' ); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p><?php esc_html_e( 'Aucun produit disponible pour le moment.', 'flora-shop' ); ?></p>
    <?php endif; ?>

    <!-- Grille des packs : la carte liste le contenu du pack et autorise l'ajout direct au panier. -->
    <?php if ( ! empty( $packs ) ) : ?>
        <h2 class="flora-section-title"><?php esc_html_e( 'Nos Packs', 'flora-shop' ); ?></h2>
        <div class="flora-products-grid">
            <?php foreach ( $packs as $pk ) :
                    $pack_detail_url = add_query_arg( 'flora_pack', $pk->slug, Flora_Helpers::get_page_url( 'product' ) );
                    $pack_products_list = $db->get_pack_products( $pk->id );
                ?>
                <div class="flora-product-card flora-pack-card" data-pack-id="<?php echo esc_attr( $pk->id ); ?>">
                    <a href="<?php echo esc_url( $pack_detail_url ); ?>" class="flora-product-image-link">
                        <div class="flora-product-image">
                            <?php if ( $pk->image_url ) : ?>
                                <img src="<?php echo esc_url( $pk->image_url ); ?>" alt="<?php echo esc_attr( $pk->name ); ?>">
                            <?php else : ?>
                                <div class="flora-product-placeholder">
                                    <span class="dashicons dashicons-dashicons-category"></span>
                                </div>
                            <?php endif; ?>
                            <span class="flora-badge-pack"><?php esc_html_e( 'PACK', 'flora-shop' ); ?></span>
                        </div>
                    </a>
                    <div class="flora-product-info">
                        <h3><a href="<?php echo esc_url( $pack_detail_url ); ?>" class="flora-product-title-link"><?php echo esc_html( $pk->name ); ?></a></h3>
                        <p class="flora-product-price"><?php echo esc_html( Flora_Helpers::format_price( $pk->pack_price ) ); ?></p>
                        <?php if ( $pk->description ) : ?>
                            <p class="flora-product-desc"><?php echo esc_html( wp_trim_words( $pk->description, 20 ) ); ?></p>
                        <?php endif; ?>
                        <?php if ( ! empty( $pack_products_list ) ) : ?>
                            <ul class="flora-pack-contents">
                                <?php foreach ( $pack_products_list as $pp ) :
                                    $pp_prod = $db->get_product( $pp->product_id );
                                    if ( $pp_prod ) :
                                ?>
                                    <li><?php echo esc_html( $pp_prod->name ); ?> × <?php echo esc_html( $pp->quantity ); ?></li>
                                <?php
                                    endif;
                                endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <div class="flora-product-actions">
                            <div class="flora-qty-control">
                                <button type="button" class="flora-qty-minus" data-index="pack-<?php echo esc_attr( $pk->id ); ?>">−</button>
                                <input type="number" class="flora-qty-input" id="qty-pack-<?php echo esc_attr( $pk->id ); ?>" value="1" min="1">
                                <button type="button" class="flora-qty-plus" data-index="pack-<?php echo esc_attr( $pk->id ); ?>">+</button>
                            </div>
                            <button type="button" class="button button-primary flora-add-to-cart"
                                data-type="pack"
                                data-id="<?php echo esc_attr( $pk->id ); ?>"
                                data-qty-input="qty-pack-<?php echo esc_attr( $pk->id ); ?>">
                                <?php esc_html_e( 'Ajouter au panier', 'flora-shop' ); ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div id="flora-cart-notification" class="flora-notification" style="display:none;"></div>
</div>
