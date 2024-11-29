<?php
if (!defined('ABSPATH')) exit;

class Image_Handler {
    /**
     * Compress the provided image file.
     *
     * @param string $image_path Path to the image file.
     * @param int $quality Compression quality (10-100).
     * @return string|false Compressed image data or false on failure.
     */
    public static function compress_image($image_path, $quality) {
        // Validate the image path
        if (!file_exists($image_path) || !is_readable($image_path)) {
            return false;
        }

        // Get image info
        $info = getimagesize($image_path);
        if (!$info || !isset($info['mime'])) {
            return false;
        }

        $mime = $info['mime'];
        $image = null;

        // Handle supported image formats
        try {
            switch ($mime) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($image_path);
                    ob_start();
                    imagejpeg($image, null, $quality);
                    break;

                case 'image/png':
                    $image = imagecreatefrompng($image_path);
                    ob_start();
                    imagepng($image, round($quality / 10)); // PNG compression uses 0-9 scale
                    break;

                case 'image/gif':
                    $image = imagecreatefromgif($image_path);
                    ob_start();
                    imagegif($image);
                    break;

                case 'image/webp':
                    $image = imagecreatefromwebp($image_path);
                    ob_start();
                    imagewebp($image, null, $quality);
                    break;

                default:
                    return false; // Unsupported format
            }
        } catch (Exception $e) {
            error_log('Compression error: ' . $e->getMessage());
            return false;
        }

        $compressed_data = ob_get_clean();
        if ($image) {
            imagedestroy($image);
        }

        return $compressed_data;
    }

    /**
     * Handle the AJAX request for image compression and replacement.
     */
    public static function handle_ajax_request() {
        // Verify nonce for security
        check_ajax_referer('image_compressor_nonce', 'security');

        // Sanitize and validate inputs
        $image_id = intval($_POST['image_id']);
        $quality = intval($_POST['quality']);
        $replace = isset($_POST['replace']) && $_POST['replace'] === 'true';

        if ($image_id <= 0 || $quality < 10 || $quality > 100) {
            wp_send_json_error(['message' => 'Invalid parameters provided.']);
            return;
        }

        $image_path = sanitize_text_field(get_attached_file($image_id));
        if (!$image_path || !file_exists($image_path) || !is_readable($image_path)) {
            wp_send_json_error(['message' => 'File not found or unreadable.']);
            return;
        }

        $original_size = filesize($image_path);
        $compressed_data = self::compress_image($image_path, $quality);

        if ($compressed_data) {
            try {
                if ($replace) {
                    // Replace the original file
                    file_put_contents($image_path, $compressed_data);
                    clearstatcache(true, $image_path);
                    $new_size = filesize($image_path);

                    // Update attachment metadata
                    wp_update_attachment_metadata($image_id, wp_generate_attachment_metadata($image_id, $image_path));

                    wp_send_json_success([
                        'message'       => 'Image replaced successfully.',
                        'new_file_url'  => wp_get_attachment_url($image_id),
                        'original_size' => size_format($original_size),
                        'new_size'      => size_format($new_size),
                        'saved_space'   => size_format($original_size - $new_size),
                    ]);
                } else {
                    // Save the compressed file alongside the original
                    $new_path = pathinfo($image_path, PATHINFO_DIRNAME) . '/' . pathinfo($image_path, PATHINFO_FILENAME) . '-compressed.' . pathinfo($image_path, PATHINFO_EXTENSION);
                    file_put_contents($new_path, $compressed_data);
                    $new_size = filesize($new_path);

                    wp_send_json_success([
                        'message'       => 'Image compressed successfully.',
                        'new_file_url'  => wp_get_attachment_url($image_id),
                        'original_size' => size_format($original_size),
                        'new_size'      => size_format($new_size),
                        'saved_space'   => size_format($original_size - $new_size),
                    ]);
                }
            } catch (Exception $e) {
                wp_send_json_error(['message' => 'Error saving the compressed file: ' . $e->getMessage()]);
            }
        } else {
            wp_send_json_error(['message' => 'Compression failed.']);
        }
    }
}

// Register the AJAX handler
add_action('wp_ajax_compress_image', ['Image_Handler', 'handle_ajax_request']);
