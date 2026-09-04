<div class="wrap flora-admin">
    <h1><?php echo $pack ? esc_html__( 'Modifier le pack', 'flora-shop' ) : esc_html__( 'Ajouter un pack', 'flora-shop' ); ?></h1>

    <form method="post" action="">
        <?php Flora_Helpers::wpnonce_field( 'flora_save_pack' ); ?>
        <input type="hidden" name="flora_action" value="save">
        <?php if ( $pack ) : ?>
            <input type="hidden" name="pack_id" value="<?php echo esc_attr( $pack->id ); ?>">
        <?php endif; ?>

        <table class="form-table">
            <tr>
                <th><label for="name"><?php esc_html_e( 'Nom du pack', 'flora-shop' ); ?> *</label></th>
                <td><input type="text" id="name" name="name" class="regular-text" required value="<?php echo $pack ? esc_attr( $pack->name ) : ''; ?>"></td>
            </tr>
            <tr>
                <th><label for="description"><?php esc_html_e( 'Description', 'flora-shop' ); ?></label></th>
                <td><textarea id="description" name="description" class="large-text" rows="5"><?php echo $pack ? esc_textarea( $pack->description ) : ''; ?></textarea></td>
            </tr>
            <tr>
                <th><label for="pack_price"><?php esc_html_e( 'Prix du pack', 'flora-shop' ); ?> *</label></th>
                <td><input type="number" step="0.01" id="pack_price" name="pack_price" class="small-text" required value="<?php echo $pack ? esc_attr( $pack->pack_price ) : '0.00'; ?>"></td>
            </tr>
            <tr>
                <th><label for="image_url"><?php esc_html_e( 'URL de l\'image', 'flora-shop' ); ?></label></th>
                <td>
                    <input type="url" id="image_url" name="image_url" class="large-text" value="<?php echo $pack ? esc_url( $pack->image_url ) : ''; ?>">
                    <button type="button" class="button flora-upload-btn" data-target="image_url"><?php esc_html_e( 'Choisir une image', 'flora-shop' ); ?></button>
                </td>
            </tr>
            <tr>
                <th><label for="sort_order"><?php esc_html_e( 'Ordre de tri', 'flora-shop' ); ?></label></th>
                <td><input type="number" id="sort_order" name="sort_order" class="small-text" value="<?php echo $pack ? esc_attr( $pack->sort_order ) : '0'; ?>"></td>
            </tr>
            <tr>
                <th><label for="status"><?php esc_html_e( 'Statut', 'flora-shop' ); ?></label></th>
                <td>
                    <select id="status" name="status">
                        <option value="publish" <?php selected( $pack ? $pack->status : '', 'publish' ); ?>><?php esc_html_e( 'Publié', 'flora-shop' ); ?></option>
                        <option value="draft" <?php selected( $pack ? $pack->status : '', 'draft' ); ?>><?php esc_html_e( 'Brouillon', 'flora-shop' ); ?></option>
                    </select>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e( 'Produits dans ce pack', 'flora-shop' ); ?></h2>
        <table class="wp-list-table widefat fixed striped" id="flora-pack-products">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Produit', 'flora-shop' ); ?></th>
                    <th style="width:150px;"><?php esc_html_e( 'Quantité', 'flora-shop' ); ?></th>
                    <th style="width:80px;"><?php esc_html_e( 'Action', 'flora-shop' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $pack_products ) ) : ?>
                    <?php foreach ( $pack_products as $pp ) : ?>
                        <tr class="flora-pack-product-row">
                            <td>
                                <select name="pack_products[<?php echo esc_attr( $pp->product_id ); ?>][product_id]" class="regular-text">
                                    <option value=""><?php esc_html_e( '-- Choisir --', 'flora-shop' ); ?></option>
                                    <?php foreach ( $all_products as $ap ) : ?>
                                        <option value="<?php echo esc_attr( $ap->id ); ?>" <?php selected( $pp->product_id, $ap->id ); ?>><?php echo esc_html( $ap->name . ' (' . Flora_Helpers::format_price( $ap->price ) . ')' ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="number" name="pack_products[<?php echo esc_attr( $pp->product_id ); ?>][quantity]" min="1" value="<?php echo esc_attr( $pp->quantity ); ?>" class="small-text"></td>
                            <td><button type="button" class="button flora-remove-row"><?php esc_html_e( 'Supprimer', 'flora-shop' ); ?></button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <p>
            <button type="button" class="button" id="flora-add-pack-product"><?php esc_html_e( '+ Ajouter un produit', 'flora-shop' ); ?></button>
        </p>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Enregistrer le pack', 'flora-shop' ); ?></button>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-packs' ) ); ?>" class="button"><?php esc_html_e( 'Annuler', 'flora-shop' ); ?></a>
        </p>
    </form>

    <script type="text/html" id="flora-pack-product-template">
        <tr class="flora-pack-product-row">
            <td>
                <select name="pack_products[new_{{index}}][product_id]" class="regular-text">
                    <option value=""><?php esc_html_e( '-- Choisir --', 'flora-shop' ); ?></option>
                    <?php foreach ( $all_products as $ap ) : ?>
                        <option value="<?php echo esc_attr( $ap->id ); ?>"><?php echo esc_html( $ap->name . ' (' . Flora_Helpers::format_price( $ap->price ) . ')' ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><input type="number" name="pack_products[new_{{index}}][quantity]" min="1" value="1" class="small-text"></td>
            <td><button type="button" class="button flora-remove-row"><?php esc_html_e( 'Supprimer', 'flora-shop' ); ?></button></td>
        </tr>
    </script>
</div>
