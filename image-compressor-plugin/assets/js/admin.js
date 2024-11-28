jQuery(document).ready(function ($) {
    const showLoading = () => $('#loading-spinner').show();
    const hideLoading = () => $('#loading-spinner').hide();

    /**
     * Handle compression and generate preview.
     */
    $(document).on('click', '.compress-btn', function () {
        const imageId = $(this).data('id');
        const quality = $(`#quality-slider-${imageId}`).val();

        if (quality < 10 || quality > 100) {
            alert('Quality must be between 10 and 100.');
            return;
        }

        showLoading();
        $.post(imageCompressor.ajaxUrl, {
            action: 'compress_image',
            security: imageCompressor.nonce,
            image_id: imageId,
            quality: quality,
            replace: false // Only compress, no replacement
        }, function (response) {
            hideLoading();
            if (response.success) {
                const newSize = size_format(response.data.new_size);
                const savedSpace = size_format(response.data.saved_space);
                const newFileUrl = response.data.new_file_url;

                // Update new size in the table
                $(`button[data-id=${imageId}]`).closest('tr').find('.new-size').text(newSize);

                // Update compressed image preview
                const previewCell = $(`.post-compression-preview-${imageId}`);
                previewCell.html(`
                    <a href="${newFileUrl}" data-lightbox="post-compression-${imageId}" data-title="Compressed Image">
                        <img src="${newFileUrl}" alt="Compressed Image" style="max-width: 100px;">
                    </a>
                `);

                alert(`Image compressed successfully! Saved space: ${savedSpace}`);
            } else {
                alert('Error: ' + response.data.message);
            }
        }).fail(function () {
            hideLoading();
            alert('Error: Unable to process the request. Please try again.');
        });
    });

    /**
     * Replace original image with compressed version.
     */
    $(document).on('click', '.replace-btn', function () {
        const imageId = $(this).data('id');
        const quality = $(`#quality-slider-${imageId}`).val();

        if (quality < 10 || quality > 100) {
            alert('Quality must be between 10 and 100.');
            return;
        }

        if (!confirm('Are you sure you want to replace the original image with the compressed version? This action cannot be undone.')) {
            return;
        }

        showLoading();
        $.post(imageCompressor.ajaxUrl, {
            action: 'compress_image',
            security: imageCompressor.nonce,
            image_id: imageId,
            quality: quality,
            replace: true // Replace original image
        }, function (response) {
            hideLoading();
            if (response.success) {
                const newSize = size_format(response.data.new_size);
                const savedSpace = size_format(response.data.saved_space);

                // Update new size in the table
                $(`button[data-id=${imageId}]`).closest('tr').find('.new-size').text(newSize);

                // Update "Before" preview with the compressed image URL
                const newFileUrl = response.data.new_file_url;
                $(`a[data-lightbox="pre-compression-${imageId}"] img`).attr('src', newFileUrl);
                $(`a[data-lightbox="pre-compression-${imageId}"]`).attr('href', newFileUrl);

                alert(`Original image replaced successfully! Saved space: ${savedSpace}`);
            } else {
                alert('Error: ' + response.data.message);
            }
        }).fail(function () {
            hideLoading();
            alert('Error: Unable to process the request. Please try again.');
        });
    });

    /**
     * Format size in human-readable format.
     */
    const size_format = (bytes) => {
        if (bytes >= 1073741824) {
            return (bytes / 1073741824).toFixed(2) + ' GB';
        } else if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(2) + ' MB';
        } else if (bytes >= 1024) {
            return (bytes / 1024).toFixed(2) + ' KB';
        } else {
            return bytes + ' bytes';
        }
    };
});
