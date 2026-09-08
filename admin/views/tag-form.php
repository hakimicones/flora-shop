<?php /* Vue du formulaire d'ajout / modification d'une étiquette : nom (slug auto-généré). */ ?>

<div class="wrap flora-admin">
    <h1><?php echo $tag ? esc_html__( 'Modifier l\'étiquette', 'flora-shop' ) : esc_html__( 'Ajouter une étiquette', 'flora-shop' ); ?></h1>

    <?php /* --- Formulaire d'enregistrement : l'action "save" est traitée côté serveur --- */ ?>
    <form method="post" action="">
        <?php Flora_Helpers::wpnonce_field( 'flora_save_tag' ); ?>
        <input type="hidden" name="flora_action" value="save">
        <?php if ( $tag ) : ?>
            <input type="hidden" name="tag_id" value="<?php echo esc_attr( $tag->id ); ?>">
        <?php endif; ?>

        <table class="form-table">
            <tr>
                <th><label for="name"><?php esc_html_e( 'Nom de l\'étiquette', 'flora-shop' ); ?> *</label></th>
                <td>
                    <input type="text" id="name" name="name" class="regular-text" required value="<?php echo $tag ? esc_attr( $tag->name ) : ''; ?>">
                    <p class="description"><?php esc_html_e( 'Le slug utilisé dans les shortcodes est généré automatiquement (ex : PROMO → promo).', 'flora-shop' ); ?></p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e( 'Enregistrer l\'étiquette', 'flora-shop' ); ?></button>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=flora-tags' ) ); ?>" class="button"><?php esc_html_e( 'Annuler', 'flora-shop' ); ?></a>
        </p>
    </form>
</div>