<?php /* Vue de la liste des packs : tableau avec image, prix, nombre de produits, statut et actions. */ ?>

<div class="wrap flora-admin">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Packs', 'flora-shop' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-packs&action=add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Ajouter un pack', 'flora-shop' ); ?></a>
    <hr class="wp-header-end">

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:50px;"><?php esc_html_e( 'ID', 'flora-shop' ); ?></th>
                <th style="width:60px;"><?php esc_html_e( 'Image', 'flora-shop' ); ?></th>
                <th><?php esc_html_e( 'Nom', 'flora-shop' ); ?></th>
                <th style="width:120px;"><?php esc_html_e( 'Prix pack', 'flora-shop' ); ?></th>
                <th style="width:80px;"><?php esc_html_e( 'Produits', 'flora-shop' ); ?></th>
                <th style="width:100px;"><?php esc_html_e( 'Statut', 'flora-shop' ); ?></th>
                <th style="width:200px;"><?php esc_html_e( 'Actions', 'flora-shop' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php /* --- Lignes du tableau : un pack par ligne, avec comptage des produits associés --- */ ?>
            <?php if ( empty( $packs ) ) : ?>
                <tr><td colspan="7"><?php esc_html_e( 'Aucun pack trouvé.', 'flora-shop' ); ?></td></tr>
            <?php else : ?>
                <?php
                $db_packs = Flora_DB::get_instance();
                foreach ( $packs as $pk ) :
                    $pp_count = $db_packs->get_pack_products( $pk->id );
                ?>
                    <tr>
                        <td><?php echo esc_html( $pk->id ); ?></td>
                        <td>
                            <?php if ( $pk->image_url ) : ?>
                                <img src="<?php echo esc_url( $pk->image_url ); ?>" alt="" style="width:50px;height:50px;object-fit:cover;">
                            <?php else : ?>
                                <span class="dashicons dashicons-dashicons-category" style="font-size:40px;width:50px;height:50px;line-height:50px;color:#ccc;"></span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo esc_html( $pk->name ); ?></strong></td>
                        <td><?php echo esc_html( Flora_Helpers::format_price( $pk->pack_price ) ); ?></td>
                        <td><?php echo esc_html( count( $pp_count ) ); ?></td>
                        <td>
                            <span class="flora-status flora-status-<?php echo esc_attr( $pk->status ); ?>">
                                <?php echo esc_html( ucfirst( $pk->status ) ); ?>
                            </span>
                        </td>
                        <?php /* --- Colonne actions : lien modifier + formulaire de suppression --- */ ?>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-packs&action=edit&id=' . $pk->id ) ); ?>"><?php esc_html_e( 'Modifier', 'flora-shop' ); ?></a> |
                            <form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Supprimer ce pack ?', 'flora-shop' ); ?>');">
                                <?php wp_nonce_field( 'flora_save_pack', '_flora_nonce' ); ?>
                                <input type="hidden" name="flora_action" value="delete">
                                <input type="hidden" name="pack_id" value="<?php echo esc_attr( $pk->id ); ?>">
                                <button type="submit" class="flora-link-btn" style="color:#d63638;"><?php esc_html_e( 'Supprimer', 'flora-shop' ); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
