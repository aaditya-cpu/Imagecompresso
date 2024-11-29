<div class="image-compressor-dashboard">
    <h1>Image Compressor</h1>
    <p>Select images to compress. Adjust the quality slider and review changes before replacing the original image.</p>

    <!-- Top Bar -->
    <div class="top-bar">
        <span id="total-size">Total Size: Calculating...</span>
        <span id="saved-space">Saved Space: 0 KB</span>
    </div>

    <div id="loading-spinner" style="display:none;">Processing...</div>

    <!-- Master Slider for Bulk Compression -->
    <div class="master-slider-container">
        <label for="master-quality-slider">Set Compression Quality for All:</label>
        <input type="range" id="master-quality-slider" min="10" max="100" value="80">
        <span id="master-quality-value">80</span>
    </div>

    <!-- Size Filter -->
    <div class="filter-container">
        <label for="size-filter">Filter by Size:</label>
        <select id="size-filter">
            <option value="all">All Sizes</option>
            <option value="small">Small (< 100 KB)</option>
            <option value="medium">Medium (100 KB - 1 MB)</option>
            <option value="large">Large (> 1 MB)</option>
        </select>
        <button id="apply-filter">Apply Filter</button>
    </div>

    <!-- Bulk Compress and Replace Buttons -->
    <div class="bulk-action-container">
        <button id="bulk-compress-btn" disabled>Bulk Compress</button>
        <button id="bulk-replace-btn" disabled>Bulk Replace</button>
    </div>

    <!-- Table Wrapper -->
    <div class="table-wrapper">
        <table id="image-table" class="display">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all"></th>
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
                // Query to fetch media library images
                $args = [
                    'post_type'      => 'attachment',
                    'post_mime_type' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                    'post_status'    => 'inherit',
                    'posts_per_page' => -1,
                ];

                $images = new WP_Query($args);

                // Check if images exist
                if ($images->have_posts()) :
                    while ($images->have_posts()) : $images->the_post();
                        $image_id = get_the_ID();
                        $image_url = wp_get_attachment_url($image_id);
                        $image_path = get_attached_file($image_id);
                        $image_size = file_exists($image_path) ? filesize($image_path) : 0;

                        // Escape values for security
                        $image_url_esc = esc_url($image_url);
                        $image_title_esc = esc_html(get_the_title());
                        $image_size_human = size_format($image_size);
                ?>
                        <tr data-size="<?php echo esc_attr($image_size); ?>">
                            <td>
                                <input type="checkbox" class="image-select" data-id="<?php echo esc_attr($image_id); ?>">
                            </td>
                            <td>
                                <a href="<?php echo $image_url_esc; ?>" data-lightbox="pre-compression-<?php echo esc_attr($image_id); ?>" data-title="Original Image">
                                    <img src="<?php echo $image_url_esc; ?>" alt="Original Image" style="max-width: 100px;">
                                </a>
                            </td>
                            <td class="post-compression-preview-<?php echo esc_attr($image_id); ?>">
                                <span>No compressed image yet.</span>
                            </td>
                            <td><?php echo $image_title_esc; ?></td>
                            <td class="current-size"><?php echo $image_size_human; ?></td>
                            <td class="new-size">-</td>
                            <td>
                                <div class="slider-container">
                                    <span class="slider-label">-</span>
                                    <input type="range" id="quality-slider-<?php echo esc_attr($image_id); ?>" min="10" max="100" value="80" class="quality-slider">
                                    <span class="slider-label">+</span>
                                </div>
                                <button class="compress-btn" data-id="<?php echo esc_attr($image_id); ?>">Compress</button>
                                <button class="replace-btn" data-id="<?php echo esc_attr($image_id); ?>">Replace Original</button>
                            </td>
                        </tr>
                <?php
                    endwhile;
                else :
                ?>
                    <tr>
                        <td colspan="7">No images found in the media library.</td>
                    </tr>
                <?php
                endif;
                wp_reset_postdata();
                ?>
            </tbody>
        </table>
    </div>
</div>
