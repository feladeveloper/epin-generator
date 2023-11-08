# PGS Voucher Management Plugin

PGS Voucher Management is a WordPress plugin that allows you to generate and manage vouchers pins (e-pins) with for the PGS System. It provides an intuitive interface for generating e-pins in batches, managing their status, and sending them via email.

## Features

- Batch e-pin generation.
- Customizable denomination for e-pins.
- Generate e-pins with a unique batch ID.
- Activate or deactivate e-pin batches.
- Send e-pins to users via email as Excel files.

## Installation

1. Download the `e-pin-management` plugin folder.
2. Upload the entire folder to your WordPress plugins directory (`wp-content/plugins/`).
3. Activate the plugin through the WordPress admin interface.

## Usage

### E-Pin Generation

1. Go to the "E-Pin Management" section in your WordPress admin dashboard.
2. Click on "Generate E-Pins."
3. Enter the number of vouchers to generate and the denomination.
4. Submit the form to create a new batch of e-pins.

### PGS Vouchers

1. Navigate to the "Batch Details" section within the "E-Pin Management" menu.
2. View and manage the details of generated batches.
3. Use the "Action" column to activate or deactivate batches.

### Using REST API

You can use the provided REST API endpoints to interact with the plugin programmatically:

- `GET /wp-json/pgs/v1/voucher?voucher_pin={voucher_pin}`: Retrieve voucher details by voucher pin and status.
- `POST /wp-json/pgs/v1/redeem-voucher`: Use a voucher by providing the "voucher_pin" via a POST request.

## Dependencies

This plugin relies on the PhpSpreadsheet library to generate Excel files. Make sure you have it installed in your project.

## Support

If you encounter any issues or have questions, please contact our support team.

## License

This plugin is released under the [MIT License](LICENSE).

## Contributors

- [SGS Team](https://saltingstein.com)

## Contribute

We welcome contributions and pull requests. Feel free to contribute to the development of this plugin.

