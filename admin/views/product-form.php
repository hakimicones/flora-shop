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
        </table>

        <?php /* --- Boutons d'enregistrement et de retour à la liste --- */ ?>
        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Enregistrer le produit', 'flora-shop' ); ?></button>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-products' ) ); ?>" class="button"><?php esc_html_e( 'Annuler', 'flora-shop' ); ?></a>
        </p>
    </form>
</div>
