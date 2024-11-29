jQuery(document).ready(function ($) {
    const showLoading = () => $('#loading-spinner').show();
    const hideLoading = () => $('#loading-spinner').hide();

    /**
     * Prevent duplicate dashboard rendering.
     */
    if ($('.image-compressor-dashboard').length > 1) {
        $('.image-compressor-dashboard').slice(1).remove();
    }

    /**
     * Update the top bar with total size and saved space.
     */
    const updateTopBar = () => {
        let totalSize = 0;
        let savedSpace = 0;

        $('#image-table tbody tr').each(function () {
            const currentSize = parseInt($(this).data('size'), 10) || 0;
            const newSize = parseInt($(this).find('.new-size').text().replace(/[^0-9]/g, '') || 0, 10);

            totalSize += currentSize;
            if (newSize) {
                savedSpace += currentSize - newSize;
            }
        });

        $('#total-size').text(`Total Size: ${size_format(totalSize)}`);
        $('#saved-space').text(`Saved Space: ${size_format(savedSpace)}`);
    };

    /**
     * Format file size into human-readable format.
     */
    const size_format = (bytes) => {
        if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
        return bytes + ' bytes';
    };

    /**
     * Initialize DataTables.
     */
    const initializeTable = () => {
        if ($.fn.DataTable.isDataTable('#image-table')) {
            $('#image-table').DataTable().destroy();
        }
        $('#image-table').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            responsive: true,
            columnDefs: [{ orderable: false, targets: [0, 1, 2, 6] }],
        });
    };

    // Initialize DataTables on page load
    initializeTable();
    updateTopBar();

    /**
     * Reinitialize DataTables and recalculate the top bar after updates.
     */
    const refreshUI = () => {
        updateTopBar();
        initializeTable();
    };

    /**
     * Apply size filters.
     */
    $('#apply-filter').on('click', function () {
        const filterValue = $('#size-filter').val();
        $('#image-table tbody tr').each(function () {
            const size = parseInt($(this).data('size'), 10) || 0;
            if (
                (filterValue === 'small' && size >= 100 * 1024) ||
                (filterValue === 'medium' && (size < 100 * 1024 || size > 1024 * 1024)) ||
                (filterValue === 'large' && size <= 1024 * 1024)
            ) {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
        refreshUI();
    });

    /**
     * Master slider to set all quality sliders.
     */
    $('#master-quality-slider').on('input', function () {
        const masterValue = $(this).val();
        $('#master-quality-value').text(masterValue);
        $('.quality-slider').val(masterValue);
    });

    /**
     * Handle bulk compress and replace buttons.
     */
    $('#bulk-compress-btn').on('click', function () {
        const selectedImages = $('.image-select:checked');
        const imageIds = selectedImages.map(function () {
            return $(this).data('id');
        }).get();

        compressImages(imageIds, false); // Compress only
    });

    $('#bulk-replace-btn').on('click', function () {
        const selectedImages = $('.image-select:checked');
        const imageIds = selectedImages.map(function () {
            return $(this).data('id');
        }).get();

        compressImages(imageIds, true); // Replace original images
    });

    /**
     * Compress selected images one by one.
     */
    const compressImages = (imageIds, replace) => {
        if (imageIds.length === 0) {
            alert(replace ? 'Bulk replacement completed!' : 'Bulk compression completed!');
            refreshUI();
            return;
        }

        const imageId = imageIds.shift();
        const quality = $('#master-quality-slider').val();

        showLoading();
        $.post(imageCompressor.ajaxUrl, {
            action: 'compress_image',
            security: imageCompressor.nonce,
            image_id: imageId,
            quality: quality,
            replace: replace,
        }, function (response) {
            hideLoading();
            if (response.success) {
                const newSize = size_format(response.data.new_size);
                const savedSpace = size_format(response.data.saved_space);

                const row = $(`button[data-id=${imageId}]`).closest('tr');
                row.find('.new-size').text(newSize);

                if (replace) {
                    // Update the "before" preview with the new image
                    const previewCell = row.find('a[data-lightbox^="pre-compression-"]');
                    previewCell.attr('href', response.data.new_file_url);
                    previewCell.find('img').attr('src', response.data.new_file_url);
                } else {
                    // Update the "after" preview
                    const previewCell = row.find(`.post-compression-preview-${imageId}`);
                    previewCell.html(`
                        <a href="${response.data.new_file_url}" data-lightbox="post-compression-${imageId}" data-title="Compressed Image">
                            <img src="${response.data.new_file_url}" alt="Compressed Image" style="max-width: 100px;">
                        </a>
                    `);
                }

                compressImages(imageIds, replace);
            } else {
                alert(`Error: ${response.data.message}`);
            }
        });
    };

    /**
     * Select all checkboxes when the "Select All" checkbox is toggled.
     */
    $('#select-all').on('change', function () {
        const isChecked = $(this).is(':checked');
        $('.image-select').prop('checked', isChecked);
        toggleBulkButtons();
    });

    /**
     * Enable or disable bulk buttons based on selection.
     */
    const toggleBulkButtons = () => {
        const hasSelection = $('.image-select:checked').length > 0;
        $('#bulk-compress-btn, #bulk-replace-btn').prop('disabled', !hasSelection);
    };

    // Monitor individual checkbox changes
    $(document).on('change', '.image-select', function () {
        toggleBulkButtons();
    });

    // Ensure all UI elements are refreshed on page load
    refreshUI();
});
