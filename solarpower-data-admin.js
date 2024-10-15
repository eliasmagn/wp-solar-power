jQuery(document).ready(function($) {
    $('#solarpower-test-connection').on('click', function(e) {
        e.preventDefault();
        var data = {
            action: 'solarpower_test_connection',
            nonce: SolarPowerAjax.nonce
        };
        $('#solarpower-test-result').html('<p>' + 'Testing connection...' + '</p>');

        $.post(SolarPowerAjax.ajax_url, data, function(response) {
            $('#solarpower-test-result').html('<p>' + response + '</p>');
        });
    });

    // Externe Datenbankfelder anzeigen oder ausblenden
    function toggleExternalDbFields() {
        if ($('#use_external_db').is(':checked')) {
            $('.external-db-field').closest('tr').show();
        } else {
            $('.external-db-field').closest('tr').hide();
        }
    }

    toggleExternalDbFields();

    $('#use_external_db').change(function() {
        toggleExternalDbFields();
    });
});
