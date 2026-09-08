<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Gestion des mises à jour de Flora Shop via les Releases GitHub.
 *
 * S'appuie sur la bibliothèque « plugin-update-checker » (v5, MIT) embarquée
 * dans vendor/plugin-update-checker. Les mises à jour sont publiées sur
 * https://github.com/hakimicones/flora-shop sous forme de Release taggée
 * v<version> avec l'artefact flora-shop.zip — WordPress les signale alors
 * dans Extensions → Mises à jour et les installe en un clic (ou
 * automatiquement selon la bascule de l'extension).
 */
class Flora_Updates {

    // URL du dépôt GitHub public hébergeant les releases du plugin.
    const GITHUB_REPO_URL = 'https://github.com/hakimicones/flora-shop';

    // Constructeur : crée la vérification de mise à jour (admin uniquement, public → aucun token).
    public function __construct() {
        $checker_file = FLORA_SHOP_PATH . 'vendor/plugin-update-checker/plugin-update-checker.php';

        if ( ! file_exists( $checker_file ) ) {
            return;
        }

        require_once $checker_file;

        if ( ! method_exists( 'YahnisElsts\PluginUpdateChecker\v5\PucFactory', 'buildUpdateChecker' ) ) {
            return;
        }

        try {
            $checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
                self::GITHUB_REPO_URL,
                FLORA_SHOP_FILE,
                'flora-shop'
            );

            // Branche contenant les releases stables.
            $checker->setBranch( 'main' );

            // Utilise l'artefact flora-shop.zip des Releases plutôt que l'archive source.
            $checker->getVcsApi()->enableReleaseAssets( '/\.zip($|[?&#])/i' );
        } catch ( Exception $e ) {
            return;
        }
    }
}