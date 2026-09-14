<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Aides communes aux listes admin (produits, packs, promotions, commandes…).
 *
 * Fournit la pagination WP (per_page = 20), le calcul de l'offset courant et la
 * barre de filtres/résultats réutilisable : champ de recherche, menus déroulants
 * de filtres, boutons « Filtrer » / « Réinitialiser » / « Exporter Excel ».
 * La barre est un formulaire GET qui conserve page, onglet et les valeurs des
 * filtres actifs ; l'export pointe vers le même jeu de filtres via un URL noncé.
 */
class Flora_Admin_List {

    // Nombre d'éléments affichés par page sur les grandes listes.
    const PER_PAGE = 20;

    // Page courante (GET 'paged'), bornée à 1 au minimum.
    public static function current_page() {
        $paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
        return max( 1, $paged );
    }

    // Nombre d'éléments par page.
    public static function per_page() {
        return self::PER_PAGE;
    }

    // Offset SQL de la page courante.
    public static function offset() {
        return ( self::current_page() - 1 ) * self::per_page();
    }

    // Construit le nombre total de pages à partir du total d'éléments.
    public static function total_pages( $total ) {
        return (int) ceil( $total / self::per_page() );
    }

    // Affiche les liens de pagination WP (vide si une seule page). $base = liste
    // des query vars à conserver (page, onglet, filtres actifs — sans 'paged').
    public static function paginate( $total, $base = array() ) {
        $pages = self::total_pages( $total );
        if ( $pages <= 1 ) {
            return '';
        }

        $base['paged'] = '%#%';

        return paginate_links( array(
            'base'      => add_query_arg( $base, admin_url( 'admin.php' ) ),
            'format'    => '',
            'current'   => self::current_page(),
            'total'     => $pages,
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
        ) );
    }

    // Affiche la barre de filtres : recherche, selects ($config['filters'] :
    // name/label/options/current), boutons Filtrer/Réinitialiser et Exporter Excel.
    public static function bar( $config ) {
        $page        = isset( $config['page'] ) ? $config['page'] : '';
        $tab         = isset( $config['tab'] ) ? $config['tab'] : '';
        $search      = isset( $config['search'] ) ? $config['search'] : '';
        $filters     = isset( $config['filters'] ) ? $config['filters'] : array();
        $export_args = isset( $config['export_args'] ) ? (array) $config['export_args'] : array();

        // Détecte un filtre actif pour afficher le lien « Réinitialiser ».
        $has_active = '' !== $search;
        foreach ( $filters as $f ) {
            if ( isset( $f['current'] ) && '' !== $f['current'] ) {
                $has_active = true;
            }
        }

        echo '<div class="flora-filter-bar">';
        echo '<form method="get" class="flora-filter-form">';
        echo '<input type="hidden" name="page" value="' . esc_attr( $page ) . '">';
        if ( $tab ) {
            echo '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '">';
        }

        echo '<label class="flora-search-field">';
        echo '<span class="screen-reader-text">' . esc_html__( 'Rechercher', 'flora-shop' ) . '</span>';
        echo '<input type="search" name="flora_s" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr__( 'Rechercher…', 'flora-shop' ) . '">';
        echo '</label>';

        foreach ( $filters as $f ) {
            $name    = isset( $f['name'] ) ? $f['name'] : '';
            $label   = isset( $f['label'] ) ? $f['label'] : '';
            $options = isset( $f['options'] ) ? $f['options'] : array();
            $current = isset( $f['current'] ) ? $f['current'] : '';

            echo '<label class="flora-filter-field">' . esc_html( $label ) . ' ';
            if ( isset( $f['type'] ) && 'date' === $f['type'] ) {
                echo '<input type="date" name="' . esc_attr( $name ) . '" value="' . esc_attr( $current ) . '">';
            } else {
                echo '<select name="' . esc_attr( $name ) . '">';
                echo '<option value="">' . esc_html__( 'Tous', 'flora-shop' ) . '</option>';
                foreach ( $options as $value => $option_label ) {
                    echo '<option value="' . esc_attr( $value ) . '"' . selected( $current, $value, false ) . '>' . esc_html( $option_label ) . '</option>';
                }
                echo '</select>';
            }
            echo '</label>';
        }

        echo '<button type="submit" class="button">' . esc_html__( 'Filtrer', 'flora-shop' ) . '</button>';

        if ( $has_active ) {
            $reset_query = array( 'page' => $page );
            if ( $tab ) {
                $reset_query['tab'] = $tab;
            }
            echo '<a class="button flora-reset-btn" href="' . esc_url( add_query_arg( $reset_query, admin_url( 'admin.php' ) ) ) . '">' . esc_html__( 'Réinitialiser', 'flora-shop' ) . '</a>';
        }
        echo '</form>';

        echo '<a class="button button-primary flora-export-btn" href="' . esc_url( Flora_Exporter::export_url( $export_args ) ) . '">' . esc_html__( 'Exporter Excel', 'flora-shop' ) . '</a>';
        echo '</div>';
    }
}