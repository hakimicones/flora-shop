<?php /* Vue de la liste des catégories : tableau des catégories partagées produits / packs. */ ?>

<div class="wrap flora-admin">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Catégories', 'flora-shop' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-categories&action=add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Ajouter une catégorie', 'flora-shop' ); ?></a>
    <hr class="wp-header-end">

    <p class="description"><?php esc_html_e( 'Une catégorie est associée à un produit ou un pack. Les shortcodes de la boutique filtrent les articles par catégorie.', 'flora-shop' ); ?></p>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:60px;"><?php esc_html_e( 'ID', 'flora-shop' ); ?></th>
                <th><?php esc_html_e( 'Nom', 'flora-shop' ); ?></th>
                <th><?php esc_html_e( 'Slug', 'flora-shop' ); ?></th>
                <th style="width:120px;"><?php esc_html_e( 'Ordre', 'flora-shop' ); ?></th>
                <th style="width:200px;"><?php esc_html_e( 'Actions', 'flora-shop' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $categories ) ) : ?>
                <tr><td colspan="5"><?php esc_html_e( 'Aucune catégorie configurée.', 'flora-shop' ); ?></td></tr>
            <?php else : ?>
                <?php foreach ( $categories as $cat ) : ?>
                    <tr>
                        <td><?php echo esc_html( $cat->id ); ?></td>
                        <td><?php echo esc_html( $cat->name ); ?></td>
                        <td><code><?php echo esc_html( $cat->slug ); ?></code></td>
                        <td><?php echo esc_html( $cat->sort_order ); ?></td>
                        <?php /* --- Colonne actions : lien modifier + formulaire de suppression --- */ ?>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-categories&action=edit&id=' . $cat->id ) ); ?>"><?php esc_html_e( 'Modifier', 'flora-shop' ); ?></a> |
                            <form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Supprimer cette catégorie ?', 'flora-shop' ); ?>');">
                                <?php wp_nonce_field( 'flora_delete_category', '_flora_nonce' ); ?>
                                <input type="hidden" name="flora_action" value="delete">
                                <input type="hidden" name="category_id" value="<?php echo esc_attr( $cat->id ); ?>">
                                <button type="submit" class="flora-link-btn" style="color:#d63638;"><?php esc_html_e( 'Supprimer', 'flora-shop' ); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>