# Google Address Validation Tester Plugin for WordPress

A WordPress plugin that provides a user interface for testing and demonstrating the Google Address Validation API integration. This plugin allows you to validate addresses, get detailed validation scores, and manage API settings through the WordPress admin interface.

## Features

- Visual interface for address validation testing
- Comprehensive validation metrics including:
    - Validation score and confidence level
    - Address standardization and formatting
    - USPS data integration for US addresses
    - Geocoding information
    - Detailed validation status indicators
- Multi-language support for address formatting
- Configurable caching system
- Detailed validation results including:
    - Confidence scoring (0-100)
    - Standardized address components
    - Validation granularity levels
    - Component confirmation status
    - Geocoding information
    - USPS-specific data for US addresses

## Requirements

- PHP 7.4 or later
- WordPress 6.7.1 or later
- Google Maps API key with Address Validation API enabled

## Installation

1. Download or clone this repository
2. Place in your WordPress plugins directory
3. Run `composer install` in the plugin directory
4. Activate the plugin in WordPress
5. Add your Google Address Validation API key in Google > Address Validation

## Usage

1. Navigate to Google > Address Validation in your WordPress admin panel
2. Enter your Google Address Validation API key in the settings section
3. Configure caching preferences (optional)
4. Enter an address to validate
5. Select additional options:
    - Enable USPS data for US addresses
    - Choose preferred language for address formatting
6. View comprehensive validation results including:
    - Validation score and confidence level
    - Standardized address format
    - Detailed component analysis
    - USPS-specific data (for US addresses)

## Features in Detail

### Address Validation
- Get validation scores and confidence levels
- View standardized address formats
- Check component-level validation status
- Verify deliverability and shipping status
- Identify potential issues or inconsistencies

### USPS Integration
- Carrier route information
- Delivery point codes
- Address deliverability status
- Commercial mail receiver status
- Vacancy information

### Multilingual Support
- Format addresses in multiple languages
- Support for major languages including:
    - English, French, German, Spanish
    - Italian, Portuguese, Dutch, Polish
    - Japanese, Korean, Chinese

### Caching System
- Configurable cache duration
- Cache clearing functionality
- Reduced API usage and improved performance

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL v2 or later License.

## Support

- Documentation: https://github.com/arraypress/google-address-validation-plugin
- Issue Tracker: https://github.com/arraypress/google-address-validation-plugin/issues