<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Page admin « Produits » : affiche la liste, le formulaire d'ajout/édition et
 * traite la sauvegarde / suppression des produits (POST). Accès restreint à la
 * capability 'manage_options' ; chaque traitement POST est protégé par un nonce
 * (vérifié via Flora_Helpers::verify_nonce()).
 */
class Flora_Admin_Products {

    public static function render() {
        // Point d'entrée de la page : vérifie la permission, gère le POST puis affiche liste ou formulaire.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Accès non autorisé.', 'flora-shop' ) );
        }

        self::handle_actions();

        $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list'; // phpcs:ignore WordPress.Security.NonceVerification

        if ( 'add' === $action || 'edit' === $action ) {
            self::render_form( $action );
        } else {
            self::render_list();
        }
    }

    private static function handle_actions() {
        // Traite les actions POST (save / delete) de la page Produits.
        if ( ! isset( $_POST['flora_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }

        $post_action = sanitize_text_field( wp_unslash( $_POST['flora_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
        // Vérification du nonce avant tout traitement d'enregistrement.
        Flora_Helpers::verify_nonce( 'flora_save_product' );

        $db = Flora_DB::get_instance();

        if ( 'save' === $post_action ) {
            // Assainissement systématique des champs POST avant écriture en base.
            $data = array(
                'name'        => Flora_Helpers::sanitize_text( $_POST['name'] ),
                'slug'        => sanitize_title( $_POST['name'] ),
                'description' => wp_kses_post( $_POST['description'] ),
                'price'       => Flora_Helpers::sanitize_float( $_POST['price'] ),
                'weight'      => Flora_Helpers::sanitize_float( $_POST['weight'] ),
                'image_url'   => esc_url_raw( $_POST['image_url'] ),
                'stock_qty'   => Flora_Helpers::sanitize_number( $_POST['stock_qty'] ),
                'stock_status' => $_POST['stock_qty'] > 0 ? 'instock' : 'outofstock',
                'status'      => sanitize_text_field( $_POST['status'] ),
                'sort_order'  => Flora_Helpers::sanitize_number( $_POST['sort_order'] ),
                'category_id' => isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0,
            );

            $id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

            if ( $id > 0 ) {
                $db->update_product( $id, $data );
            } else {
                $id = $db->insert_product( $data );
            }

            // Enregistrement des étiquettes du produit (tableau d'IDs assaini en entiers).
            $tag_ids = array();
            if ( ! empty( $_POST['tags'] ) && is_array( $_POST['tags'] ) ) {
                foreach ( $_POST['tags'] as $tag_id ) {
                    $tag_ids[] = absint( $tag_id );
                }
            }
            $db->set_item_tags( 'product', $id, $tag_ids );

            // Enregistrement des traductions (nom / slug / description par langue secondaire).
            $db->set_translations( 'product', $id, Flora_Helpers::extract_translations( $_POST ) );

            wp_safe_redirect( admin_url( 'admin.php?page=flora-products&flora_notice=product_saved' ) );
            exit;
        }

        if ( 'delete' === $post_action ) {
            // Suppression : l'identifiant est converti en entier (absint) pour éviter toute injection.
            $id = absint( $_POST['product_id'] );
            if ( $id > 0 ) {
                $db->delete_product( $id );
            }
            wp_safe_redirect( admin_url( 'admin.php?page=flora-products&flora_notice=product_deleted' ) );
            exit;
        }
    }

private static function render_list() {
        $db = Flora_DB::get_instance();

        $args           = self::get_list_args();
        $total          = $db->count_products( $args );
        $args['limit']  = Flora_Admin_List::per_page();
        $args['offset'] = Flora_Admin_List::offset();
        $products       = $db->get_products( $args );
        $all_categories = $db->get_categories();

        include FLORA_SHOP_PATH . 'admin/views/products-list.php';
    }

    // Export Excel : reconstruit les lignes de la liste filtrée (sans pagination)
    // puis télécharge le classeur. Intercepté par Flora_Admin::handle_export().
    public static function export() {
        $db   = Flora_DB::get_instance();
        $args = self::get_list_args();
        $all  = $db->get_products( $args );

        $tag_map = array();
        foreach ( $db->get_tags() as $tag ) {
            $tag_map[ $tag->id ] = $tag->name;
        }

        $rows = array();
        foreach ( $all as $p ) {
            $cat_name = '';
            if ( ! empty( $p->category_id ) ) {
                $cat = $db->get_category( $p->category_id );
                $cat_name = $cat ? $cat->name : '';
            }
            $tag_names = array();
            foreach ( $db->get_item_tags( 'product', $p->id ) as $tag_id ) {
                if ( isset( $tag_map[ $tag_id ] ) ) {
                    $tag_names[] = $tag_map[ $tag_id ];
                }
            }
            $rows[] = array(
                (int) $p->id,
                $p->name,
                $p->slug,
                (float) $p->price,
                'outofstock' === $p->stock_status ? 0 : (int) $p->stock_qty,
                $cat_name,
                implode( ', ', $tag_names ),
                ucfirst( $p->status ),
            );
        }
        Flora_Exporter::handle_export( 'flora-produits.xlsx', array( 'ID', 'Nom', 'Slug', 'Prix', 'Stock', 'Catégorie', 'Étiquettes', 'Statut' ), $rows );
    }

    // Récupère les filtres GET (recherche, statut, stock, catégorie) pour la liste produits.
    private static function get_list_args() {
        $search   = isset( $_GET['flora_s'] ) ? sanitize_text_field( wp_unslash( $_GET['flora_s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        $status   = isset( $_GET['flora_status'] ) ? sanitize_text_field( wp_unslash( $_GET['flora_status'] ) ) : 'any'; // phpcs:ignore WordPress.Security.NonceVerification
        $stock    = isset( $_GET['flora_stock'] ) ? sanitize_text_field( wp_unslash( $_GET['flora_stock'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        $category = isset( $_GET['flora_category'] ) ? sanitize_text_field( wp_unslash( $_GET['flora_category'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

        return array(
            'status'       => $status,
            'search'       => $search,
            'stock_status' => $stock,
            'category'     => $category,
        );
    }

    private static function render_form( $action ) {
        // Affiche le formulaire d'ajout / édition ; charge le produit existant en mode édition.
        $product = null;
        if ( 'edit' === $action && isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            $db      = Flora_DB::get_instance();
            // Lecture GET assainie par absint avant chargement du produit.
            $product = $db->get_product( absint( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
        }

        // Catégories et étiquettes disponibles, et étiquettes déjà rattachées au produit.
        $db           = Flora_DB::get_instance();
        $all_categories = $db->get_categories();
        $all_tags       = $db->get_tags();
        $product_tags   = array();
        $translations   = array();
        if ( $product ) {
            $product_tags = $db->get_item_tags( 'product', $product->id );
            $translations = $db->get_translations( 'product', $product->id );
        }

        include FLORA_SHOP_PATH . 'admin/views/product-form.php';
    }
}
