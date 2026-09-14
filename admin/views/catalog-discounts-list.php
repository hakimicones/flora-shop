<?php /* Vue de la liste des remises catalogue (catégorie / type / tag) : cible, quantité min., récompense, statut. */ ?>

<div class="wrap flora-admin">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Remises par catégorie / type / tag', 'flora-shop' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-promotions&tab=catalog&action=add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Ajouter une remise', 'flora-shop' ); ?></a>
    <hr class="wp-header-end">

    <nav class="nav-tab-wrapper" style="padding-bottom:0;">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-promotions' ) ); ?>" class="nav-tab"><?php esc_html_e( 'Promotions', 'flora-shop' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-promotions&tab=catalog' ) ); ?>" class="nav-tab nav-tab-active"><?php esc_html_e( 'Remises catégorie / type / tag', 'flora-shop' ); ?></a>
    </nav>

    <?php
    Flora_Admin_List::bar( array(
        'page'   => 'flora-promotions',
        'tab'    => 'catalog',
        'search' => $args['search'],
        'filters' => array(
            array(
                'name'    => 'flora_status',
                'label'   => __( 'Statut', 'flora-shop' ),
                'options' => array( 'active' => __( 'Active', 'flora-shop' ), 'inactive' => __( 'Inactive', 'flora-shop' ) ),
                'current' => 'any' === $args['status'] ? '' : $args['status'],
            ),
            array(
                'name'    => 'flora_scope',
                'label'   => __( 'Portée', 'flora-shop' ),
                'options' => array( 'category' => __( 'Catégorie', 'flora-shop' ), 'type' => __( 'Type article', 'flora-shop' ), 'tag' => __( 'Étiquette', 'flora-shop' ) ),
                'current' => isset( $args['scope'] ) ? $args['scope'] : '',
            ),
        ),
        'export_args' => array_merge( array( 'page' => 'flora-promotions', 'tab' => 'catalog' ), array_diff_key( $args, array( 'limit' => '', 'offset' => '' ) ) ),
    ) );
    ?>

    <p class="flora-result-count"><?php printf( esc_html( _n( '%d élément trouvé.', '%d éléments trouvés.', $total, 'flora-shop' ) ), $total ); ?></p>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:50px;"><?php esc_html_e( 'ID', 'flora-shop' ); ?></th>
                <th><?php esc_html_e( 'Cible', 'flora-shop' ); ?></th>
                <th style="width:100px;"><?php esc_html_e( 'Quantité min.', 'flora-shop' ); ?></th>
                <th><?php esc_html_e( 'Récompense', 'flora-shop' ); ?></th>
                <th style="width:80px;"><?php esc_html_e( 'Limite', 'flora-shop' ); ?></th>
                <th style="width:100px;"><?php esc_html_e( 'Statut', 'flora-shop' ); ?></th>
                <th style="width:200px;"><?php esc_html_e( 'Actions', 'flora-shop' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $discounts ) ) : ?>
                <tr><td colspan="7"><?php esc_html_e( 'Aucune remise configurée.', 'flora-shop' ); ?></td></tr>
            <?php else : ?>
                <?php
                $category_map = array();
                foreach ( $categories as $cat ) {
                    $category_map[ $cat->id ] = $cat->name;
                }
                $tag_map = array();
                foreach ( $tags as $tg ) {
                    $tag_map[ $tg->id ] = $tg->name;
                }
                $product_map = array();
                foreach ( $all_products as $ap ) {
                    $product_map[ $ap->id ] = $ap;
                }
                $pack_map = array();
                foreach ( $all_packs as $apk ) {
                    $pack_map[ $apk->id ] = $apk;
                }

                foreach ( $discounts as $dc ) :
                    $scope       = $dc->scope ? $dc->scope : 'category';
                    $item_type   = isset( $dc->item_type ) && $dc->item_type ? $dc->item_type : 'both';
                    $reward_type = $dc->reward_type ? $dc->reward_type : 'percent';
                    $free_type   = isset( $dc->free_type ) && $dc->free_type ? $dc->free_type : 'product';

                    if ( 'type' === $scope ) {
                        $target_label = 'pack' === $item_type ? esc_html__( 'Tous les packs', 'flora-shop' ) : esc_html__( 'Tous les produits', 'flora-shop' );
                    } elseif ( 'tag' === $scope ) {
                        $target_label = isset( $tag_map[ $dc->target_id ] )
                            ? sprintf( esc_html__( 'Étiquette « %s »', 'flora-shop' ), $tag_map[ $dc->target_id ] )
                            : esc_html__( 'Étiquette inconnue', 'flora-shop' );
                        if ( 'both' !== $item_type ) {
                            $target_label .= ' (' . ( 'pack' === $item_type ? esc_html__( 'packs', 'flora-shop' ) : esc_html__( 'produits', 'flora-shop' ) ) . ')';
                        }
                    } else {
                        $target_label = isset( $category_map[ $dc->target_id ] )
                            ? sprintf( esc_html__( 'Catégorie « %s »', 'flora-shop' ), $category_map[ $dc->target_id ] )
                            : esc_html__( 'Catégorie inconnue', 'flora-shop' );
                        if ( 'both' !== $item_type ) {
                            $target_label .= ' (' . ( 'pack' === $item_type ? esc_html__( 'packs', 'flora-shop' ) : esc_html__( 'produits', 'flora-shop' ) ) . ')';
                        }
                    }

                    if ( 'percent' === $reward_type ) {
                        $reward_label = sprintf( esc_html__( '-%s%%', 'flora-shop' ), $dc->discount_percent );
                    } elseif ( 'amount' === $reward_type ) {
                        $reward_label = sprintf( esc_html__( '-%s', 'flora-shop' ), Flora_Helpers::format_price( $dc->discount_amount ) );
                    } elseif ( 'pack' === $free_type && isset( $pack_map[ $dc->free_product_id ] ) ) {
                        $reward_label = sprintf( esc_html__( 'Pack offert : %s', 'flora-shop' ), $pack_map[ $dc->free_product_id ]->name );
                        if ( $dc->free_qty > 1 ) {
                            $reward_label .= ' × ' . $dc->free_qty;
                        }
                    } elseif ( isset( $product_map[ $dc->free_product_id ] ) ) {
                        $reward_label = sprintf( esc_html__( 'Produit offert : %s', 'flora-shop' ), $product_map[ $dc->free_product_id ]->name );
                        if ( $dc->free_qty > 1 ) {
                            $reward_label .= ' × ' . $dc->free_qty;
                        }
                    } else {
                        $reward_label = esc_html__( 'Inconnu', 'flora-shop' );
                    }
                ?>
                    <tr>
                        <td><?php echo esc_html( $dc->id ); ?></td>
                        <td><?php echo wp_kses_post( $target_label ); ?></td>
                        <td><?php echo esc_html( $dc->trigger_qty ); ?></td>
                        <td><?php echo esc_html( $reward_label ); ?></td>
                        <td><?php echo $dc->limit_per_order > 0 ? esc_html( $dc->limit_per_order ) : esc_html__( 'Illimité', 'flora-shop' ); ?></td>
                        <td>
                            <span class="flora-status flora-status-<?php echo 'active' === $dc->status ? 'publish' : 'draft'; ?>">
                                <?php echo esc_html( ucfirst( $dc->status ) ); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-promotions&tab=catalog&action=edit&id=' . $dc->id ) ); ?>"><?php esc_html_e( 'Modifier', 'flora-shop' ); ?></a> |
                            <form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Supprimer cette remise ?', 'flora-shop' ); ?>');">
                                <?php wp_nonce_field( 'flora_delete_catalog_discount', '_flora_nonce' ); ?>
                                <input type="hidden" name="flora_action" value="delete">
                                <input type="hidden" name="discount_id" value="<?php echo esc_attr( $dc->id ); ?>">
                                <button type="submit" class="flora-link-btn" style="color:#d63638;"><?php esc_html_e( 'Supprimer', 'flora-shop' ); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>