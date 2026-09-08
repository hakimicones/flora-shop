<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe d'importation des données géographiques (wilayas et communes)
 * à partir d'un fichier SQL dédié.
 */
class Flora_Importer {

    // Chemins candidats du fichier SQL de référence, dans l'ordre :
    // 1. données embarquées dans le plugin (pack autonome, rien à téléverser) ;
    // 2. dossier « wilaya » à la racine de WordPress (déploiements existants) ;
    // 3. dossier « wilaya » sœur de WordPress (ancien emplacement par défaut).
    private static function default_sql_paths() {
        return array(
            FLORA_SHOP_PATH . 'data/wilayas_communes.sql',
            ABSPATH . 'wilaya/mysql_wilayas_communes.sql',
            ABSPATH . '../wilaya/mysql_wilayas_communes.sql',
        );
    }

    // Récupère le chemin du fichier SQL : celui passé en argument s'il est renseigné,
    // sinon le premier chemin candidat qui existe. Retourne le chemin ou null.
    public static function resolve_sql_path( $sql_file_path = '' ) {
        if ( ! empty( $sql_file_path ) ) {
            return file_exists( $sql_file_path ) ? $sql_file_path : null;
        }

        foreach ( self::default_sql_paths() as $candidate ) {
            if ( file_exists( $candidate ) ) {
                return $candidate;
            }
        }

        return null;
    }

    // Lance l'importation complète : lit le fichier SQL puis importe les wilayas et communes.
    public static function run( $sql_file_path = '' ) {
        $sql_file_path = self::resolve_sql_path( $sql_file_path );

        if ( ! $sql_file_path ) {
            $tried = implode( ', ', self::default_sql_paths() );
            return new WP_Error( 'file_not_found', sprintf( __( 'Fichier SQL des wilayas/communes introuvable. Chemin(s) vérifié(s) : %s', 'flora-shop' ), $tried ) );
        }

        $content = file_get_contents( $sql_file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
        if ( false === $content ) {
            return new WP_Error( 'read_error', __( 'Impossible de lire le fichier SQL.', 'flora-shop' ) );
        }

        $wilayas_imported  = self::import_wilayas( $content );
        $communes_imported = self::import_communes( $content );

        return array(
            'wilayas'  => $wilayas_imported,
            'communes' => $communes_imported,
        );
    }

    // Importe les wilayas : parse les INSERT du fichier SQL et insère/met à jour en base.
    private static function import_wilayas( $content ) {
        global $wpdb;
        $table = $wpdb->prefix . 'flora_wilayas';

        $pattern = '/INSERT\s+INTO\s+wilaya\s*\([^)]*\)\s*VALUES\s*(.*?);/is';
        if ( ! preg_match( $pattern, $content, $matches ) ) {
            return 0;
        }

        $rows = self::parse_values( $matches[1] );

        $count = 0;
        foreach ( $rows as $row ) {
            if ( count( $row ) < 5 ) {
                continue;
            }

            $data = array(
                'code'      => absint( $row[0] ),
                'name'      => sanitize_text_field( $row[1] ),
                'name_ar'   => sanitize_text_field( $row[2] ),
                'latitude'  => (float) $row[3],
                'longitude' => (float) $row[4],
            );

            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT code FROM {$table} WHERE code = %d", $data['code'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            if ( $exists ) {
                $wpdb->update( $table, $data, array( 'code' => $data['code'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            } else {
                $wpdb->insert( $table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            }
            $count++;
        }

        return $count;
    }

    // Importe les communes : parse les INSERT et insère/met à jour par code postal.
    private static function import_communes( $content ) {
        global $wpdb;
        $table = $wpdb->prefix . 'flora_communes';

        preg_match_all( '/INSERT\s+INTO\s+commune\s*\([^)]*\)\s*VALUES\s*(.*?);/is', $content, $inserts );
        if ( empty( $inserts[1] ) ) {
            return 0;
        }

        $count = 0;
        foreach ( $inserts[1] as $values_block ) {
            $rows = self::parse_values( $values_block );

            foreach ( $rows as $row ) {
                if ( count( $row ) < 9 ) {
                    continue;
                }

                $post_code = $row[1];

                $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE post_code = %s", $post_code ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

                $data = array(
                    'post_code'   => sanitize_text_field( $post_code ),
                    'name'        => sanitize_text_field( $row[2] ),
                    'name_ar'     => sanitize_text_field( $row[3] ),
                    'daira'       => sanitize_text_field( $row[4] ),
                    'daira_ar'    => sanitize_text_field( $row[5] ),
                    'wilaya_code' => absint( $row[6] ),
                    'latitude'    => (float) $row[7],
                    'longitude'   => (float) $row[8],
                );

                if ( $exists ) {
                    $wpdb->update( $table, $data, array( 'id' => $exists ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                } else {
                    $wpdb->insert( $table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                }
                $count++;
            }
        }

        return $count;
    }

    // Extrait la liste des lignes (valeurs SQL) depuis un bloc VALUES en gérant les chaînes échappées.
    private static function parse_values( $values_block ) {
        $values_block = trim( $values_block );
        if ( substr( $values_block, -1 ) === ';' ) {
            $values_block = substr( $values_block, 0, -1 );
        }

        $rows   = array();
        $length = strlen( $values_block );
        $i      = 0;

        while ( $i < $length ) {
            if ( $values_block[ $i ] !== '(' ) {
                $i++;
                continue;
            }

            $j      = $i + 1;
            $in_str = false;
            $depth  = 1;

            while ( $j < $length && $depth > 0 ) {
                $c = $values_block[ $j ];

                if ( $in_str ) {
                    if ( $c === "\\" ) {
                        $j += 2;
                        continue;
                    }
                    if ( $c === "'" ) {
                        if ( $j + 1 < $length && $values_block[ $j + 1 ] === "'" ) {
                            $j += 2;
                            continue;
                        }
                        $in_str = false;
                    }
                    $j++;
                    continue;
                }

                if ( $c === "'" ) {
                    $in_str = true;
                } elseif ( $c === '(' ) {
                    $depth++;
                } elseif ( $c === ')' ) {
                    $depth--;
                    if ( $depth === 0 ) {
                        $row_text = substr( $values_block, $i, $j - $i + 1 );
                        $rows[]   = self::parse_row_tokens( $row_text );
                        break;
                    }
                }

                $j++;
            }

            $i = $j + 1;
        }

        return $rows;
    }

    // Tokenise une seule ligne SQL (parenthèses) en un tableau de valeurs PHP.
    private static function parse_row_tokens( $row_text ) {
        $row_text = trim( $row_text );
        if ( substr( $row_text, 0, 1 ) === '(' ) {
            $row_text = substr( $row_text, 1 );
        }
        if ( substr( $row_text, -1 ) === ')' ) {
            $row_text = substr( $row_text, 0, -1 );
        }

        $tokens = array();
        $length = strlen( $row_text );
        $i      = 0;

        while ( $i < $length ) {
            $c = $row_text[ $i ];

            if ( $c === ' ' || $c === ',' ) {
                $i++;
                continue;
            }

            if ( $c === "'" ) {
                $j = $i + 1;
                $value = '';
                while ( $j < $length ) {
                    $c2 = $row_text[ $j ];
                    if ( $c2 === "\\" ) {
                        $value .= $row_text[ $j + 1 ];
                        $j += 2;
                        continue;
                    }
                    if ( $c2 === "'" ) {
                        if ( $j + 1 < $length && $row_text[ $j + 1 ] === "'" ) {
                            $value .= "'";
                            $j += 2;
                            continue;
                        }
                        $j++;
                        break;
                    }
                    $value .= $c2;
                    $j++;
                }
                $tokens[] = $value;
                $i = $j;
                continue;
            }

            $j = $i;
            $value = '';
            while ( $j < $length && $row_text[ $j ] !== ',' && $row_text[ $j ] !== ')' && $row_text[ $j ] !== ' ' ) {
                $value .= $row_text[ $j ];
                $j++;
            }
            $tokens[] = $value;
            $i = $j;
        }

        return $tokens;
    }
}
