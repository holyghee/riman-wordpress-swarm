<?php
/**
 * Plugin Name:       Riman Test Plugin
 * Description:       Einfaches Test-Plugin zur Verifikation von Deployments (Shortcode: [riman_test_plugin]).
 * Version:           0.1.0
 * Author:            RIMAN / Holger Brandt
 * License:           GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

// Shortcode: [riman_test_plugin]
add_shortcode('riman_test_plugin', function () {
    $ts = gmdate('Y-m-d H:i:s');
    return '<div class="riman-test-plugin" style="padding:.75rem 1rem;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;font:14px/1.4 system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,\"Noto Sans\",\"Helvetica Neue\",Helvetica,Arial,sans-serif;color:#0f172a">'
        . '<strong>Riman Test Plugin:</strong> OK — ' . esc_html($ts) . ' UTC'
        . '</div>';
});

// Kleiner Healthcheck in Tools → Website-Zustand (als Debug-Info)
add_filter('debug_information', function ($info) {
    $info['riman_test_plugin'] = [
        'label'  => 'Riman Test Plugin',
        'fields' => [
            'status' => [
                'label' => 'Status',
                'value' => 'aktiv',
            ],
            'version' => [
                'label' => 'Version',
                'value' => '0.1.0',
            ],
        ],
    ];
    return $info;
});

