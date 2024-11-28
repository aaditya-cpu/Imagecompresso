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
        // Validate image path
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
                    imagepng($image, round($quality / 10)); // PNG compression level (0-9)
                    break;

                case 'image/gif':
                    $image = imagecreatefromgif($image_path);
                    ob_start();
                    imagegif($image);
                    break;

                default:
                    return false; // Unsupported format
            }
        } catch (Exception $e) {
            return false; // Handle unexpected errors
        }

        $compressed_data = ob_get_clean();
        imagedestroy($image);

        return $compressed_data;
    }

    /**
     * Handle AJAX request for image compression.
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
            wp_send_json_error(['message' => 'File does not exist or is unreadable.']);
            return;
        }

        if (!is_writable($image_path) && $replace) {
            wp_send_json_error(['message' => 'File is not writable. Check permissions.']);
            return;
        }

        $original_size = filesize($image_path);
        $compressed_data = self::compress_image($image_path, $quality);

        if ($compressed_data) {
            if ($replace) {
                // Replace the original file
                try {
                    file_put_contents($image_path, $compressed_data);
                    clearstatcache(true, $image_path);
                    $new_size = filesize($image_path);

                    // Update attachment metadata
                    wp_update_attachment_metadata($image_id, wp_generate_attachment_metadata($image_id, $image_path));

                    wp_send_json_success([
                        'message'       => 'File replaced successfully.',
                        'original_size' => size_format($original_size),
                        'new_size'      => size_format($new_size),
                        'saved_space'   => size_format($original_size - $new_size),
                    ]);
                } catch (Exception $e) {
                    wp_send_json_error(['message' => 'Failed to replace the original file.']);
                }
            } else {
                // Save the compressed file alongside the original
                try {
                    $new_path = pathinfo($image_path, PATHINFO_DIRNAME) . '/' . pathinfo($image_path, PATHINFO_FILENAME) . '-compressed.' . pathinfo($image_path, PATHINFO_EXTENSION);
                    file_put_contents($new_path, $compressed_data);
                    $new_size = filesize($new_path);

                    wp_send_json_success([
                        'message'       => 'File compressed successfully.',
                        'original_size' => size_format($original_size),
                        'new_size'      => size_format($new_size),
                        'saved_space'   => size_format($original_size - $new_size),
                        'new_file_url'  => wp_get_attachment_url($image_id),
                    ]);
                } catch (Exception $e) {
                    wp_send_json_error(['message' => 'Failed to save the compressed file.']);
                }
            }
        } else {
            wp_send_json_error(['message' => 'Compression failed. Unsupported format or processing error.']);
        }
    }
}

// Register AJAX handler
add_action('wp_ajax_compress_image', ['Image_Handler', 'handle_ajax_request']);
