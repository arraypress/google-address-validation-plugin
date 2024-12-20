<?php
/**
 * Google Address Validation API Client Class
 *
 * @package     ArrayPress\Google\AddressValidation
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\Google\AddressValidation;

use WP_Error;

/**
 * Class Client
 *
 * A comprehensive utility class for interacting with the Google Address Validation API.
 */
class Client {

	/**
	 * API key for Google Address Validation
	 *
	 * @var string
	 */
	private string $api_key;

	/**
	 * Base URL for the Address Validation API
	 *
	 * @var string
	 */
	private const API_ENDPOINT = 'https://addressvalidation.googleapis.com/v1:validateAddress';

	/**
	 * Whether to enable response caching
	 *
	 * @var bool
	 */
	private bool $enable_cache;

	/**
	 * Cache expiration time in seconds
	 *
	 * @var int
	 */
	private int $cache_expiration;

	/**
	 * Initialize the Address Validation client
	 *
	 * @param string $api_key          API key for Google Address Validation
	 * @param bool   $enable_cache     Whether to enable caching (default: true)
	 * @param int    $cache_expiration Cache expiration in seconds (default: 24 hours)
	 */
	public function __construct( string $api_key, bool $enable_cache = true, int $cache_expiration = 86400 ) {
		$this->api_key          = $api_key;
		$this->enable_cache     = $enable_cache;
		$this->cache_expiration = $cache_expiration;
	}

	/**
	 * Validate an address
	 *
	 * @param string|array $address           Address to validate (string or array of components)
	 * @param array        $options           Additional options for validation
	 * @param string|null  $previous_response Previous response ID for sequential validation
	 * @param string|null  $session_token     Session token for billing purposes
	 *
	 * @return Response|WP_Error Response object or WP_Error on failure
	 */
	public function validate( $address, array $options = [], ?string $previous_response = null, ?string $session_token = null ) {
		// Prepare the request body
		$body = [
			'address' => $this->prepare_address( $address )
		];

		// Add optional parameters
		if ( $previous_response ) {
			$body['previousResponseId'] = $previous_response;
		}

		if ( isset( $options['enable_usps'] ) ) {
			$body['enableUspsCass'] = (bool) $options['enable_usps'];
		}

		if ( isset( $options['language_options'] ) ) {
			$body['languageOptions'] = $options['language_options'];
		}

		if ( $session_token ) {
			$body['sessionToken'] = $session_token;
		}

		// Generate cache key if caching is enabled
		$cache_key = null;
		if ( $this->enable_cache ) {
			$cache_key   = $this->get_cache_key( wp_json_encode( $body ) );
			$cached_data = get_transient( $cache_key );
			if ( false !== $cached_data ) {
				return new Response( $cached_data );
			}
		}

		$response = $this->make_request( $body );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Cache the response if caching is enabled
		if ( $this->enable_cache && $cache_key ) {
			set_transient( $cache_key, $response, $this->cache_expiration );
		}

		return new Response( $response );
	}

	/**
	 * Prepare address data for API request
	 *
	 * @param string|array $address Address data
	 *
	 * @return array Formatted address data
	 */
	private function prepare_address( $address ): array {
		if ( is_string( $address ) ) {
			return [
				'addressLines' => [ $address ]
			];
		}

		$formatted    = [];
		$valid_fields = [
			'revision',
			'regionCode',
			'languageCode',
			'postalCode',
			'sortingCode',
			'administrativeArea',
			'locality',
			'sublocality',
			'addressLines',
		];

		foreach ( $valid_fields as $field ) {
			if ( isset( $address[ $field ] ) ) {
				if ( $field === 'addressLines' && is_string( $address[ $field ] ) ) {
					$formatted[ $field ] = [ $address[ $field ] ];
				} else {
					$formatted[ $field ] = $address[ $field ];
				}
			}
		}

		return $formatted;
	}

	/**
	 * Make a request to the Address Validation API
	 *
	 * @param array $body Request body parameters
	 *
	 * @return array|WP_Error Response array or WP_Error on failure
	 */
	private function make_request( array $body ) {
		$url = add_query_arg( [ 'key' => $this->api_key ], self::API_ENDPOINT );

		$response = wp_remote_post( $url, [
			'timeout' => 15,
			'headers' => [
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json'
			],
			'body'    => wp_json_encode( $body )
		] );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'api_error',
				sprintf(
					__( 'Address Validation API request failed: %s', 'arraypress' ),
					$response->get_error_message()
				)
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code !== 200 ) {
			return new WP_Error(
				'api_error',
				sprintf(
					__( 'Address Validation API returned error code: %d', 'arraypress' ),
					$response_code
				)
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error(
				'json_error',
				__( 'Failed to parse Address Validation API response', 'arraypress' )
			);
		}

		// Check for API errors
		if ( isset( $data['error'] ) ) {
			return new WP_Error(
				'api_error',
				sprintf(
					__( 'Address Validation API returned error: %s', 'arraypress' ),
					$data['error']['message'] ?? 'Unknown error'
				)
			);
		}

		return $data;
	}

	/**
	 * Generate cache key
	 *
	 * @param string $identifier Cache identifier
	 *
	 * @return string Cache key
	 */
	private function get_cache_key( string $identifier ): string {
		return 'google_address_validation_' . md5( $identifier . $this->api_key );
	}

	/**
	 * Clear cached data
	 *
	 * @param string|null $identifier Optional specific cache to clear
	 *
	 * @return bool True on success, false on failure
	 */
	public function clear_cache( ?string $identifier = null ): bool {
		if ( $identifier !== null ) {
			return delete_transient( $this->get_cache_key( $identifier ) );
		}

		global $wpdb;
		$pattern = $wpdb->esc_like( '_transient_google_address_validation_' ) . '%';

		return $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
					$pattern
				)
			) !== false;
	}

}