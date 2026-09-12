<?php /* Vue des paramètres du plugin : devise, seuil de livraison gratuite, pages de la boutique et remises sur panier. */ ?>

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
                <th><label for="grid_columns"><?php esc_html_e( 'Colonnes de la grille', 'flora-shop' ); ?></label></th>
                <td>
                    <select id="grid_columns" name="grid_columns">
                        <?php for ( $i = 2; $i <= 5; $i++ ) : ?>
                            <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $grid_columns, $i ); ?>><?php echo esc_html( $i ); ?></option>
                        <?php endfor; ?>
                    </select>
                    <p class="description"><?php esc_html_e( 'Nombre de colonnes de la grille produits sur ordinateur (mobile : toujours 2 colonnes).', 'flora-shop' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="cart_button_fr"><?php esc_html_e( 'Bouton panier (Français)', 'flora-shop' ); ?></label></th>
                <td>
                    <input type="text" id="cart_button_fr" name="cart_button_fr" class="regular-text" value="<?php echo esc_attr( $cart_button_fr ); ?>">
                    <p class="description"><?php esc_html_e( 'Libellé du lien panier affiché en français dans la boutique.', 'flora-shop' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="cart_button_ar"><?php esc_html_e( 'Bouton panier (Arabe)', 'flora-shop' ); ?></label></th>
                <td>
                    <input type="text" id="cart_button_ar" name="cart_button_ar" class="regular-text" value="<?php echo esc_attr( $cart_button_ar ); ?>">
                    <p class="description"><?php esc_html_e( 'Libellé du lien panier affiché en arabe dans la boutique.', 'flora-shop' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="add_to_cart_fr"><?php esc_html_e( 'Ajouter au panier (Français)', 'flora-shop' ); ?></label></th>
                <td>
                    <input type="text" id="add_to_cart_fr" name="add_to_cart_fr" class="regular-text" value="<?php echo esc_attr( $add_to_cart_fr ); ?>">
                    <p class="description"><?php esc_html_e( 'Libellé du bouton d\'ajout au panier affiché en français.', 'flora-shop' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="add_to_cart_ar"><?php esc_html_e( 'Ajouter au panier (Arabe)', 'flora-shop' ); ?></label></th>
                <td>
                    <input type="text" id="add_to_cart_ar" name="add_to_cart_ar" class="regular-text" value="<?php echo esc_attr( $add_to_cart_ar ); ?>">
                    <p class="description"><?php esc_html_e( 'Libellé du bouton d\'ajout au panier affiché en arabe.', 'flora-shop' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="discount_mode"><?php esc_html_e( 'Mode de remise', 'flora-shop' ); ?></label></th>
                <td>
                    <select id="discount_mode" name="discount_mode">
                        <option value="promo_only" <?php selected( $discount_mode, 'promo_only' ); ?>><?php esc_html_e( 'Promotions seulement (ignorer les remises % si une promo s\'applique)', 'flora-shop' ); ?></option>
                        <option value="all" <?php selected( $discount_mode, 'all' ); ?>><?php esc_html_e( 'Tout cumuler (promotions + remises %)', 'flora-shop' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'En mode « Promotions seulement », une remise % réglée ici est ignorée dès qu\'une promotion produit/pack s\'applique au panier.', 'flora-shop' ); ?></p>
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

        <?php /* --- Langues : activées, libellés et langue par défaut de la boutique --- */ ?>
        <h2><?php esc_html_e( 'Langues', 'flora-shop' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Chaque langue secondaire peut traduire le nom et la description des produits et packs. La langue par défaut correspond aux données saisies dans les fiches.', 'flora-shop' ); ?></p>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:100px;"><?php esc_html_e( 'Activée', 'flora-shop' ); ?></th>
                    <th style="width:150px;"><?php esc_html_e( 'Code', 'flora-shop' ); ?></th>
                    <th><?php esc_html_e( 'Libellé', 'flora-shop' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $languages as $i => $lang ) : ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="languages[<?php echo esc_attr( $i ); ?>][enabled]" value="1" <?php checked( empty( $lang['enabled'] ) || (int) $lang['enabled'] === 1 ); ?>>
                        </td>
                        <td><input type="text" name="languages[<?php echo esc_attr( $i ); ?>][code]" class="small-text" value="<?php echo esc_attr( isset( $lang['code'] ) ? $lang['code'] : '' ); ?>"></td>
                        <td><input type="text" name="languages[<?php echo esc_attr( $i ); ?>][label]" class="regular-text" value="<?php echo esc_attr( isset( $lang['label'] ) ? $lang['label'] : '' ); ?>"></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <table class="form-table">
            <tr>
                <th><label for="default_language"><?php esc_html_e( 'Langue par défaut', 'flora-shop' ); ?></label></th>
                <td>
                    <select id="default_language" name="default_language">
                        <?php foreach ( Flora_Helpers::flora_languages() as $lang_code => $lang_label ) : ?>
                            <option value="<?php echo esc_attr( $lang_code ); ?>" <?php selected( $default_language, $lang_code ); ?>><?php echo esc_html( $lang_label ); ?> (<?php echo esc_html( $lang_code ); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e( 'Langue des champs principaux des fiches produits/packs (elle n\'est pas traduisible).', 'flora-shop' ); ?></p>
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
