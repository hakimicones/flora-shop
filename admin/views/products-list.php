<?php /* Vue de la liste des produits : barre de filtres, tableau avec image, prix, stock, statut, pagination et export. */ ?>

<div class="wrap flora-admin">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Produits', 'flora-shop' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-products&action=add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Ajouter un produit', 'flora-shop' ); ?></a>
    <hr class="wp-header-end">

    <?php
    $category_options = array();
    foreach ( $all_categories as $cat ) {
        $category_options[ $cat->slug ] = $cat->name;
    }
    Flora_Admin_List::bar( array(
        'page'        => 'flora-products',
        'search'      => $args['search'],
        'filters'     => array(
            array(
                'name'    => 'flora_status',
                'label'   => __( 'Statut', 'flora-shop' ),
                'options' => array( 'publish' => __( 'Publié', 'flora-shop' ), 'draft' => __( 'Brouillon', 'flora-shop' ), 'trash' => __( 'Corbeille', 'flora-shop' ) ),
                'current' => 'any' === $args['status'] ? '' : $args['status'],
            ),
            array(
                'name'    => 'flora_stock',
                'label'   => __( 'Stock', 'flora-shop' ),
                'options' => array( 'instock' => __( 'En stock', 'flora-shop' ), 'outofstock' => __( 'Rupture', 'flora-shop' ) ),
                'current' => $args['stock_status'],
            ),
            array(
                'name'    => 'flora_category',
                'label'   => __( 'Catégorie', 'flora-shop' ),
                'options' => $category_options,
                'current' => $args['category'],
            ),
        ),
        'export_args' => array_merge( array( 'page' => 'flora-products' ), array_diff_key( $args, array( 'limit' => '', 'offset' => '' ) ) ),
    ) );
    ?>

    <p class="flora-result-count"><?php printf( esc_html( _n( '%d élément trouvé.', '%d éléments trouvés.', $total, 'flora-shop' ) ), $total ); ?></p>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:50px;"><?php esc_html_e( 'ID', 'flora-shop' ); ?></th>
                <th style="width:60px;"><?php esc_html_e( 'Image', 'flora-shop' ); ?></th>
                <th><?php esc_html_e( 'Nom', 'flora-shop' ); ?></th>
                <th style="width:120px;"><?php esc_html_e( 'Prix', 'flora-shop' ); ?></th>
                <th style="width:80px;"><?php esc_html_e( 'Stock', 'flora-shop' ); ?></th>
                <th style="width:100px;"><?php esc_html_e( 'Statut', 'flora-shop' ); ?></th>
                <th style="width:200px;"><?php esc_html_e( 'Actions', 'flora-shop' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $products ) ) : ?>
                <tr><td colspan="7"><?php esc_html_e( 'Aucun produit trouvé.', 'flora-shop' ); ?></td></tr>
            <?php else : ?>
                <?php foreach ( $products as $p ) : ?>
                    <tr>
                        <td><?php echo esc_html( $p->id ); ?></td>
                        <td>
                            <?php if ( $p->image_url ) : ?>
                                <img src="<?php echo esc_url( $p->image_url ); ?>" alt="" style="width:50px;height:50px;object-fit:cover;">
                            <?php else : ?>
                                <span class="dashicons dashicons-format-image" style="font-size:40px;width:50px;height:50px;line-height:50px;color:#ccc;"></span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo esc_html( $p->name ); ?></strong></td>
                        <td><?php echo esc_html( Flora_Helpers::format_price( $p->price ) ); ?></td>
                        <td>
                            <?php if ( $p->stock_status === 'outofstock' ) : ?>
                                <span style="color:#d63638;"><?php esc_html_e( 'Rupture', 'flora-shop' ); ?></span>
                            <?php else : ?>
                                <?php echo esc_html( $p->stock_qty ); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="flora-status flora-status-<?php echo esc_attr( $p->status ); ?>">
                                <?php echo esc_html( ucfirst( $p->status ) ); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-products&action=edit&id=' . $p->id ) ); ?>"><?php esc_html_e( 'Modifier', 'flora-shop' ); ?></a> |
                            <form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Supprimer ce produit ?', 'flora-shop' ); ?>');">
                                <?php wp_nonce_field( 'flora_save_product', '_flora_nonce' ); ?>
                                <input type="hidden" name="flora_action" value="delete">
                                <input type="hidden" name="product_id" value="<?php echo esc_attr( $p->id ); ?>">
                                <button type="submit" class="flora-link-btn" style="color:#d63638;"><?php esc_html_e( 'Supprimer', 'flora-shop' ); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php
    $pagination_base = array( 'page' => 'flora-products' );
    if ( '' !== $args['search'] ) { $pagination_base['flora_s'] = $args['search']; }
    if ( 'any' !== $args['status'] ) { $pagination_base['flora_status'] = $args['status']; }
    if ( '' !== $args['stock_status'] ) { $pagination_base['flora_stock'] = $args['stock_status']; }
    if ( '' !== $args['category'] ) { $pagination_base['flora_category'] = $args['category']; }
    ?>
    <?php if ( $pagination = Flora_Admin_List::paginate( $total, $pagination_base ) ) : ?>
    <div class="tablenav bottom"><div class="tablenav-pages"><?php echo $pagination; ?></div></div>
    <?php endif; ?>
</div>
