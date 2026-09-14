<?php /* Vue du formulaire d'ajout / modification d'une remise par catégorie / type / tag : cible, quantité et récompense. */ ?>

<div class="wrap flora-admin">
    <h1><?php echo $discount ? esc_html__( 'Modifier la remise', 'flora-shop' ) : esc_html__( 'Ajouter une remise à la boutique', 'flora-shop' ); ?></h1>

    <nav class="nav-tab-wrapper" style="padding-bottom:0;">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-promotions' ) ); ?>" class="nav-tab"><?php esc_html_e( 'Promotions', 'flora-shop' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-promotions&tab=catalog' ) ); ?>" class="nav-tab nav-tab-active"><?php esc_html_e( 'Remises catégorie / type / tag', 'flora-shop' ); ?></a>
    </nav>

    <?php /* --- Formulaire principal de la remise (action "save") --- */ ?>
    <form method="post" action="">
        <?php Flora_Helpers::wpnonce_field( 'flora_save_catalog_discount' ); ?>
        <input type="hidden" name="flora_action" value="save">
        <?php if ( $discount ) : ?>
            <input type="hidden" name="discount_id" value="<?php echo esc_attr( $discount->id ); ?>">
        <?php endif; ?>

        <?php
        $scope       = $discount ? $discount->scope : 'category';
        $item_type   = $discount ? ( isset( $discount->item_type ) && $discount->item_type ? $discount->item_type : 'both' ) : 'both';
        $reward_type = $discount ? $discount->reward_type : 'percent';
        ?>

        <table class="form-table">
            <?php /* --- Section cible : catégorie, type (produit/pack) ou étiquette --- */ ?>
            <tr>
                <th><label for="target_scope"><?php esc_html_e( 'Type de cible', 'flora-shop' ); ?> *</label></th>
                <td>
                    <select id="target_scope" name="target_scope" class="flora-catalog-scope">
                        <option value="category" <?php selected( $scope, 'category' ); ?>><?php esc_html_e( 'Catégorie', 'flora-shop' ); ?></option>
                        <option value="type" <?php selected( $scope, 'type' ); ?>><?php esc_html_e( 'Type d\'article (produit ou pack)', 'flora-shop' ); ?></option>
                        <option value="tag" <?php selected( $scope, 'tag' ); ?>><?php esc_html_e( 'Étiquette', 'flora-shop' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'La remise s\'applique aux articles du panier correspondant à la cible.', 'flora-shop' ); ?></p>
                </td>
            </tr>
            <tr class="flora-catalog-scope-row" data-scope="category">
                <th><label for="catalog_target_category"><?php esc_html_e( 'Catégorie', 'flora-shop' ); ?> *</label></th>
                <td>
                    <select id="catalog_target_category" name="catalog_target_id">
                        <option value=""><?php esc_html_e( '-- Choisir une catégorie --', 'flora-shop' ); ?></option>
                        <?php foreach ( $categories as $cat ) : ?>
                            <option value="<?php echo esc_attr( $cat->id ); ?>" <?php selected( $discount ? $discount->target_id : 0, $cat->id ); ?>><?php echo esc_html( $cat->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr class="flora-catalog-scope-row" data-scope="type">
                <th><label for="target_type"><?php esc_html_e( 'Type d\'article', 'flora-shop' ); ?> *</label></th>
                <td>
                    <select id="target_type" name="target_type">
                        <option value="product" <?php selected( $discount ? $item_type : 'product', 'product' ); ?>><?php esc_html_e( 'Produits (tous)', 'flora-shop' ); ?></option>
                        <option value="pack" <?php selected( $discount ? $item_type : 'product', 'pack' ); ?>><?php esc_html_e( 'Packs (tous)', 'flora-shop' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr class="flora-catalog-scope-row" data-scope="tag">
                <th><label for="catalog_target_tag"><?php esc_html_e( 'Étiquette', 'flora-shop' ); ?> *</label></th>
                <td>
                    <select id="catalog_target_tag" name="catalog_target_id">
                        <option value=""><?php esc_html_e( '-- Choisir une étiquette --', 'flora-shop' ); ?></option>
                        <?php foreach ( $tags as $tg ) : ?>
                            <option value="<?php echo esc_attr( $tg->id ); ?>" <?php selected( $discount ? $discount->target_id : 0, $tg->id ); ?>><?php echo esc_html( $tg->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr class="flora-catalog-scope-row" data-scope="both">
                <th><label for="item_scope"><?php esc_html_e( 'Articles concernés', 'flora-shop' ); ?></label></th>
                <td>
                    <select id="item_scope" name="item_scope">
                        <option value="both" <?php selected( $item_type, 'both' ); ?>><?php esc_html_e( 'Produits et packs', 'flora-shop' ); ?></option>
                        <option value="product" <?php selected( $item_type, 'product' ); ?>><?php esc_html_e( 'Produits uniquement', 'flora-shop' ); ?></option>
                        <option value="pack" <?php selected( $item_type, 'pack' ); ?>><?php esc_html_e( 'Packs uniquement', 'flora-shop' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="trigger_qty"><?php esc_html_e( 'Quantité d\'articles éligibles', 'flora-shop' ); ?> *</label></th>
                <td>
                    <input type="number" id="trigger_qty" name="trigger_qty" class="small-text" min="1" required value="<?php echo $discount ? esc_attr( $discount->trigger_qty ) : '3'; ?>">
                    <p class="description"><?php esc_html_e( 'Nombre minimum d\'articles de la cible présents au panier pour déclencher la remise.', 'flora-shop' ); ?></p>
                </td>
            </tr>

            <?php /* --- Section récompense : article gratuit, réduction en % ou en montant (lignes affichées selon le type) --- */ ?>
            <tr>
                <th><label for="reward_type"><?php esc_html_e( 'Type de récompense', 'flora-shop' ); ?> *</label></th>
                <td>
                    <select id="reward_type" name="reward_type" class="flora-promo-reward-type">
                        <option value="percent" <?php selected( $reward_type, 'percent' ); ?>><?php esc_html_e( 'Réduction (%)', 'flora-shop' ); ?></option>
                        <option value="amount" <?php selected( $reward_type, 'amount' ); ?>><?php esc_html_e( 'Réduction (montant)', 'flora-shop' ); ?></option>
                        <option value="free" <?php selected( $reward_type, 'free' ); ?>><?php esc_html_e( 'Produit / pack offert (gratuit)', 'flora-shop' ); ?></option>
                    </select>
                </td>
            </tr>

            <?php /* ===== RÉCOMPENSE RÉDUCTION % ===== */ ?>
            <tr class="flora-promo-reward-row" data-type="percent">
                <th><label for="discount_percent"><?php esc_html_e( 'Réduction (%)', 'flora-shop' ); ?> *</label></th>
                <td>
                    <input type="number" id="discount_percent" name="discount_percent" class="small-text" min="1" max="100" step="0.01" value="<?php echo $discount ? esc_attr( $discount->discount_percent ) : '10'; ?>">
                    <p class="description"><?php esc_html_e( 'Pourcentage appliqué sur le sous-total des articles éligibles.', 'flora-shop' ); ?></p>
                </td>
            </tr>

            <?php /* ===== RÉCOMPENSE RÉDUCTION MONTANT ===== */ ?>
            <tr class="flora-promo-reward-row" data-type="amount">
                <th><label for="discount_amount"><?php esc_html_e( 'Montant de la réduction (DA)', 'flora-shop' ); ?> *</label></th>
                <td>
                    <input type="number" id="discount_amount" name="discount_amount" class="small-text" min="1" step="0.01" value="<?php echo $discount ? esc_attr( $discount->discount_amount ) : '300'; ?>">
                    <p class="description"><?php esc_html_e( 'Montant déduit par lot de quantité éligible.', 'flora-shop' ); ?></p>
                </td>
            </tr>

            <?php /* ===== RÉCOMPENSE GRATUITE ===== */ ?>
            <tr class="flora-promo-reward-row" data-type="free">
                <th><label for="free_type"><?php esc_html_e( 'Type d\'article offert', 'flora-shop' ); ?> *</label></th>
                <td>
                    <select id="free_type" name="free_type" class="flora-promo-free-type">
                        <option value="product" <?php selected( $discount ? ( isset( $discount->free_type ) && $discount->free_type ? $discount->free_type : 'product' ) : 'product', 'product' ); ?>><?php esc_html_e( 'Produit', 'flora-shop' ); ?></option>
                        <option value="pack" <?php selected( $discount ? ( isset( $discount->free_type ) && $discount->free_type ? $discount->free_type : 'product' ) : 'product', 'pack' ); ?>><?php esc_html_e( 'Pack', 'flora-shop' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr class="flora-promo-reward-row flora-promo-free-row" data-type="free" data-free-type="product">
                <th><label for="free_product_id"><?php esc_html_e( 'Produit offert', 'flora-shop' ); ?> *</label></th>
                <td>
                    <select id="free_product_id" name="free_product_id">
                        <option value=""><?php esc_html_e( '-- Choisir un produit --', 'flora-shop' ); ?></option>
                        <?php foreach ( $all_products as $p ) : ?>
                            <option value="<?php echo esc_attr( $p->id ); ?>" <?php selected( $discount ? $discount->free_product_id : 0, $p->id ); ?>><?php echo esc_html( $p->name . ' (' . Flora_Helpers::format_price( $p->price ) . ')' ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr class="flora-promo-reward-row flora-promo-free-row" data-type="free" data-free-type="pack">
                <th><label for="free_pack_id"><?php esc_html_e( 'Pack offert', 'flora-shop' ); ?> *</label></th>
                <td>
                    <select id="free_pack_id" name="free_pack_id">
                        <option value=""><?php esc_html_e( '-- Choisir un pack --', 'flora-shop' ); ?></option>
                        <?php foreach ( $all_packs as $pk ) : ?>
                            <option value="<?php echo esc_attr( $pk->id ); ?>" <?php selected( $discount ? $discount->free_product_id : 0, $pk->id ); ?>><?php echo esc_html( $pk->name . ' (' . Flora_Helpers::format_price( $pk->pack_price ) . ')' ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr class="flora-promo-reward-row flora-promo-free-row" data-type="free" data-free-type="product">
                <th><label for="free_qty"><?php esc_html_e( 'Quantité offerte', 'flora-shop' ); ?> *</label></th>
                <td><input type="number" id="free_qty" name="free_qty" class="small-text" min="1" value="<?php echo $discount ? esc_attr( $discount->free_qty ) : '1'; ?>"></td>
            </tr>

            <tr>
                <th><label for="limit_per_order"><?php esc_html_e( 'Limite par commande (0 = illimité)', 'flora-shop' ); ?></label></th>
                <td><input type="number" id="limit_per_order" name="limit_per_order" class="small-text" min="0" value="<?php echo $discount ? esc_attr( $discount->limit_per_order ) : '0'; ?>"></td>
            </tr>
            <tr>
                <th><label for="start_date"><?php esc_html_e( 'Date de début', 'flora-shop' ); ?></label></th>
                <td><input type="date" id="start_date" name="start_date" value="<?php echo $discount && $discount->start_date ? esc_attr( $discount->start_date ) : ''; ?>"></td>
            </tr>
            <tr>
                <th><label for="end_date"><?php esc_html_e( 'Date de fin', 'flora-shop' ); ?></label></th>
                <td><input type="date" id="end_date" name="end_date" value="<?php echo $discount && $discount->end_date ? esc_attr( $discount->end_date ) : ''; ?>"></td>
            </tr>
            <tr>
                <th><label for="status"><?php esc_html_e( 'Statut', 'flora-shop' ); ?></label></th>
                <td>
                    <select id="status" name="status">
                        <option value="active" <?php selected( $discount ? $discount->status : 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'flora-shop' ); ?></option>
                        <option value="inactive" <?php selected( $discount ? $discount->status : 'active', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'flora-shop' ); ?></option>
                    </select>
                </td>
            </tr>

            <?php /* --- Section affichage sur la fiche : texte personnalisé (FR / AR) et CSS inline du message --- */ ?>
            <tr>
                <th colspan="2" style="padding-bottom:0;">
                    <h2 style="margin:0 0 6px;font-size:14px;"><?php esc_html_e( 'Affichage sur la fiche produit / pack', 'flora-shop' ); ?></h2>
                    <p class="description" style="margin-top:0;"><?php esc_html_e( 'Personnalisez le message affiché dans la liste des promotions de la fiche. Laissez vide pour masquer cette promotion sur la fiche.', 'flora-shop' ); ?></p>
                </th>
            </tr>
            <tr>
                <th><label for="message_fr"><?php esc_html_e( 'Message (français)', 'flora-shop' ); ?></label></th>
                <td>
                    <input type="text" id="message_fr" name="message_fr" class="regular-text" value="<?php echo $discount && isset( $discount->message_fr ) ? esc_attr( $discount->message_fr ) : ''; ?>">
                </td>
            </tr>
            <tr>
                <th><label for="message_ar"><?php esc_html_e( 'Message (arabe)', 'flora-shop' ); ?></label></th>
                <td>
                    <input type="text" id="message_ar" name="message_ar" class="regular-text" value="<?php echo $discount && isset( $discount->message_ar ) ? esc_attr( $discount->message_ar ) : ''; ?>">
                </td>
            </tr>
            <tr>
                <th><label for="message_css"><?php esc_html_e( 'CSS inline (optionnel)', 'flora-shop' ); ?></label></th>
                <td>
                    <input type="text" id="message_css" name="message_css" class="regular-text" placeholder="<?php esc_attr_e( 'background:#fff3cd;color:#8a6d3b;border-radius:6px;', 'flora-shop' ); ?>" value="<?php echo $discount && isset( $discount->message_css ) ? esc_attr( $discount->message_css ) : ''; ?>">
                    <p class="description"><?php esc_html_e( 'CSS appliqué au message (ex : couleur de fond, bordure, police).', 'flora-shop' ); ?></p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Enregistrer la remise', 'flora-shop' ); ?></button>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-promotions&tab=catalog' ) ); ?>" class="button"><?php esc_html_e( 'Annuler', 'flora-shop' ); ?></a>
        </p>
    </form>
</div>

<?php
/*
 * floracatalogToggle() affiche la ligne de cible correspondant au type choisi (catégorie, type, étiquette)
 * et les lignes de récompense selon le type de récompense et le type d'article offert.
 * Comme pour les promotions, les champs des lignes masquées sont désactivés (attribut « disabled ») :
 * ils ne sont pas envoyés au serveur, la sauvegarde vérifie donc chaque clé avec isset().
 */
?>
<script type="text/javascript">
(function($) {
    function floracatalogToggle() {
        var scope = $('#target_scope').val();
        var rewardType = $('#reward_type').val();
        var freeType = $('#free_type').val();

        // Ligne « Articles concernés » visible uniquement pour catégorie / étiquette (pas pour le type).
        $('tr.flora-catalog-scope-row').each(function() {
            var rowScope = $(this).data('scope');
            var visible = rowScope !== 'both' ? scope === rowScope : (scope === 'category' || scope === 'tag');
            $(this).toggle(visible);
            $(this).find('input, select').prop('disabled', !visible);
        });

        $('.flora-promo-reward-row').each(function() {
            var show = $(this).data('type') === rewardType;
            if ($(this).hasClass('flora-promo-free-row')) {
                show = rewardType === 'free' && $(this).data('free-type') === freeType;
            }
            $(this).toggle(show);
            $(this).find('input, select').prop('disabled', !show);
        });

        // Le champ de la cible (catégorie / étiquette) est requis selon la portée choisie.
        $('#catalog_target_category').prop('required', scope === 'category');
        $('#catalog_target_tag').prop('required', scope === 'tag');
    }

    floracatalogToggle();
    $('#target_scope, #reward_type, #free_type').on('change', floracatalogToggle);
})(jQuery);
</script>