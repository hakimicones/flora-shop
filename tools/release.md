# Procédure de release Flora Shop

Déclenchement d'une nouvelle version puis publication automatique via GitHub Actions.

## Le flux côté client (aucune action requise)

1. Le plugin embarque `plugin-update-checker` (lib officielle MIT, `vendor/plugin-update-checker/`).
2. À intervalles réguliers, WordPress interroge le dépôt public `hakimicones/flora-shop`.
3. Dès qu'un tag/release possède un `flora-shop.zip` plus récent que la version installée,
   **Extensions → Mises à jour** affiche la mise à jour.
4. L'utilisateur clique « Mettre à jour » — ou active les **mises à jour automatiques**
   de l'extension (plugni moniteur) — et WordPress installe le zip à sa place.

## Publier une nouvelle version (développeur uniquement)

1. **Bump la version** AVANT de tagger, dans `flora-shop.php` :
   - l'en-tête : ` * Version: X.Y.Z`
   - la constante : `define( 'FLORA_SHOP_VERSION', 'X.Y.Z' );`
   Ces deux valeurs doivent être strictement identiques (le workflow échoue sinon).

2. **Ajouter la migration** dans `inc/class-flora-activator.php` le cas échéant :
   - une nouvelle étape `upgrade_X_Y_Z()` appelée depuis `maybe_upgrade()`
     (`if ( version_compare( $installed, 'X.Y.Z', '<' ) ) { self::upgrade_X_Y_Z(); }`).
   - L'étape doit être **idempotente** (vérifier l'existence des colonnes/options avant d'agir).
   - Les nouvelles options par défaut sont ajoutées automatiquement par `ensure_options()`.

3. **Valider en local** :
   - `php -l` sur les fichiers modifiés ;
   - tester la migration depuis la version précédente (staging) ;
   - vérifier boutique + checkout après migration.

4. **Commit + tag + push** :
   ```bash
   git add -A && git commit -m "Version X.Y.Z : <résumé>"
   git tag vX.Y.Z
   git push origin main --tags
   ```

5. GitHub Actions fait tout le reste : contrôle de cohérence, fabrication de
   `flora-shop.zip`, création de la Release et attachement du zip.

## Contrôles recommandés après mise à jour sur un site du client

- [ ] Sauvegarde **DB + fichiers** avant d'autoriser une mise à jour.
- [ ] Une fois la mise à jour installée : `flora_shop_version` a bien changé
      (`wp_options`), les tables/colonnes sont présentes.
- [ ] Boutique, fiche produit/pack, panier (volet), checkout et confirmation OK.
- [ ] `error_log` / débogage sans warning nouveau.
- [ ] En cas de problème : restaurer la sauvegarde DB, réinstaller le zip précédent,
      corriger → bump `X+1` (jamais de re-release sur le même numéro).

## Rappels

- Le zip en racine doit contenir `flora-shop/` (pas `flora-shop-vX/...`) — c'est le workflow qui l'assure.
- Ne jamais publier de secrets dans le repo : le dépôt est **public**, tout le code est visible.
- Pour une livraison initiale sur un nouveau site :
  décompresser le zip dans `wp-content/plugins/`, activer le plugin,
  puis activer les mises à jour automatiques de l'extension si souhaité.