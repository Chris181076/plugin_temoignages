<?php
function afficher_temoignages_shortcode($atts)
{
    if (!is_singular()) {
        return ''; // Ne rien afficher si ce n'est pas une page/article unique
    }
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
            // Nouveau code correct
            $options = get_option('options_couleurs_temoignage');
            $couleur_fond = isset($options['couleur_fond']) ? $options['couleur_fond'] : '#ffffff';
            $couleur_texte = isset($options['couleur_text']) ? $options['couleur_text'] : '#000000';


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
        <p data-poste="<?php echo esc_attr($poste); ?>">
            <strong>Poste :</strong> <?php echo esc_html($poste); ?>
        </p>
    <?php endif;?>

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


    function send_temoignage_handler() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && 
            isset($_POST['submit']) 
            && isset($_POST['temoignage_nonce'])) {
            send_temoignage();
        }
    }



function temoignage_form()
{

    ob_start();
    ?>
    <form method="POST" action="<?php echo esc_url(get_permalink()); ?>" enctype="multipart/form-data">
        <?php wp_nonce_field('submit_wpnonce', 'temoignage_nonce'); ?>
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
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit']) && isset($_POST['temoignage_nonce'])  && wp_verify_nonce($_POST['temoignage_nonce'], 'submit_wpnonce') ) {
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

    wp_nonce_field('metabox_temoignage_nonce', 'metabox_temoignage_field');


    $entreprise = get_post_meta($post->ID, 'entreprise', true);
    $poste = get_post_meta($post->ID, 'poste', true);


    echo '<p><label for="entreprise">Entreprise :</label><br>';
    echo '<input type="text" id="entreprise" name="entreprise" value="' . esc_attr($entreprise) . '" style="width:100%;" /></p>';

    echo '<p><label for="poste">Poste :</label><br>';
    echo '<input type="text" id="poste" name="poste" value="' . esc_attr($poste) . '" style="width:100%;" /></p>';
}
function sauver_metabox_temoignage($post_id)
{       if (get_post_type($post_id) !== 'temoignage') {
    return;
}
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
    if (get_post_type($post_id) !== 'temoignage') {
        return;
    }
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
        'edit.php?post_type=temoignage',
        'Options Témoignage',
        'Options',
        'manage_options',
        'options_temoignage_plugin',
        'afficher_page_options_plugin'
    );
}
add_action('admin_menu', 'ajouter_page_options_plugin');


function afficher_page_options_plugin()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('Vous n\'avez pas les permissions suffisantes.'));
    }

    if (false === get_option('options_couleurs_temoignage')) {
        update_option('options_couleurs_temoignage', array(
            'couleur_fond' => '#ffffff',
            'couleur_text' => '#000000'
        ));
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('options_temoignage_group');
            do_settings_sections('options_temoignage_plugin');
            submit_button('Enregistrer les modifications');
            ?>
        </form>
    </div>
<?php
}



function initialiser_options_temoignage()
{
   
    register_setting(
        'options_temoignage_group',    
        'options_couleurs_temoignage', 
        array(
            'type' => 'array',
            'sanitize_callback' => 'sanitiser_options_couleurs_temoignage',
            'default' => array(
                'couleur_fond' => '#ffffff',
                'couleur_text' => '#000000'
            ),
            'show_in_rest' => false
        )
    );

    add_settings_section(
        'section_couleurs_temoignage',
        'Paramètres des couleurs',
        null,
        'options_temoignage_plugin'
    );

 
    add_settings_field(
        'couleur_fond',
        'Couleur de fond',
        'afficher_champ_couleur_fond',
        'options_temoignage_plugin',
        'section_couleurs_temoignage'
    );

    add_settings_field(
        'couleur_text',
        'Couleur du texte',
        'afficher_champ_couleur_text',
        'options_temoignage_plugin',
        'section_couleurs_temoignage'
    );
}
add_action('admin_init', 'initialiser_options_temoignage');

function afficher_champ_couleur_fond()
{
    $options = get_option('options_couleurs_temoignage');
    $couleur_fond = isset($options['couleur_fond']) ? $options['couleur_fond'] : '#ffffff';
?>
    <input type="text"
        name="options_couleurs_temoignage[couleur_fond]"
        value="<?php echo esc_attr($couleur_fond); ?>"
        class="my-color-field" />
<?php
}

function afficher_champ_couleur_text()
{
    $options = get_option('options_couleurs_temoignage');
    $couleur_text = isset($options['couleur_text']) ? $options['couleur_text'] : '#000000';
?>
    <input type="text"
        name="options_couleurs_temoignage[couleur_text]"
        value="<?php echo esc_attr($couleur_text); ?>"
        class="my-color-field" />
<?php
}

function sanitiser_options_couleurs_temoignage($input)
{
    $new_input = array();

    if (isset($input['couleur_fond'])) {
        $new_input['couleur_fond'] = sanitize_hex_color($input['couleur_fond']);
    }

    if (isset($input['couleur_text'])) {
        $new_input['couleur_text'] = sanitize_hex_color($input['couleur_text']);
    }

    return $new_input;
}


function charger_assets_page_options($hook_suffix)
{
    if ($hook_suffix === get_plugin_page_hookname('options_temoignage_plugin', 'edit.php?post_type=temoignage')) {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_add_inline_script('wp-color-picker', '
            jQuery(document).ready(function($){
                $(".my-color-field").wpColorPicker();
            });
        ');
    }
}
add_action('admin_enqueue_scripts', 'charger_assets_page_options');




function shortcode_filtre_temoignages() {
    global $wpdb;
    $post_id = get_the_ID();
    
    // Récupérer les postes uniques
    $postes = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT pm_poste.meta_value 
        FROM {$wpdb->postmeta} pm_poste
        INNER JOIN {$wpdb->postmeta} pm_article 
        ON pm_poste.post_id = pm_article.post_id
        WHERE pm_poste.meta_key = 'poste' 
        AND pm_article.meta_key = 'article_id'
        AND pm_article.meta_value = %d
        AND pm_poste.meta_value != '' 
        ORDER BY pm_poste.meta_value ASC",
        $post_id
    ));

    ob_start();
    ?>
    <div class="filtre-temoignages-wrapper">
        <select id="filtre-poste" onchange="filtrerTemoignagesParPoste(this.value)">
            <option value="">Tous les postes</option>
            <?php foreach ($postes as $poste) : ?>
                <option value="<?php echo esc_attr($poste); ?>">
                    <?php echo esc_html($poste); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <script>
    function filtrerTemoignagesParPoste(posteSelectionne) {
        const temoignages = document.querySelectorAll('.temoignage');
        
        temoignages.forEach(temoignage => {
            const posteTemoignage = temoignage.querySelector('[data-poste]');
            if (!posteTemoignage) return;
            
            if (posteSelectionne === '' || posteTemoignage.getAttribute('data-poste') === posteSelectionne) {
                temoignage.style.display = '';
            } else {
                temoignage.style.display = 'none';
            }
        });
    }
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('filtre_temoignages', 'shortcode_filtre_temoignages');

    
    
