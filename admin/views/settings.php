<?php /* Vue des paramètres du plugin : devise, seuil de livraison gratuite, pages de la boutique et règles de remises sur produits / panier. */ ?>

<div class="wrap flora-admin">
    <h1><?php esc_html_e( 'Paramètres Flora Shop', 'flora-shop' ); ?></h1>

    <?php /* --- Formulaire général de sauvegarde des paramètres --- */ ?>
    <form method="post" action="">
        <?php Flora_Helpers::wpnonce_field( 'flora_save_settings' ); ?>
        <input type="hidden" name="flora_action" value="save_settings">

        <?php /* --- Réglages généraux : devise et seuil de livraison gratuite --- */ ?>
        <h2><?php esc_html_e( 'Général', 'flora-shop' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="currency"><?php esc_html_e( 'Devise', 'flora-shop' ); ?></label></th>
                <td><input type="text" id="currency" name="currency" class="small-text" value="<?php echo esc_attr( $currency ); ?>"></td>
            </tr>
            <tr>
                <th><label for="free_shipping_threshold"><?php esc_html_e( 'Seuil livraison gratuite', 'flora-shop' ); ?></label></th>
                <td>
                    <input type="number" step="0.01" id="free_shipping_threshold" name="free_shipping_threshold" class="small-text" value="<?php echo esc_attr( $free_shipping_threshold ); ?>">
                    <p class="description"><?php esc_html_e( 'Montant du sous-total pour bénéficier de la livraison gratuite (0 = désactivé).', 'flora-shop' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Confirmation', 'flora-shop' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="show_order_email" value="1" <?php checked( $show_order_email ); ?>>
                        <?php esc_html_e( 'Afficher l\'email du client sur la page de confirmation', 'flora-shop' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="custom_css"><?php esc_html_e( 'CSS personnalisé', 'flora-shop' ); ?></label></th>
                <td>
                    <textarea id="custom_css" name="custom_css" class="large-text code" rows="10" placeholder="<?php esc_attr_e( '/* Votre CSS ici */', 'flora-shop' ); ?>"><?php echo esc_textarea( $custom_css ); ?></textarea>
                    <p class="description"><?php esc_html_e( 'CSS injecté sur les pages de la boutique (après les styles du plugin).', 'flora-shop' ); ?></p>
                </td>
            </tr>
        </table>

        <?php /* --- Pages de la boutique : association de chaque étape à une page WordPress existante --- */ ?>
        <h2><?php esc_html_e( 'Pages', 'flora-shop' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Sélectionnez la page associée à chaque étape de la boutique. Ces sélections remplacent les pages créées automatiquement.', 'flora-shop' ); ?></p>
        <?php $all_pages_list = Flora_Helpers::get_all_pages(); ?>
        <table class="form-table">
            <?php foreach ( Flora_Helpers::flora_pages() as $pkey => $pinfo ) : ?>
                <tr>
                    <th><label for="flora_page_<?php echo esc_attr( $pkey ); ?>"><?php echo esc_html( $pinfo['title'] ); ?></label></th>
                    <td>
                        <select name="<?php echo esc_attr( 'flora_page[' . $pkey . ']' ); ?>" id="<?php echo esc_attr( 'flora_page_' . $pkey ); ?>">
                            <option value="0"><?php esc_html_e( '— Aucune page —', 'flora-shop' ); ?></option>
                            <?php echo walk_page_dropdown_tree( $all_pages_list, 0, array(
                                'selected'    => $flora_pages[ $pkey ],
                                'value_field' => 'ID',
                            ) ); ?>
                        </select>
                        <p class="description"><?php echo esc_html( sprintf( __( 'Slug : %s — Shortcode : %s', 'flora-shop' ), $pinfo['slug'], $pinfo['shortcode'] ) ); ?></p>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <?php /* --- Règles de remise par produit (tableau de lignes dynamiques) --- */ ?>
        <h2><?php esc_html_e( 'Remises sur produits', 'flora-shop' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Appliquer un pourcentage de remise sur un produit spécifique si la quantité est supérieure ou égale au seuil.', 'flora-shop' ); ?></p>
        <table class="wp-list-table widefat fixed striped" id="flora-product-discounts">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Produit', 'flora-shop' ); ?></th>
                    <th style="width:150px;"><?php esc_html_e( 'Quantité min.', 'flora-shop' ); ?></th>
                    <th style="width:150px;"><?php esc_html_e( 'Remise (%)', 'flora-shop' ); ?></th>
                    <th style="width:80px;"><?php esc_html_e( 'Action', 'flora-shop' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $product_discounts ) ) : ?>
                    <?php foreach ( $product_discounts as $i => $pd ) : ?>
                        <tr>
                            <td>
                                <select name="product_discounts[<?php echo esc_attr( $i ); ?>][product_id]" class="regular-text">
                                    <option value=""><?php esc_html_e( '-- Choisir --', 'flora-shop' ); ?></option>
                                    <?php foreach ( $all_products as $p ) : ?>
                                        <option value="<?php echo esc_attr( $p->id ); ?>" <?php selected( $pd['product_id'], $p->id ); ?>><?php echo esc_html( $p->name ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="number" name="product_discounts[<?php echo esc_attr( $i ); ?>][min_qty]" class="small-text" min="1" value="<?php echo esc_attr( $pd['min_qty'] ); ?>"></td>
                            <td><input type="number" name="product_discounts[<?php echo esc_attr( $i ); ?>][percent]" class="small-text" min="1" max="100" value="<?php echo esc_attr( $pd['percent'] ); ?>"></td>
                            <td><button type="button" class="button flora-remove-row"><?php esc_html_e( 'Supprimer', 'flora-shop' ); ?></button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <p><button type="button" class="button" id="flora-add-product-discount"><?php esc_html_e( '+ Ajouter une règle', 'flora-shop' ); ?></button></p>

        <?php /* --- Règles de remise sur panier (tableau de lignes dynamiques) --- */ ?>
        <h2><?php esc_html_e( 'Remises sur panier', 'flora-shop' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Appliquer un pourcentage de remise sur le sous-total du panier si le montant atteint un seuil.', 'flora-shop' ); ?></p>
        <table class="wp-list-table widefat fixed striped" id="flora-cart-discounts">
            <thead>
                <tr>
                    <th style="width:200px;"><?php esc_html_e( 'Sous-total minimum', 'flora-shop' ); ?></th>
                    <th style="width:200px;"><?php esc_html_e( 'Remise (%)', 'flora-shop' ); ?></th>
                    <th style="width:80px;"><?php esc_html_e( 'Action', 'flora-shop' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $cart_discounts ) ) : ?>
                    <?php foreach ( $cart_discounts as $i => $cd ) : ?>
                        <tr>
                            <td><input type="number" step="0.01" name="cart_discounts[<?php echo esc_attr( $i ); ?>][min_total]" class="regular-text" value="<?php echo esc_attr( $cd['min_total'] ); ?>"></td>
                            <td><input type="number" name="cart_discounts[<?php echo esc_attr( $i ); ?>][percent]" class="small-text" min="1" max="100" value="<?php echo esc_attr( $cd['percent'] ); ?>"></td>
                            <td><button type="button" class="button flora-remove-row"><?php esc_html_e( 'Supprimer', 'flora-shop' ); ?></button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <p><button type="button" class="button" id="flora-add-cart-discount"><?php esc_html_e( '+ Ajouter une règle', 'flora-shop' ); ?></button></p>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Enregistrer les paramètres', 'flora-shop' ); ?></button>
        </p>
    </form>
</div>
