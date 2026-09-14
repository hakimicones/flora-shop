<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Export Excel (.xlsx) des listes admin via PhpSpreadsheet.
 *
 * Produit un vrai classeur XLSX (officiellement ouvert sous Excel / LibreOffice),
 * encodé en UTF-8 (texte arabe géré nativement). S'utilise en GET depuis une
 * page admin : le bouton « Exporter Excel » pointe vers un URL signé par nonce
 * sur la même page (flora_export=1), le contrôleur reconstruit les colonnes et
 * lignes selon les filtres actifs puis appelle Flora_Exporter::handle_export().
 * Accès strictement restreint à la capability 'manage_options'.
 */
class Flora_Exporter {

    // Vérifie la capacité et le nonce avant tout export. Termine le script si invalide.
    private static function verify() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Accès non autorisé.', 'flora-shop' ) );
        }
        check_admin_referer( 'flora_export' );
    }

    // Construit l'URL du bouton d'export en préservant les filtres actifs et le nonce.
    public static function export_url( $query = array() ) {
        $query['flora_export'] = '1';
        return wp_nonce_url( add_query_arg( $query, admin_url( 'admin.php' ) ), 'flora_export' );
    }

    // Génère et télécharge un classeur .xlsx : $columns (libellés) et $rows (tableau de lignes).
    public static function handle_export( $filename, $columns, $rows ) {
        self::verify();

        if ( empty( $columns ) ) {
            return;
        }

        try {
            $columns = array_values( $columns );
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // En-tête en gras + lignes.
            $sheet->fromArray( $columns, null, 'A1' );
            $last_col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex( count( $columns ) );
            $sheet->getStyle( 'A1:' . $last_col . '1' )->getFont()->setBold( true );
            $sheet->getStyle( 'A1:' . $last_col . '1' )->getFont()->setSize( 11 );

            if ( ! empty( $rows ) ) {
                $sheet->fromArray( array_values( $rows ), null, 'A2' );
            }

            // Largeur automatique des colonnes et ligne d'en-tête figée.
            foreach ( $columns as $i => $column ) {
                $sheet->getColumnDimension( \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex( $i + 1 ) )->setAutoSize( true );
            }
            $sheet->freezePane( 'A2' );

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx( $spreadsheet );
            $writer->setPreCalculateFormulas( false );

            $filename = sanitize_file_name( $filename );
            if ( substr( strtolower( $filename ), -5 ) !== '.xlsx' ) {
                $filename .= '.xlsx';
            }

            if ( ! headers_sent() ) {
                header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
                header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
                header( 'Cache-Control: max-age=0' );
                header( 'Expires: 0' );
            }

            $writer->save( 'php://output' );
        } catch ( \Throwable $e ) {
            wp_die( esc_html__( 'Erreur lors de l\'export Excel.', 'flora-shop' ) );
        }

        exit;
    }
}