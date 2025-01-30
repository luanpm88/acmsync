<?php
/**
 * @wordpress-plugin
 * Plugin Name:       WordPress Plugin for Acelle Plugin
 * Plugin URI:        https://acellemail.com/
 * Description:       A plugin.
 * Version:           2.1
 * Author:            Acelle Team @ Basic Technology
 * Author URI:        https://acellemail.com/
 */

// Get laravel app response
function acmsync_getResponse($path=null)
{
    if (!defined('LARAVEL_START')) {
        define('LARAVEL_START', microtime(true));
    }
    require __DIR__.'/vendor/autoload.php';
    $app = require_once __DIR__.'/bootstrap/app.php';
    $acmsync_kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    if (!$path) {
        $path = isset($_REQUEST['path']) ? $_REQUEST['path'] : '/';
    }
    $response = $acmsync_kernel->handle(
        App\Wordpress\LaravelRequest::capture($path)
    );

    return $response;
}

function acmsync_activate() {
    acmsync_getResponse('/');

    // actually call the Artisan command
    \Artisan::call('migrate');
}
  
register_activation_hook( __FILE__, 'acmsync_activate' );

// Main admin menu
function acmsync_menu()
{
    // add menu page
    $menu = add_menu_page(esc_html__('ACM Connect', 'acmsync'), esc_html__('ACM Connect', 'acmsync'), 'edit_pages', 'wp-acmsync-main', function () {
    }, null, 54);
}
add_action('admin_menu', 'acmsync_menu');

// Default sub menu
function acmsync_menu_main()
{
    $hook = add_submenu_page('wp-acmsync-main', esc_html__('Dashboard', 'acmsync'), esc_html__('Dashboard', 'acmsync'), 'edit_pages', 'wp-acmsync-main', function () {
        $response = acmsync_getResponse('/acelle-connect');

        // send response
        $response->sendHeaders();
        $response->sendContent();
    });
}
add_action('admin_menu', 'acmsync_menu_main');

// Ajax page
function acmsync_ajax()
{
    $response = acmsync_getResponse($path);

    // Comment line below, do not send response
    $response->send();

    // Do not use wp_die() here, it will produce WP default layout, use die() instead;
    die();
}
add_action('wp_ajax_acmsync_ajax', 'acmsync_ajax');

// Helpers
/**
 * WP action helper for laravel.
 */

function acmsync_public_url($path)
{
    return plugins_url('acmsync/public/' . $path);
}

/**
 * WP action helper for laravel.
 */
function acmsync_wp_action($name, $parameters = [], $absolute = true)
{
    $base = url('/');
    $full = app('url')->action($name, $parameters, $absolute);
    $path = str_replace($base, '', $full);

    return admin_url('admin.php?page=wp-acmsync-main&path=' . str_replace('?', '&', $path));
}

/**
 * WP action helper for laravel.
 */
function acmsync_lr_action($name, $parameters = [], $absolute = true)
{
    $base = url('/');
    $full = app('url')->action($name, $parameters, $absolute);
    $path = str_replace($base, '', $full);
    return admin_url('admin-ajax.php?action=acmsync_ajax&path=' . str_replace('?', '&', $path));
}

/**
 * WP url helper for laravel.
 */
function acmsync_wp_url($path = null, $parameters = [], $secure = null)
{
    if (is_null($path)) {
        $path = app(\Illuminate\Routing\UrlGenerator::class);
    }

    $base = url('/');
    $full = app(\Illuminate\Routing\UrlGenerator::class)->to($path, $parameters, $secure);
    $path = str_replace($base, '', $full);

    return admin_url('admin.php?page=wp-acmsync-main&path=' . str_replace('?', '&', $path));
}

/**
 * WP url helper for laravel.
 */
function acmsync_lr_url($path = null, $parameters = [], $secure = null)
{
    if (is_null($path)) {
        $path = app(\Illuminate\Routing\UrlGenerator::class);
    }

    $base = url('/');
    $full = app(\Illuminate\Routing\UrlGenerator::class)->to($path, $parameters, $secure);
    $path = str_replace($base, '', $full);

    return admin_url('admin-ajax.php?action=acmsync_ajax&path=' . str_replace('?', '&', $path));
}

// WordPress rest api connect
function acmsync_connect( $data ) {
    $response = acmsync_getResponse('/connect');
    // Comment line below, do not send response
    $response->send();

    die();
}
add_action( 'rest_api_init', function () {
    register_rest_route( '/acelle', '/connect', array(
        'methods' => 'GET',
        'callback' => 'acmsync_connect',
    ));
});

// add beemail css to WordPress admin area
function acmsync_add_theme_scripts()
{
    wp_enqueue_style('acmsync', plugin_dir_url(__FILE__) . 'public/css/wp-admin.css');
}
add_action('admin_enqueue_scripts', 'acmsync_add_theme_scripts');
