# PGS Vouchers Plugin for WordPress

PGS Vouchers is a WordPress plugin designed to generate and manage electronic pins (e-pins) that can be used as vouchers within your WordPress site. This plugin provides an admin interface for creating batches of e-pins, a REST API for retrieving and redeeming e-pins, and a way to visualize batch details.

## Features

- **E-Pin Generation**: Generate e-pins with a simple admin interface.
- **REST API Endpoints**: Retrieve and redeem e-pins through custom API endpoints.
- **Batch Management**: View and manage batches of generated e-pins.

## Directory Structure

Below is the directory structure of the PGS Vouchers plugin:
```
voucher-plugin/
├── PhpSpreadsheet/ # PhpSpreadsheet library files
├── apis/ # REST API endpoints
│ ├── get-voucher.php # Endpoint to get voucher details
│ └── redeem-voucher.php # Endpoint to redeem a voucher
├── voucher.php # Main plugin file
├── README.txt # README file
├── batches-table.php # Admin page for batch details
├── composer.json # Composer dependencies
└── index.php # Index file for security
```
## Installation

1. Clone or download the plugin repository.
2. If you haven't already, install Composer to manage dependencies. Then run `composer install` in the plugin directory to install PHP Office Spreadsheet.
3. Upload the plugin files to your `/wp-content/plugins/pgs-vouchers` directory, or install the plugin through the WordPress plugins screen directly.
4. Activate the plugin through the 'Plugins' screen in WordPress.

## Usage

### Admin Interface

Navigate to the 'E-Pin Management' page in the WordPress admin menu. Here, you can generate new e-pins by specifying the number of pins you want to create.

### REST API Endpoints

#### Retrieve E-Pin Details

- **GET** `/wp-json/pgs/v1/voucher`
- Parameters:
  - `voucher_pin`: The pin of the voucher you want to retrieve.

#### Redeem E-Pin

- **POST** `/wp-json/pgs/v1/redeem-voucher`
- Parameters:
  - `voucher_pin`: The pin of the voucher you want to redeem.

### Viewing Batch Details

Navigate to the 'Batch Details' submenu under the 'E-Pin Management' menu to view details of generated batches.

## Contributing

Contributions are welcome from the community. To contribute:

1. Fork the repository.
2. Create a new branch for each feature or improvement.
3. Send a pull request from each feature branch to the main branch.

Please follow the WordPress coding standards for PHP, HTML, and JavaScript.

## Dependencies

- [PHP Office Spreadsheet](https://github.com/PHPOffice/PhpSpreadsheet): Used for generating Excel files containing lists of generated e-pins.

## Support

If you encounter any problems or have any queries about using the plugin, please submit an issue on the GitHub repository.

## License

The PGS Vouchers plugin is open-sourced software licensed under the GPL v2 or later.

