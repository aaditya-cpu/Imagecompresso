<div class="image-compressor-dashboard">
    <h1>Image Compressor</h1>
    <p>Select images to compress. Adjust the quality slider and review changes before replacing the original image.</p>
    <div id="loading-spinner" style="display:none;">Processing...</div>

    <table id="image-table">
        <thead>
            <tr>
                <th>Preview (Before)</th>
                <th>Preview (After)</th>
                <th>File Name</th>
                <th>Current Size</th>
                <th>New Size</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $args = [
                'post_type'      => 'attachment',
                'post_mime_type' => ['image/jpeg', 'image/png', 'image/gif'],
                'post_status'    => 'inherit',
                'posts_per_page' => -1,
            ];

            $images = new WP_Query($args);

            if ($images->have_posts()) :
                while ($images->have_posts()) : $images->the_post();
                    $image_id = get_the_ID();
                    $image_url = wp_get_attachment_url($image_id);
                    $image_path = get_attached_file($image_id);
                    $image_size = file_exists($image_path) ? filesize($image_path) : 0;

                    // Sanitize values to prevent XSS
                    $image_url_esc = esc_url($image_url);
                    $image_title_esc = esc_html(get_the_title());
                    $image_size_human = size_format($image_size);
            ?>
                    <tr>
                        <!-- Original Image Preview -->
                        <td>
                            <a href="<?php echo $image_url_esc; ?>" data-lightbox="pre-compression-<?php echo $image_id; ?>" data-title="Original Image">
                                <img src="<?php echo $image_url_esc; ?>" alt="Original Image" style="max-width: 100px;">
                            </a>
                        </td>
                        <!-- Placeholder for Compressed Image Preview -->
                        <td class="post-compression-preview-<?php echo $image_id; ?>">
                            <span>No compressed image yet.</span>
                        </td>
                        <td><?php echo $image_title_esc; ?></td>
                        <td><?php echo $image_size_human; ?></td>
                        <td class="new-size">-</td>
                        <td>
                            <div class="slider-container">
                                <span class="slider-label">-</span>
                                <input type="range" id="quality-slider-<?php echo $image_id; ?>" min="10" max="100" value="80" class="quality-slider">
                                <span class="slider-label">+</span>
                            </div>
                            <button class="compress-btn" data-id="<?php echo $image_id; ?>">Compress</button>
                            <button class="replace-btn" data-id="<?php echo $image_id; ?>">Replace Original</button>
                        </td>
                    </tr>
            <?php
                endwhile;
            else :
            ?>
                <tr>
                    <td colspan="6">No images found in the media library.</td>
                </tr>
            <?php
            endif;
            wp_reset_postdata();
            ?>
        </tbody>
    </table>
</div>
