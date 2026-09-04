<div class="wrap flora-admin">
    <h1><?php echo $promo ? esc_html__( 'Modifier la promotion', 'flora-shop' ) : esc_html__( 'Ajouter une promotion', 'flora-shop' ); ?></h1>

    <form method="post" action="">
        <?php Flora_Helpers::wpnonce_field( 'flora_save_promo' ); ?>
        <input type="hidden" name="flora_action" value="save">
        <?php if ( $promo ) : ?>
            <input type="hidden" name="promo_id" value="<?php echo esc_attr( $promo->id ); ?>">
        <?php endif; ?>

        <?php
        $trigger_type = $promo ? $promo->trigger_type : 'product';
        $reward_type  = $promo ? $promo->reward_type : 'free';
        ?>

        <table class="form-table">
            <tr>
                <th><label for="trigger_type"><?php esc_html_e( 'Type de déclencheur', 'flora-shop' ); ?> *</label></th>
                <td>
                    <select id="trigger_type" name="trigger_type" class="flora-promo-trigger-type">
                        <option value="product" <?php selected( $trigger_type, 'product' ); ?>><?php esc_html_e( 'Produit', 'flora-shop' ); ?></option>
                        <option value="pack" <?php selected( $trigger_type, 'pack' ); ?>><?php esc_html_e( 'Pack', 'flora-shop' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr class="flora-promo-trigger-row" data-type="product">
                <th><label for="trigger_product_id"><?php esc_html_e( 'Produit déclencheur (acheté)', 'flora-shop' ); ?> *</label></th>
                <td>
                    <select id="trigger_product_id" name="trigger_product_id" required>
                        <option value=""><?php esc_html_e( '-- Choisir un produit --', 'flora-shop' ); ?></option>
                        <?php foreach ( $all_products as $p ) : ?>
                            <option value="<?php echo esc_attr( $p->id ); ?>" <?php selected( $promo ? $promo->trigger_product_id : 0, $p->id ); ?>><?php echo esc_html( $p->name . ' (' . Flora_Helpers::format_price( $p->price ) . ')' ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr class="flora-promo-trigger-row" data-type="pack">
                <th><label for="trigger_pack_id"><?php esc_html_e( 'Pack déclencheur (acheté)', 'flora-shop' ); ?> *</label></th>
                <td>
                    <select id="trigger_pack_id" name="trigger_pack_id">
                        <option value=""><?php esc_html_e( '-- Choisir un pack --', 'flora-shop' ); ?></option>
                        <?php foreach ( $all_packs as $pk ) : ?>
                            <option value="<?php echo esc_attr( $pk->id ); ?>" <?php selected( $promo ? $promo->trigger_product_id : 0, $pk->id ); ?>><?php echo esc_html( $pk->name . ' (' . Flora_Helpers::format_price( $pk->pack_price ) . ')' ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="trigger_qty"><?php esc_html_e( 'Quantité déclenchante', 'flora-shop' ); ?> *</label></th>
                <td><input type="number" id="trigger_qty" name="trigger_qty" class="small-text" min="1" required value="<?php echo $promo ? esc_attr( $promo->trigger_qty ) : '3'; ?>"></td>
            </tr>
            <tr>
                <th><label for="reward_type"><?php esc_html_e( 'Type de récompense', 'flora-shop' ); ?> *</label></th>
                <td>
                    <select id="reward_type" name="reward_type" class="flora-promo-reward-type">
                        <option value="free" <?php selected( $reward_type, 'free' ); ?>><?php esc_html_e( 'Produit / pack offert (gratuit)', 'flora-shop' ); ?></option>
                        <option value="percent" <?php selected( $reward_type, 'percent' ); ?>><?php esc_html_e( 'Réduction (%)', 'flora-shop' ); ?></option>
                    </select>
                </td>
            </tr>

            <?php /* ===== RÉCOMPENSE GRATUITE ===== */ ?>
            <tr class="flora-promo-reward-row" data-type="free">
                <th><label for="free_type"><?php esc_html_e( 'Type d\'article offert', 'flora-shop' ); ?> *</label></th>
                <td>
                    <select id="free_type" name="free_type" class="flora-promo-free-type">
                        <option value="product" <?php selected( $promo ? $promo->free_type : 'product', 'product' ); ?>><?php esc_html_e( 'Produit', 'flora-shop' ); ?></option>
                        <option value="pack" <?php selected( $promo ? $promo->free_type : 'product', 'pack' ); ?>><?php esc_html_e( 'Pack', 'flora-shop' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr class="flora-promo-reward-row flora-promo-free-row" data-type="free" data-free-type="product">
                <th><label for="free_product_id"><?php esc_html_e( 'Produit offert', 'flora-shop' ); ?> *</label></th>
                <td>
                    <select id="free_product_id" name="free_product_id">
                        <option value=""><?php esc_html_e( '-- Choisir un produit --', 'flora-shop' ); ?></option>
                        <?php foreach ( $all_products as $p ) : ?>
                            <option value="<?php echo esc_attr( $p->id ); ?>" <?php selected( $promo ? $promo->free_product_id : 0, $p->id ); ?>><?php echo esc_html( $p->name . ' (' . Flora_Helpers::format_price( $p->price ) . ')' ); ?></option>
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
                            <option value="<?php echo esc_attr( $pk->id ); ?>" <?php selected( $promo ? $promo->free_product_id : 0, $pk->id ); ?>><?php echo esc_html( $pk->name . ' (' . Flora_Helpers::format_price( $pk->pack_price ) . ')' ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr class="flora-promo-reward-row flora-promo-free-row" data-type="free" data-free-type="product">
                <th><label for="free_qty"><?php esc_html_e( 'Quantité offerte', 'flora-shop' ); ?> *</label></th>
                <td><input type="number" id="free_qty" name="free_qty" class="small-text" min="1" required value="<?php echo $promo ? esc_attr( $promo->free_qty ) : '1'; ?>"></td>
            </tr>

            <?php /* ===== RÉCOMPENSE RÉDUCTION % ===== */ ?>
            <tr class="flora-promo-reward-row" data-type="percent">
                <th><label for="discount_percent"><?php esc_html_e( 'Réduction (%)', 'flora-shop' ); ?> *</label></th>
                <td>
                    <input type="number" id="discount_percent" name="discount_percent" class="small-text" min="1" max="100" step="0.01" required value="<?php echo $promo ? esc_attr( $promo->discount_percent ) : '10'; ?>">
                    <p class="description"><?php esc_html_e( 'Pourcentage appliqué sur les articles déclencheurs éligibles.', 'flora-shop' ); ?></p>
                </td>
            </tr>

            <tr>
                <th><label for="limit_per_order"><?php esc_html_e( 'Limite par commande (0 = illimité)', 'flora-shop' ); ?></label></th>
                <td><input type="number" id="limit_per_order" name="limit_per_order" class="small-text" min="0" value="<?php echo $promo ? esc_attr( $promo->limit_per_order ) : '0'; ?>"></td>
            </tr>
            <tr>
                <th><label for="start_date"><?php esc_html_e( 'Date de début', 'flora-shop' ); ?></label></th>
                <td><input type="date" id="start_date" name="start_date" value="<?php echo $promo && $promo->start_date ? esc_attr( $promo->start_date ) : ''; ?>"></td>
            </tr>
            <tr>
                <th><label for="end_date"><?php esc_html_e( 'Date de fin', 'flora-shop' ); ?></label></th>
                <td><input type="date" id="end_date" name="end_date" value="<?php echo $promo && $promo->end_date ? esc_attr( $promo->end_date ) : ''; ?>"></td>
            </tr>
            <tr>
                <th><label for="status"><?php esc_html_e( 'Statut', 'flora-shop' ); ?></label></th>
                <td>
                    <select id="status" name="status">
                        <option value="active" <?php selected( $promo ? $promo->status : 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'flora-shop' ); ?></option>
                        <option value="inactive" <?php selected( $promo ? $promo->status : 'active', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'flora-shop' ); ?></option>
                    </select>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Enregistrer la promotion', 'flora-shop' ); ?></button>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-promotions' ) ); ?>" class="button"><?php esc_html_e( 'Annuler', 'flora-shop' ); ?></a>
        </p>
    </form>
</div>

<script type="text/javascript">
(function($) {
    function floraPromoToggle() {
        var triggerType = $('#trigger_type').val();
        var rewardType = $('#reward_type').val();
        var freeType = $('#free_type').val();

        $('.flora-promo-trigger-row').each(function() {
            $(this).toggle($(this).data('type') === triggerType);
        });

        $('.flora-promo-reward-row').each(function() {
            var show = $(this).data('type') === rewardType;
            if ($(this).hasClass('flora-promo-free-row')) {
                show = rewardType === 'free' && $(this).data('free-type') === freeType;
            }
            $(this).toggle(show);
        });

        var triggerRequired = triggerType === 'product' ? '#trigger_product_id' : '#trigger_pack_id';
        var triggerOptional = triggerType === 'product' ? '#trigger_pack_id' : '#trigger_product_id';
        $(triggerRequired).prop('required', true);
        $(triggerOptional).prop('required', false);
})(jQuery);
</script>