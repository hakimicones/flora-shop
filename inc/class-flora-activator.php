<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe gérant l'activation, la désactivation et les migrations du plugin.
 *
 * Crée les tables de la base de données, définit les options par défaut
 * et applique les migrations de schéma lors des mises à jour de version.
 */
class Flora_Activator {

    // Hook d'activation : crée les tables, les pages et les options par défaut puis rafraîchit les règles de réécriture.
    public static function activate() {
        self::create_tables();
        self::create_pages();
        self::ensure_options();
        self::maybe_upgrade();
        flush_rewrite_rules();
    }

    // Hook de désactivation : rafraîchit les règles de réécriture.
    public static function deactivate() {
        flush_rewrite_rules();
    }

    // Insère les options par défaut si elles sont absentes (idempotent) :
    // n'écrase jamais une valeur existante, garantit le seed après une mise à jour.
    private static function ensure_options() {
        $defaults = array(
            'flora_shop_version'         => FLORA_SHOP_VERSION,
            'flora_currency'             => 'DZD',
            'flora_free_shipping_threshold' => 0,
            'flora_product_discounts'    => array(),
            'flora_cart_discounts'       => array(),
            'flora_shipping_methods'     => self::default_shipping_methods(),
            'flora_show_order_email'     => 1,
        );

        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key, false ) ) {
                add_option( $key, $value );
            }
        }
    }

    // Définit la configuration par défaut des deux méthodes de livraison :
    // « domicile » (livraison à l'adresse) et « liaison » (retrait au bureau de liaison).
    private static function default_shipping_methods() {
        return array(
            'home'    => array(
                'enabled'     => 1,
                'label'       => __( 'Livraison à domicile', 'flora-shop' ),
                'description' => __( 'Livraison à l\'adresse indiquée (wilaya / commune).', 'flora-shop' ),
            ),
            'liaison' => array(
                'enabled'     => 1,
                'label'       => __( 'Livraison au bureau de liaison', 'flora-shop' ),
                'description' => __( 'Retrait de la commande au bureau de liaison de votre wilaya.', 'flora-shop' ),
            ),
        );
    }

    // Crée les pages Flora manquantes (par slug) et enregistre leur ID dans
    // les options « flora_page_$key ». Les pages existantes sont réutilisées.
    private static function create_pages() {
        foreach ( Flora_Helpers::flora_pages() as $key => $page ) {
            $existing = get_page_by_path( $page['slug'] );

            if ( $existing && 'publish' === $existing->post_status ) {
                $page_id = (int) $existing->ID;
            } else {
                $page_id = wp_insert_post( array(
                    'post_title'   => $page['title'],
                    'post_name'    => $page['slug'],
                    'post_content' => $page['shortcode'],
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                ) );
            }

            if ( $page_id && ! is_wp_error( $page_id ) ) {
                update_option( 'flora_page_' . $key, $page_id );
            }
        }
    }

    // Crée ou met à jour toutes les tables du plugin via dbDelta, puis lance les migrations de colonnes.
    private static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $prefix = $wpdb->prefix . 'flora_';

        $sql_products = "CREATE TABLE {$prefix}products (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description longtext,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            weight decimal(8,2) DEFAULT NULL,
            image_url varchar(500) DEFAULT '',
            stock_qty int(11) NOT NULL DEFAULT 0,
            stock_status varchar(20) NOT NULL DEFAULT 'instock',
            status varchar(20) NOT NULL DEFAULT 'publish',
            sort_order int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY slug (slug),
            KEY status (status)
        ) $charset;";

        $sql_packs = "CREATE TABLE {$prefix}packs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            pack_price decimal(10,2) NOT NULL DEFAULT 0.00,
            description longtext,
            image_url varchar(500) DEFAULT '',
            status varchar(20) NOT NULL DEFAULT 'publish',
            sort_order int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY slug (slug),
            KEY status (status)
        ) $charset;";

        $sql_pack_products = "CREATE TABLE {$prefix}pack_products (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            pack_id bigint(20) unsigned NOT NULL,
            product_id bigint(20) unsigned NOT NULL,
            quantity int(11) NOT NULL DEFAULT 1,
            sort_order int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY pack_id (pack_id),
            KEY product_id (product_id)
        ) $charset;";

        $sql_communes = "CREATE TABLE {$prefix}communes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_code varchar(10) NOT NULL,
            name varchar(255) NOT NULL,
            name_ar varchar(255) DEFAULT '',
            daira varchar(255) DEFAULT '',
            daira_ar varchar(255) DEFAULT '',
            wilaya_code int(11) NOT NULL,
            latitude decimal(10,7) DEFAULT NULL,
            longitude decimal(10,7) DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY post_code (post_code),
            KEY wilaya_code (wilaya_code)
        ) $charset;";

        $sql_shipping_rates = "CREATE TABLE {$prefix}shipping_rates (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            wilaya_code int(11) NOT NULL,
            commune_id bigint(20) unsigned NOT NULL DEFAULT 0,
            base_fee decimal(10,2) NOT NULL DEFAULT 0.00,
            per_kg_fee decimal(10,2) NOT NULL DEFAULT 0.00,
            bureau_fee decimal(10,2) NOT NULL DEFAULT 0.00,
            bureau_per_kg_fee decimal(10,2) NOT NULL DEFAULT 0.00,
            PRIMARY KEY  (id),
            KEY wilaya_code (wilaya_code),
            KEY commune_id (commune_id)
        ) $charset;";

        $sql_promotions = "CREATE TABLE {$prefix}promotions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            trigger_type varchar(10) NOT NULL DEFAULT 'product',
            trigger_product_id bigint(20) unsigned NOT NULL,
            trigger_qty int(11) NOT NULL DEFAULT 1,
            reward_type varchar(10) NOT NULL DEFAULT 'free',
            free_type varchar(10) NOT NULL DEFAULT 'product',
            free_product_id bigint(20) unsigned NOT NULL,
            free_qty int(11) NOT NULL DEFAULT 1,
            discount_percent decimal(5,2) NOT NULL DEFAULT 0.00,
            discount_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            limit_per_order int(11) NOT NULL DEFAULT 0,
            start_date date DEFAULT NULL,
            end_date date DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            PRIMARY KEY  (id),
            KEY trigger_product_id (trigger_product_id),
            KEY status (status)
        ) $charset;";

        $sql_orders = "CREATE TABLE {$prefix}orders (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_number varchar(50) NOT NULL,
            customer_name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(50) DEFAULT '',
            address text,
            wilaya_code int(11) DEFAULT 0,
            commune_id bigint(20) unsigned DEFAULT 0,
            shipping_method varchar(20) NOT NULL DEFAULT 'home',
            subtotal decimal(10,2) NOT NULL DEFAULT 0.00,
            discount_total decimal(10,2) NOT NULL DEFAULT 0.00,
            shipping_fee decimal(10,2) NOT NULL DEFAULT 0.00,
            total decimal(10,2) NOT NULL DEFAULT 0.00,
            status varchar(30) NOT NULL DEFAULT 'pending',
            notes text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY order_number (order_number),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset;";

        $sql_order_details = "CREATE TABLE {$prefix}order_details (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned NOT NULL,
            item_type varchar(20) NOT NULL DEFAULT 'product',
            item_id bigint(20) unsigned NOT NULL,
            item_name varchar(255) NOT NULL,
            quantity int(11) NOT NULL DEFAULT 1,
            unit_price decimal(10,2) NOT NULL DEFAULT 0.00,
            discount_applied decimal(10,2) NOT NULL DEFAULT 0.00,
            PRIMARY KEY  (id),
            KEY order_id (order_id)
        ) $charset;";

        $sql_categories = "CREATE TABLE {$prefix}categories (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description varchar(500) DEFAULT '',
            sort_order int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) $charset;";

        $sql_tags = "CREATE TABLE {$prefix}tags (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) $charset;";

        $sql_tag_items = "CREATE TABLE {$prefix}tag_items (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            item_type varchar(10) NOT NULL DEFAULT 'product',
            item_id bigint(20) unsigned NOT NULL,
            tag_id bigint(20) unsigned NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY item_tag (item_type, item_id, tag_id),
            KEY type_tag (item_type, tag_id)
        ) $charset;";

        dbDelta( $sql_products );
        dbDelta( $sql_packs );
        dbDelta( $sql_pack_products );
        dbDelta( $sql_communes );
        dbDelta( $sql_shipping_rates );
        dbDelta( $sql_promotions );
        dbDelta( $sql_orders );
        dbDelta( $sql_order_details );
        dbDelta( $sql_categories );
        dbDelta( $sql_tags );
        dbDelta( $sql_tag_items );

        self::migrate_wilayas_table();
        self::migrate_communes_columns();
        self::migrate_shipping_rates_columns();
        self::migrate_orders_columns();
        self::migrate_promotions_columns();
        self::migrate_category_id_columns();
    }

    // Vérifie si une mise à jour de version est nécessaire et applique, dans l'ordre,
    // chaque étape de migration dont la version est supérieure à celle installée.
    // Chaque étape est idempotente : elle peut être rejouée sans risque.
    public static function maybe_upgrade() {
        $installed = (string) get_option( 'flora_shop_version', '0' );

        self::create_pages();
        self::create_tables();
        self::ensure_options();

        if ( version_compare( $installed, '1.4.0', '<' ) ) {
            self::upgrade_1_4_0();
        }

        if ( version_compare( $installed, '1.5.0', '<' ) ) {
            self::upgrade_1_5_0();
        }

        if ( version_compare( $installed, FLORA_SHOP_VERSION, '<' ) ) {
            update_option( 'flora_shop_version', FLORA_SHOP_VERSION );
        }
    }

    // Migration 1.4.0 : ajout des méthodes de livraison (domicile / bureau de liaison).
    // Les colonnes et options associées sont créées par create_tables()/ensure_options(),
    // cette étape prépare l'existant (valeurs de migration éventuelles).
    private static function upgrade_1_4_0() {}

    // Migration 1.5.0 : catégories (colonne category_id sur produits/packs) et tags
    // (table flora_tags + liaison flora_tag_items). Les tables et colonnes sont créées
    // par create_tables(), cette étape est réservée aux traitements de données éventuels.
    private static function upgrade_1_5_0() {}

    // Recrée la table wilayas si elle est absente ou corrompue, et migre les colonnes wilaya_id → wilaya_code.
    private static function migrate_wilayas_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'flora_wilayas';
        $table_communes = $wpdb->prefix . 'flora_communes';
        $table_rates = $wpdb->prefix . 'flora_shipping_rates';
        $table_orders = $wpdb->prefix . 'flora_orders';

        $columns = $wpdb->get_col( "DESCRIBE {$table}", 0 ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        if ( is_array( $columns ) && in_array( 'id', $columns, true ) && ! isset( $columns[0] ) ) {
            $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $columns = array();
        }

        if ( empty( $columns ) ) {
            $charset = $wpdb->get_charset_collate();
            $wpdb->query( "CREATE TABLE {$table} (
                code int(11) NOT NULL,
                name varchar(255) NOT NULL,
                name_ar varchar(255) DEFAULT '',
                latitude decimal(10,7) DEFAULT NULL,
                longitude decimal(10,7) DEFAULT NULL,
                PRIMARY KEY  (code)
            ) {$charset}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

            $wpdb->query( "UPDATE {$table_rates} SET wilaya_code = CAST(wilaya_id AS SIGNED) WHERE wilaya_code = 0 AND wilaya_id > 0" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->query( "UPDATE {$table_orders} SET wilaya_code = CAST(wilaya_id AS SIGNED) WHERE wilaya_code = 0 AND wilaya_id > 0" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }
    }

    // Ajoute les colonnes manquantes à la table communes (post_code, name_ar, daira, coordonnées, wilaya_code).
    private static function migrate_communes_columns() {
        global $wpdb;
        $table = $wpdb->prefix . 'flora_communes';
        $columns = $wpdb->get_col( "DESCRIBE {$table}", 0 ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        if ( ! is_array( $columns ) ) {
            return;
        }

        if ( in_array( 'wilaya_id', $columns, true ) && ! in_array( 'wilaya_code', $columns, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN wilaya_code int(11) NOT NULL AFTER name" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->query( "UPDATE {$table} SET wilaya_code = wilaya_id" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->query( "ALTER TABLE {$table} DROP COLUMN wilaya_id" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }

        $columns = $wpdb->get_col( "DESCRIBE {$table}", 0 ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        if ( ! in_array( 'post_code', $columns, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN post_code varchar(10) NOT NULL DEFAULT '' AFTER id, ADD COLUMN name_ar varchar(255) NOT NULL DEFAULT '' AFTER name, ADD COLUMN daira varchar(255) NOT NULL DEFAULT '' AFTER name_ar, ADD COLUMN daira_ar varchar(255) NOT NULL DEFAULT '' AFTER daira, ADD COLUMN latitude decimal(10,7) DEFAULT NULL AFTER wilaya_code, ADD COLUMN longitude decimal(10,7) DEFAULT NULL AFTER latitude" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }
    }

    // Remplace la colonne wilaya_id par wilaya_code dans la table des frais de livraison
    // et ajoute les colonnes « bureau » (méthode de livraison au bureau de liaison).
    private static function migrate_shipping_rates_columns() {
        global $wpdb;
        $table = $wpdb->prefix . 'flora_shipping_rates';
        $columns = $wpdb->get_col( "DESCRIBE {$table}", 0 ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        if ( is_array( $columns ) && in_array( 'wilaya_id', $columns, true ) && ! in_array( 'wilaya_code', $columns, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN wilaya_code int(11) NOT NULL DEFAULT 0 AFTER id" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->query( "UPDATE {$table} SET wilaya_code = wilaya_id" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->query( "ALTER TABLE {$table} DROP COLUMN wilaya_id" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }

        $columns = $wpdb->get_col( "DESCRIBE {$table}", 0 ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        if ( is_array( $columns ) && ! in_array( 'bureau_fee', $columns, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN bureau_fee decimal(10,2) NOT NULL DEFAULT 0.00 AFTER per_kg_fee" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }

        $columns = $wpdb->get_col( "DESCRIBE {$table}", 0 ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        if ( is_array( $columns ) && ! in_array( 'bureau_per_kg_fee', $columns, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN bureau_per_kg_fee decimal(10,2) NOT NULL DEFAULT 0.00 AFTER bureau_fee" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }
    }

    // Remplace la colonne wilaya_id par wilaya_code dans la table des commandes
    // et ajoute la colonne shipping_method (méthode de livraison choisie).
    private static function migrate_orders_columns() {
        global $wpdb;
        $table = $wpdb->prefix . 'flora_orders';
        $columns = $wpdb->get_col( "DESCRIBE {$table}", 0 ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        if ( is_array( $columns ) && in_array( 'wilaya_id', $columns, true ) && ! in_array( 'wilaya_code', $columns, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN wilaya_code int(11) NOT NULL DEFAULT 0 AFTER address" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->query( "UPDATE {$table} SET wilaya_code = wilaya_id" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->query( "ALTER TABLE {$table} DROP COLUMN wilaya_id" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }

        $columns = $wpdb->get_col( "DESCRIBE {$table}", 0 ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        if ( is_array( $columns ) && ! in_array( 'shipping_method', $columns, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN shipping_method varchar(20) NOT NULL DEFAULT 'home' AFTER commune_id" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }
    }

    // Ajoute les colonnes manquantes à la table promotions (trigger_type, reward_type, free_type, remises).
    private static function migrate_promotions_columns() {
        global $wpdb;
        $table   = $wpdb->prefix . 'flora_promotions';
        $columns = $wpdb->get_col( "DESCRIBE {$table}", 0 ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        if ( ! is_array( $columns ) ) {
            return;
        }

        if ( ! in_array( 'trigger_type', $columns, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN trigger_type varchar(10) NOT NULL DEFAULT 'product' AFTER id" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }

        if ( ! in_array( 'reward_type', $columns, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN reward_type varchar(10) NOT NULL DEFAULT 'free' AFTER trigger_qty" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }

        if ( ! in_array( 'free_type', $columns, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN free_type varchar(10) NOT NULL DEFAULT 'product' AFTER reward_type" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }

        if ( ! in_array( 'discount_percent', $columns, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN discount_percent decimal(5,2) NOT NULL DEFAULT 0.00 AFTER free_qty" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }

        $columns = $wpdb->get_col( "DESCRIBE {$table}", 0 ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        if ( ! in_array( 'discount_amount', $columns, true ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN discount_amount decimal(10,2) NOT NULL DEFAULT 0.00 AFTER discount_percent" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }
    }

    // Ajoute la colonne category_id (catégorie unique par article) aux tables produits et packs
    // lorsqu'elle est absente, avec son index. Rerun sans risque (idempotent).
    private static function migrate_category_id_columns() {
        global $wpdb;

        foreach ( array( 'products', 'packs' ) as $table_name ) {
            $table   = $wpdb->prefix . 'flora_' . $table_name;
            $columns = $wpdb->get_col( "DESCRIBE {$table}", 0 ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

            if ( ! is_array( $columns ) || in_array( 'category_id', $columns, true ) ) {
                continue;
            }

            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN category_id bigint(20) unsigned NOT NULL DEFAULT 0 AFTER status, ADD KEY category_id (category_id)" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }
    }
}
