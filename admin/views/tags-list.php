<?php /* Vue de la liste des étiquettes : barre de filtres/export, tableau des tags. */ ?>

<div class="wrap flora-admin">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Étiquettes', 'flora-shop' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-tags&action=add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Ajouter une étiquette', 'flora-shop' ); ?></a>
    <hr class="wp-header-end">

    <?php
    Flora_Admin_List::bar( array(
        'page'        => 'flora-tags',
        'search'      => $search,
        'export_args' => array( 'page' => 'flora-tags', 'flora_s' => $search ),
    ) );
    ?>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:60px;"><?php esc_html_e( 'ID', 'flora-shop' ); ?></th>
                <th><?php esc_html_e( 'Nom', 'flora-shop' ); ?></th>
                <th><?php esc_html_e( 'Slug', 'flora-shop' ); ?></th>
                <th style="width:200px;"><?php esc_html_e( 'Actions', 'flora-shop' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $tags ) ) : ?>
                <tr><td colspan="4"><?php esc_html_e( 'Aucune étiquette configurée.', 'flora-shop' ); ?></td></tr>
            <?php else : ?>
                <?php foreach ( $tags as $tag ) : ?>
                    <tr>
                        <td><?php echo esc_html( $tag->id ); ?></td>
                        <td><?php echo esc_html( $tag->name ); ?></td>
                        <td><code><?php echo esc_html( $tag->slug ); ?></code></td>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-tags&action=edit&id=' . $tag->id ) ); ?>"><?php esc_html_e( 'Modifier', 'flora-shop' ); ?></a> |
                            <form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Supprimer cette étiquette ?', 'flora-shop' ); ?>');">
                                <?php wp_nonce_field( 'flora_delete_tag', '_flora_nonce' ); ?>
                                <input type="hidden" name="flora_action" value="delete">
                                <input type="hidden" name="tag_id" value="<?php echo esc_attr( $tag->id ); ?>">
                                <button type="submit" class="flora-link-btn" style="color:#d63638;"><?php esc_html_e( 'Supprimer', 'flora-shop' ); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
