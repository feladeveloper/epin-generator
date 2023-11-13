
# BuyByRaffle Plugin Coding Guide

## Table of Contents

- [Introduction](#introduction)
- [Features](#features)
- [Installation](#installation)
- [Contribution](#contribution)
- [Coding Guide](#coding-guide)
  - [Directory Structure](#directory-structure)
  - [Description](#description)
  - [Naming Conventions and Autoloading](#naming-conventions-and-autoloading)
  - [Method Naming](#method-naming)
  - [Comments and Documentation](#comments-and-documentation)
  - [Exception Handling](#exception-handling)
  - [Database](#database)
  - [WordPress Hooks](#wordpress-hooks)
  - [Miscellaneous](#miscellaneous)

## Introduction

The BuyByRaffle plugin is an innovative addition to the WooCommerce store, integrating raffles into the shopping experience. This README provides all the necessary information to understand, install, and contribute to the plugin.

## Features

- Adds a new product attribute to WooCommerce for categorizing products as 'Hero' or 'Bait'.
- Creates and manages custom database tables for raffles, tickets, logs, and queued raffles.
- Updates raffle status based on associated products.
- Makes 'Hero' products non-purchasable and removes them from archives and search results.

## Installation

1. Download the plugin ZIP file from the GitHub repository.
2. Go to your WordPress admin panel and navigate to 'Plugins -> Add New'.
3. Click the 'Upload Plugin' button and choose the downloaded ZIP file.
4. After uploading, click 'Activate' to enable the plugin on your WooCommerce store.
5. Verify that the plugin is working by going to a WooCommerce product and checking for a new attribute for 'BuyByRaffle Product Group'. Also check if 4 custom tables have been created in the database: raffles, tickets, logs, and queued raffles.

## Contribution

If you'd like to contribute to this project, please follow the coding guidelines as outlined in the 'Coding Guide' segment below. All contributions should be made via pull requests on GitHub.

## Coding Guide

### Directory Structure

```
- BuyByRaffle
  |-- Includes
  |   |-- BuyByRaffleHeroProductHandler.php
  |   |-- BuyByRaffleProductAttributeHandler.php
  |   |-- BuyByRaffleProductFieldHandler.php
  |   |-- BuyByRaffleStatusUpdaterHandler.php
  |   |-- BuyByRaffleTableInstallerHandler.php
  |-- js
  |-- css
  |-- autoloader.php
  |-- buybyraffle.php
  |-- uninstall.php
```

### Description

- **BuyByRaffle**: Root directory containing all files and folders related to the plugin.
- **Includes**: Directory containing all the class files that handle specific functionalities.
- **js**: Directory for JavaScript files.
- **css**: Directory for CSS files.
- **autoloader.php**: Autoloader script for the plugin.
- **buybyraffle.php**: The main file that initiates the plugin.
- **uninstall.php**: File to handle uninstallation logic.

### Naming Conventions and Autoloading

#### Classes

- Class names should be in PascalCase.
- Filenames should match the class names and should also be in PascalCase. E.g., a class `BuyByRaffleStatusUpdater` should be in a file named `BuyByRaffleStatusUpdater.php`.

#### Autoloading

The autoloading mechanism is defined in `autoloader.php`. It uses the `spl_autoload_register` function. Classes are autoloaded based on their names and expected directory location.

### Method Naming

- Methods within classes should use camelCase.

### Comments and Documentation

- Use DocBlocks for class and method documentation.
- Inline comments should be used to explain complex sections of code.

### Exception Handling

- Use try-catch blocks for error-prone sections of code.
- Log exceptions for debugging.

### Database

- Custom tables are handled in the `BuyByRaffleTableInstaller` class.

### WordPress Hooks

- WordPress hooks (actions and filters) are primarily registered within class constructors.

### Miscellaneous

- Always ensure compatibility with WooCommerce as it's a required dependency.

## Conclusion

This guide should be reviewed and updated as the project evolves.
