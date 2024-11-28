<?php
if (!defined('ABSPATH')) exit;

class Image_Compressor {
    public function __construct() {
        // Hook into the admin menu to create the dashboard page
        add_action('admin_menu', [$this, 'add_admin_menu']);
        // Hook to enqueue custom scripts and styles for the admin dashboard
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Add a menu page for the Image Compressor plugin.
     */
    public function add_admin_menu() {
        add_menu_page(
            'Image Compressor',               // Page title
            'Image Compressor',               // Menu title
            'manage_options',                 // Capability required
            'image-compressor',               // Menu slug
            [$this, 'render_dashboard'],      // Callback function
            'dashicons-image-filter',         // Icon for the menu
            80                                // Position in the menu
        );
    }

    /**
     * Enqueue styles and scripts for the admin dashboard.
     *
     * @param string $hook The current admin page.
     */
    public function enqueue_assets($hook) {
        // Only enqueue assets on the Image Compressor dashboard page
        if ($hook !== 'toplevel_page_image-compressor') return;

        // Enqueue custom CSS for the plugin
        wp_enqueue_style(
            'image-compressor-css', 
            IMAGE_COMPRESSOR_PLUGIN_URL . 'assets/css/admin.css'
        );

        // Enqueue custom JavaScript for the plugin
        wp_enqueue_script(
            'image-compressor-js', 
            IMAGE_COMPRESSOR_PLUGIN_URL . 'assets/js/admin.js', 
            ['jquery'], 
            null, 
            true
        );

        // Enqueue Lightbox2 CSS via CDN
        wp_enqueue_style(
            'lightbox-css', 
            'https://cdn.jsdelivr.net/npm/lightbox2@2.11.5/dist/css/lightbox.min.css'
        );

        // Enqueue Lightbox2 JavaScript via CDN
        wp_enqueue_script(
            'lightbox-js', 
            'https://cdn.jsdelivr.net/npm/lightbox2@2.11.5/dist/js/lightbox.min.js', 
            ['jquery'], 
            null, 
            true
        );

        // Localize JavaScript for AJAX calls
        wp_localize_script('image-compressor-js', 'imageCompressor', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('image_compressor_nonce'),
        ]);
    }

    /**
     * Render the admin dashboard page for the Image Compressor plugin.
     */
    public function render_dashboard() {
        // Check if there are images in the media library
        $args = [
            'post_type'      => 'attachment',
            'post_mime_type' => ['image/jpeg', 'image/png', 'image/gif'],
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
        ];
    
        $images = new WP_Query($args);
    
        if (!$images->have_posts()) {
            echo '<div class="notice notice-warning"><p>No images found in the media library.</p></div>';
            return;
        }
    
        // Include the dashboard template
        include IMAGE_COMPRESSOR_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }
    
}
