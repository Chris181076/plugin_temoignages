<?php
function afficher_temoignages_shortcode($atts)
{
    $post_id = get_the_ID();

    $atts = shortcode_atts([
        'nombre' => 5,
    ], $atts, 'temoignages');

    $args = [
        'post_type' => 'temoignage',
        'posts_per_page' => intval($atts['nombre']),
        'post_status' => 'publish',
        'meta_key' => 'article_id',
        'meta_value' => $post_id
    ];
    $query = new WP_Query($args);

    ob_start();

    if ($query->have_posts()) :

        echo '<div class="liste-temoignages">';

        while ($query->have_posts()) : $query->the_post();
            $entreprise = get_post_meta(get_the_ID(), 'entreprise', true);
            $poste = get_post_meta(get_the_ID(), 'poste', true);
            $note = get_field('note');
            $couleur_fond = get_option('couleur_fond_temoignage', '#ffffff');
            $couleur_texte = get_option('couleur_text_temoignage', '#000000');


echo '<div class="temoignage" style="background-color:' . esc_attr($couleur_fond) . '; color:' . esc_attr($couleur_texte) . ';">'
?>
                <?php if (has_post_thumbnail()) : ?>
                    <figure class="figure">
                        <?php the_post_thumbnail('thumbnail', ['class' => 'photo-temoin']); ?>
                    </figure>
                <?php else : ?>
                    <figure class="figure">
                        <img src="<?php echo get_template_directory_uri(); ?>/pictures/default.jpg" alt="image par défaut" class="photo-temoin">
                    </figure>
                <?php endif; ?>

                <h3><?php echo ucfirst(get_the_title()); ?></h3>

                <?php if ($entreprise) : ?>
                    <p><strong>Entreprise :</strong> <?php echo esc_html($entreprise); ?></p>
                <?php endif; ?>

                <?php if ($poste) : ?>
                    <p><strong>Poste :</strong> <?php echo esc_html($poste); ?></p>
                <?php endif; ?>

                <div class="contenu"><?php the_content(); ?></div>

                <?php if ($note) : ?>
                    <div class="note">
                        <?php
                        for ($i = 1; $i <= 5; $i++) {
                            echo ($i <= intval($note)) ? '⭐' : '☆';
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>

    <?php endwhile;

        echo '</div>';
    endif;

    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('temoignages', 'afficher_temoignages_shortcode');


add_action('init', 'send_temoignage_handler');

function send_temoignage_handler()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
        send_temoignage();
    }
}


function temoignage_form()
{

    ob_start();
    ?>
    <form method="POST" action="<?php echo esc_url(get_permalink()); ?>" enctype="multipart/form-data">
        <?php wp_nonce_field('submit_wpnonce'); ?>
        <div class="ligneForm">
            <label for="nom">Votre nom :</label>
            <input type="text" id="nom" name="nom" required />
        </div>

        <div class="ligneForm">
            <label for="entreprise">Votre entreprise:</label>
            <input type="text" id="entreprise" name="entreprise" required />
        </div>

        <div class="ligneForm">
            <label for="poste">Votre poste :</label>
            <input type="text" id="poste" name="poste">
        </div>

        <div class="ligneForm">
            <label for="temoignage">Votre témoignage :</label>
            <textarea id="temoignage" name="temoignage" required></textarea>
        </div>

        <div class="ligneForm">
            <label for="note">Note (1 à 5) :</label>
            <input type="number" id="note" name="note" min="1" max="5" required>
        </div>

        <div class="ligneForm">
            <label class='photo-temoin' for="image">Télécharger votre photo :</label>
            <input type="file" id="image" name="image" accept="image/*" />
        </div>

        <input class="submit" type="submit" name="submit" value="Envoyer le témoignage" />
    </form>
    <?php

    if (isset($_GET['submitted']) && $_GET['submitted'] === 'success') {
        echo '<div class="success-message">Merci !</div>';
    }

    return ob_get_clean();
}

add_shortcode('temoignage_form', 'temoignage_form');

function send_temoignage()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit']) && check_admin_referer('submit_wpnonce')) {
        $nom = sanitize_text_field($_POST['nom']);
        $temoignage = sanitize_textarea_field($_POST['temoignage']);
        $note = intval($_POST['note']);
        $entreprise = sanitize_text_field($_POST['entreprise']);
        $poste = sanitize_text_field($_POST['poste']);

        $image_id = 0;

        if (!empty($_FILES['image']['name'])) {
            // Vérification du fichier
            $file = $_FILES['image'];

            require_once(ABSPATH . 'wp-admin/includes/file.php');

            $upload = wp_handle_upload($file, ['test_form' => false]);

            if (isset($upload['file'])) {
                $file_type = wp_check_filetype(basename($upload['file']));
                $wp_filetype = $file_type['type'];


                if (in_array($wp_filetype, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {

                    $attachment = [
                        'guid'           => $upload['url'],
                        'post_mime_type' => $wp_filetype,
                        'post_title'     => sanitize_file_name($file['name']),
                        'post_content'   => '',
                        'post_status'    => 'inherit'
                    ];

                    $image_id = wp_insert_attachment($attachment, $upload['file']);
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    $attach_data = wp_generate_attachment_metadata($image_id, $upload['file']);
                    wp_update_attachment_metadata($image_id, $attach_data);
                }
            }
        }

        $new_post = array(
            'post_title'    => $nom,
            'post_content'  => $temoignage,
            'post_status'   => 'publish',
            'post_type'     => 'temoignage',
            'meta_input'    => array(
                'note' => $note,
                'entreprise' => $entreprise,
                'poste' => $poste
            ),
        );
        $post_id = wp_insert_post($new_post);
        if (!is_wp_error($post_id)) {
            if ($image_id) {
                set_post_thumbnail($post_id, $image_id);
            }
            wp_redirect(add_query_arg('submitted', 'success', get_permalink()));
            exit;
        }
    }
}
function ajouter_metabox_temoignage()
{
    add_meta_box(
        'info_entreprise_poste',
        'Informations Entreprise et Poste',
        'render_metabox_temoignage',
        'temoignage',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'ajouter_metabox_temoignage');

function render_metabox_temoignage($post)
{

    wp_nonce_field('sauver_info_temoignage', 'temoignage_nonce');


    $entreprise = get_post_meta($post->ID, 'entreprise', true);
    $poste = get_post_meta($post->ID, 'poste', true);


    echo '<p><label for="entreprise">Entreprise :</label><br>';
    echo '<input type="text" id="entreprise" name="entreprise" value="' . esc_attr($entreprise) . '" style="width:100%;" /></p>';

    echo '<p><label for="poste">Poste :</label><br>';
    echo '<input type="text" id="poste" name="poste" value="' . esc_attr($poste) . '" style="width:100%;" /></p>';
}
function sauver_metabox_temoignage($post_id)
{
    // Sécurité (vérifie que c’est légitime)
    if (!isset($_POST['temoignage_nonce']) || !wp_verify_nonce($_POST['temoignage_nonce'], 'sauver_info_temoignage')) {
        return;
    }

    // Évite de sauvegarder pendant des autosaves
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Permission
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Maintenant, on enregistre !
    if (isset($_POST['entreprise'])) {
        update_post_meta($post_id, 'entreprise', sanitize_text_field($_POST['entreprise']));
    }
    if (isset($_POST['poste'])) {
        update_post_meta($post_id, 'poste', sanitize_text_field($_POST['poste']));
    }
}
add_action('save_post', 'sauver_metabox_temoignage');

function ajouter_metabox_article_id()
{
    add_meta_box(
        'article_id_meta_box',
        'Page liée',
        'afficher_metabox_article_id',
        'temoignage',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'ajouter_metabox_article_id');

function afficher_metabox_article_id($post)
{
    $valeur = get_post_meta($post->ID, 'article_id', true);
    $articles = get_posts([
        'post_type' => 'post',
        'numberposts' => -1,
    ]);

    echo '<label for="article_id">Associer à la page :</label>';
    echo '<select name="article_id" id="article_id">';
    foreach ($articles as $post) {
        $selected = ($valeur == $post->ID) ? 'selected' : '';
        echo '<option value="' . esc_attr($post->ID) . '" ' . $selected . '>' . esc_html($post->post_title) . '</option>';
    }
    echo '</select>';
}
function sauvegarder_article_id($post_id)
{
    if (array_key_exists('article_id', $_POST)) {
        update_post_meta(
            $post_id,
            'article_id',
            intval($_POST['article_id'])
        );
    }
}
add_action('save_post', 'sauvegarder_article_id');

function ajouter_page_options_plugin()
{
    add_submenu_page(
        'edit.php?post_type=temoignage',  // parent slug (menu "Témoignage")
        'Options Témoignage',
        'Options',
        'publish_posts',
        'options_temoignage_plugin',      // slug de la page
        'afficher_page_options_plugin'    // fonction callback qui affiche la page
    );
}
add_action('admin_menu', 'ajouter_page_options_plugin');

function afficher_page_options_plugin()
{
    ?>
    <div class="wrap">
        <h1>Options des Témoignages</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('options_temoignage_group');
            do_settings_sections('options_temoignage_plugin');
            submit_button();
            ?>
        </form>
    </div>
<?php
}

function initialiser_options_temoignage()
{
    register_setting('options_temoignage_group', 'couleur_fond_temoignage');
    register_setting('options_temoignage_group', 'couleur_text_temoignage');

    add_settings_section(
        'section_personnalisation',
        'Personnalisation',
        null,
        'options_temoignage_plugin'
    );


    add_settings_field(
        'couleur_fond_temoignage',
        'Couleur de fond des témoignages',
        'afficher_champ_couleur_fond',
        'options_temoignage_plugin',
        'section_personnalisation'
    );


    add_settings_field(
        'couleur_text_temoignage',
        'Couleur de texte des témoignages',
        'afficher_champ_couleur_text',
        'options_temoignage_plugin',
        'section_personnalisation'
    );
}
add_action('admin_init', 'initialiser_options_temoignage');


function afficher_champ_couleur_fond()
{

    $couleur_fond = get_option('couleur_fond_temoignage', '#ffffff');

    echo '<input type="text" name="couleur_fond_temoignage" value="' . esc_attr($couleur_fond) . '" class="my-color-field" data-default-color="#ffffff" />';
}


function afficher_champ_couleur_text()
{

    $couleur_text = get_option('couleur_text_temoignage', '#000000');

    echo '<input type="text" name="couleur_text_temoignage" value="' . esc_attr($couleur_text) . '" class="my-color-field" data-default-color="#000000" />';
}

function charger_assets_page_options($hook_suffix)
{
    
    if ($hook_suffix === 'temoignage_page_options_temoignage_plugin') {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }
    wp_enqueue_script(
        'couleur-js',
        plugin_dir_url(__FILE__) . 'assets/couleur.js',
        array('wp-color-picker'),
        false,
        true
    );
}
add_action('admin_enqueue_scripts', 'charger_assets_page_options');

