<?php
// Sicherstellen, dass diese Datei von einer übergeordneten Datei eingebunden wird
if (!defined('ABSPATH')) {
    exit;
}

// Einstellungsseite hinzufügen
add_action('admin_menu', 'solarpower_add_admin_menu');
add_action('admin_init', 'solarpower_settings_init');

function solarpower_add_admin_menu() {
    add_options_page(
        __('Solar Power Data', 'solarpower-data'),
        __('Solar Power Data', 'solarpower-data'),
        'manage_options',
        'solarpower_data',
        'solarpower_options_page'
    );
}

function solarpower_settings_init() {
    register_setting('solarpower_settings', 'solarpower_options', 'solarpower_options_validate');

    add_settings_section(
        'solarpower_settings_section',
        __('Settings', 'solarpower-data'),
        'solarpower_settings_section_callback',
        'solarpower_settings'
    );

    add_settings_field(
        'solarpower_token',
        __('API Token', 'solarpower-data'),
        'solarpower_token_render',
        'solarpower_settings',
        'solarpower_settings_section'
    );

    add_settings_field(
        'solarpower_production_now_url',
        __('Production Now URL', 'solarpower-data'),
        'solarpower_production_now_url_render',
        'solarpower_settings',
        'solarpower_settings_section'
    );

    add_settings_field(
        'solarpower_production_watt_url',
        __('Production Watt URL', 'solarpower-data'),
        'solarpower_production_watt_url_render',
        'solarpower_settings',
        'solarpower_settings_section'
    );

    add_settings_field(
        'solarpower_sold_watt_url',
        __('Sold Watt URL', 'solarpower-data'),
        'solarpower_sold_watt_url_render',
        'solarpower_settings',
        'solarpower_settings_section'
    );

    // Neues Feld für Datenabrufintervall
    add_settings_field(
        'data_fetch_interval',
        __('Data Fetch Interval', 'solarpower-data'),
        'solarpower_data_fetch_interval_render',
        'solarpower_settings',
        'solarpower_settings_section'
    );

    // Neues Feld für Datenbereinigung
    add_settings_field(
        'enable_data_cleanup',
        __('Enable Data Cleanup', 'solarpower-data'),
        'solarpower_enable_data_cleanup_render',
        'solarpower_settings',
        'solarpower_settings_section'
    );

    add_settings_field(
        'data_retention_days',
        __('Data Retention Days', 'solarpower-data'),
        'solarpower_data_retention_days_render',
        'solarpower_settings',
        'solarpower_settings_section'
    );

    // Neues Feld für Verwendung externer Datenbank
    add_settings_field(
        'use_external_db',
        __('Use External Database', 'solarpower-data'),
        'solarpower_use_external_db_render',
        'solarpower_settings',
        'solarpower_settings_section'
    );

    // Felder für externe Datenbankverbindung
    add_settings_field(
        'external_db_host',
        __('External DB Host', 'solarpower-data'),
        'solarpower_external_db_host_render',
        'solarpower_settings',
        'solarpower_settings_section'
    );

    add_settings_field(
        'external_db_name',
        __('External DB Name', 'solarpower-data'),
        'solarpower_external_db_name_render',
        'solarpower_settings',
        'solarpower_settings_section'
    );

    add_settings_field(
        'external_db_user',
        __('External DB User', 'solarpower-data'),
        'solarpower_external_db_user_render',
        'solarpower_settings',
        'solarpower_settings_section'
    );

    add_settings_field(
        'external_db_password',
        __('External DB Password', 'solarpower-data'),
        'solarpower_external_db_password_render',
        'solarpower_settings',
        'solarpower_settings_section'
    );
}

function solarpower_options_validate($input) {
    $options = array();

    $options['solarpower_token'] = sanitize_text_field($input['solarpower_token']);
    $options['solarpower_production_now_url'] = esc_url_raw($input['solarpower_production_now_url']);
    $options['solarpower_production_watt_url'] = esc_url_raw($input['solarpower_production_watt_url']);
    $options['solarpower_sold_watt_url'] = esc_url_raw($input['solarpower_sold_watt_url']);

    $options['data_fetch_interval'] = sanitize_text_field($input['data_fetch_interval']);

    $options['enable_data_cleanup'] = isset($input['enable_data_cleanup']) ? true : false;
    $options['data_retention_days'] = intval($input['data_retention_days']);

    $options['use_external_db'] = isset($input['use_external_db']) ? true : false;
    $options['external_db_host'] = sanitize_text_field($input['external_db_host']);
    $options['external_db_name'] = sanitize_text_field($input['external_db_name']);
    $options['external_db_user'] = sanitize_text_field($input['external_db_user']);
    $options['external_db_password'] = sanitize_text_field($input['external_db_password']);

    return $options;
}

function solarpower_token_render() {
    $options = get_option('solarpower_options');
    ?>
    <input type='text' name='solarpower_options[solarpower_token]' value='<?php echo esc_attr($options['solarpower_token']); ?>' style="width: 100%;">
    <p class="description"><?php _e('Enter your Home Assistant Long-Lived Access Token.', 'solarpower-data'); ?></p>
    <?php
}

function solarpower_production_now_url_render() {
    $options = get_option('solarpower_options');
    ?>
    <input type='text' name='solarpower_options[solarpower_production_now_url]' value='<?php echo esc_attr($options['solarpower_production_now_url']); ?>' style="width: 100%;">
    <p class="description"><?php _e('Example: https://your-home-assistant/api/states/sensor.production_now', 'solarpower-data'); ?></p>
    <?php
}

function solarpower_production_watt_url_render() {
    $options = get_option('solarpower_options');
    ?>
    <input type='text' name='solarpower_options[solarpower_production_watt_url]' value='<?php echo esc_attr($options['solarpower_production_watt_url']); ?>' style="width: 100%;">
    <p class="description"><?php _e('Example: https://your-home-assistant/api/states/sensor.production_watt', 'solarpower-data'); ?></p>
    <?php
}

function solarpower_sold_watt_url_render() {
    $options = get_option('solarpower_options');
    ?>
    <input type='text' name='solarpower_options[solarpower_sold_watt_url]' value='<?php echo esc_attr($options['solarpower_sold_watt_url']); ?>' style="width: 100%;">
    <p class="description"><?php _e('Example: https://your-home-assistant/api/states/sensor.sold_watt', 'solarpower-data'); ?></p>
    <?php
}

function solarpower_data_fetch_interval_render() {
    $options = get_option('solarpower_options');
    $interval = isset($options['data_fetch_interval']) ? $options['data_fetch_interval'] : 'hourly';
    $schedules = wp_get_schedules();
    ?>
    <select name='solarpower_options[data_fetch_interval]'>
        <?php foreach ($schedules as $key => $schedule): ?>
            <option value="<?php echo esc_attr($key); ?>" <?php selected($interval, $key); ?>>
                <?php echo esc_html($schedule['display']); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description"><?php _e('Select how often data should be fetched.', 'solarpower-data'); ?></p>
    <?php
}

function solarpower_enable_data_cleanup_render() {
    $options = get_option('solarpower_options');
    $enabled = isset($options['enable_data_cleanup']) ? $options['enable_data_cleanup'] : false;
    ?>
    <input type='checkbox' name='solarpower_options[enable_data_cleanup]' <?php checked($enabled, true); ?> value="1">
    <label for='enable_data_cleanup'><?php _e('Enable automatic data cleanup', 'solarpower-data'); ?></label>
    <?php
}

function solarpower_data_retention_days_render() {
    $options = get_option('solarpower_options');
    $days = isset($options['data_retention_days']) ? $options['data_retention_days'] : 30;
    ?>
    <input type='number' name='solarpower_options[data_retention_days]' value='<?php echo esc_attr($days); ?>' min="1" max="365">
    <p class="description"><?php _e('Number of days to retain data.', 'solarpower-data'); ?></p>
    <?php
}

function solarpower_use_external_db_render() {
    $options = get_option('solarpower_options');
    $enabled = isset($options['use_external_db']) ? $options['use_external_db'] : false;
    ?>
    <input type='checkbox' name='solarpower_options[use_external_db]' <?php checked($enabled, true); ?> value="1" id="use_external_db">
    <label for='use_external_db'><?php _e('Use external database for data storage', 'solarpower-data'); ?></label>
    <?php
}

function solarpower_external_db_host_render() {
    $options = get_option('solarpower_options');
    ?>
    <input type='text' name='solarpower_options[external_db_host]' value='<?php echo esc_attr($options['external_db_host']); ?>' style="width: 100%;">
    <p class="description"><?php _e('Example: localhost or 127.0.0.1', 'solarpower-data'); ?></p>
    <?php
}

function solarpower_external_db_name_render() {
    $options = get_option('solarpower_options');
    ?>
    <input type='text' name='solarpower_options[external_db_name]' value='<?php echo esc_attr($options['external_db_name']); ?>' style="width: 100%;">
    <p class="description"><?php _e('Name of the external database.', 'solarpower-data'); ?></p>
    <?php
}

function solarpower_external_db_user_render() {
    $options = get_option('solarpower_options');
    ?>
    <input type='text' name='solarpower_options[external_db_user]' value='<?php echo esc_attr($options['external_db_user']); ?>' style="width: 100%;">
    <p class="description"><?php _e('Username for the external database.', 'solarpower-data'); ?></p>
    <?php
}

function solarpower_external_db_password_render() {
    $options = get_option('solarpower_options');
    ?>
    <input type='password' name='solarpower_options[external_db_password]' value='<?php echo esc_attr($options['external_db_password']); ?>' style="width: 100%;">
    <p class="description"><?php _e('Password for the external database.', 'solarpower-data'); ?></p>
    <?php
}

function solarpower_settings_section_callback() {
    echo '<p>' . __('Enter your Home Assistant API settings and configure the plugin options below:', 'solarpower-data') . '</p>';
}

function solarpower_options_page() {
    $last_status = solarpower_get_last_status();
    ?>
    <div class="wrap">
        <h2><?php _e('Solar Power Data Settings', 'solarpower-data'); ?></h2>
        <form action='options.php' method='post'>
            <?php
            settings_fields('solarpower_settings');
            do_settings_sections('solarpower_settings');
            submit_button();
            ?>
        </form>

        <h2><?php _e('Plugin Status', 'solarpower-data'); ?></h2>
        <p><?php echo esc_html($last_status); ?></p>

        <button id="solarpower-test-connection" class="button button-primary"><?php _e('Test Connection', 'solarpower-data'); ?></button>
        <div id="solarpower-test-result"></div>
    </div>
    <?php
}

// Admin-Skript einbinden
add_action('admin_enqueue_scripts', 'solarpower_admin_enqueue_scripts');
function solarpower_admin_enqueue_scripts($hook) {
    if ($hook !== 'settings_page_solarpower_data') {
        return;
    }

    wp_enqueue_script('solarpower-data-admin', plugin_dir_url(__FILE__) . 'solarpower-data-admin.js', array('jquery'), null, true);
    wp_localize_script('solarpower-data-admin', 'SolarPowerAjax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('solarpower_ajax_nonce')
    ));
}

