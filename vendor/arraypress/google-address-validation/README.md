# Google Address Validation API for WordPress

A PHP library for integrating with the Google Address Validation API in WordPress. This library provides robust address validation, standardization, and verification capabilities with support for WordPress transient caching and WP_Error handling.

## Features

- ✅ **Address Validation**: Verify and standardize addresses globally
- 🏠 **Detailed Components**: Access all address components and metadata
- 📫 **USPS Support**: Enhanced validation for US addresses with USPS data
- 🌍 **International**: Support for addresses worldwide with region code handling
- 🔄 **Response Parsing**: Clean response object for easy data access
- ⚡ **WordPress Integration**: Native transient caching and WP_Error support
- 🛡️ **Type Safety**: Full type hinting and strict types
- 🏢 **Business Detection**: Identify business locations and PO boxes
- 📍 **Geocoding**: Get coordinates for validated addresses
- 🔍 **Validation Details**: Access validation status and issues

## Requirements

- PHP 7.4 or later
- WordPress 5.0 or later
- Google Address Validation API key

## Installation

Install via Composer:

```bash
composer require arraypress/google-address-validation
```

## Basic Usage

```php
use ArrayPress\Google\AddressValidation\Client;

// Initialize client with your API key
$client = new Client( 'your-google-api-key' );

// Validate an address
$result = $client->validate( '1600 Amphitheatre Parkway, Mountain View, CA' );
if ( ! is_wp_error( $result ) ) {
    // Check if address is verified
    if ( $result->is_verified() ) {
        // Get standardized address
        $address = $result->get_standardized_address();
        echo "Formatted Address: {$address['formatted_address']}\n";
        
        // Get coordinates
        if ( $geocode = $result->get_geocode() ) {
            echo "Latitude: {$geocode['latitude']}\n";
            echo "Longitude: {$geocode['longitude']}\n";
        }
    }
}
```

## Extended Examples

### USPS Validation for US Addresses

```php
// Enable USPS validation for US addresses
$result = $client->validate(
    '1600 Pennsylvania Avenue NW, Washington, DC 20500',
    'US',
    true // Enable USPS validation
);

if  ( ! is_wp_error( $result ) ) {
    $usps_data = $result->get_usps_data();
    // Access USPS-specific data
}
```

### Address Type Detection

```php
$result = $client->validate( '123 Business Street, Anytown, USA' );
if ( ! is_wp_error( $result ) ) {
    if ( $result->is_residential() ) {
        echo "This is a residential address\n";
    } elseif ( $result->is_po_box() ) {
        echo "This is a PO Box\n";
    } else {
        echo "This is likely a commercial address\n";
    }
}
```

### Working with Address Components

```php
$result = $client->validate( '1600 Amphitheatre Parkway, Mountain View, CA' );
if ( ! is_wp_error( $result ) ) {
    $components = $result->get_standardized_address();
    echo "Street: {$components['street_number']} {$components['street_name']}\n";
    echo "City: {$components['locality']}\n";
    echo "State: {$components['administrative_area']}\n";
    echo "ZIP: {$components['postal_code']}\n";
    echo "Country: {$components['country']}\n";
}
```

### Handling Responses with Caching

```php
// Initialize with custom cache duration (1 hour = 3600 seconds)
$client = new Client( 'your-api-key', true, 3600 );

// Results will be cached
$result = $client->validate( '1600 Amphitheatre Parkway, Mountain View, CA' );

// Clear specific cache
$client->clear_cache( 'validate_1600 Amphitheatre Parkway, Mountain View, CA' );

// Clear all validation caches
$client->clear_cache();
```

## API Methods

### Client Methods

* `validate( $address, $region_code = '', $enable_usps = false, $previous_address = false)`: Validate an address
* `clear_cache( $identifier = null)`: Clear cached responses

### Response Methods

#### Validation Methods
* `check_validity()`: Get detailed validation analysis
* `is_fully_validated()`: Check if address is completely validated
* `is_high_confidence()`: Check if address has high confidence validation
* `is_minimal_valid()`: Check if address meets minimal validation requirements
* `is_standardized()`: Check if address is in standard format
* `is_exact_match()`: Check if address is an exact match without inferences
* `is_verification_needed()`: Check if address needs additional verification
* `is_deliverable()`: Check if address is deliverable (USPS data for US addresses)
* `is_shippable()`: Check if address is valid for shipping
* `is_valid_landmark()`: Check if address is a valid landmark/POI
* `is_us_address()`: Check if address is in the United States
* `has_minimal_components()`: Check if address has required components

#### Address Properties
* `is_business()`: Check if address is a business location
* `is_residential()`: Check if address is residential
* `is_po_box()`: Check if address is a PO Box
* `is_active()`: Check if address is active (USPS)
* `is_vacant()`: Check if address is vacant (USPS)
* `is_commercial_mail_receiver()`: Check if address is a CMRA (USPS)

#### Basic Example
```php
use ArrayPress\Google\AddressValidation\Client;

// Initialize client
$client = new Client( 'your-google-api-key' );

// Validate an address
$result = $client->validate( '1600 Amphitheatre Parkway, Mountain View, CA' );
if ( ! is_wp_error( $result ) ) {
    // Get detailed validation status
    $validity = $result->check_validity();
    
    if ( $validity['is_valid'] ) {
        echo "Confidence Level: {$validity['confidence_level']}\n";
        
        // Check specific validation aspects
        if ( $result->is_high_confidence() ) {
            echo "High confidence validation\n";
        }
        
        if ( $result->is_shippable() ) {
            echo "Valid shipping address\n";
        }
    } else {
        echo "Validation Issues:\n";
        foreach ( $validity['issues'] as $issue ) {
            echo "- $issue\n";
        }
    }
}
```

## Use Cases

* **Address Verification**: Validate customer addresses
* **Data Standardization**: Standardize address data
* **USPS Integration**: Enhanced US address validation
* **Fraud Prevention**: Verify address authenticity
* **International Support**: Validate global addresses
* **Business Verification**: Identify business locations
* **Data Quality**: Maintain clean address data
* **Location Services**: Support location-based features

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL-2.0-or-later License.

## Support

- [Documentation](https://github.com/arraypress/google-address-validation)
- [Issue Tracker](https://github.com/arraypress/google-address-validation/issues)