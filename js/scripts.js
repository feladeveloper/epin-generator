jQuery(document).ready(function($) {
    var submitButton = $('#publish'); // Replace with your actual button's selector if different

    // Define a function that toggles the visibility and requirement of the hero product field
    function toggleHeroProductField() {
        var productTag = $('#product-tag').val();
        
        // Show the hero product select only if 'bait' is selected
        if (productTag === 'bait') {
            $('#associated_hero_id').closest('.form-field').show();
            $('#associated_hero_id').prop('required', true);
            submitButton.prop('disabled', true);
        } else {
            // If 'solo' or anything else, no hero product is needed
            $('#associated_hero_id').closest('.form-field').hide();
            $('#associated_hero_id').prop('required', false);
            // Only enable the submit button if a tag other than 'bait' is selected or if it's 'solo'
            submitButton.prop('disabled', productTag === '' || productTag === 'bait');
        }
    }

    // Define a function to toggle the disabled state of the submit button
    function toggleSubmitButtonState() {
        var productTag = $('#product-tag').val();
        var shouldDisable = productTag === 'bait' && !$('#associated_hero_id').val();
        submitButton.prop('disabled', shouldDisable || productTag === '');
    }

    // Event handler for when the product tag dropdown changes
    $('#product-tag').change(function() {
        toggleHeroProductField();
        toggleSubmitButtonState();
        
        // AJAX logic remains the same as your original, only executed if 'bait' is selected
    });

    // Event handler for when the hero product dropdown changes
    $('#associated_hero_id').change(toggleSubmitButtonState);

    // Call the function to set the initial state on page load
    toggleHeroProductField();
    toggleSubmitButtonState();
});