<?php
/**
 * Plugin Name: Image Compressor Plugin
 * Description: Compress and manage images directly within the WordPress media library.
 * Version: 1.3.0
 * Author: Aaditya Goenka
 * License: GPL-2.0+
 */

if (!defined('ABSPATH')) exit;

define('IMAGE_COMPRESSOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IMAGE_COMPRESSOR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required classes
require_once IMAGE_COMPRESSOR_PLUGIN_DIR . 'includes/class-image-compressor.php';
require_once IMAGE_COMPRESSOR_PLUGIN_DIR . 'includes/class-image-handler.php';

// Initialize the plugin
function image_compressor_init() {
    new Image_Compressor();
}
add_action('plugins_loaded', 'image_compressor_init');
