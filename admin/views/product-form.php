<?php /* Vue du formulaire d'ajout / modification d'un produit : champs d'information et actions d'enregistrement. */ ?>

<div class="wrap flora-admin">
    <h1><?php echo $product ? esc_html__( 'Modifier le produit', 'flora-shop' ) : esc_html__( 'Ajouter un produit', 'flora-shop' ); ?></h1>

    <?php /* --- Formulaire d'enregistrement : l'action "save" est traitée côté serveur --- */ ?>
    <form method="post" action="">
        <?php Flora_Helpers::wpnonce_field( 'flora_save_product' ); ?>
        <input type="hidden" name="flora_action" value="save">
        <?php if ( $product ) : ?>
            <input type="hidden" name="product_id" value="<?php echo esc_attr( $product->id ); ?>">
        <?php endif; ?>

        <?php /* --- Champs du produit (nom, description, prix, poids, image, stock, tri, statut) --- */ ?>
        <table class="form-table">
            <tr>
                <th><label for="name"><?php esc_html_e( 'Nom du produit', 'flora-shop' ); ?> *</label></th>
                <td><input type="text" id="name" name="name" class="regular-text" required value="<?php echo $product ? esc_attr( $product->name ) : ''; ?>"></td>
            </tr>
            <tr>
                <th><label for="description"><?php esc_html_e( 'Description', 'flora-shop' ); ?></label></th>
                <td><textarea id="description" name="description" class="large-text" rows="5"><?php echo $product ? esc_textarea( $product->description ) : ''; ?></textarea></td>
            </tr>
            <tr>
                <th><label for="price"><?php esc_html_e( 'Prix', 'flora-shop' ); ?> *</label></th>
                <td><input type="number" step="0.01" id="price" name="price" class="small-text" required value="<?php echo $product ? esc_attr( $product->price ) : '0.00'; ?>"></td>
            </tr>
            <tr>
                <th><label for="weight"><?php esc_html_e( 'Poids (kg)', 'flora-shop' ); ?></label></th>
                <td><input type="number" step="0.01" id="weight" name="weight" class="small-text" value="<?php echo $product ? esc_attr( $product->weight ) : '0.00'; ?>"></td>
            </tr>
            <tr>
                <th><label for="image_url"><?php esc_html_e( 'URL de l\'image', 'flora-shop' ); ?></label></th>
                <td>
                    <input type="url" id="image_url" name="image_url" class="large-text" value="<?php echo $product ? esc_url( $product->image_url ) : ''; ?>">
                    <button type="button" class="button flora-upload-btn" data-target="image_url"><?php esc_html_e( 'Choisir une image', 'flora-shop' ); ?></button>
                </td>
            </tr>
            <tr>
                <th><label for="stock_qty"><?php esc_html_e( 'Quantité en stock', 'flora-shop' ); ?></label></th>
                <td><input type="number" id="stock_qty" name="stock_qty" class="small-text" value="<?php echo $product ? esc_attr( $product->stock_qty ) : '0'; ?>"></td>
            </tr>
            <tr>
                <th><label for="sort_order"><?php esc_html_e( 'Ordre de tri', 'flora-shop' ); ?></label></th>
                <td><input type="number" id="sort_order" name="sort_order" class="small-text" value="<?php echo $product ? esc_attr( $product->sort_order ) : '0'; ?>"></td>
            </tr>
            <tr>
                <th><label for="status"><?php esc_html_e( 'Statut', 'flora-shop' ); ?></label></th>
                <td>
                    <select id="status" name="status">
                        <option value="publish" <?php selected( $product ? $product->status : '', 'publish' ); ?>><?php esc_html_e( 'Publié', 'flora-shop' ); ?></option>
                        <option value="draft" <?php selected( $product ? $product->status : '', 'draft' ); ?>><?php esc_html_e( 'Brouillon', 'flora-shop' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="category_id"><?php esc_html_e( 'Catégorie', 'flora-shop' ); ?></label></th>
                <td>
                    <select id="category_id" name="category_id">
                        <option value="0"><?php esc_html_e( '— Aucune —', 'flora-shop' ); ?></option>
                        <?php foreach ( $all_categories as $cat ) : ?>
                            <option value="<?php echo esc_attr( $cat->id ); ?>" <?php selected( $product ? $product->category_id : 0, $cat->id ); ?>><?php echo esc_html( $cat->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Étiquettes', 'flora-shop' ); ?></th>
                <td>
                    <?php if ( empty( $all_tags ) ) : ?>
                        <p class="description"><?php esc_html_e( 'Aucune étiquette disponible. Créez-en dans le menu « Étiquettes ».', 'flora-shop' ); ?></p>
                    <?php else : ?>
                        <?php foreach ( $all_tags as $tag ) : ?>
                            <label style="display:inline-block; margin-right:12px;">
                                <input type="checkbox" name="tags[]" value="<?php echo esc_attr( $tag->id ); ?>" <?php checked( in_array( $tag->id, $product_tags, true ) ); ?>>
                                <?php echo esc_html( $tag->name ); ?>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <?php /* --- Traductions : nom et description par langue secondaire (optionnel) --- */ ?>
        <h2><?php esc_html_e( 'Traductions', 'flora-shop' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Ces champs affichent le produit dans les langues secondaires de la boutique. La langue par défaut est celle des champs principaux.', 'flora-shop' ); ?></p>
        <?php if ( empty( Flora_Helpers::secondary_languages() ) ) : ?>
            <p class="description"><?php esc_html_e( 'Aucune langue secondaire configurée dans les paramètres du plugin.', 'flora-shop' ); ?></p>
        <?php else : ?>
            <table class="form-table">
                <?php foreach ( Flora_Helpers::secondary_languages() as $code => $label ) : ?>
                    <?php $tr = isset( $translations[ $code ] ) ? $translations[ $code ] : null; ?>
                    <tr>
                        <th colspan="2" style="padding-bottom:0;">
                            <h3 style="margin:12px 0 4px;"><?php echo esc_html( $label ); ?> (<?php echo esc_html( $code ); ?>)</h3>
                        </th>
                    </tr>
                    <tr>
                        <th><label for="translations_<?php echo esc_attr( $code ); ?>_name"><?php esc_html_e( 'Nom traduit', 'flora-shop' ); ?></label></th>
                        <td><input type="text" id="translations_<?php echo esc_attr( $code ); ?>_name" name="translations[<?php echo esc_attr( $code ); ?>][name]" class="regular-text" value="<?php echo $tr ? esc_attr( $tr->name ) : ''; ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="translations_<?php echo esc_attr( $code ); ?>_description"><?php esc_html_e( 'Description traduite', 'flora-shop' ); ?></label></th>
                        <td><textarea id="translations_<?php echo esc_attr( $code ); ?>_description" name="translations[<?php echo esc_attr( $code ); ?>][description]" class="large-text" rows="5"><?php echo $tr ? esc_textarea( $tr->description ) : ''; ?></textarea></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <?php /* --- Boutons d'enregistrement et de retour à la liste --- */ ?>
        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Enregistrer le produit', 'flora-shop' ); ?></button>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-products' ) ); ?>" class="button"><?php esc_html_e( 'Annuler', 'flora-shop' ); ?></a>
        </p>
    </form>
</div>
