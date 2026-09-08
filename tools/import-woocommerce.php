<?php
/**
 * Outil CLI : importation des catégories, produits et packs WooCommerce
 * vers les tables custom Flora Shop.
 *
 * Usage :
 *   php tools/import-woocommerce.php --mode=categories [--dry-run]
 *   php tools/import-woocommerce.php --mode=products  [--dry-run]
 *
 * Notes :
 *   - Nécessite WooCommerce et le plugin Flora Shop actifs.
 *   - « --mode=products » écrase (suppression SQL) les produits/packs Flora
 *     avant de réimporter les données WooCommerce.
 *   - Les prix sont importés tels quels (TTC boutique) ; pour les packs
 *     variables, seul le prix de la première variation est conservé.
 *   - Les traductions arabes sont importées via Polylang si disponibles,
 *     sinon repli sur le contenu par défaut (fr).
 *
 * @package Flora_Shop
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( "Ce script doit être exécuté en ligne de commande.\n" );
}

// Bootstrap WordPress depuis le dossier du script.
define( 'WP_USE_THEMES', false );
if ( ! defined( 'ABSPATH' ) ) {
	$candidate = dirname( __DIR__ );
	$found     = false;
	while ( ! $found && strlen( $candidate ) > 3 ) {
		if ( file_exists( $candidate . '/wp-load.php' ) ) {
			$found = true;
			define( 'ABSPATH', rtrim( $candidate, '/' ) . '/' );
			break;
		}
		$parent = dirname( $candidate );
		if ( $parent === $candidate ) {
			break;
		}
		$candidate = $parent;
	}
	if ( ! $found ) {
		exit( "wp-load.php introuvable. Placez le script sous la racine WordPress.\n" );
	}
}

require ABSPATH . 'wp-load.php';


// --- Options de ligne de commande -------------------------------------------------

$args      = array();
$mode      = 'products';
$dry_run   = false;

foreach ( array_slice( $argv, 1 ) as $arg ) {
	if ( 0 === strpos( $arg, '--mode=' ) ) {
		$mode = substr( $arg, 7 );
	} elseif ( '--dry-run' === $arg ) {
		$dry_run = true;
	} elseif ( '--help' === $arg || '-h' === $arg ) {
		echo "Usage : php tools/import-woocommerce.php --mode=categories|products [--dry-run]\n";
		exit( 0 );
	}
}

if ( ! in_array( $mode, array( 'categories', 'products' ), true ) ) {
	exit( "Mode inconnu : {$mode}. Utilisez --mode=categories ou --mode=products.\n" );
}

// --- Préconditions -----------------------------------------------------------------

if ( ! class_exists( 'WooCommerce' ) ) {
	exit( "WooCommerce n'est pas actif.\n" );
}
if ( ! class_exists( 'Flora_DB' ) ) {
	exit( "Le plugin Flora Shop ('Flora_DB') n'est pas actif.\n" );
}

global $wpdb;
$db     = Flora_DB::get_instance();
$prefix = $wpdb->prefix . 'flora_';

// S'assure que les tables/colonnes nécessaires existent (parent_id categories,
// category_translations, etc.).
if ( class_exists( 'Flora_Activator' ) && method_exists( 'Flora_Activator', 'maybe_upgrade' ) ) {
	Flora_Activator::maybe_upgrade();
}

/**
 * Affiche un message de log préfixé.
 *
 * @param string $message Le message à afficher.
 */
function flora_cli_log( $message ) {
	echo '[flora-import] ' . $message . PHP_EOL;
}

/**
 * Retourne un slug unique.
 *
 * @param string $value Chaîne source.
 * @return string Slug nettoyé.
 */
function flora_cli_slug( $value ) {
	$slug = sanitize_title( $value );
	return $slug ? $slug : 'element-' . time() . '-' . wp_rand( 100, 999 );
}

/**
 * Récupère l'URL de l'image mise en avant d'un post.
 *
 * @param int $post_id ID du post.
 * @return string URL ou chaîne vide.
 */
function flora_cli_thumbnail_url( $post_id ) {
	$thumb = get_post_thumbnail_id( $post_id );
	if ( ! $thumb ) {
		return '';
	}
	$url = wp_get_attachment_url( $thumb );
	return $url ? $url : '';
}

/**
 * Résout l'ID de la catégorie Flora correspondant à la première catégorie
 * WooCommerce (product_cat) non « uncategorized » d'un post.
 *
 * @param int   $post_id        ID du post WooCommerce.
 * @param array $wc_to_flora_map Map wc_term_id => flora_category_id.
 * @return int ID de catégorie Flora, ou 0.
 */
function flora_cli_resolve_category( $post_id, $wc_to_flora_map ) {
	$terms = wp_get_post_terms( $post_id, 'product_cat' );
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return 0;
	}

	foreach ( $terms as $term ) {
		if ( 'uncategorized' === $term->slug ) {
			continue;
		}
		if ( isset( $wc_to_flora_map[ $term->term_id ] ) && (int) $wc_to_flora_map[ $term->term_id ] > 0 ) {
			return (int) $wc_to_flora_map[ $term->term_id ];
		}
	}

	return 0;
}

// ===================================================================================
// MODE : CATEGORIES
// ===================================================================================

if ( 'categories' === $mode ) {
	flora_cli_log( 'Import des catégories product_cat → flora_categories' . ( $dry_run ? ' (dry-run, aucune écriture)' : '' ) );

	$wc_cats = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'orderby'    => 'term_group',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $wc_cats ) || empty( $wc_cats ) ) {
		exit( 'Aucune catégorie product_cat trouvée.' . PHP_EOL );
	}

	if ( ! $dry_run ) {
		// Suppression SQL des catégories et de leurs traductions.
		$wpdb->query( "DELETE FROM {$prefix}category_translations" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( "DELETE FROM {$prefix}categories" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	$created      = 0;
	$ar_imported  = 0;
	$wc_to_flora  = array(); // wc_term_id => flora_category_id
	$sort         = 0;

	// Première passe : catégories racine puis enfants (tri par parent).
	$ordered = $wc_cats;
	usort(
		$ordered,
		function ( $a, $b ) {
			return ( (int) $a->parent === (int) $b->parent )
				? strcmp( (string) $a->name, (string) $b->name )
				: ( (int) $a->parent > (int) $b->parent ? 1 : -1 );
		}
	);

	foreach ( $ordered as $cat ) {
		if ( 'uncategorized' === $cat->slug ) {
			flora_cli_log( "  - (ignoré) catégorie 'uncategorized'" );
			continue;
		}

		$slug = flora_cli_slug( $cat->name );
		// Évite un conflit de slug avec 'uncategorized'.
		if ( 'uncategorized' === $slug ) {
			$slug .= '-o';
		}

		$parent_id = 0;
		if ( ! empty( $cat->parent ) && isset( $wc_to_flora[ $cat->parent ] ) ) {
			$parent_id = $wc_to_flora[ $cat->parent ];
		}

		if ( $dry_run ) {
			flora_cli_log( "  - (dry) catégorie '{$cat->name}' slug='{$slug}' parent={$parent_id} (wc term {$cat->term_id})" );
			$created ++;
			continue;
		}

		$id = $db->insert_category(
			array(
				'name'       => $cat->name,
				'slug'       => $slug,
				'parent_id'  => $parent_id,
				'description'=> '',
				'sort_order' => $sort ++,
			)
		);

		if ( ! $id ) {
			flora_cli_log( "  - (échec) insertion catégorie '{$cat->name}'" );
			continue;
		}

		$created ++;
		$wc_to_flora[ $cat->term_id ] = (int) $id;

		// Traduction arabe Polylang (term).
		if ( function_exists( 'pll_get_term_translations' ) ) {
			$translations = pll_get_term_translations( $cat->term_id );
			$ar_id        = ! empty( $translations['ar'] ) ? (int) $translations['ar'] : 0;

			if ( $ar_id ) {
				$ar_name = get_term_field( 'name', $ar_id, 'product_cat' );
				$ar_slug = get_term_field( 'slug', $ar_id, 'product_cat' );

				if ( $ar_name && ! is_wp_error( $ar_name ) && strip_tags( (string) $ar_name ) !== $cat->name ) {
					$ok = $db->set_translations(
						'category',
						$id,
						array(
							'ar' => array(
								'name'        => $ar_name,
								'slug'        => ! is_wp_error( $ar_slug ) ? $ar_slug : '',
								'description' => '',
							),
						)
					);
					if ( $ok ) {
						$ar_imported ++;
					}
				}
			}
		}
	}

	flora_cli_log( "Terminé : {$created} catégories, {$ar_imported} traductions arabes." );
	exit( 0 );
}

// ===================================================================================
// MODE : PRODUCTS
// ===================================================================================

flora_cli_log( 'Import des produits et packs WooCommerce → Flora Shop' . ( $dry_run ? ' (dry-run, aucune écriture)' : '' ) );

// --- Nettoyage SQL préalable ------------------------------------------------------

if ( ! $dry_run ) {
	flora_cli_log( 'Suppression SQL des données produits/packs/pack_products existantes.' );
	$wpdb->query( "DELETE FROM {$prefix}product_translations" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->query( "DELETE FROM {$prefix}products" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->query( "DELETE FROM {$prefix}pack_translations" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->query( "DELETE FROM {$prefix}packs" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->query( "DELETE FROM {$prefix}pack_products" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

// --- Étape A : import des catégories (pour mapper category_id) ---------------------

flora_cli_log( 'Étape A : import des catégories WooCommerce.' );

$wc_cats = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'orderby'    => 'term_group',
		'order'      => 'ASC',
	)
);
$wc_to_flora_map = array(); // wc_term_id => flora_category_id
$translation_order = array();

if ( ! is_wp_error( $wc_cats ) && ! empty( $wc_cats ) ) {
	if ( ! $dry_run ) {
		$wpdb->query( "DELETE FROM {$prefix}category_translations" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( "DELETE FROM {$prefix}categories" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	$ordered = $wc_cats;
	usort(
		$ordered,
		function ( $a, $b ) {
			return ( (int) $a->parent === (int) $b->parent )
				? strcmp( (string) $a->name, (string) $b->name )
				: ( (int) $a->parent > (int) $b->parent ? 1 : -1 );
		}
	);

	$sort = 0;
	foreach ( $ordered as $cat ) {
		if ( 'uncategorized' === $cat->slug ) {
			continue;
		}
		$slug = flora_cli_slug( $cat->name );
		if ( 'uncategorized' === $slug ) {
			$slug .= '-o';
		}

		$parent_id = 0;
		if ( ! empty( $cat->parent ) && isset( $wc_to_flora_map[ $cat->parent ] ) ) {
			$parent_id = $wc_to_flora_map[ $cat->parent ];
		}

		if ( $dry_run ) {
			$wc_to_flora_map[ $cat->term_id ] = -1;
			continue;
		}

		$id = $db->insert_category(
			array(
				'name'        => $cat->name,
				'slug'        => $slug,
				'parent_id'   => $parent_id,
				'description' => '',
				'sort_order'  => $sort ++,
			)
		);

		if ( $id ) {
			$wc_to_flora_map[ $cat->term_id ] = (int) $id;

			if ( function_exists( 'pll_get_term_translations' ) ) {
				$translations = pll_get_term_translations( $cat->term_id );
				$ar_id        = ! empty( $translations['ar'] ) ? (int) $translations['ar'] : 0;
				if ( $ar_id ) {
					$ar_name = get_term_field( 'name', $ar_id, 'product_cat' );
					$ar_slug = get_term_field( 'slug', $ar_id, 'product_cat' );
					if ( $ar_name && ! is_wp_error( $ar_name ) && strip_tags( (string) $ar_name ) !== $cat->name ) {
						$db->set_translations(
							'category',
							$id,
							array(
								'ar' => array(
									'name'        => $ar_name,
									'slug'        => ! is_wp_error( $ar_slug ) ? $ar_slug : '',
									'description' => '',
								),
							)
						);
					}
				}
			}
		}
	}
}

// --- Pré-condition : tags existants ------------------------------------------------

$existing_tags = array();
foreach ( (array) $db->get_tags() as $tag ) {
	$existing_tags[ $tag->slug ] = (int) $tag->id;
}

/**
 * Récupère (ou crée) l'ID d'un tag Flora par son slug.
 *
 * @param string $slug Slug du tag.
 * @param bool   $dry_run Mode simulation.
 * @return int ID du tag ou 0.
 */
$get_or_create_tag = function ( $slug ) use ( $db, &$existing_tags, $dry_run ) {
	$slug = flora_cli_slug( $slug );
	if ( isset( $existing_tags[ $slug ] ) ) {
		return $existing_tags[ $slug ];
	}
	if ( $dry_run ) {
		return -1;
	}
	$id = $db->insert_tag( array( 'name' => ucfirst( str_replace( '-', ' ', $slug ) ), 'slug' => $slug ) );
	if ( $id ) {
		$existing_tags[ $slug ] = (int) $id;
	}
	return (int) $id;
};

// --- Données produits WooCommerce ---------------------------------------------------

// Produits simples + parents de packs (hors variations) publiés, dans la langue par
// défaut uniquement (les traductions arabes sont importées via Polylang plus bas).
$default_lang = function_exists( 'pll_default_language' ) ? pll_default_language() : 'fr';
$lang_filter  = '';

if ( $default_lang ) {
	$lang_filter = "AND EXISTS (
		SELECT 1 FROM {$wpdb->term_relationships} tr_lang
		INNER JOIN {$wpdb->term_taxonomy} tt_lang ON tt_lang.term_taxonomy_id = tr_lang.term_taxonomy_id
		INNER JOIN {$wpdb->terms} t_lang ON t_lang.term_id = tt_lang.term_id
		WHERE tr_lang.object_id = {$wpdb->posts}.ID
		  AND tt_lang.taxonomy = 'language'
		  AND t_lang.slug = %s
	)";
}

$query = "SELECT ID, post_title, post_name, post_content, post_type, post_status
	 FROM {$wpdb->posts}
	 WHERE post_type = 'product'
	   AND post_status = 'publish'
	   {$lang_filter}
	 ORDER BY ID";

if ( $lang_filter ) {
	$query = $wpdb->prepare( $query, $default_lang ); // phpcs:ignore WordPress.DB.PreparedSQL
}

$posts = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

$count_products = 0;
$count_packs    = 0;
$count_matched  = 0;

foreach ( $posts as $post ) {
	$wc_product = wc_get_product( $post->ID );
	if ( ! $wc_product ) {
		continue;
	}

	$is_variable = $wc_product->is_type( 'variable' );

	if ( $is_variable ) {
		// ===== PACK =====
		flora_cli_log( '  (pack) ' . $post->post_title );

		$variations = $wc_product->get_children();
		$base_price = 0;
		if ( is_array( $variations ) ) {
			foreach ( $variations as $var_id ) {
				$var = wc_get_product( $var_id );
				if ( $var ) {
					$base_price = (float) $var->get_price();
					break; // première variation uniquement.
				}
			}
		}

		$slug    = flora_cli_slug( $post->post_name ? $post->post_name : $post->post_title );
		$img_url = flora_cli_thumbnail_url( $post->ID );

		if ( $dry_run ) {
			$count_packs ++;
			flora_cli_log( "    - (dry) pack '{$post->post_title}' slug='{$slug}' prix={$base_price} img=" . ( $img_url ? 'oui' : 'non' ) );
		} else {
			$pack_id = $db->insert_pack(
				array(
					'name'        => $post->post_title,
					'slug'        => $slug,
					'pack_price'  => $base_price,
					'description' => $post->post_content,
					'image_url'   => $img_url,
					'category_id' => flora_cli_resolve_category( $post->ID, $wc_to_flora_map ),
					'status'      => 'publish',
					'sort_order'  => $count_packs,
				)
			);

			if ( ! $pack_id ) {
				flora_cli_log( "    - (échec) insertion pack '{$post->post_title}'" );
				continue;
			}
			$count_packs ++;

			// Traduction arabe du pack (Polylang).
			if ( function_exists( 'pll_get_post' ) ) {
				$ar_id = pll_get_post( $post->ID, 'ar' );
				if ( $ar_id ) {
					$db->set_translations(
						'pack',
						$pack_id,
						array(
							'ar' => array(
								'name'        => get_the_title( $ar_id ),
								'slug'        => get_post_field( 'post_name', $ar_id ),
								'description' => get_post_field( 'post_content', $ar_id ),
							),
						)
					);
				}
			}

			// Matching automatique pack → produits à partir du contenu FR.
			$linked = flora_cli_match_pack_products( $post->post_content, $db );
			foreach ( $linked as $item ) {
				$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					"{$prefix}pack_products",
					array(
						'pack_id'    => $pack_id,
						'product_id' => $item['product_id'],
						'quantity'   => $item['quantity'],
						'sort_order' => $item['sort'],
					)
				);
				$count_matched ++;
			}
		}

		continue;
	}

	// ===== PRODUIT SIMPLE =====
	$price       = (float) $wc_product->get_price();
	$regular     = (float) $wc_product->get_regular_price();
	if ( $price <= 0 && $regular > 0 ) {
		$price = $regular;
	}

	$weight      = $wc_product->get_weight();
	$weight      = ( '' === $weight || null === $weight ) ? null : (float) $weight;

	$stock_qty   = (int) $wc_product->get_stock_quantity();
	$manage      = $wc_product->get_manage_stock();
	$stock_status = $wc_product->get_stock_status();
	if ( empty( $stock_status ) ) {
		$stock_status = 'instock';
	}
	if ( ! $manage && 'outofstock' !== $stock_status ) {
		$stock_qty = 0;
	}

	$slug    = flora_cli_slug( $post->post_name ? $post->post_name : $post->post_title );
	$img_url = flora_cli_thumbnail_url( $post->ID );

	// Catégorie principale : première product_cat mappée.
	$category_id = flora_cli_resolve_category( $post->ID, $wc_to_flora_map );

	// Catégories restantes → tags (ignorées pour les packs : celles-ci portent la
	// catégorie "Packs Femme"/"Packs Homme", pas de sous-catégorie produit).
	$other_slugs = array();
	$wc_cats     = wp_get_post_terms( $post->ID, 'product_cat' );
	if ( ! is_wp_error( $wc_cats ) ) {
		foreach ( $wc_cats as $idx => $c ) {
			if ( 'uncategorized' === $c->slug || isset( $wc_to_flora_map[ $c->term_id ] ) ) {
				continue;
			}
			$other_slugs[] = $c->slug;
		}
	}

	if ( $dry_run ) {
		$count_products ++;
		flora_cli_log( "  - (dry) produit '{$post->post_title}' slug='{$slug}' prix={$price} cat={$category_id}" );
	} else {
		$product_id = $db->insert_product(
			array(
				'name'         => $post->post_title,
				'slug'         => $slug,
				'description'  => $post->post_content,
				'price'        => $price,
				'weight'       => $weight,
				'image_url'    => $img_url,
				'stock_qty'    => $stock_qty,
				'stock_status' => $stock_status,
				'status'       => 'publish',
				'category_id'  => $category_id,
				'sort_order'   => $count_products,
			)
		);

		if ( ! $product_id ) {
			flora_cli_log( "  - (échec) insertion produit '{$post->post_title}'" );
			continue;
		}
		$count_products ++;

		// Catégories restantes → tags.
		$tag_ids = array();
		foreach ( $other_slugs as $tslug ) {
			$tid = $get_or_create_tag( $tslug );
			if ( $tid > 0 ) {
				$tag_ids[] = $tid;
			}
		}
		if ( $tag_ids ) {
			$db->set_item_tags( 'product', $product_id, $tag_ids );
		}

		// Traduction arabe du produit (Polylang).
		if ( function_exists( 'pll_get_post' ) ) {
			$ar_id = pll_get_post( $post->ID, 'ar' );
			if ( $ar_id ) {
				$db->set_translations(
					'product',
					$product_id,
					array(
						'ar' => array(
							'name'        => get_the_title( $ar_id ),
							'slug'        => get_post_field( 'post_name', $ar_id ),
							'description' => get_post_field( 'post_content', $ar_id ),
						),
					)
				);
			}
		}
	}
}

flora_cli_log(
	sprintf(
		'Terminé : %d produits, %d packs, %d liaisons pack→produit.',
		$count_products,
		$count_packs,
		$count_matched
	)
);
exit( 0 );

/**
 * Tente d'associer les produits listés dans la description d'un pack
 * aux produits Flora déjà importés (matching par sous-chaîne des noms).
 *
 * @param string  $description Contenu HTML du pack.
 * @param Flora_DB $db Instance Flora_DB.
 * @return array Liste de ['product_id', 'quantity', 'sort'].
 */
function flora_cli_match_pack_products( $description, $db ) {
	$lines = preg_split( '/\R/', (string) $description );
	$out   = array();
	$sort  = 0;

	foreach ( $lines as $line ) {
		$line = trim( strip_tags( $line ) );
		if ( '' === $line ) {
			continue;
		}

		// Les descriptions de packs listent les articles avec un index numérique
		// ("1 Stick ...", "2 Roll-On ...") : ce n'est PAS une quantité, chaque
		// article est présent une fois dans le pack.
		$name = $line;
		if ( preg_match( '/^\s*(\d+)\s+(.*)$/u', $line, $m ) ) {
			$name = trim( $m[2] );
		}
		if ( '' === $name ) {
			continue;
		}

		$product_id = flora_cli_find_product_wooish( $name, $db );
		if ( $product_id > 0 ) {
			$out[] = array(
				'product_id' => $product_id,
				'quantity'   => 1,
				'sort'       => $sort ++,
			);
		}
	}

	return $out;
}

/**
 * Cherche un produit Flora correspondant à une ligne de contenu de pack.
 *
 * Stratégie : on identifie la famille (stick, roll-on, brume, crème, masque,
 * savon, gel, shampooing, sérum…) et le genre (femme/homme) de la ligne, puis on
 * restreint la recherche aux produits de la / des catégorie(s) correspondante(s).
 * Le nom de la famille / les mots-clés restants sont ensuite comparés aux noms
 * de produits (matching par sous-chaîne normalisée, ratio de mots >= 0.5).
 *
 * @param string  $name Nom de la ligne (ex : "Stick Déodorant Femme YARA").
 * @param Flora_DB $db Instance Flora_DB.
 * @return int ID du produit ou 0.
 */
function flora_cli_find_product_wooish( $name, $db ) {
	$name = trim( (string) $name );
	if ( '' === $name ) {
		return 0;
	}

	$normalize = function ( $s ) {
		$s = mb_strtolower( $s, 'UTF-8' );
		$s = str_replace(
			array( 'é', 'è', 'ê', 'ë', 'à', 'â', 'ô', 'î', 'ï', 'û', 'ù', 'ç', "'", '’', '&#8217;', '&' ),
			array( 'e', 'e', 'e', 'e', 'a', 'a', 'o', 'i', 'i', 'u', 'u', 'c', ' ', ' ', ' ', ' ' ),
			$s
		);
		return trim( preg_replace( '/\s+/', ' ', $s ) );
	};

	$ref = $normalize( $name );

	// Famille -> slug(s) de catégorie(s) Flora candidates.
	// L'ordre compte : les correspondances les plus précises d'abord.
	$family_map = array(
		'stick deodorant'        => array( 'stick-homme', 'stick-femme' ),
		'stick'                  => array( 'stick-homme', 'stick-femme' ),
		'roll-on'                => array( 'roll-on-homme', 'roll-on-femme' ),
		'roll on'                => array( 'roll-on-homme', 'roll-on-femme' ),
		'rollon'                 => array( 'roll-on-homme', 'roll-on-femme' ),
		'brume'                  => array( 'brume-homme', 'brume-femme' ),
		'creme deodorante'       => array( 'creme-deodorante-homme', 'creme-deodorante-femme' ),
		'creme'                  => array( 'creme-deodorante-homme', 'creme-deodorante-femme' ),
		'masque d argile'        => array( 'masque-a-largile-verte' ),
		'masque argile'          => array( 'masque-a-largile-verte' ),
		'masque'                 => array( 'masque-capillaire', 'masque-a-largile-verte' ),
		'savon dzair'            => array( 'savon-dzair', 'savon-dose' ),
		'savon'                  => array( 'savon-dose', 'savon-dzair' ),
		'gel douche'             => array( 'gel-douche-homme', 'gel-douche-femme' ),
		'shampooing'             => array( 'shampooing-homme', 'shampooing-femme', 'apres-shampooing' ),
		'apres shampooing'       => array( 'apres-shampooing' ),
		'serum'                  => array( 'serum' ),
		'huile'                  => array( 'serum', 'masque-capillaire', 'masque-a-largile-verte', 'apres-shampooing', 'shampooing-femme', 'shampooing-homme' ),
	);

	$family_cats = array();
	foreach ( $family_map as $key => $cats ) {
		if ( false !== strpos( $ref, $key ) ) {
			$family_cats = $cats;
			break;
		}
	}

	// Genre : resserre les candidats quand la ligne le précise.
	if ( false !== strpos( $ref, 'femme' ) ) {
		$family_cats = array_values( array_filter( $family_cats, function ( $s ) { return false !== strpos( $s, 'femme' ); } ) );
	} elseif ( false !== strpos( $ref, 'homme' ) ) {
		$family_cats = array_values( array_filter( $family_cats, function ( $s ) { return false !== strpos( $s, 'homme' ); } ) );
	}

	// Catégories Flora correspondant aux slugs candidats.
	$cat_by_slug = array();
	foreach ( (array) $db->get_categories() as $cat ) {
		$cat_by_slug[ $cat->slug ] = (int) $cat->id;
	}

	$candidate_cat_ids = array();
	foreach ( $family_cats as $slug ) {
		if ( isset( $cat_by_slug[ $slug ] ) && ! in_array( $cat_by_slug[ $slug ], $candidate_cat_ids, true ) ) {
			$candidate_cat_ids[] = $cat_by_slug[ $slug ];
		}
	}

	$family_words = array( 'stick', 'deodorant', 'roll', 'brume', 'creme', 'masque', 'savon', 'gel', 'douche', 'shampooing', 'apres', 'serum', 'huile', 'dzair', 'dose', 'dargile', 'argile', 'verte', 'femme', 'homme', 'ml', 'g' );
	$stopwords    = array( 'et', 'ou', 'de', 'le', 'la', 'les', 'une', 'un', 'du', 'des', 'a', 'au', 'aux', 'pour', 'sur', 'avec', 'sans', 'est', 'sont' );

	$keywords = array();
	foreach ( preg_split( '/\s+/', $ref ) as $w ) {
		if ( '' === $w || in_array( $w, $family_words, true ) || in_array( $w, $stopwords, true ) ) {
			continue;
		}
		$keywords[] = $w;
	}

	// Aucun mot-clé restant -> impossible de discriminer.
	if ( empty( $keywords ) ) {
		return 0;
	}

	$best_id  = 0;
	$best_ok  = 0;

	foreach ( (array) $db->get_products() as $prod ) {
		$prod_ref = $normalize( $prod->name );

		// Restriction aux catégories candidates quand elles existent.
		if ( $candidate_cat_ids && ! in_array( (int) $prod->category_id, $candidate_cat_ids, true ) ) {
			continue;
		}

		$match_count = 0;
		foreach ( $keywords as $kw ) {
			if ( false !== strpos( $prod_ref, $kw ) ) {
				$match_count ++;
			}
		}

		$ratio = $match_count / count( $keywords );
		if ( $ratio >= 0.5 && $ratio > $best_ok ) {
			$best_ok = $ratio;
			$best_id = (int) $prod->id;
		}
	}

	return $best_id;
}
