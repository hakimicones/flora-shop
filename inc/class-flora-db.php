<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Couche d'accès à la base de données pour le plugin Flora Shop.
 *
 * Gère les requêtes directes vers les tables personnalisées : produits, packs,
 * wilayas, communes, tarifs de livraison, promotions, commandes et détails de commande.
 * Implémente le singleton pour garantir une instance unique.
 */
class Flora_DB {

    private static $instance = null;

    // Retourne l'instance unique de la classe (singleton).
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Constructeur privé pour empêcher l'instanciation externe (singleton).
    private function __construct() {}

    // Retourne le nom qualifié d'une table Flora (préfixe WP + 'flora_' + nom).
    public function table( $name ) {
        global $wpdb;
        return $wpdb->prefix . 'flora_' . $name;
    }

    // Récupère un produit par son ID. Retourne un objet ligne ou null.
    public function get_product( $id ) {
        global $wpdb;
        $table = $this->table( 'products' );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Récupère un produit publié par son slug. Retourne un objet ligne ou null.
    public function get_product_by_slug( $slug ) {
        global $wpdb;
        $table = $this->table( 'products' );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s AND status = 'publish'", $slug ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Récupère une liste de produits filtrés et paginés. Retourne un tableau d'objets.
    public function get_products( $args = array() ) {
        global $wpdb;
        $table  = $this->table( 'products' );
        $where  = "WHERE 1=1";
        $params = array();

        if ( ! empty( $args['status'] ) ) {
            $where   .= " AND status = %s";
            $params[] = $args['status'];
        } else {
            $where .= " AND status = 'publish'";
        }

        // Filtre par catégorie (1+ slugs séparés par des virgules) : l'article appartient à l'une d'elles.
        if ( ! empty( $args['category'] ) ) {
            $slugs = self::clean_slug_list( $args['category'] );
            if ( $slugs ) {
                $table_categories = $this->table( 'categories' );
                $placeholders     = implode( ', ', array_fill( 0, count( $slugs ), '%s' ) );
                $where           .= " AND {$table}.category_id IN (SELECT id FROM {$table_categories} WHERE slug IN ({$placeholders}))";
                foreach ( $slugs as $slug ) {
                    $params[] = $slug;
                }
            }
        }

        // Filtre par étiquette (1+ slugs séparés par des virgules) : l'article possède l'une d'elles.
        if ( ! empty( $args['tag'] ) ) {
            $slugs = self::clean_slug_list( $args['tag'] );
            if ( $slugs ) {
                $table_tags     = $this->table( 'tags' );
                $table_tag_items = $this->table( 'tag_items' );
                $placeholders    = implode( ', ', array_fill( 0, count( $slugs ), '%s' ) );
                $where          .= " AND EXISTS (
                    SELECT 1 FROM {$table_tag_items} ti
                    INNER JOIN {$table_tags} t ON t.id = ti.tag_id
                    WHERE ti.item_type = 'product' AND ti.item_id = {$table}.id AND t.slug IN ({$placeholders})
                )";
                foreach ( $slugs as $slug ) {
                    $params[] = $slug;
                }
            }
        }

        if ( ! empty( $args['limit'] ) ) {
            $limit = "LIMIT " . absint( $args['limit'] );
            if ( ! empty( $args['offset'] ) ) {
                $limit .= " OFFSET " . absint( $args['offset'] );
            }
        } else {
            $limit = '';
        }

        $order = ! empty( $args['orderby'] ) ? sanitize_sql_orderby( $args['orderby'] ) : 'ORDER BY sort_order ASC, id DESC';

        // Requête préparée uniquement lorsqu'il y a des paramètres dynamiques.
        if ( ! empty( $params ) ) {
            return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} {$where} {$order} {$limit}", $params ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }

        return $wpdb->get_results( "SELECT * FROM {$table} {$where} {$order} {$limit}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Nettoie une liste de slugs séparés par des virgules (trim + sanitize_title), en écartant les valeurs vides.
    private static function clean_slug_list( $list ) {
        $slugs = array_map( 'sanitize_title', array_map( 'trim', explode( ',', (string) $list ) ) );
        $slugs = array_values( array_filter( $slugs ) );
        return $slugs;
    }

    // Insère un produit et retourne l'ID inséré.
    public function insert_product( $data ) {
        global $wpdb;
        $table = $this->table( 'products' );
        $wpdb->insert( $table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->insert_id;
    }

    // Met à jour un produit. Retourne le nombre de lignes affectées.
    public function update_product( $id, $data ) {
        global $wpdb;
        $table = $this->table( 'products' );
        return $wpdb->update( $table, $data, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Supprime logiquement un produit (statut 'trash'). Retourne le nombre de lignes affectées.
    public function delete_product( $id ) {
        global $wpdb;
        $table = $this->table( 'products' );
        return $wpdb->update( $table, array( 'status' => 'trash' ), array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Récupère un pack par son ID. Retourne un objet ligne ou null.
    public function get_pack( $id ) {
        global $wpdb;
        $table = $this->table( 'packs' );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Récupère un pack publié par son slug. Retourne un objet ligne ou null.
    public function get_pack_by_slug( $slug ) {
        global $wpdb;
        $table = $this->table( 'packs' );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s AND status = 'publish'", $slug ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Récupère une liste de packs filtrés par statut. Retourne un tableau d'objets.
    public function get_packs( $args = array() ) {
        global $wpdb;
        $table  = $this->table( 'packs' );
        $where  = "WHERE 1=1";
        $params = array();

        if ( ! empty( $args['status'] ) ) {
            $where   .= " AND status = %s";
            $params[] = $args['status'];
        } else {
            $where .= " AND status = 'publish'";
        }

        // Filtre par catégorie (1+ slugs séparés par des virgules) : le pack appartient à l'une d'elles.
        if ( ! empty( $args['category'] ) ) {
            $slugs = self::clean_slug_list( $args['category'] );
            if ( $slugs ) {
                $table_categories = $this->table( 'categories' );
                $placeholders     = implode( ', ', array_fill( 0, count( $slugs ), '%s' ) );
                $where           .= " AND {$table}.category_id IN (SELECT id FROM {$table_categories} WHERE slug IN ({$placeholders}))";
                foreach ( $slugs as $slug ) {
                    $params[] = $slug;
                }
            }
        }

        // Filtre par étiquette (1+ slugs séparés par des virgules) : le pack possède l'une d'elles.
        if ( ! empty( $args['tag'] ) ) {
            $slugs = self::clean_slug_list( $args['tag'] );
            if ( $slugs ) {
                $table_tags     = $this->table( 'tags' );
                $table_tag_items = $this->table( 'tag_items' );
                $placeholders    = implode( ', ', array_fill( 0, count( $slugs ), '%s' ) );
                $where          .= " AND EXISTS (
                    SELECT 1 FROM {$table_tag_items} ti
                    INNER JOIN {$table_tags} t ON t.id = ti.tag_id
                    WHERE ti.item_type = 'pack' AND ti.item_id = {$table}.id AND t.slug IN ({$placeholders})
                )";
                foreach ( $slugs as $slug ) {
                    $params[] = $slug;
                }
            }
        }

        $order = 'ORDER BY sort_order ASC, id DESC';

        // Requête préparée uniquement lorsqu'il y a des paramètres dynamiques.
        if ( ! empty( $params ) ) {
            return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} {$where} {$order}", $params ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }

        return $wpdb->get_results( "SELECT * FROM {$table} {$where} {$order}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Insère un pack et retourne l'ID inséré.
    public function insert_pack( $data ) {
        global $wpdb;
        $table = $this->table( 'packs' );
        $wpdb->insert( $table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->insert_id;
    }

    // Met à jour un pack. Retourne le nombre de lignes affectées.
    public function update_pack( $id, $data ) {
        global $wpdb;
        $table = $this->table( 'packs' );
        return $wpdb->update( $table, $data, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Supprime logiquement un pack (statut 'trash'). Retourne le nombre de lignes affectées.
    public function delete_pack( $id ) {
        global $wpdb;
        $table = $this->table( 'packs' );
        return $wpdb->update( $table, array( 'status' => 'trash' ), array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Récupère toutes les catégories triées. Retourne un tableau d'objets.
    public function get_categories() {
        global $wpdb;
        $table = $this->table( 'categories' );
        return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY sort_order ASC, name ASC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Récupère une catégorie par son ID. Retourne un objet ligne ou null.
    public function get_category( $id ) {
        global $wpdb;
        $table = $this->table( 'categories' );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Récupère une catégorie par son slug. Retourne un objet ligne ou null.
    public function get_category_by_slug( $slug ) {
        global $wpdb;
        $table = $this->table( 'categories' );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s", $slug ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Insère une catégorie et retourne l'ID inséré.
    public function insert_category( $data ) {
        global $wpdb;
        $table = $this->table( 'categories' );
        $wpdb->insert( $table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->insert_id;
    }

    // Met à jour une catégorie. Retourne le nombre de lignes affectées.
    public function update_category( $id, $data ) {
        global $wpdb;
        $table = $this->table( 'categories' );
        return $wpdb->update( $table, $data, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Supprime une catégorie par son ID. Retourne le nombre de lignes affectées.
    public function delete_category( $id ) {
        global $wpdb;
        $table = $this->table( 'categories' );
        return $wpdb->delete( $table, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Détache une catégorie supprimée des articles (produits et packs) : remet category_id à 0.
    // Retourne le nombre total de lignes mises à jour.
    public function clear_category_links( $category_id ) {
        global $wpdb;
        $category_id = absint( $category_id );
        $updated     = 0;

        foreach ( array( 'products', 'packs' ) as $table_name ) {
            $table   = $this->table( $table_name );
            $updated += (int) $wpdb->update( $table, array( 'category_id' => 0 ), array( 'category_id' => $category_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }

        return $updated;
    }

    // Récupère toutes les étiquettes triées par nom. Retourne un tableau d'objets.
    public function get_tags() {
        global $wpdb;
        $table = $this->table( 'tags' );
        return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY name ASC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Récupère une étiquette par son ID. Retourne un objet ligne ou null.
    public function get_tag( $id ) {
        global $wpdb;
        $table = $this->table( 'tags' );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Récupère une étiquette par son slug. Retourne un objet ligne ou null.
    public function get_tag_by_slug( $slug ) {
        global $wpdb;
        $table = $this->table( 'tags' );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s", $slug ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Insère une étiquette et retourne l'ID inséré.
    public function insert_tag( $data ) {
        global $wpdb;
        $table = $this->table( 'tags' );
        $wpdb->insert( $table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->insert_id;
    }

    // Met à jour une étiquette. Retourne le nombre de lignes affectées.
    public function update_tag( $id, $data ) {
        global $wpdb;
        $table = $this->table( 'tags' );
        return $wpdb->update( $table, $data, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Supprime une étiquette par son ID. Retourne le nombre de lignes affectées.
    public function delete_tag( $id ) {
        global $wpdb;
        $table = $this->table( 'tags' );
        return $wpdb->delete( $table, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Retourne les IDs des étiquettes associées à un article (produit ou pack). Retourne un tableau d'entiers.
    public function get_item_tags( $item_type, $item_id ) {
        global $wpdb;
        $table = $this->table( 'tag_items' );
        return array_map( 'absint', (array) $wpdb->get_col( $wpdb->prepare( "SELECT tag_id FROM {$table} WHERE item_type = %s AND item_id = %d ORDER BY id ASC", $item_type, $item_id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Retire les liaisons des articles vers une étiquette supprimée. Retourne le nombre de lignes affectées.
    public function clear_tag_links( $tag_id ) {
        global $wpdb;
        $table = $this->table( 'tag_items' );
        return $wpdb->delete( $table, array( 'tag_id' => absint( $tag_id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Enregistre les étiquettes d'un article : purge des anciennes liaisons puis insertion des nouvelles.
    // Retourne vrai en cas de succès. Le type d'article est restreint à 'product' ou 'pack'.
    public function set_item_tags( $item_type, $item_id, $tag_ids ) {
        global $wpdb;

        if ( ! in_array( $item_type, array( 'product', 'pack' ), true ) ) {
            return false;
        }

        $table = $this->table( 'tag_items' );
        $wpdb->delete( $table, array( 'item_type' => $item_type, 'item_id' => $item_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        $ok = true;
        foreach ( $tag_ids as $tag_id ) {
            $tag_id = absint( $tag_id );
            if ( $tag_id <= 0 ) {
                continue;
            }
            $inserted = $wpdb->insert( $table, array( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                'item_type' => $item_type,
                'item_id'   => $item_id,
                'tag_id'    => $tag_id,
            ) );
            if ( false === $inserted ) {
                $ok = false;
                break;
            }
        }

        return $ok;
    }

    // Retourne le nom qualifié de la table de traductions selon le type d'article.
    private function translation_table( $item_type ) {
        if ( 'product' === $item_type ) {
            return $this->table( 'product_translations' );
        }
        if ( 'category' === $item_type ) {
            return $this->table( 'category_translations' );
        }
        return $this->table( 'pack_translations' );
    }

    // Retourne la clé d'association (product_id / category_id / pack_id) selon le type d'article.
    private function translation_id_key( $item_type ) {
        if ( 'product' === $item_type ) {
            return 'product_id';
        }
        if ( 'category' === $item_type ) {
            return 'category_id';
        }
        return 'pack_id';
    }

    // Récupère toutes les traductions d'un article sous forme de tableau [lang => objet ligne].
    // Retourne un tableau vide si aucune traduction n'existe.
    public function get_translations( $item_type, $item_id ) {
        global $wpdb;
        $table  = $this->translation_table( $item_type );
        $id_key = $this->translation_id_key( $item_type );
        $rows   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$id_key} = %d", $item_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        $translations = array();
        foreach ( (array) $rows as $row ) {
            $translations[ $row->lang ] = $row;
        }

        return $translations;
    }

    // Récupère la traduction d'un article dans une langue donnée. Retourne un objet ligne ou null.
    public function get_translation( $item_type, $item_id, $lang ) {
        global $wpdb;
        $table  = $this->translation_table( $item_type );
        $id_key = $this->translation_id_key( $item_type );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$id_key} = %d AND lang = %s", $item_id, $lang ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Enregistre les traductions d'un article : purge des anciennes lignes puis insertion des nouvelles.
    // « translations » est un tableau [lang => ['name', 'slug', 'description']] ; seules les langues
    // actives pourvues d'un nom non vide sont écrites. Un slug vide est déduit du nom. Retourne vrai en cas de succès.
    public function set_translations( $item_type, $item_id, $translations ) {
        global $wpdb;

        if ( ! in_array( $item_type, array( 'product', 'category', 'pack' ), true ) ) {
            return false;
        }

        $table     = $this->translation_table( $item_type );
        $id_key    = $this->translation_id_key( $item_type );
        $languages = array_keys( Flora_Helpers::flora_languages() );

        // Remplacement complet : purge de toutes les traductions de l'article puis réinsertion.
        $wpdb->delete( $table, array( $id_key => $item_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        if ( empty( $translations ) || ! is_array( $translations ) ) {
            return true;
        }

        $ok = true;
        foreach ( $translations as $lang => $entry ) {
            $lang = sanitize_title( (string) $lang );

            // Seules les langues actives du shop sont acceptées, et une traduction sans nom est ignorée.
            if ( ! in_array( $lang, $languages, true ) || empty( $entry['name'] ) ) {
                continue;
            }

            $inserted = $wpdb->insert( $table, array( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $id_key     => $item_id,
                'lang'      => $lang,
                'name'      => sanitize_text_field( $entry['name'] ),
                'slug'      => ! empty( $entry['slug'] ) ? sanitize_title( $entry['slug'] ) : sanitize_title( $entry['name'] ),
                'description' => isset( $entry['description'] ) ? wp_kses_post( (string) $entry['description'] ) : '',
            ) );

            if ( false === $inserted ) {
                $ok = false;
                break;
            }
        }

        return $ok;
    }

    // Applique la traduction d'une langue donnée à un article (remplace nom et description).
    // Le slug reste celui de la langue par défaut : les URLs des fiches restent stables entre les langues.
    // Retourne l'article localisé (ou l'article inchangé si absent / non traduit).
    public function localize_item( $item, $item_type, $lang = '' ) {
        if ( ! $item ) {
            return $item;
        }

        if ( ! $lang ) {
            $lang = Flora_Helpers::get_active_lang();
        }

        // Langue par défaut : la ligne de base contient déjà les bonnes valeurs.
        if ( $lang === Flora_Helpers::default_language() ) {
            return $item;
        }

        $translation = $this->get_translation( $item_type, $item->id, $lang );
        if ( ! $translation ) {
            return $item;
        }

        if ( '' !== $translation->name ) {
            $item->name = $translation->name;
        }
        if ( isset( $translation->description ) && '' !== $translation->description && null !== $translation->description ) {
            $item->description = $translation->description;
        }

        return $item;
    }

    // Applique la traduction en masse à une liste d'articles du même type : une seule requête
    // pour tous les IDs, puis remplace nom/description sur chaque ligne traduite.
    // Retourne la liste localisée (inchangée pour la langue par défaut ou sans traduction).
    public function hydrate_languages( $item_type, $items, $lang = '' ) {
        if ( empty( $items ) || ! is_array( $items ) ) {
            return $items;
        }

        if ( ! $lang ) {
            $lang = Flora_Helpers::get_active_lang();
        }

        if ( $lang === Flora_Helpers::default_language() ) {
            return $items;
        }

        global $wpdb;
        $table  = $this->translation_table( $item_type );
        $id_key = $this->translation_id_key( $item_type );

        $ids = array();
        foreach ( $items as $item ) {
            $ids[] = (int) $item->id;
        }
        $ids = array_values( array_unique( array_filter( $ids ) ) );

        if ( empty( $ids ) ) {
            return $items;
        }

        $placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
        $params       = array_merge( array( $lang ), $ids );
        $rows         = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE lang = %s AND {$id_key} IN ({$placeholders})", $params ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        $by_id = array();
        foreach ( (array) $rows as $row ) {
            $by_id[ (int) $row->{$id_key} ] = $row;
        }

        foreach ( $items as $i => $item ) {
            $id = (int) $item->id;
            if ( ! isset( $by_id[ $id ] ) ) {
                continue;
            }

            $translation = $by_id[ $id ];
            if ( '' !== $translation->name ) {
                $items[ $i ]->name = $translation->name;
            }
            if ( isset( $translation->description ) && '' !== $translation->description && null !== $translation->description ) {
                $items[ $i ]->description = $translation->description;
            }
        }

        return $items;
    }

    // Récupère les produits liés à un pack. Retourne un tableau d'objets.
    public function get_pack_products( $pack_id ) {
        global $wpdb;
        $table = $this->table( 'pack_products' );
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE pack_id = %d ORDER BY sort_order ASC", $pack_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Enregistre la composition d'un pack : purge des anciennes liaisons puis insertion des nouvelles, le tout dans une transaction. Retourne vrai en cas de succès.
    public function set_pack_products( $pack_id, $products ) {
        global $wpdb;
        $table = $this->table( 'pack_products' );

        // La purge + réinsertion est atomique : une panne entre les deux ne doit pas laisser le pack sans composition.
        $wpdb->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        // Purge des liaisons existantes puis réinsertion : remplacement complet de la composition du pack.
        $wpdb->delete( $table, array( 'pack_id' => $pack_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        $ok = true;
        foreach ( $products as $index => $item ) {
            $inserted = $wpdb->insert( $table, array( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                'pack_id'    => $pack_id,
                'product_id' => absint( $item['product_id'] ),
                'quantity'   => absint( $item['quantity'] ),
                'sort_order' => $index,
            ) );

            if ( false === $inserted ) {
                $ok = false;
                break;
            }
        }

        if ( $ok ) {
            $wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        } else {
            $wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }

        return $ok;
    }

    // Récupère toutes les wilayas triées par code. Retourne un tableau d'objets.
    public function get_wilayas() {
        global $wpdb;
        $table = $this->table( 'wilayas' );
        return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY code ASC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Récupère une wilaya par son code. Retourne un objet ligne ou null.
    public function get_wilaya( $code ) {
        global $wpdb;
        $table = $this->table( 'wilayas' );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE code = %d", absint( $code ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Insère une wilaya, ou la met à jour si le code existe déjà. Retourne le code de la wilaya.
    public function insert_wilaya( $data ) {
        global $wpdb;
        $table = $this->table( 'wilayas' );
        $existing = $wpdb->get_var( $wpdb->prepare( "SELECT code FROM {$table} WHERE code = %d", absint( $data['code'] ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        if ( $existing ) {
            $wpdb->update( $table, $data, array( 'code' => absint( $data['code'] ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            return absint( $data['code'] );
        }
        $wpdb->insert( $table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->insert_id;
    }

    // Supprime une wilaya par son code. Retourne le nombre de lignes affectées.
    public function delete_wilaya( $code ) {
        global $wpdb;
        $table = $this->table( 'wilayas' );
        return $wpdb->delete( $table, array( 'code' => absint( $code ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Récupère les communes, optionnellement filtrées par wilaya. Retourne un tableau d'objets.
    public function get_communes( $wilaya_code = 0 ) {
        global $wpdb;
        $table = $this->table( 'communes' );

        if ( $wilaya_code > 0 ) {
            $query = $wpdb->prepare( "SELECT * FROM {$table} WHERE wilaya_code = %d ORDER BY name ASC", absint( $wilaya_code ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        } else {
            $query = "SELECT * FROM {$table} ORDER BY name ASC"; // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }

        return $wpdb->get_results( $query );
    }

    // Insère une commune, ou la met à jour si son code postal existe déjà. Retourne l'ID de la commune.
    public function insert_commune( $data ) {
        global $wpdb;
        $table = $this->table( 'communes' );
        if ( ! empty( $data['post_code'] ) ) {
            $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE post_code = %s", $data['post_code'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            if ( $existing ) {
                $wpdb->update( $table, $data, array( 'id' => $existing ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                return $existing;
            }
        }
        $wpdb->insert( $table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->insert_id;
    }

    // Supprime une commune par son ID. Retourne le nombre de lignes affectées.
    public function delete_commune( $id ) {
        global $wpdb;
        $table = $this->table( 'communes' );
        return $wpdb->delete( $table, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Récupère le tarif de livraison à domicile : d'abord celui de la commune, sinon celui de la wilaya. Retourne un objet ligne ou null.
    public function get_shipping_rate( $wilaya_code, $commune_id = 0 ) {
        global $wpdb;
        $table = $this->table( 'shipping_rates' );

        if ( $commune_id > 0 ) {
            $rate = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE commune_id = %d", $commune_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            if ( $rate ) {
                return $rate;
            }
        }

        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE wilaya_code = %d AND commune_id = 0", absint( $wilaya_code ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Récupère le tarif « bureau de liaison » d'une wilaya (ligne wilaya, commune_id = 0).
    // Retourne un objet ligne ou null. Permet de lire bureau_fee et bureau_per_kg_fee.
    public function get_liaison_rate( $wilaya_code ) {
        global $wpdb;
        $table = $this->table( 'shipping_rates' );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE wilaya_code = %d AND commune_id = 0", absint( $wilaya_code ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Récupère l'ensemble des tarifs de livraison. Retourne un tableau d'objets.
    public function get_all_shipping_rates() {
        global $wpdb;
        $table = $this->table( 'shipping_rates' );
        return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id ASC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Insère un tarif de livraison, ou le met à jour s'il existe déjà pour la wilaya/commune.
    // Les quatre champs (domicile + bureau) sont écrits sur la même ligne. Retourne l'ID ou le nombre de lignes affectées.
    public function upsert_shipping_rate( $data ) {
        global $wpdb;
        $table = $this->table( 'shipping_rates' );

        $data['wilaya_code'] = absint( $data['wilaya_code'] );
        $data['commune_id']  = ! empty( $data['commune_id'] ) ? absint( $data['commune_id'] ) : 0;
        $data['bureau_fee']  = (float) $data['bureau_fee'];
        $data['bureau_per_kg_fee'] = (float) $data['bureau_per_kg_fee'];

        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE wilaya_code = %d AND commune_id = %d", $data['wilaya_code'], $data['commune_id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        if ( $exists ) {
            return $wpdb->update( $table, $data, array( 'id' => $exists ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        } else {
            $wpdb->insert( $table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            return $wpdb->insert_id;
        }
    }

    // Supprime un tarif de livraison par son ID. Retourne le nombre de lignes affectées.
    public function delete_shipping_rate( $id ) {
        global $wpdb;
        $table = $this->table( 'shipping_rates' );
        return $wpdb->delete( $table, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Récupère les promotions, éventuellement uniquement les promotions actives à la date du jour. Retourne un tableau d'objets.
    public function get_promotions( $active_only = false ) {
        global $wpdb;
        $table = $this->table( 'promotions' );
        $where = '';
        if ( $active_only ) {
            $today = current_time( 'Y-m-d' );
            $where = $wpdb->prepare( " WHERE status = 'active' AND (start_date IS NULL OR start_date <= %s) AND (end_date IS NULL OR end_date = '0000-00-00' OR end_date >= %s)", $today, $today ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }
        return $wpdb->get_results( "SELECT * FROM {$table} {$where} ORDER BY id DESC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Récupère une promotion par son ID. Retourne un objet ligne ou null.
    public function get_promotion( $id ) {
        global $wpdb;
        $table = $this->table( 'promotions' );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Insère une promotion après normalisation de ses dates. Retourne l'ID inséré.
    public function insert_promotion( $data ) {
        global $wpdb;
        $table = $this->table( 'promotions' );
        self::normalize_promotion_dates( $data );
        $wpdb->insert( $table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->insert_id;
    }

    // Met à jour une promotion après normalisation de ses dates. Retourne le nombre de lignes affectées.
    public function update_promotion( $id, $data ) {
        global $wpdb;
        $table = $this->table( 'promotions' );
        self::normalize_promotion_dates( $data );
        return $wpdb->update( $table, $data, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Normalise les dates de promotion par référence : une chaîne vide devient NULL pour laisser la base gérer l'absence de limite.
    // Note : passées par référence, les dates vides sont converties en NULL (valide pour des colonnes DATE).
    private static function normalize_promotion_dates( &$data ) {
        foreach ( array( 'start_date', 'end_date' ) as $field ) {
            // Une date libre est enregistrée comme NULL pour ne pas bloquer la validité de la promotion.
            if ( isset( $data[ $field ] ) && '' === $data[ $field ] ) {
                $data[ $field ] = null;
            }
        }
    }

    // Supprime une promotion par son ID. Retourne le nombre de lignes affectées.
    public function delete_promotion( $id ) {
        global $wpdb;
        $table = $this->table( 'promotions' );
        return $wpdb->delete( $table, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Récupère les remises catalogue (catégorie / type / tag), éventuellement uniquement
    // les remises actives à la date du jour. Retourne un tableau d'objets.
    public function get_catalog_discounts( $active_only = false ) {
        global $wpdb;
        $table = $this->table( 'catalog_discounts' );
        $where = '';
        if ( $active_only ) {
            $today = current_time( 'Y-m-d' );
            $where = $wpdb->prepare( " WHERE status = 'active' AND (start_date IS NULL OR start_date <= %s) AND (end_date IS NULL OR end_date = '0000-00-00' OR end_date >= %s)", $today, $today ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }
        return $wpdb->get_results( "SELECT * FROM {$table} {$where} ORDER BY id DESC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Récupère une remise catalogue par son ID. Retourne un objet ligne ou null.
    public function get_catalog_discount( $id ) {
        global $wpdb;
        $table = $this->table( 'catalog_discounts' );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Insère une remise catalogue après normalisation de ses dates. Retourne l'ID inséré.
    public function insert_catalog_discount( $data ) {
        global $wpdb;
        $table = $this->table( 'catalog_discounts' );
        self::normalize_promotion_dates( $data );
        $wpdb->insert( $table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->insert_id;
    }

    // Met à jour une remise catalogue après normalisation de ses dates. Retourne le nombre de lignes affectées.
    public function update_catalog_discount( $id, $data ) {
        global $wpdb;
        $table = $this->table( 'catalog_discounts' );
        self::normalize_promotion_dates( $data );
        return $wpdb->update( $table, $data, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Supprime une remise catalogue par son ID. Retourne le nombre de lignes affectées.
    public function delete_catalog_discount( $id ) {
        global $wpdb;
        $table = $this->table( 'catalog_discounts' );
        return $wpdb->delete( $table, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Insère une commande et retourne l'ID inséré.
    public function insert_order( $data ) {
        global $wpdb;
        $table = $this->table( 'orders' );
        $wpdb->insert( $table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->insert_id;
    }

    // Met à jour une commande. Retourne le nombre de lignes affectées.
    public function update_order( $id, $data ) {
        global $wpdb;
        $table = $this->table( 'orders' );
        return $wpdb->update( $table, $data, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Récupère une commande par son ID. Retourne un objet ligne ou null.
    public function get_order( $id ) {
        global $wpdb;
        $table = $this->table( 'orders' );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Récupère une commande par son numéro de commande. Retourne un objet ligne ou null.
    public function get_order_by_number( $order_number ) {
        global $wpdb;
        $table = $this->table( 'orders' );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE order_number = %s", $order_number ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Récupère une liste de commandes filtrées par statut, avec pagination optionnelle. Retourne un tableau d'objets.
    public function get_orders( $args = array() ) {
        global $wpdb;
        $table  = $this->table( 'orders' );
        $where  = "WHERE 1=1";
        $params = array();

        if ( ! empty( $args['status'] ) ) {
            $where   .= " AND status = %s";
            $params[] = $args['status'];
        }

        $order = 'ORDER BY id DESC';
        $limit = '';

        if ( ! empty( $args['limit'] ) ) {
            $limit = "LIMIT " . absint( $args['limit'] );
            if ( ! empty( $args['offset'] ) ) {
                $limit .= " OFFSET " . absint( $args['offset'] );
            }
        }

        // Requête préparée uniquement lorsqu'il y a des paramètres dynamiques.
        if ( ! empty( $params ) ) {
            return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} {$where} {$order} {$limit}", $params ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }

        return $wpdb->get_results( "SELECT * FROM {$table} {$where} {$order} {$limit}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Compte les commandes, optionnellement filtrées par statut. Retourne un entier.
    public function count_orders( $status = '' ) {
        global $wpdb;
        $table = $this->table( 'orders' );
        if ( $status ) {
            return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", $status ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Calcule le revenu des commandes valides, borné par une période optionnelle. Retourne un flottant.
    public function get_revenue( $start_date = '', $end_date = '' ) {
        global $wpdb;
        $table  = $this->table( 'orders' );
        $where  = "WHERE status IN ('pending', 'processing', 'completed')";
        $params = array();

        if ( $start_date ) {
            $where   .= " AND created_at >= %s";
            $params[] = $start_date . ' 00:00:00';
        }
        if ( $end_date ) {
            $where   .= " AND created_at <= %s";
            $params[] = $end_date . ' 23:59:59';
        }

        // Requête préparée uniquement lorsqu'il y a des paramètres dynamiques.
        if ( ! empty( $params ) ) {
            return (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(total), 0) FROM {$table} {$where}", $params ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }

        return (float) $wpdb->get_var( "SELECT COALESCE(SUM(total), 0) FROM {$table} {$where}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Insère une ligne de détail de commande et retourne l'ID inséré.
    public function insert_order_detail( $data ) {
        global $wpdb;
        $table = $this->table( 'order_details' );
        $wpdb->insert( $table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->insert_id;
    }

    // Récupère les lignes de détail d'une commande. Retourne un tableau d'objets.
    public function get_order_details( $order_id ) {
        global $wpdb;
        $table = $this->table( 'order_details' );
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE order_id = %d", $order_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // Décrémente le stock d'un produit en garantissant qu'il ne passe pas sous zéro (mise à jour conditionnelle à stock_qty >= quantité).
    // Retourne le nombre de lignes affectées (0 si stock insuffisant).
    public function decrement_stock( $product_id, $quantity ) {
        global $wpdb;
        $table = $this->table( 'products' );
        return $wpdb->query( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            "UPDATE {$table} SET stock_qty = stock_qty - %d, stock_status = IF(stock_qty - %d <= 0, 'outofstock', stock_status) WHERE id = %d AND stock_qty >= %d",
            $quantity,
            $quantity,
            $product_id,
            $quantity
        ) );
    }

    // Récupère les produits les plus vendus par quantité et chiffre d'affaires. Retourne un tableau d'objets.
    public function get_top_products( $limit = 5 ) {
        global $wpdb;
        $table_details = $this->table( 'order_details' );
        $table_orders   = $this->table( 'orders' );
        return $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            "SELECT od.item_id, od.item_name, SUM(od.quantity) as total_qty, SUM(od.line_total) as total_revenue
            FROM {$table_details} od
            JOIN {$table_orders} o ON od.order_id = o.id
            WHERE o.status IN ('pending', 'processing', 'completed')
            AND od.item_type = 'product'
            GROUP BY od.item_id
            ORDER BY total_qty DESC
            LIMIT %d",
            $limit
        ) );
    }

    // Regroupe par jour le nombre de commandes et le revenu sur une période donnée. Retourne un tableau d'objets.
    public function get_orders_by_date( $days = 30 ) {
        global $wpdb;
        $table = $this->table( 'orders' );
        // Date de début calculée avec le fuseau horaire du site (current_time plutôt que date()).
        $start = gmdate( 'Y-m-d', current_time( 'timestamp' ) - ( $days * DAY_IN_SECONDS ) );
        return $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            "SELECT DATE(created_at) as order_date, COUNT(*) as order_count, SUM(total) as daily_revenue
            FROM {$table}
            WHERE created_at >= %s AND status IN ('pending', 'processing', 'completed')
            GROUP BY DATE(created_at)
            ORDER BY order_date ASC",
            $start . ' 00:00:00'
        ) );
    }
}
