<?php
/*
Plugin Name: Solar Power Data
Description: Fetches data from Home Assistant and stores it in the WordPress or external database. Displays solar power data with customizable graphs using Chart.js.
Version: 2.1
Author: Elias Haisch
Text Domain: solarpower-data
*/

// Verhindern des direkten Zugriffs auf die Datei
if (!defined('ABSPATH')) {
    exit;
}

// Einbinden der Admin-Einstellungen
require_once plugin_dir_path(__FILE__) . 'solarpower-data-admin.php';

// Laden der Textdomain für Übersetzungen
add_action('plugins_loaded', 'solarpower_load_textdomain');
function solarpower_load_textdomain() {
    load_plugin_textdomain('solarpower-data', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// Datenbankverbindung initialisieren
global $solarpower_db;
$solarpower_db = null;

// Datenbank initialisieren
function solarpower_init_database() {
    global $wpdb, $solarpower_db;

    $options = get_option('solarpower_options');
    if (isset($options['use_external_db']) && $options['use_external_db']) {
        // Externe Datenbankverbindung herstellen
        $db_host = $options['external_db_host'];
        $db_name = $options['external_db_name'];
        $db_user = $options['external_db_user'];
        $db_password = $options['external_db_password'];
        $db_charset = $wpdb->charset;

        $solarpower_db = new wpdb($db_user, $db_password, $db_name, $db_host);
        if ($solarpower_db->has_cap('collation')) {
            $solarpower_db->set_charset($solarpower_db->dbh, $db_charset);
        }
    } else {
        // WordPress-Datenbank verwenden
        $solarpower_db = $wpdb;
    }

    // Datenbanktabelle erstellen
    solarpower_create_table();
}
add_action('init', 'solarpower_init_database');

// Datenbanktabelle erstellen
function solarpower_create_table() {
    global $solarpower_db;
    $table_name = $solarpower_db->prefix . 'solarpower_data';
    $charset_collate = $solarpower_db->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        timestamp INT(11) UNSIGNED NOT NULL,
        production_now FLOAT NOT NULL,
        production_watt FLOAT NOT NULL,
        sold_watt FLOAT NOT NULL,
        PRIMARY KEY (timestamp),
        INDEX (timestamp)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Datenabruf-Ereignis planen
function solarpower_schedule_event() {
    $options = get_option('solarpower_options');
    $interval = isset($options['data_fetch_interval']) ? $options['data_fetch_interval'] : 'hourly';

    if (!wp_next_scheduled('solarpower_fetch_data_event')) {
        wp_schedule_event(time(), $interval, 'solarpower_fetch_data_event');
    } else {
        // Event reschedulen, falls sich das Intervall geändert hat
        $timestamp = wp_next_scheduled('solarpower_fetch_data_event');
        wp_unschedule_event($timestamp, 'solarpower_fetch_data_event');
        wp_schedule_event(time(), $interval, 'solarpower_fetch_data_event');
    }
}
add_action('init', 'solarpower_schedule_event');

// Intervall zu Cron hinzufügen
function solarpower_add_cron_intervals($schedules) {
    $options = get_option('solarpower_options');
    if (isset($options['custom_intervals']) && is_array($options['custom_intervals'])) {
        foreach ($options['custom_intervals'] as $interval) {
            $schedules[$interval['name']] = array(
                'interval' => intval($interval['seconds']),
                'display' => esc_html($interval['display'])
            );
        }
    }
    // Beispiel für ein minütliches Intervall
    $schedules['every_minute'] = array(
        'interval' => 60,
        'display' => __('Every Minute', 'solarpower-data')
    );
    return $schedules;
}
add_filter('cron_schedules', 'solarpower_add_cron_intervals');

// An das geplante Ereignis anhängen
add_action('solarpower_fetch_data_event', 'solarpower_fetch_and_store_data');

function solarpower_fetch_and_store_data() {
    global $solarpower_db;
    $table_name = $solarpower_db->prefix . 'solarpower_data';

    $options = get_option('solarpower_options');
    $token = sanitize_text_field($options['solarpower_token']);
    $productionNowURL = esc_url_raw($options['solarpower_production_now_url']);
    $productionWattURL = esc_url_raw($options['solarpower_production_watt_url']);
    $soldWattURL = esc_url_raw($options['solarpower_sold_watt_url']);

    // Daten abrufen
    $productionNow = solarpower_fetch_data($productionNowURL, $token);
    $productionWatt = solarpower_fetch_data($productionWattURL, $token);
    $soldWatt = solarpower_fetch_data($soldWattURL, $token);

    // Daten validieren
    if ($productionNow === null || $productionWatt === null || $soldWatt === null) {
        solarpower_update_status(__('Invalid data fetched from API.', 'solarpower-data'));
        return;
    }

    $timestamp = time();

    // Prüfen, ob der Datensatz bereits vorhanden ist, um Duplikate zu vermeiden
    $existing = $solarpower_db->get_var($solarpower_db->prepare("SELECT COUNT(*) FROM $table_name WHERE timestamp = %d", $timestamp));

    if ($existing == 0) {
        // Daten in die Datenbank einfügen
        $success = $solarpower_db->insert(
            $table_name,
            array(
                'timestamp' => $timestamp,
                'production_now' => $productionNow,
                'production_watt' => $productionWatt,
                'sold_watt' => $soldWatt
            ),
            array(
                '%d',
                '%f',
                '%f',
                '%f'
            )
        );

        if ($success !== false) {
            solarpower_update_status(__('Data fetched successfully at ', 'solarpower-data') . date('Y-m-d H:i:s'));
        } else {
            solarpower_update_status(__('Failed to insert data into database at ', 'solarpower-data') . date('Y-m-d H:i:s'));
        }
    } else {
        solarpower_update_status(__('Data already exists for timestamp ', 'solarpower-data') . $timestamp);
    }

    // Alte Daten löschen, falls aktiviert
    if (isset($options['enable_data_cleanup']) && $options['enable_data_cleanup']) {
        $days_to_keep = isset($options['data_retention_days']) ? intval($options['data_retention_days']) : 30;
        $threshold = time() - ($days_to_keep * DAY_IN_SECONDS);
        $solarpower_db->query($solarpower_db->prepare("DELETE FROM $table_name WHERE timestamp < %d", $threshold));
    }

    // Fehlende Daten interpolieren
    solarpower_interpolate_missing_data();
}

function solarpower_fetch_data($url, $token) {
    $response = wp_remote_get($url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ),
        'timeout' => 10,
        'sslverify' => true,
    ));

    if (is_wp_error($response)) {
        error_log('Solar Power Data: API request failed. ' . $response->get_error_message());
        return null;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        error_log('Solar Power Data: API request returned status code ' . $status_code);
        return null;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Solar Power Data: JSON decode error: ' . json_last_error_msg());
        return null;
    }

    return isset($data->state) ? floatval($data->state) : null;
}

// Funktion zum Aktualisieren des Status
function solarpower_update_status($status) {
    update_option('solarpower_last_status', $status);
}

function solarpower_get_last_status() {
    return get_option('solarpower_last_status', __('No data fetched yet.', 'solarpower-data'));
}

// Fehlende Daten interpolieren
function solarpower_interpolate_missing_data() {
    global $solarpower_db;
    $table_name = $solarpower_db->prefix . 'solarpower_data';

    $options = get_option('solarpower_options');
    $interval = wp_get_schedules()[$options['data_fetch_interval']]['interval'];

    // Alle Zeitstempel abrufen
    $results = $solarpower_db->get_results("SELECT timestamp FROM $table_name ORDER BY timestamp ASC", ARRAY_N);

    if (empty($results)) {
        return;
    }

    $timestamps = array_column($results, 0);
    $expected_timestamps = range($timestamps[0], end($timestamps), $interval);

    $missing_timestamps = array_diff($expected_timestamps, $timestamps);

    foreach ($missing_timestamps as $missing_timestamp) {
        // Durchschnittswerte berechnen
        $before = $solarpower_db->get_row($solarpower_db->prepare("SELECT * FROM $table_name WHERE timestamp < %d ORDER BY timestamp DESC LIMIT 1", $missing_timestamp));
        $after = $solarpower_db->get_row($solarpower_db->prepare("SELECT * FROM $table_name WHERE timestamp > %d ORDER BY timestamp ASC LIMIT 1", $missing_timestamp));

        if ($before && $after) {
            $production_now = ($before->production_now + $after->production_now) / 2;
            $production_watt = ($before->production_watt + $after->production_watt) / 2;
            $sold_watt = ($before->sold_watt + $after->sold_watt) / 2;

            // Fehlenden Datensatz einfügen
            $solarpower_db->insert(
                $table_name,
                array(
                    'timestamp' => $missing_timestamp,
                    'production_now' => $production_now,
                    'production_watt' => $production_watt,
                    'sold_watt' => $sold_watt
                ),
                array(
                    '%d',
                    '%f',
                    '%f',
                    '%f'
                )
            );
        }
    }
}

// Shortcode zum Anzeigen der Daten hinzufügen
add_shortcode('display_solarpower_data', 'solarpower_display_data');

function solarpower_display_data($atts) {
    global $solarpower_db;
    $table_name = $solarpower_db->prefix . 'solarpower_data';

    // Attribute verarbeiten
    $atts = shortcode_atts(
        array(
            'days' => 7, // Standardmäßig die letzten 7 Tage anzeigen
            'chart_type' => 'line', // Standarddiagrammtyp
            'show_production_now' => true,
            'show_production_watt' => true,
            'show_sold_watt' => true,
        ),
        $atts,
        'display_solarpower_data'
    );

    $days = intval($atts['days']);
    $chart_type = sanitize_text_field($atts['chart_type']);
    $from_timestamp = time() - ($days * DAY_IN_SECONDS);

    // Daten für den angegebenen Zeitraum abrufen
    $results = $solarpower_db->get_results(
        $solarpower_db->prepare(
            "SELECT * FROM $table_name WHERE timestamp >= %d ORDER BY timestamp ASC",
            $from_timestamp
        )
    );

    if (empty($results)) {
        return __('Keine Daten verfügbar.', 'solarpower-data');
    }

    // Daten für die Diagramme vorbereiten
    $timestamps = array();
    $productionNow = array();
    $productionWatt = array();
    $soldWatt = array();

    foreach ($results as $row) {
        $timestamps[] = date('Y-m-d H:i:s', $row->timestamp);
        $productionNow[] = round($row->production_now, 2);
        $productionWatt[] = round($row->production_watt, 2);
        $soldWatt[] = round($row->sold_watt, 2);
    }

    // Chart.js und unser Skript einbinden
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true);
    wp_enqueue_script('chartjs-adapter-date-fns', 'https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@2', array('chartjs'), null, true);
    wp_enqueue_script('solarpower-data-scripts', plugin_dir_url(__FILE__) . 'solarpower-data-scripts.js', array('jquery', 'chartjs', 'chartjs-adapter-date-fns'), null, true);

    // Daten an das Skript übergeben
    $chart_data = array(
        'timestamps' => $timestamps,
        'productionNow' => $productionNow,
        'productionWatt' => $productionWatt,
        'soldWatt' => $soldWatt,
        'chartType' => $chart_type,
        'showProductionNow' => filter_var($atts['show_production_now'], FILTER_VALIDATE_BOOLEAN),
        'showProductionWatt' => filter_var($atts['show_production_watt'], FILTER_VALIDATE_BOOLEAN),
        'showSoldWatt' => filter_var($atts['show_sold_watt'], FILTER_VALIDATE_BOOLEAN),
    );
    wp_localize_script('solarpower-data-scripts', 'SolarPowerData', $chart_data);

    // Canvas-Elemente für die Diagramme ausgeben
    $output = '<div class="solarpower-charts">';
    $output .= '<canvas id="solarpowerChart" width="400" height="200"></canvas>';
    $output .= '</div>';

    return $output;
}

// Geplantes Ereignis beim Deaktivieren des Plugins entfernen
register_deactivation_hook(__FILE__, 'solarpower_deactivate');
function solarpower_deactivate() {
    $timestamp = wp_next_scheduled('solarpower_fetch_data_event');
    wp_unschedule_event($timestamp, 'solarpower_fetch_data_event');
}

// AJAX-Handler für Test-Button
add_action('wp_ajax_solarpower_test_connection', 'solarpower_test_connection');
function solarpower_test_connection() {
    check_ajax_referer('solarpower_ajax_nonce', 'nonce');

    $options = get_option('solarpower_options');
    $token = sanitize_text_field($options['solarpower_token']);
    $test_url = esc_url_raw($options['solarpower_production_now_url']);

    $response = wp_remote_get($test_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ),
        'timeout' => 10,
        'sslverify' => true,
    ));

    if (is_wp_error($response)) {
        echo __('Connection failed: ', 'solarpower-data') . $response->get_error_message();
    } else {
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code == 200) {
            echo __('Connection successful!', 'solarpower-data');
        } else {
            echo __('Connection failed with status code: ', 'solarpower-data') . $status_code;
        }
    }

    wp_die();
}
