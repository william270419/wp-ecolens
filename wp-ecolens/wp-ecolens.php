<?php
/**
 * Plugin Name: WP EcoLens - Auditor de imagens
 * Plugin URI: https://github.com/william270419/wp-ecolens
 * Description: Auditoria de imagens, cópias WebP e análise opcional de desempenho e acessibilidade com PageSpeed Insights.
 * Version: 1.0.2
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: William Marques
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */
if (!defined('ABSPATH')) { exit; }
define('ECOLENS_VERSION', '1.0.2');
require_once __DIR__ . '/includes/images.php';
require_once __DIR__ . '/includes/api.php';
require_once __DIR__ . '/includes/admin.php';

add_action('admin_menu', function () {
    add_menu_page('EcoLens Auditor', 'EcoLens Audit', 'manage_options', 'wp-ecolens', 'ecolens_admin_page', 'dashicons-performance', 80);
});
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'toplevel_page_wp-ecolens') { return; }
    wp_enqueue_style('ecolens-admin', plugins_url('assets/admin.css', __FILE__), [], ECOLENS_VERSION);
    wp_enqueue_script('ecolens-admin', plugins_url('assets/admin.js', __FILE__), ['jquery'], ECOLENS_VERSION, true);
    wp_localize_script('ecolens-admin', 'EcoLens', [
        'ajax' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ecolens_nonce'),
        'limit' => (int) get_option('ecolens_limit', 200),
    ]);
});
