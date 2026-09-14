<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Page admin « Transport » : gère les trois onglets (wilayas, communes, tarifs)
 * via des sous-actions POST. Chaque action (save / delete) est protégée par un
 * nonce dédié. Accès restreint à la capability 'manage_options' ; les champs
 * POST sont systématiquement assainis avant écriture en base.
 */
class Flora_Admin_Shipping {

    public static function render() {
        // Point d'entrée de la page : vérifie la permission, gère le POST puis inclut la vue multi-onglets.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Accès non autorisé.', 'flora-shop' ) );
        }

        self::handle_actions();

        $db               = Flora_DB::get_instance();
        $tab              = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'wilayas'; // phpcs:ignore WordPress.Security.NonceVerification
        $wilayas          = $db->get_wilayas();
        $rates            = $db->get_all_shipping_rates();
        $shipping_methods = Flora_Helpers::flora_shipping_methods();

        $wilaya_map = array();
        foreach ( $wilayas as $w ) {
            $wilaya_map[ $w->code ] = $w;
        }

        // Filtres de l'onglet communes.
        $commune_search = isset( $_GET['flora_s'] ) ? sanitize_text_field( wp_unslash( $_GET['flora_s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        $commune_wilaya = isset( $_GET['flora_wilaya'] ) ? absint( $_GET['flora_wilaya'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

        $all_communes   = array();
        $total_communes = 0;

        if ( 'communes' === $tab ) {
            $commune_args              = array( 'search' => $commune_search );
            $total_communes            = $db->count_communes( $commune_wilaya, $commune_args );
            $commune_args['limit']     = Flora_Admin_List::per_page();
            $commune_args['offset']    = Flora_Admin_List::offset();
            $all_communes              = $db->get_communes( $commune_wilaya, $commune_args );
        }

        include FLORA_SHOP_PATH . 'admin/views/shipping.php';
    }

    // Export Excel de l'onglet courant (wilayas, communes ou tarifs). Intercepté
    // par Flora_Admin::handle_export() avant tout rendu HTML de la page admin.
    public static function export() {
        $db         = Flora_DB::get_instance();
        $tab        = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'wilayas'; // phpcs:ignore WordPress.Security.NonceVerification
        $wilaya_map = array();
        foreach ( $db->get_wilayas() as $w ) {
            $wilaya_map[ $w->code ] = $w;
        }
        self::handle_export( $tab, $db, $wilaya_map );
    }

    // Téléverse l'export Excel de l'onglet courant (wilayas, communes ou tarifs).
    private static function handle_export( $tab, $db, $wilaya_map ) {
        if ( 'wilayas' === $tab ) {
            $all = $db->get_wilayas();
            $rows = array();
            foreach ( $all as $w ) {
                $rows[] = array( (int) $w->code, $w->name, $w->name_ar, $w->latitude, $w->longitude );
            }
            Flora_Exporter::handle_export( 'flora-wilayas.xlsx', array( 'Code', 'Nom', 'Nom arabe', 'Latitude', 'Longitude' ), $rows );
        }

        if ( 'communes' === $tab ) {
            $search = isset( $_GET['flora_s'] ) ? sanitize_text_field( wp_unslash( $_GET['flora_s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
            $wilaya = isset( $_GET['flora_wilaya'] ) ? absint( $_GET['flora_wilaya'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
            $all    = $db->get_communes( $wilaya, array( 'search' => $search ) );
            $rows   = array();
            foreach ( $all as $c ) {
                $w_name = isset( $wilaya_map[ $c->wilaya_code ] ) ? $wilaya_map[ $c->wilaya_code ]->name : '';
                $rows[] = array( $c->post_code, $w_name, $c->name, $c->name_ar, $c->daira, $c->daira_ar );
            }
            Flora_Exporter::handle_export( 'flora-communes.xlsx', array( 'Code postal', 'Wilaya', 'Commune', 'Nom arabe', 'Daïra', 'Daïra arabe' ), $rows );
        }

        if ( 'rates' === $tab ) {
            $all = $db->get_all_shipping_rates();
            $all_communes_map = array();
            foreach ( $wilaya_map as $w ) {
                $ccs = $db->get_communes( $w->code );
                foreach ( $ccs as $cc ) {
                    $all_communes_map[ $cc->id ] = $cc->name;
                }
            }
            $rows = array();
            foreach ( $all as $r ) {
                $w_name = isset( $wilaya_map[ $r->wilaya_code ] ) ? $wilaya_map[ $r->wilaya_code ]->name : $r->wilaya_code;
                $c_name = $r->commune_id > 0 && isset( $all_communes_map[ $r->commune_id ] ) ? $all_communes_map[ $r->commune_id ] : '—';
                $rows[] = array( $w_name, $c_name, (float) $r->base_fee, (float) $r->per_kg_fee, (float) $r->bureau_fee, (float) $r->bureau_per_kg_fee );
            }
            Flora_Exporter::handle_export( 'flora-tarifs.xlsx', array( 'Wilaya', 'Commune', 'Frais fixe domicile', 'Frais/kg domicile', 'Frais fixe bureau', 'Frais/kg bureau' ), $rows );
        }
    }

    private static function handle_actions() {
        // Traite toutes les actions POST de la page Transport (wilayas, communes, tarifs).
        if ( ! isset( $_POST['flora_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }

        $post_action = sanitize_text_field( wp_unslash( $_POST['flora_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
        $db           = Flora_DB::get_instance();

        if ( 'save_wilaya' === $post_action ) {
            Flora_Helpers::verify_nonce( 'flora_save_wilaya' );
            // Assainissement de chaque champ : absint pour les identifiants/codes,
            // sanitize_float pour latitude/longitude, sanitize_text pour les libellés.
            $data = array(
                'code'      => absint( $_POST['code'] ),
                'name'      => Flora_Helpers::sanitize_text( $_POST['name'] ),
                'name_ar'   => isset( $_POST['name_ar'] ) ? Flora_Helpers::sanitize_text( $_POST['name_ar'] ) : '',
                'latitude'  => isset( $_POST['latitude'] ) && '' !== $_POST['latitude'] ? Flora_Helpers::sanitize_float( $_POST['latitude'] ) : null,
                'longitude' => isset( $_POST['longitude'] ) && '' !== $_POST['longitude'] ? Flora_Helpers::sanitize_float( $_POST['longitude'] ) : null,
            );
            $db->insert_wilaya( $data );
            wp_safe_redirect( admin_url( 'admin.php?page=flora-shipping&tab=wilayas&flora_notice=shipping_saved' ) );
            exit;
        }

        if ( 'delete_wilaya' === $post_action ) {
            Flora_Helpers::verify_nonce( 'flora_delete_wilaya' );
            // Suppression d'une wilaya : code converti en entier via absint.
            $db->delete_wilaya( absint( $_POST['wilaya_code'] ) );
            wp_safe_redirect( admin_url( 'admin.php?page=flora-shipping&tab=wilayas&flora_notice=shipping_saved' ) );
            exit;
        }

        if ( 'save_commune' === $post_action ) {
            Flora_Helpers::verify_nonce( 'flora_save_commune' );
            // Assainissement des champs commune (code postal, nom, daira, wilaya, coordonnées).
            $data = array(
                'post_code'   => Flora_Helpers::sanitize_text( $_POST['post_code'] ),
                'name'        => Flora_Helpers::sanitize_text( $_POST['name'] ),
                'name_ar'     => isset( $_POST['name_ar'] ) ? Flora_Helpers::sanitize_text( $_POST['name_ar'] ) : '',
                'daira'       => isset( $_POST['daira'] ) ? Flora_Helpers::sanitize_text( $_POST['daira'] ) : '',
                'daira_ar'    => isset( $_POST['daira_ar'] ) ? Flora_Helpers::sanitize_text( $_POST['daira_ar'] ) : '',
                'wilaya_code' => absint( $_POST['wilaya_code'] ),
                'latitude'    => isset( $_POST['latitude'] ) && '' !== $_POST['latitude'] ? Flora_Helpers::sanitize_float( $_POST['latitude'] ) : null,
                'longitude'   => isset( $_POST['longitude'] ) && '' !== $_POST['longitude'] ? Flora_Helpers::sanitize_float( $_POST['longitude'] ) : null,
            );
            $db->insert_commune( $data );
            wp_safe_redirect( admin_url( 'admin.php?page=flora-shipping&tab=communes&flora_notice=shipping_saved' ) );
            exit;
        }

        if ( 'delete_commune' === $post_action ) {
            Flora_Helpers::verify_nonce( 'flora_delete_commune' );
            // Suppression d'une commune : identifiant converti en entier via absint.
            $db->delete_commune( absint( $_POST['commune_id'] ) );
            wp_safe_redirect( admin_url( 'admin.php?page=flora-shipping&tab=communes&flora_notice=shipping_saved' ) );
            exit;
        }

        if ( 'save_rate' === $post_action ) {
            Flora_Helpers::verify_nonce( 'flora_save_rate' );
            // Sauvegarde / mise à jour d'un tarif : identifiants en absint, frais en sanitize_float.
            // Chaque ligne porte les 2 rubriques : domicile (base + per_kg) et bureau de liaison (bureau_fee + bureau_per_kg).
            $data = array(
                'wilaya_code'        => absint( $_POST['wilaya_code'] ),
                'commune_id'         => absint( $_POST['commune_id'] ),
                'base_fee'           => Flora_Helpers::sanitize_float( $_POST['base_fee'] ),
                'per_kg_fee'         => Flora_Helpers::sanitize_float( $_POST['per_kg_fee'] ),
                'bureau_fee'         => Flora_Helpers::sanitize_float( $_POST['bureau_fee'] ),
                'bureau_per_kg_fee'  => Flora_Helpers::sanitize_float( $_POST['bureau_per_kg_fee'] ),
            );
            $db->upsert_shipping_rate( $data );
            wp_safe_redirect( admin_url( 'admin.php?page=flora-shipping&tab=rates&flora_notice=shipping_saved' ) );
            exit;
        }

        if ( 'save_shipping_methods' === $post_action ) {
            Flora_Helpers::verify_nonce( 'flora_save_shipping_methods' );
            // Sauvegarde de la configuration des méthodes de livraison (activation, libellés, descriptions).
            $methods = Flora_Helpers::flora_shipping_methods();

            foreach ( array_keys( $methods ) as $key ) {
                $methods[ $key ]['enabled']     = ! empty( $_POST['shipping_methods'][ $key ]['enabled'] ) ? 1 : 0;
                $methods[ $key ]['label']       = ! empty( $_POST['shipping_methods'][ $key ]['label'] ) ? Flora_Helpers::sanitize_text( $_POST['shipping_methods'][ $key ]['label'] ) : '';
                $methods[ $key ]['description'] = ! empty( $_POST['shipping_methods'][ $key ]['description'] ) ? Flora_Helpers::sanitize_text( $_POST['shipping_methods'][ $key ]['description'] ) : '';
            }

            update_option( 'flora_shipping_methods', $methods );
            wp_safe_redirect( admin_url( 'admin.php?page=flora-shipping&flora_notice=shipping_saved' ) );
            exit;
        }

        if ( 'delete_rate' === $post_action ) {
            Flora_Helpers::verify_nonce( 'flora_delete_rate' );
            // Suppression d'un tarif : identifiant converti en entier via absint.
            $db->delete_shipping_rate( absint( $_POST['rate_id'] ) );
            wp_safe_redirect( admin_url( 'admin.php?page=flora-shipping&tab=rates&flora_notice=shipping_saved' ) );
            exit;
        }
    }
}
