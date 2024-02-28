<?php
/**
 * Plugin Name: LTI-compatible consumer
 * Plugin URI:
 * Description: An LTI-compatible launching plugin for Wordpress.
 * Version: 0.4.6
 * Author: John Weaver <john.weaver@saltbox.com>, Darrel Kleynhans
 * License: GPLv3
 */

namespace Saltbox;

require('OAuth.php');

define('LTI_CONSUMER_PLUGIN_PATH', dirname(__FILE__) . '/');
define('LTI_CONSUMER_PLUGIN_SITE_HOME_URL', get_site_url());
define('LTI_CONSUMER_PLUGIN_URL', plugins_url('', __FILE__));


/*
 * Create the lti_launch custom post type.
 */
add_action('init', 'Saltbox\sb_create_lti_post_type_func');

function sb_create_lti_post_type_func() {
    register_post_type(
            'lti_launch',
            array(
                'labels' => array(
                    'name' => __('LTI content'),
                    'singular_name' => __('LTI content'),
                    'add_new_item' => __('Add new LTI content'),
                    'edit_item' => __('Edit LTI content'),
                    'new_item' => __('New LTI content'),
                    'view_item' => __('View LTI content'),
                    'search_items' => __('Search LTI content'),
                    'not_found' => __('No LTI content found'),
                    'not_found_in_trash' => __('No LTI content found in Trash'),
                ),
                'description' => __('An LTI-compatible tool for content launch'),
                'publicly_queryable' => true,
                'public' => true,
                'has_archive' => true,
                'supports' => array(
                    'title',
                    'editor',
                ),
            )
    );
}

// Add menu item for options
// Hook   
add_action('admin_menu', 'Saltbox\add_sb_options_submenu');

//admin_menu callback function
function add_sb_options_submenu() {
    add_submenu_page(
            'edit.php?post_type=lti_launch', //$parent_slug
            __('LTI content options'), //$page_title
            __('LTI options'), //$menu_title
            'manage_options', //$capability
            'lti_launch_options', //$menu_slug
            'Saltbox\sb_lti_options_submenu_render_page'//$function
    );

    add_submenu_page(
            'edit.php?post_type=lti_launch', //$parent_slug
            __('Content selector'), //$page_title
            __('Content selector'), //$menu_title
            'manage_options', //$capability
            'lti_content_selector', //$menu_slug
            'Saltbox\sb_lti_content_selector_submenu_render_page'//$function
    );
}

//add_submenu_page callback function
function sb_lti_options_submenu_render_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized user');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['_wpnonce_sb_lti_options_page_action']) || !wp_verify_nonce($_POST['_wpnonce_sb_lti_options_page_action'], 'sb_lti_options_page_action')) {
            //Nonce check failed.
            wp_die("Error! Nonce verification failed from admin.");
        }

        if (isset($_POST['lti_instructor_user_role'])) {
            update_option('lti_instructor_user_role', $_POST['lti_instructor_user_role']);
        }
    }

    $instructor_role = get_option('lti_instructor_user_role', 'administrator');

    $editable_roles = get_editable_roles();
    include 'lti-consumer-form.php';
}

//add_submenu_page callback function
function sb_lti_content_selector_submenu_render_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized user');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        sb_get_lti_content_selector();
        // include 'lti-launch-settings.php';
        return;
    }

    //wp_nonce_field('lti_content_inner_custom_box', 'lti_content_inner_custom_nonce');
    $instructor_role = get_option('lti_instructor_user_role', array('administrator'));
    //Get all the admin WP users.
    $args = array(
        'role__in' => $instructor_role,
        'orderby' => 'user_nicename',
        'order' => 'ASC'
    );
    $users = get_users($args);

    $consumer_key = get_option('lti_meta_consumer_key');
    $secret_key = get_option('lti_meta_secret_key');
    $display = get_option('lti_meta_display', 'iframe');
    $action = get_option('lti_meta_action');
    $launch_url = get_option('lti_meta_launch_url');
    $configuration_url = get_option('lti_meta_configuration_url');
    $return_url = get_option('lti_meta_return_url');
    $version = get_option('lti_meta_version', 'LTI-1p1');
    $instructor_user = get_option('lti_meta_instructor_user');
    $institution_role = get_option('lti_meta_roles');
    $other_parameters = get_option('lti_meta_other_parameters');
    //admin_url('edit.php?post_type=lti_launch&page=lti_content_selector')
    //echo LTI_CONSUMER_PLUGIN_URL . '/lti-content-selector.php'
    ?>
    <style>
        .display_tr,.action_button_tr { display: none};

    </style>
    <form action="<?php admin_url('edit.php?post_type=lti_launch&page=lti_content_selector') ?>" method="post" target="_blank" >
        <?php wp_nonce_field('sb_lti_content_selector_action', '_wpnonce_sb_lti_content_selector_action'); ?>
        <?php include 'lti-launch-settings.php'; ?>
        <input type="submit" name="Launch" id="launch" class="button button-primary button-large" value="launch">
    </form>
    <?php
}

function sb_get_lti_content_selector() {

    if (!isset($_POST['_wpnonce_sb_lti_content_selector_action']) || !wp_verify_nonce($_POST['_wpnonce_sb_lti_content_selector_action'], 'sb_lti_content_selector_action')) {
        //Nonce check failed.
        wp_die("Error! Nonce verification failed from admin.");
    }

    $consumer_key = sanitize_text_field(filter_input(INPUT_POST, 'lti_content_field_consumer_key'));
    $consumer_secret = sanitize_text_field(filter_input(INPUT_POST, 'lti_content_field_secret_key'));
    // $display = sanitize_text_field(filter_input(INPUT_POST, 'lti_content_field_display'));
    // $action = sanitize_text_field(filter_input(INPUT_POST, 'lti_content_field_action'));
    $launch_url = esc_url_raw(filter_input(INPUT_POST, 'lti_content_field_launch_url'));
    $configuration_url = esc_url_raw(filter_input(INPUT_POST, 'lti_content_field_configuration_url'));
    $return_url = esc_url_raw(filter_input(INPUT_POST, 'lti_content_field_return_url'));
    $version = sanitize_text_field(filter_input(INPUT_POST, 'lti_content_field_version'));
    $instructor_user = sanitize_text_field(filter_input(INPUT_POST, 'lti_content_field_instructor_user'));
    $institution_role = sanitize_text_field(filter_input(INPUT_POST, 'lti_content_field_role'));
    $other_parameters = sanitize_textarea_field(filter_input(INPUT_POST, 'lti_content_field_other_parameters'));
    $id = uniqid();

    update_option('lti_meta_consumer_key', $consumer_key);
    update_option('lti_meta_secret_key', $consumer_secret);
    // update_option('lti_meta_display', $display);
    // update_option('lti_meta_action', $action);
    update_option('lti_meta_launch_url', $launch_url);
    update_option('lti_meta_configuration_url', $configuration_url);
    update_option('lti_meta_return_url', $return_url);
    update_option('lti_meta_version', $version);
    update_option('lti_meta_instructor_user', $instructor_user);
    update_option('lti_meta_roles', $institution_role);
    update_option('lti_meta_other_parameters', $other_parameters);

    $parameters = array();
    // grab site information
    $parameters = array_merge($parameters, sb_extract_site_id());
    // grab user information
    $parameters = array_merge($parameters, sb_extract_user_id($instructor_user));

    $parameters['resource_link_id'] = $id;
    if (isset($institution_role) && $institution_role) {
        $parameters['roles'] = $institution_role;
    }

    if (isset($return_url) && $return_url) {
        $parameters['launch_presentation_return_url'] = $return_url;
    }

    if (!isset($version)) {
        $version = 'LTI-1p1';
    }

    // var_dump($parameters);
    if (isset($configuration_url) && $configuration_url) {
        $launch_url = sb_determine_launch_url($configuration_url);

        if ($launch_url == false) {
            echo ( 'Could not determine launch URL.');
        }
    } else if (!isset($launch_url) || $launch_url === "") {
        echo ( 'Missing launch URL and URL to configuration XML. One of these is required.');
    }

    if (!isset($consumer_key)) {
        echo ( 'Missing OAuth consumer key.');
    }

    if (!isset($consumer_secret)) {
        echo ( 'Missing OAuth consumer secret.');
    }

    if (!isset($display)) {
        $display = 'iframe';
    }

    if (!isset($action)) {
        $action = 'link';
    }

    if (isset($other_parameters) && !empty($other_parameters)) {
        $obj = json_decode($other_parameters);
        if ($obj != null)
            foreach ($obj as $key => $value) {
                $parameters[$key] = $value;
            }
    }

    $parameters = sb_package_launch(
            $version,
            $consumer_key, $consumer_secret,
            $launch_url,
            $parameters);

    // Strip out GET parameters from the parameters we pass
    // into the POST body.
    parse_str(parse_url($launch_url, PHP_URL_QUERY), $qs_params);
    foreach ($qs_params as $k => $v) {
        unset($parameters[$k]);
    }



    $html = sb_lti_launch(array(
        'parameters' => array_filter($parameters),
        'id' => $id,
        'display' => $display,
        'action' => $action,
        'url' => $launch_url,
        'text' => 'Lti content selector',
    ));
    echo $html;
}

add_filter('post_row_actions', 'Saltbox\sb_add_shortcode_generator_link', 10, 2);

function sb_add_shortcode_generator_link($actions, $post) {
    if ($post->post_type == 'lti_launch') {
        unset($actions['view']);
        $actions['shortcode_generator'] = 'Shortcode: [lti-launch id=' . $post->post_name . ']';
    }

    return $actions;
}

add_action('add_meta_boxes', 'Saltbox\sb_lti_content_meta_box');

function sb_lti_content_meta_box() {
    add_meta_box(
            'lti_content_custom_section_id',
            __('LTI launch settings', 'lti-consumer'),
            'Saltbox\sb_lti_content_inner_custom_box',
            'lti_launch'
    );
}

add_filter('get_sample_permalink_html', 'Saltbox\sb_permalink_removal', 1000, 4);

function sb_permalink_removal($return, $id, $new_title, $new_slug) {
    global $post;
    if ($post && $post->post_type == 'lti_launch') {
        return '';
    } else {
        return $return;
    }
}

function sb_lti_content_inner_custom_box($lti_content) {
    wp_nonce_field('lti_content_inner_custom_box', 'lti_content_inner_custom_nonce');
    $instructor_role = get_option('lti_instructor_user_role', array('administrator'));
    //Get all the admin WP users.
    $args = array(
        'role__in' => $instructor_role,
        'orderby' => 'user_nicename',
        'order' => 'ASC'
    );
    $users = get_users($args);

    $consumer_key = get_post_meta($lti_content->ID, '_lti_meta_consumer_key', true);
    $secret_key = get_post_meta($lti_content->ID, '_lti_meta_secret_key', true);
    $display = get_post_meta($lti_content->ID, '_lti_meta_display', true);
    $action = get_post_meta($lti_content->ID, '_lti_meta_action', true);
    $launch_url = get_post_meta($lti_content->ID, '_lti_meta_launch_url', true);
    $configuration_url = get_post_meta($lti_content->ID, '_lti_meta_configuration_url', true);
    $return_url = get_post_meta($lti_content->ID, '_lti_meta_return_url', true);
    $version = get_post_meta($lti_content->ID, '_lti_meta_version', true);
    $instructor_user = get_post_meta($lti_content->ID, '_lti_meta_instructor_user', true);
    $institution_role = get_post_meta($lti_content->ID, '_lti_meta_roles', true);
    $other_parameters = get_post_meta($lti_content->ID, '_lti_meta_other_parameters', true);

    if ($display === '') {
        $display = 'iframe';
    }

    if ($version !== 'LTI-1p1' && $version !== 'LTI-1p0') {
        $version = 'LTI-1p1';
    }
    echo '<p>All of the following fields are optional, and can be overridden by specifying the corresponding parameters to the lti-launch shortcode.</p>';
    include 'lti-launch-settings.php';
}

add_filter('the_content', 'Saltbox\sb_lti_content_include_launcher');

function sb_lti_content_include_launcher($content) {
    global $post;

    if ($post->post_type == 'lti_launch') {
        $content .= '<p>[lti-launch id=' . $post->post_name . ' resource_link_id=' . $post->ID . ']</p>';
    }

    return $content;
}

add_action('save_post', 'Saltbox\sb_lti_content_save_post');

function sb_lti_content_save_post($post_id) {
    // From http://codex.wordpress.org/Function_Reference/add_meta_box
    // Check if our nonce is set.
    if (!isset($_POST['lti_content_inner_custom_nonce'])) {
        return $post_id;
    }

    $nonce = $_POST['lti_content_inner_custom_nonce'];

    // Verify that the nonce is valid.
    if (!wp_verify_nonce($nonce, 'lti_content_inner_custom_box')) {
        return $post_id;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return $post_id;
    }

    /* OK, its safe for us to save the data now. */

    // Sanitize user input.
    $consumer_key = sanitize_text_field($_POST['lti_content_field_consumer_key']);
    $secret_key = sanitize_text_field($_POST['lti_content_field_secret_key']);
    $display = sanitize_text_field($_POST['lti_content_field_display']);
    $action = sanitize_text_field($_POST['lti_content_field_action']);
    $launch_url = esc_url_raw($_POST['lti_content_field_launch_url']);
    $configuration_url = esc_url_raw($_POST['lti_content_field_configuration_url']);
    $return_url = esc_url_raw($_POST['lti_content_field_return_url']);
    $version = sanitize_text_field($_POST['lti_content_field_version']);
    $instructor_user = sanitize_text_field($_POST['lti_content_field_instructor_user']);
    $institution_role = sanitize_text_field($_POST['lti_content_field_role']);
    $other_parameters = sanitize_textarea_field($_POST['lti_content_field_other_parameters']);

    // Update the meta field in the database.
    update_post_meta($post_id, '_lti_meta_consumer_key', $consumer_key);
    update_post_meta($post_id, '_lti_meta_secret_key', $secret_key);
    update_post_meta($post_id, '_lti_meta_display', $display);
    update_post_meta($post_id, '_lti_meta_action', $action);
    update_post_meta($post_id, '_lti_meta_launch_url', $launch_url);
    update_post_meta($post_id, '_lti_meta_configuration_url', $configuration_url);
    update_post_meta($post_id, '_lti_meta_return_url', $return_url);
    update_post_meta($post_id, '_lti_meta_version', $version);
    update_post_meta($post_id, '_lti_meta_instructor_user', $instructor_user);
    update_post_meta($post_id, '_lti_meta_roles', $institution_role);
    update_post_meta($post_id, '_lti_meta_other_parameters', $other_parameters);
}

/*
 * Add the lti-launch shortcode.
 */
add_shortcode('lti-launch', 'Saltbox\sb_lti_launch_func');

function sb_lti_launch_func($attrs) {
    $data = sb_lti_launch_process($attrs);
    return sb_lti_launch($data);
}

function sb_lti_launch($data) {
    if (array_key_exists('error', $data)) {
        $html = '<div class="error"><p><strong>' . $data['error'] . '</strong></p></div>';
    } else {
        $html = '';
        $id = uniqid();
        $iframeId = uniqid();

        if ($data['display'] == 'newwindow') {
            $target = '_blank';
        } else if ($data['display'] == 'iframe') {
            $target = 'frame-' . $iframeId;
        } else {
            $target = '_self';
        }

        if ($data['action'] == 'auto' || $data['display'] == 'iframe') {
            $autolaunch = 'yes';
        } else {
            $autolaunch = 'no';
        }

        $html .= "<form method=\"post\" action=\"" . esc_url($data['url']) . "\" target=\"$target\" id=\"launch-$id\" data-id=\"$id\" data-post=\"$data[id]\" data-auto-launch=\"$autolaunch\">";
        foreach ($data['parameters'] as $key => $value) {
            $html .= "<input type=\"hidden\" name=\"$key\" value=\"$value\">";
        }

        if ($data['display'] == 'iframe') {
            $html .= '<iframe style="width: 100%; height: 55em;" class="launch-frame" name="frame-' . $iframeId . '"></iframe>';
            // Immediately send the lti_launch action when showing the iframe.
            if ($data['id']) {
                do_action('lti_launch', $data['id']);
            }
        } else if ($data['action'] == 'link') {
            $html .= '<a style="cursor: pointer;" href="#" onclick="lti_consumer_launch(\'' . $id . '\')">Launch ' . $data['text'] . '</a>';
        } else {
            //$html .= '<button style="cursor: pointer;" onclick="lti_consumer_launch(\'' . $id . '\')">Launch ' . $data['text'] . '</button>';
			 $html .= '<input type="submit" value= "Launch ' . $data['text'] . '" style="cursor: pointer;">';
        }

        $html .= '</form>';
    }

    return $html;
}

add_action('wp_head', 'Saltbox\sb_lti_launch_ajaxurl');

function sb_lti_launch_ajaxurl() {
    ?>
    <script type="text/javascript">
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    </script>
    <?php
}

/*
 * Emit an 'lti_launch' action when the Javascript informs us about a
 * launch.
 */
add_action('wp_ajax_lti_launch', 'Saltbox\sb_hook_lti_launch_action_func');
add_action('wp_ajax_nopriv_lti_launch', 'Saltbox\sb_hook_lti_launch_action_func');

function sb_hook_lti_launch_action_func() {
    $lti_launch = get_post($_POST['post']);
    // make sure that at least the post id is valid
    if ($lti_launch && $lti_launch->post_type == 'lti_launch') {
        do_action('lti_launch', $_POST['post']);
    }
}

/*
 * Find lti-launch shortcodes in posts and add a resource_link_id to any found
 * if they don't already have one set.
 */
add_action('save_post', 'Saltbox\sb_ensure_resource_link_id_func', 5, 1);

function sb_ensure_resource_link_id_func($post_id) {
    // get post content
    $content = get_post($post_id)->post_content;

    // does it contains our shortcode
    $pattern = get_shortcode_regex();
    preg_match_all("/$pattern/s", $content, $matches);

    foreach ($matches[0] as $match) {
        if (strpos($match, '[lti-launch') === 0) {
            // Replace the original shortcode with the rewritten one
            $content = substr_replace(
                    $content,
                    sb_add_resource_link_id_if_not_present($match),
                    strpos($content, $match),
                    strlen($match));
        }
    }

    // transform content
    // unhook this function so it doesn't loop infinitely
    remove_action('save_post', 'Saltbox\sb_ensure_resource_link_id_func', 5, 1);

    // update the post, which calls save_post again
    wp_update_post(array('ID' => $post_id, 'post_content' => $content));

    // re-hook this function
    add_action('save_post', 'Saltbox\sb_ensure_resource_link_id_func', 5, 1);

    return $post_id;
}

/*
 * Insert our LTI launch script into the page.
 */
add_action('wp_enqueue_scripts', 'Saltbox\sb_add_launch_script_func');
add_action('admin_enqueue_scripts', 'Saltbox\sb_add_launch_script_func');

function sb_add_launch_script_func() {
    wp_enqueue_script('lti_launch', plugins_url('scripts/launch.js', __FILE__), array('jquery'));
}

function sb_add_resource_link_id_if_not_present($shortcode) {
    // split args out of shortcode, excluding the [] as well
    $pieces = explode(' ', substr($shortcode, 1, -1));

    // check if resource_link_id is present
    $found = false;
    foreach ($pieces as $piece) {
        if (strpos(trim($piece), 'resource_link_id=') === 0) {
            $found = true;
            break;
        }
    }

    // add resource_link_id if not present
    if (!$found) {
        array_push($pieces, 'resource_link_id=' . uniqid());
    }

    $onsave = false;
    foreach ($pieces as $piece) {
        if (strpos(trim($piece), 'onsave=') === 0) {
            $onsave = true;
            break;
        }
    }

      if ($onsave) {
           return do_shortcode( '[' . implode(' ', $pieces) . ']');

            // Replace the original shortcode with the rewritten one
            $content = substr_replace(
                    $content,
                    $processed,
                    strpos($content, $match),
                    strlen($match));
        }else{
        // recombine args
        return '[' . implode(' ', $pieces) . ']';
        }

}

/*
 * Utilities
 */

function sb_extract_user_id($userId) {
    $current_user = get_lti_wp_user($userId);
    if ($current_user == null) {
        return array('error' => 'You must be logged in to launch this content.');
    }

    return array(
        'user_id' => $current_user->ID,
        'lis_person_contact_email_primary' => $current_user->user_email,
        'lis_person_name_given' => $current_user->user_firstname,
        'lis_person_name_family' => $current_user->user_lastname,
    );
}

// Find some relevant information about the current user, or the selected instructor user.
function get_lti_wp_user($userId) {
    if (isset($userId) && intval($userId) > 0) {
        return get_user_by('id', $userId);
    } else if (is_user_logged_in()) {
        return wp_get_current_user();
    } else {
        $postBody = file_get_contents("php://input");
        $body = json_decode($postBody, true);
        if (!empty($body['token'])) {
            $token = $body['token'];
            $user = apply_filters('vibebp_api_get_user_from_token', '', $token);
            $user_id = $user->id;
            if (!empty($user_id)) {
                return get_user_by('id', $user_id);
            }
        }
    }
    return null;
}

function sb_extract_site_id() {
    // Find some relevant information about the site
    $context_id = basename(get_permalink());
    if (empty($context_id)) {
        $context_id = 'app';
    }
    return array(
        'context_id' => $context_id,
        'tool_consumer_instance_url' => get_site_url(),
    );
}

function sb_determine_launch_url($configuration_url) {
    $launch_url = wp_cache_get($configuration_url, 'lti-consumer', false, $found);
    if ($found) {
        return $launch_url;
    }

    $parts = parse_url($configuration_url);

    if ($parts == false || !array_key_exists('scheme', $parts) || ($parts['scheme'] != 'http' && $parts['scheme'] != 'https')) {
        // Don't trust weird URLs (could be file path or something).
        $launch_url = false;
    } else {
        try {
            $opts = array(
                'http' => array(
                    'header' => "Accept: application/xml\r\n"
                )
            );

            $context = stream_context_create($opts);
            $config_string = file_get_contents($configuration_url, false, $context);

            $config = simplexml_load_string($config_string);
            $launch_url = (string) $config->children('blti', true)->launch_url;
        } catch (Exception $e) {
            $launch_url = false;
        }
    }

    // Keep it for 30 minutes
    wp_cache_set($configuration_url, $launch_url, 'lti-consumer', 30 * 60);
    return $launch_url;
}

function sb_lti_launch_process($attrs) {
    // Reject launch for non-logged in users    
    if (get_lti_wp_user(null) == null) {
        return array('error' => 'You must be logged in to launch this content.');
    } else {
        $instructor_user = -1;
        $parameters = array();
        // grab site information
        $parameters = array_merge($parameters, sb_extract_site_id());

        $post_id = '';
        $text = '';

        if (array_key_exists('id', $attrs)) {
            $posts = get_posts(array(
                'name' => $attrs['id'],
                'post_type' => 'lti_launch',
                'post_status' => 'publish',
                'posts_per_page' => 1,
            ));
            if ($posts) {
                $lti_content = $posts[0];
                $post_id = $lti_content->ID;
                $consumer_key = get_post_meta($lti_content->ID, '_lti_meta_consumer_key', true);
                $consumer_secret = get_post_meta($lti_content->ID, '_lti_meta_secret_key', true);
                $display = get_post_meta($lti_content->ID, '_lti_meta_display', true);
                $action = get_post_meta($lti_content->ID, '_lti_meta_action', true);
                $launch_url = get_post_meta($lti_content->ID, '_lti_meta_launch_url', true);
                $configuration_url = get_post_meta($lti_content->ID, '_lti_meta_configuration_url', true);
                if ($configuration_url === "") {
                    unset($configuration_url);
                }
                $return_url = get_post_meta($lti_content->ID, '_lti_meta_return_url', true);
                $text = $lti_content->post_title;
                $version = get_post_meta($lti_content->ID, '_lti_meta_version', true) or 'LTI-1p1';
                $instructor_user = intval(get_post_meta($lti_content->ID, '_lti_meta_instructor_user', true));
                $institution_role = get_post_meta($lti_content->ID, '_lti_meta_roles', true);
                $other_parameters = get_post_meta($lti_content->ID, '_lti_meta_other_parameters', true);
            }
        }
        // grab user information
        $parameters = array_merge($parameters, sb_extract_user_id($instructor_user));

        // incorporate information from $attrs
        if (array_key_exists('resource_link_id', $attrs)) {
            $parameters['resource_link_id'] = $attrs['resource_link_id'];
        } else {
            return array('error' => 'You must specify the resource_link_id.');
        }

        if (array_key_exists('return_url', $attrs)) {
            $parameters['launch_presentation_return_url'] = $attrs['return_url'];
        } else if (isset($return_url) && $return_url) {
            $parameters['launch_presentation_return_url'] = $return_url;
        }

        if (array_key_exists('roles', $attrs)) {
            $parameters['roles'] = $attrs['roles'];
        } else if (isset($institution_role) && $institution_role) {
            $parameters['roles'] = $institution_role;
        }

        if (array_key_exists('version', $attrs)) {
            $version = $attrs['version'];
        } else if (!isset($version)) {
            $version = 'LTI-1p1';
        }

        if (array_key_exists('configuration_url', $attrs)) {
            $launch_url = sb_determine_launch_url($attrs['configuration_url']);

            if ($launch_url == false) {
                return array('error' => 'Could not determine launch URL.');
            }
        } else if (array_key_exists('launch_url', $attrs)) {
            $launch_url = $attrs['launch_url'];
        } else if (isset($configuration_url) && $configuration_url) {
            $launch_url = sb_determine_launch_url($configuration_url);

            if ($launch_url == false) {
                return array('error' => 'Could not determine launch URL.');
            }
        } else if (!isset($launch_url) || $launch_url === "") {
            return array('error' => 'Missing launch URL and URL to configuration XML. One of these is required.');
        }

        if (array_key_exists('consumer_key', $attrs)) {
            $consumer_key = $attrs['consumer_key'];
        } else if (!isset($consumer_key)) {
            return array('error' => 'Missing OAuth consumer key.');
        }

        if (array_key_exists('secret_key', $attrs)) {
            $consumer_secret = $attrs['secret_key'];
        } else if (!isset($consumer_secret)) {
            return array('error' => 'Missing OAuth consumer secret.');
        }

        if (!isset($display)) {
            $display = 'newwindow';
        }

        if (array_key_exists('display', $attrs)) {
            $display = $attrs['display'];
        }

        if (array_key_exists('action', $attrs)) {
            $action = $attrs['action'];
        } else if (!isset($action)) {
            $action = 'button';
        }

        if (isset($other_parameters) && !empty($other_parameters)) {
            $obj = json_decode($other_parameters);
            if ($obj != null)
                foreach ($obj as $key => $value) {
                    $parameters[$key] = $value;
                }
        }

        $parameters = sb_package_launch(
                $version,
                $consumer_key, $consumer_secret,
                $launch_url,
                $parameters);

        // Strip out GET parameters from the parameters we pass
        // into the POST body.
        parse_str(parse_url($launch_url, PHP_URL_QUERY), $qs_params);
        foreach ($qs_params as $k => $v) {
            unset($parameters[$k]);
        }

        return array(
            'parameters' => $parameters,
            'id' => $post_id,
            'display' => $display,
            'action' => $action,
            'url' => $launch_url,
            'text' => $text,
        );
    }
}

function sb_package_launch($version, $key, $secret, $launch_url, $parameters) {
    $parameters['lti_version'] = $version;
    $parameters['lti_message_type'] = 'basic-lti-launch-request';

    $consumer = new OAuthConsumer($key, $secret);
    $oauth_request = OAuthRequest::from_consumer_and_token(
                    $consumer, null, 'POST',
                    $launch_url, $parameters);
    $oauth_request->sign_request(
            new OAuthSignatureMethod_HMAC_SHA1(), $consumer, null);
    return $oauth_request->get_parameters();
}