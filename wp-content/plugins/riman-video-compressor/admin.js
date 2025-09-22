/**
 * RIMAN Video Compressor Admin Interface
 */

jQuery(document).ready(function($) {

    // Button Click Handler
    $('#compress-existing-videos').on('click', function(e) {
        e.preventDefault();

        const button = $(this);
        const progressDiv = $('#compression-progress');
        const progressText = $('#progress-text');
        const progressFill = $('.progress-fill');

        // Button Status
        button.prop('disabled', true).text('Komprimierung läuft...');
        progressDiv.show();
        progressText.text('Starte Komprimierung aller Videos...');
        progressFill.css('width', '10%');

        // AJAX Request
        $.ajax({
            url: rimanVideoCompressor.ajax_url,
            type: 'POST',
            data: {
                action: 'compress_video_manual',
                nonce: rimanVideoCompressor.nonce
            },
            success: function(response) {
                if (response.success) {
                    progressFill.css('width', '100%');
                    progressText.html(
                        `✅ <strong>Komprimierung erfolgreich!</strong><br>` +
                        `${response.data.processed} Videos verarbeitet<br>` +
                        `${response.data.message}`
                    );

                    // Auto-Hide nach 5 Sekunden
                    setTimeout(function() {
                        progressDiv.fadeOut();
                        button.prop('disabled', false).text('Alle Videos komprimieren');
                    }, 5000);

                } else {
                    progressText.html(
                        `❌ <strong>Fehler:</strong><br>` +
                        `${response.data || 'Unbekannter Fehler'}`
                    );
                    progressFill.css('width', '100%').css('background-color', '#dc3232');
                }
            },
            error: function(xhr, status, error) {
                progressText.html(
                    `❌ <strong>Verbindungsfehler:</strong><br>` +
                    `${error || status}<br>` +
                    `Bitte Browser-Konsole überprüfen`
                );
                progressFill.css('width', '100%').css('background-color', '#dc3232');
                console.error('RIMAN Video Compressor AJAX Error:', xhr, status, error);
            },
            complete: function() {
                // Button nach Fehler wieder aktivieren
                setTimeout(function() {
                    if (button.prop('disabled')) {
                        button.prop('disabled', false).text('Alle Videos komprimieren');
                    }
                }, 3000);
            }
        });
    });

    // Progress Bar CSS (falls nicht vorhanden)
    if ($('.progress-bar').length && !$('head').find('style[data-riman-progress]').length) {
        $('head').append(`
            <style data-riman-progress>
                .progress-bar {
                    width: 100%;
                    height: 20px;
                    background-color: #f1f1f1;
                    border-radius: 10px;
                    overflow: hidden;
                    margin: 10px 0;
                }
                .progress-fill {
                    height: 100%;
                    background-color: #0073aa;
                    transition: width 0.3s ease;
                    border-radius: 10px;
                }
                #compression-progress {
                    margin: 20px 0;
                    padding: 20px;
                    background: #f9f9f9;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                }
                #progress-text {
                    font-size: 14px;
                    margin-top: 10px;
                }
            </style>
        `);
    }

    // Debug-Informationen
    console.log('🔧 RIMAN Video Compressor Admin JS geladen');
    console.log('📋 AJAX URL:', rimanVideoCompressor.ajax_url);
    console.log('🔑 Nonce:', rimanVideoCompressor.nonce.substring(0, 8) + '...');

});