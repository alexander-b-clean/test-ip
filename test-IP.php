<?php
/**
 * Plugin Name: Test IP
 * Plugin URI:
 * Description:
 * Version: 1.0.0
 * License: GPL-2.0+
 * Text Domain: test-ip
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('TEST_IP_VERSION', '1.0.0');
define('TEST_IP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TEST_IP_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Add hidden field to comment form.
 */
function test_ip_add_hidden_field()
{
    echo '<input type="hidden" name="test_ip_field" value="" />';
}

add_action('comment_form', 'test_ip_add_hidden_field');

/**
 * Enqueue script to get IP address.
 */
function test_ip_enqueue_script()
{
    if (is_singular() && comments_open()) {
        wp_enqueue_script(
            'test-ip-script',
            TEST_IP_PLUGIN_URL . 'js/test-ip.js',
            array(),
            TEST_IP_VERSION,
            true
        );

        wp_localize_script(
            'test-ip-script',
            'test_ip_vars',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('test_ip_nonce'),
            )
        );
    }
}

add_action('wp_enqueue_scripts', 'test_ip_enqueue_script');

/**
 * AJAX handler to get IP address.
 */
function test_ip_get_ip_ajax()
{
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'test_ip_nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    $ip = test_ip_get_ip();
    wp_send_json_success(array('ip' => $ip));
}

add_action('wp_ajax_test_ip_get_ip', 'test_ip_get_ip_ajax');
add_action('wp_ajax_nopriv_test_ip_get_ip', 'test_ip_get_ip_ajax');

/**
 * Test validate IP before saved comment.
 */
function test_ip_validate_comment($commentdata)
{
    if (!isset($_POST['test_ip_field']) || empty($_POST['test_ip_field'])) {
        wp_die(
            'Empty IP field',
            'Empty IP field',
            array('response' => 403, 'back_link' => true)
        );
    }

    $submitted_ip = sanitize_text_field(wp_unslash($_POST['test_ip_field']));
    $actual_ip = test_ip_get_ip();
    if ($submitted_ip !== $actual_ip) {
        wp_die(
            'IP validation error',
            'IP validation error',
            array('response' => 403, 'back_link' => true)
        );
    }

    return $commentdata;
}

add_filter('pre_comment_approved', 'test_ip_validate_comment');

/**
 * Get IP address.
 */
function test_ip_get_ip()
{

    if (isset($_SERVER['HTTP_X_REAL_IP'])) {
        return sanitize_text_field(wp_unslash($_SERVER['HTTP_X_REAL_IP']));
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Proxy servers can send through this header like this: X-Forwarded-For: client1, proxy1, proxy2
        // Make sure we always only send through the first IP in the list which should always be the client IP.
        $value = trim(current(preg_split('/,/', sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])))));
        // Account for the '<IPv4 address>:<port>', '[<IPv6>]' and '[<IPv6>]:<port>' cases, removing the port.
        $value = preg_replace('/([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)\:.*|\[([^]]+)\].*/', '$1$2', $value);
        return (string)rest_is_ip_address($value);
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        return sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
    }
    return '';
}