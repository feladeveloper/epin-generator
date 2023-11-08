<?php
// Add a submenu page to display batch details
function epin_batch_details_page() {
    add_submenu_page(
        'epin-management', // Parent menu slug
        'Batch Details', // Page title
        'Batch Details', // Menu title
        'manage_options', // Capability required to access this page
        'epin-batch-details', // Page slug
        'display_batch_details' // Callback function to display the content
    );
}
add_action('admin_menu', 'epin_batch_details_page');

function display_batch_details() {
    global $wpdb;
    $batch_table_name = $wpdb->prefix . 'epin_batches';

    // Retrieve batch data from the batch table
    $batch_data = $wpdb->get_results("SELECT * FROM $batch_table_name");

    // Display the batch details in a table
    echo '<div class="wrap">';
    echo '<h2>Batch Details</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead>
            <tr>
                <th>ID</th>
                <th>Batch ID</th>
                <th>Created By</th>
                <th>Denomination</th>
                <th>Number of Pins</th>
                <th>Status</th>
                <th>Date Created</th>
                <th>Action</th>
            </tr>
          </thead>';
    echo '<tbody>';
    
    foreach ($batch_data as $batch) {
        echo '<tr>';
        echo '<td>' . esc_html($batch->id) . '</td>';
        echo '<td>' . esc_html($batch->batch_id) . '</td>';
        echo '<td>' . esc_html($batch->created_by) . '</td>';
        echo '<td>' . esc_html($batch->denomination) . '</td>';
        echo '<td>' . esc_html($batch->number_of_pins) . '</td>';
        echo '<td>' . esc_html($batch->status) . '</td>';
        echo '<td>' . esc_html($batch->date_created) . '</td>';
        echo '<td>';
        $status_class = ($batch->status === 'active') ? 'active' : 'inactive';
        echo '<button class="toggle-button" data-batch-id="' . esc_attr($batch->batch_id) . '">' . $status_class . '</button>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.toggle-button').click(function() {
            var batchId = $(this).data('batch-id');
            var statusCell = $('.status-' + batchId);
            
            if (statusCell.text() === 'active') {
                statusCell.text('inactive');
                $(this).text('inactive');
            } else {
                statusCell.text('active');
                $(this).text('active');
            }
        });
    });
    </script>

<?php
}
