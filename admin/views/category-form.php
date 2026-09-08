<?php /* Vue du formulaire d'ajout / modification d'une catégorie : nom, slug, description et ordre de tri. */ ?>

<div class="wrap flora-admin">
    <h1><?php echo $category ? esc_html__( 'Modifier la catégorie', 'flora-shop' ) : esc_html__( 'Ajouter une catégorie', 'flora-shop' ); ?></h1>

    <?php /* --- Formulaire d'enregistrement : l'action "save" est traitée côté serveur --- */ ?>
    <form method="post" action="">
        <?php Flora_Helpers::wpnonce_field( 'flora_save_category' ); ?>
        <input type="hidden" name="flora_action" value="save">
        <?php if ( $category ) : ?>
            <input type="hidden" name="category_id" value="<?php echo esc_attr( $category->id ); ?>">
        <?php endif; ?>

        <table class="form-table">
            <tr>
                <th><label for="name"><?php esc_html_e( 'Nom de la catégorie', 'flora-shop' ); ?> *</label></th>
                <td><input type="text" id="name" name="name" class="regular-text" required value="<?php echo $category ? esc_attr( $category->name ) : ''; ?>"></td>
            </tr>
            <tr>
                <th><label for="description"><?php esc_html_e( 'Description', 'flora-shop' ); ?></label></th>
                <td><textarea id="description" name="description" class="large-text" rows="3"><?php echo $category ? esc_textarea( $category->description ) : ''; ?></textarea></td>
            </tr>
            <tr>
                <th><label for="sort_order"><?php esc_html_e( 'Ordre de tri', 'flora-shop' ); ?></label></th>
                <td><input type="number" id="sort_order" name="sort_order" class="small-text" value="<?php echo $category ? esc_attr( $category->sort_order ) : '0'; ?>"></td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Enregistrer la catégorie', 'flora-shop' ); ?></button>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-categories' ) ); ?>" class="button"><?php esc_html_e( 'Annuler', 'flora-shop' ); ?></a>
        </p>
    </form>
</div>