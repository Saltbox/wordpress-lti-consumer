<?php
/**
 * Plugin Name: LTI-compatible consumer
 * Plugin URI: 
 * Description: An LTI-compatible launching plugin for Wordpress.
 * Version: 0.1.20
 * Author: John Weaver <john.weaver@saltbox.com>
 * License: GPLv3
 */


require('OAuth.php');


// Hook up callbacks
add_shortcode('lti-launch', 'lti_launch_func');

add_action('save_post', 'ensure_resource_link_id_func');

add_action('wp_enqueue_scripts', 'add_launch_script_func');


function lti_launch_func($attrs) {
    $data = lti_launch_process($attrs);

    if ( array_key_exists('error', $data) ) {
        $html = '<div class="error"><p><strong>' . $data['error'] . '</strong></p></div>';
    } else {
        $html = '';
        $id = uniqid();
        $iframeId = uniqid();

        if ( $data['display'] == 'newwindow' ) {
            $target = '_blank';
        } else if ( $data['display'] == 'iframe' ) {
            $target = 'frame-' . $iframeId;
        } else {
            $target = '_self';
        }

        if ( $data['action'] == 'auto' || $data['display'] == 'iframe' ) {
            $autolaunch = 'yes';
        } else {
            $autolaunch = 'no';
        }

        $html .= "<form method=\"post\" action=\"$data[url]\" target=\"$target\" id=\"launch-$id\" data-auto-launch=\"$autolaunch\">";
        foreach ( $data['parameters'] as $key => $value ) {
            $html .= "<input type=\"hidden\" name=\"$key\" value=\"$value\">";
        }

        if ( $data['display'] == 'iframe' ) {
            $html .= '<iframe style="width: 100%; height: 55em;" class="launch-frame" id="frame-' . $iframeId . '"></iframe>';
        } else if ( $data['action'] == 'link' ) {
            $html .= '<a href="#" onclick="lti_consumer_launch(\'' . $id . '\')">Launch</a>';
        } else {
            $html .= '<button onclick="lti_consumer_launch(\'' . $id . '\')">Launch</button>';
        }

        $html .= '</form>';
    }

    return $html;
}


function ensure_resource_link_id_func($post_id) {
    // get post content
    $content = get_post($post_id)->post_content;

    // does it contains our shortcode
    $pattern = get_shortcode_regex();
    preg_match_all("/$pattern/s", $content, $matches);

    foreach ( $matches[0] as $match ) {
        if ( strpos($match, '[lti-launch') === 0 ) {
            // Replace the original shortcode with the rewritten one
            $content = substr_replace(
                $content,
                add_resource_link_id_if_not_present($match),
                strpos($content, $match),
                strlen($match));
        }
    }

    // transform content
    
    // unhook this function so it doesn't loop infinitely
    remove_action('save_post', 'ensure_resource_link_id_func');

    // update the post, which calls save_post again
    wp_update_post(array('ID' => $post_id, 'post_content' => $content));

    // re-hook this function
    add_action('save_post', 'ensure_resource_link_id_func');
}


function add_launch_script_func() {
    wp_enqueue_script('lti_launch', plugins_url('scripts/launch.js', __FILE__), array('jquery'));
}


function add_resource_link_id_if_not_present($shortcode) {
    // split args out of shortcode, excluding the [] as well
    $pieces = explode(' ', substr($shortcode, 1, -1));

    // check if resource_link_id is present
    $found = false;
    foreach ( $pieces as $piece ) {
        if ( strpos(trim($piece), 'resource_link_id=') === 0 ) {
            $found = true;
            break;
        }
    }

    // add resource_link_id if not present
    if ( !$found ) {
        array_push($pieces, 'resource_link_id=' . uniqid());
    }

    // recombine args
    return '[' . implode(' ', $pieces) . ']';
}


function extract_user_id() {
    // Find some relevant information about the current user
    $current_user = wp_get_current_user();

    return array(
        'user_id' => $current_user->user_id,
        'lis_person_contact_email_primary' => $current_user->user_email,
        'lis_person_name_given' => $current_user->user_firstname,
        'lis_person_name_family' => $current_user->user_lastname,
    );
}

function extract_site_id() {
    // Find some relevant information about the site
    return array(
        'context_id' => basename(get_permalink()),
        'tool_consumer_instance_url' => get_site_url(),
    );
}


function determine_launch_url($configuration_url) {
    $launch_url = wp_cache_get($configuration_url, 'lti-consumer', false, $found);
    if ( $found ) {
        return $launch_url;
    }

    $parts = parse_url($configuration_url);

    if ( $parts == false || !array_key_exists('scheme', $parts) || ($parts['scheme'] != 'http' && $parts['scheme'] != 'https') ) {
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
        } catch ( Exception $e ) {
            $launch_url = false;
        }
    }

    // Keep it for 30 minutes
    wp_cache_set($configuration_url, $launch_url, 'lti-consumer', 30 * 60);
    return $launch_url;
}


function lti_launch_process($attrs) {
    // Reject launch for non-logged in users
    if ( !is_user_logged_in() ) {
        return array('error' => 'You must be logged in to launch this content.');
    } else {
        $parameters = array();
        // grab user information
        $parameters = array_merge($parameters, extract_user_id());
        // grab site information
        $parameters = array_merge($parameters, extract_site_id());

        // incorporate information from $attrs
        if ( array_key_exists('resource_link_id', $attrs) ) {
            $parameters['resource_link_id'] = $attrs['resource_link_id'];
        } else {
            return array('error' => 'You must specify the resource_link_id.');
        }

        if ( array_key_exists('return_url', $attrs) ) {
            $parameters['launch_presentation_return_url'] = $attrs['return_url'];
        }

        if ( array_key_exists('configuration_url', $attrs) ) {
            $launch_url = determine_launch_url($attrs['configuration_url']);

            if ( $launch_url == false ) {
                return 'Could not determine launch URL.';
            }
        } else if ( array_key_exists('launch_url', $attrs) ) {
            $launch_url = $attrs['launch_url'];
        } else {
            return array('error' => 'Missing launch URL and URL to configuration XML. One of these is required.');
        }

        if ( array_key_exists('consumer_key', $attrs) ) {
            $consumer_key = $attrs['consumer_key'];
        } else {
            return array('error' => 'Missing OAuth consumer key.');
        }

        if ( array_key_exists('secret_key', $attrs) ) {
            $consumer_secret = $attrs['secret_key'];
        } else {
            return array('error' => 'Missing OAuth consumer secret.');
        }

        $display = 'newwindow';

        if ( array_key_exists('display', $attrs) ) {
            $display = $attrs['display'];
        }

        if ( array_key_exists('action', $attrs) ) {
            $action = $attrs['action'];
        } else {
            $action = 'button';
        }

        return array(
            'parameters' => package_launch(
                $consumer_key, $consumer_secret,
                $launch_url,
                $parameters),
            'display' => $display,
            'action' => $action,
            'url' => $launch_url,
        );
    }
}


function package_launch($key, $secret, $launch_url, $parameters) {
    $parameters['lti_version'] = 'LTI-1p1';
    $parameters['lti_message_type'] = 'basic-lti-launch-request';

    $consumer = new OAuthConsumer($key, $secret);
    $oauth_request = OAuthRequest::from_consumer_and_token(
        $consumer, null, 'POST',
        $launch_url, $parameters);
    $oauth_request->sign_request(
        new OAuthSignatureMethod_HMAC_SHA1(), $consumer, null);
    return $oauth_request->get_parameters();
};
