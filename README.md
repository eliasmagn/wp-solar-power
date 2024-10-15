# Solar Power Data WordPress Plugin Documentation

**Plugin Name:** Solar Power Data  
**Description:** Fetches data from Home Assistant and stores it in the WordPress or external database. Displays solar power data with customizable graphs using Chart.js.  
**Version:** 2.1  
**Author:** Elias Haisch  
**Text Domain:** solarpower-data  

---

## Table of Contents

- [Introduction](#introduction)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
  - [Home Assistant API Setup](#home-assistant-api-setup)
  - [Plugin Settings](#plugin-settings)
- [Usage](#usage)
  - [Shortcode](#shortcode)
  - [Shortcode Attributes](#shortcode-attributes)
  - [Examples](#examples)
- [Data Management](#data-management)
  - [Data Fetch Interval](#data-fetch-interval)
  - [Data Cleanup](#data-cleanup)
  - [External Database](#external-database)
- [Troubleshooting](#troubleshooting)
- [Frequently Asked Questions](#frequently-asked-questions)
- [Support](#support)
- [Changelog](#changelog)
- [License](#license)

---

## Introduction

The **Solar Power Data** plugin fetches solar power data from your Home Assistant instance and stores it in the WordPress database or an external database. It provides a shortcode to display the data in customizable charts on your WordPress website using Chart.js.

---

## Features

- **Data Fetching from Home Assistant**: Retrieve solar power data via Home Assistant's API.
- **Efficient Data Storage**: Store data in the WordPress database or an external database to prevent overloading your main database.
- **Customizable Data Fetch Intervals**: Choose how often data should be fetched (e.g., every minute, hourly, daily).
- **Flexible Display Options**: Customize chart types, displayed datasets, and time periods.
- **Interactive Charts**: Visualize data with interactive charts using Chart.js.
- **User-Friendly Configuration**: Easily configure API access and plugin options via the WordPress admin panel.
- **Data Interpolation**: Automatically interpolate missing data points to maintain consistent charts.
- **Connection Testing**: Test your Home Assistant API connection directly from the plugin settings.

---

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- A Home Assistant instance with accessible API and valid Long-Lived Access Token
- cURL and JSON PHP extensions enabled on your server

---

## Installation

1. **Download the Plugin**

   - Download the latest version of the plugin as a ZIP file.

2. **Upload the Plugin to WordPress**

   - Log in to your WordPress admin dashboard.
   - Navigate to **Plugins > Add New**.
   - Click on **Upload Plugin** and select the ZIP file you downloaded.
   - Click **Install Now**.

3. **Activate the Plugin**

   - After installation, click **Activate Plugin**.

4. **Verify Installation**

   - Ensure the plugin appears in your list of active plugins.

---

## Configuration

### Home Assistant API Setup

Before configuring the plugin, you need to set up API access in your Home Assistant instance.

1. **Generate a Long-Lived Access Token**

   - Log in to your Home Assistant instance.
   - Click on your user profile in the bottom left corner.
   - Scroll down to the **Long-Lived Access Tokens** section.
   - Click **Create Token**, give it a name (e.g., "WordPress Solar Data"), and copy the generated token.

2. **Identify Sensor URLs**

   - Determine the API URLs for the sensors you want to fetch:
     - **Production Now URL**: Current power production sensor.
     - **Production Watt URL**: Total energy produced in watt-hours.
     - **Sold Watt URL**: Total energy sold back to the grid in watt-hours.
   - The URL format is usually:

     ```
     https://your-home-assistant-url/api/states/sensor.your_sensor_name
     ```

     **Example:**

     ```
     https://homeassistant.local:8123/api/states/sensor.production_now
     ```

### Plugin Settings

1. **Access Plugin Settings**

   - In your WordPress admin dashboard, navigate to **Settings > Solar Power Data**.

2. **Configure API Settings**

   - **API Token**: Paste the Long-Lived Access Token you generated.
   - **Production Now URL**: Enter the API URL for the current power production sensor.
   - **Production Watt URL**: Enter the API URL for the total energy produced sensor.
   - **Sold Watt URL**: Enter the API URL for the total energy sold sensor.

3. **Data Fetch Interval**

   - Choose how often the plugin should fetch data from Home Assistant.
   - Options include **Every Minute**, **Hourly**, **Twice Daily**, **Daily**, etc.

4. **Data Cleanup**

   - **Enable Automatic Data Cleanup**: Check this option to automatically remove old data.
   - **Data Retention Days**: Specify how many days of data to retain (e.g., 30).

5. **External Database (Optional)**

   - **Use External Database for Data Storage**: Check this option to store data in a separate database.
   - **External DB Host**: Enter the host address (e.g., `localhost` or `127.0.0.1`).
   - **External DB Name**: Enter the name of the external database.
   - **External DB User**: Enter the username for the external database.
   - **External DB Password**: Enter the password for the external database.

6. **Test Connection**

   - Click the **Test Connection** button to verify the plugin can connect to your Home Assistant API.
   - The result will display below the button.

7. **Save Changes**

   - Click **Save Changes** to apply your settings.

8. **Plugin Status**

   - After saving, the **Plugin Status** section displays the latest status message, indicating whether data was successfully fetched.

---

## Usage

### Shortcode

To display the solar power data on your website, use the shortcode:

```
[display_solarpower_data]
```

Place this shortcode in any page or post where you want the charts to appear.

### Shortcode Attributes

You can customize the displayed data using the following attributes:

- **days**: Number of days to display data for.
  - **Default**: `7`
  - **Example**: `days="14"`
- **chart_type**: Type of chart to display (`line`, `bar`, `pie`, etc.).
  - **Default**: `line`
  - **Example**: `chart_type="bar"`
- **show_production_now**: Display the current production power.
  - **Default**: `true`
  - **Options**: `true`, `false`
  - **Example**: `show_production_now="false"`
- **show_production_watt**: Display the total produced energy.
  - **Default**: `true`
  - **Options**: `true`, `false`
  - **Example**: `show_production_watt="false"`
- **show_sold_watt**: Display the total energy sold back to the grid.
  - **Default**: `true`
  - **Options**: `true`, `false`
  - **Example**: `show_sold_watt="false"`

### Examples

1. **Display Data for the Last 14 Days**

   ```
   [display_solarpower_data days="14"]
   ```

2. **Display Only the Current Production Power in a Bar Chart**

   ```
   [display_solarpower_data chart_type="bar" show_production_watt="false" show_sold_watt="false"]
   ```

3. **Display Total Produced and Sold Energy Without Current Production**

   ```
   [display_solarpower_data show_production_now="false"]
   ```

4. **Display Data for the Last 30 Days in a Pie Chart**

   ```
   [display_solarpower_data days="30" chart_type="pie"]
   ```

---

## Data Management

### Data Fetch Interval

- The plugin uses WordPress Cron to schedule data fetching.
- You can set the interval in **Settings > Solar Power Data** under **Data Fetch Interval**.
- Available intervals include:

  - **Every Minute**
  - **Hourly**
  - **Twice Daily**
  - **Daily**

**Note:** WordPress Cron jobs depend on site traffic. If your site has low traffic, consider setting up a real Cron job on your server.

### Data Cleanup

- Enable **Automatic Data Cleanup** to prevent the database from growing indefinitely.
- Set **Data Retention Days** to specify how many days of data to keep.

### External Database

- If you're concerned about overloading your WordPress database, you can store data in an external database.
- Configure the external database settings in the plugin configuration.
- Ensure the external database is accessible from your WordPress installation.

---

## Troubleshooting

### Common Issues and Solutions

1. **Chart Not Displaying**

   - **Cause**: Missing Date Adapter for Chart.js.
   - **Solution**: Ensure that `chartjs-adapter-date-fns` is correctly enqueued in the plugin.

2. **No Data Available**

   - **Cause**: Plugin hasn't fetched data yet or API connection failed.
   - **Solution**: Check the **Plugin Status** in the settings to see the latest status message. Use the **Test Connection** button to verify API access.

3. **Connection Failed**

   - **Cause**: Incorrect API Token or Sensor URLs.
   - **Solution**: Double-check your API token and sensor URLs. Ensure your Home Assistant instance is reachable from your WordPress server.

4. **Cron Job Not Running**

   - **Cause**: Low site traffic or WordPress Cron issues.
   - **Solution**: Consider setting up a real Cron job on your server to trigger WordPress Cron events.

### Enabling Debugging

- To enable debugging, add the following lines to your `wp-config.php` file:

  ```php
  define('WP_DEBUG', true);
  define('WP_DEBUG_LOG', true);
  ```

- Check the debug log at `wp-content/debug.log` for error messages related to the plugin.

---

## Frequently Asked Questions

**Q:** *Can I display data for custom time periods?*  
**A:** Yes, use the `days` attribute in the shortcode to specify the number of days.

---

**Q:** *How secure is my Home Assistant API Token?*  
**A:** The API token is stored in the WordPress options table. Ensure your WordPress installation is secure and up-to-date.

---

**Q:** *Can I customize the appearance of the charts?*  
**A:** The plugin uses Chart.js for rendering charts. You can customize the appearance by modifying the `solarpower-data-scripts.js` file or enqueuing your own scripts.

---

**Q:** *How do I prevent the WordPress database from getting overloaded?*  
**A:** Enable the **Automatic Data Cleanup** feature or use an **External Database** for data storage.

---

**Q:** *Does the plugin support other sensors or data sources?*  
**A:** Currently, the plugin is designed for specific solar power sensors from Home Assistant. You can modify the plugin code to support additional sensors.

---

**Q:** *I changed the data fetch interval, but the plugin still uses the old interval.*  
**A:** The plugin reschedules the Cron event when the interval changes. If issues persist, deactivate and reactivate the plugin.

---

## Support

If you encounter issues or have questions, please contact the plugin author:

- **Email**: [elias.haisch@example.com](mailto:elias.haisch@example.com)
- **GitHub**: [https://github.com/eliashaisch/solarpower-data](https://github.com/eliashaisch/solarpower-data)

Please provide detailed information about your issue, including any error messages and steps to reproduce the problem.

---

## Changelog

### Version 2.1

- **Fixed**: Chart.js date adapter issue causing charts not to display.
- **Added**: Connection testing feature in the settings page.
- **Added**: Plugin status display to show the latest data fetch status.
- **Improved**: Help texts and examples in the plugin settings.
- **Enhanced**: Missing data interpolation to handle gaps in data fetching.

### Version 2.0

- **Added**: Display options and configurability for users.
- **Added**: Option to use an external database.
- **Added**: Variable data fetch intervals.
- **Improved**: Documentation updates.

### Version 1.1

- **Optimized**: Database structure and added data cleanup.
- **Added**: Ability to adjust the displayed time period.
- **Enhanced**: Improved charts and time axis optimization.

### Version 1.0

- **Initial Release**

---

## License

This plugin is licensed under the [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

---

## Additional Notes

- **Security**: Always ensure your WordPress installation and plugins are up-to-date to maintain security.
- **Data Privacy**: Be cautious when handling sensitive data. The plugin stores API tokens and fetched data in your database.
- **Customization**: Advanced users can customize the plugin by modifying the PHP and JavaScript files. Ensure you maintain backups before making changes.
- **Cron Jobs**: For reliable data fetching, especially on low-traffic sites, consider setting up a server-side Cron job to trigger WordPress Cron events.

---

## Acknowledgments

- **Chart.js**: [https://www.chartjs.org/](https://www.chartjs.org/)
- **WordPress Plugin Handbook**: [https://developer.wordpress.org/plugins/](https://developer.wordpress.org/plugins/)

---

*This documentation was generated to assist users in installing, configuring, and using the Solar Power Data WordPress plugin effectively. For any further assistance, please reach out via the support channels provided.*
