<?php
/**
 * Google Address Validation API Response Class
 *
 * This class handles and structures the response data from the Google Address Validation API.
 * It provides methods to access and validate address components, as well as determine the
 * validity and usability of addresses for different purposes.
 *
 * @package     ArrayPress\Google\AddressValidation
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\Google\AddressValidation;

/**
 * Class Response
 *
 * @package ArrayPress\Google\AddressValidation
 */
class Response {

	/**
	 * Address validation confidence levels
	 *
	 * @var string
	 */
	public const CONFIDENCE_HIGH = 'HIGH';
	public const CONFIDENCE_MEDIUM = 'MEDIUM';
	public const CONFIDENCE_LOW = 'LOW';
	public const CONFIDENCE_UNCERTAIN = 'UNCERTAIN';

	/**
	 * Address granularity level
	 *
	 * @var string
	 */
	public const GRANULARITY_PREMISE = 'PREMISE';

	/**
	 * USPS confirmation statuses
	 *
	 * @var string
	 */
	public const USPS_CONFIRMED = 'Y';
	public const USPS_NOT_CONFIRMED = 'N';

	/**
	 * Raw response data from the API
	 *
	 * Stores the complete response data from the Google Address Validation API.
	 *
	 * @var array{
	 *     responseId: string,
	 *     result: array{
	 *         verdict: array,
	 *         address: array,
	 *         geocode: array,
	 *         metadata: array,
	 *         uspsData?: array
	 *     }
	 * }
	 */
	private array $data;

	/**
	 * Cache for computed values
	 *
	 * @var array<string, mixed>
	 */
	private array $cache = [];

	/**
	 * Initialize the response object
	 *
	 * @param array $data Raw response data from Address Validation API
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * Get the response ID
	 *
	 * @return string|null The unique identifier for this validation response
	 */
	public function get_response_id(): ?string {
		return $this->data['responseId'] ?? null;
	}

	/**
	 * Get verdict information
	 *
	 * @return array|null The complete verdict information from the API
	 */
	public function get_verdict(): ?array {
		return $this->data['result']['verdict'] ?? null;
	}

	/**
	 * Get input granularity level
	 *
	 * @return string|null The granularity level of the input address
	 */
	public function get_input_granularity(): ?string {
		return $this->data['result']['verdict']['inputGranularity'] ?? null;
	}

	/**
	 * Get validation granularity level
	 *
	 * @return string|null The granularity level of the validation
	 */
	public function get_validation_granularity(): ?string {
		return $this->data['result']['verdict']['validationGranularity'] ?? null;
	}

	/**
	 * Get geocode granularity level
	 *
	 * @return string|null The granularity level of the geocoding
	 */
	public function get_geocode_granularity(): ?string {
		return $this->data['result']['verdict']['geocodeGranularity'] ?? null;
	}

	/**
	 * Check if address is complete
	 *
	 * @return bool True if the address is complete, false otherwise
	 */
	public function is_address_complete(): bool {
		return $this->data['result']['verdict']['addressComplete'] ?? false;
	}

	/**
	 * Check if address has unconfirmed components
	 *
	 * @return bool True if the address has unconfirmed components
	 */
	public function has_unconfirmed_components(): bool {
		return $this->data['result']['verdict']['hasUnconfirmedComponents'] ?? false;
	}

	/**
	 * Check if address has inferred components
	 *
	 * @return bool True if the address has inferred components
	 */
	public function has_inferred_components(): bool {
		return $this->data['result']['verdict']['hasInferredComponents'] ?? false;
	}

	/**
	 * Check if address has replaced components
	 *
	 * @return bool True if the address has replaced components
	 */
	public function has_replaced_components(): bool {
		return $this->data['result']['verdict']['hasReplacedComponents'] ?? false;
	}

	/**
	 * Get formatted address
	 *
	 * @return string|null The complete formatted address string
	 */
	public function get_formatted_address(): ?string {
		return $this->data['result']['address']['formattedAddress'] ?? null;
	}

	/**
	 * Get postal address object
	 *
	 * @return array|null The complete postal address data
	 */
	public function get_postal_address(): ?array {
		return $this->data['result']['address']['postalAddress'] ?? null;
	}

	/**
	 * Get address lines
	 *
	 * @return array The address lines array
	 */
	public function get_address_lines(): array {
		return $this->get_postal_address()['addressLines'] ?? [];
	}

	/**
	 * Get administrative area (state/province)
	 *
	 * @return string|null The administrative area
	 */
	public function get_administrative_area(): ?string {
		return $this->get_postal_address()['administrativeArea'] ?? null;
	}

	/**
	 * Get region code (country)
	 *
	 * @return string|null The region code
	 */
	public function get_region_code(): ?string {
		return $this->get_postal_address()['regionCode'] ?? null;
	}

	/**
	 * Get locality (city)
	 *
	 * @return string|null The locality name
	 */
	public function get_locality(): ?string {
		return $this->get_postal_address()['locality'] ?? null;
	}

	/**
	 * Get sublocality
	 *
	 * @return string|null The sublocality name
	 */
	public function get_sublocality(): ?string {
		return $this->get_postal_address()['sublocality'] ?? null;
	}

	/**
	 * Get postal code
	 *
	 * @return string|null The postal code
	 */
	public function get_postal_code(): ?string {
		return $this->get_postal_address()['postalCode'] ?? null;
	}

	/**
	 * Get sorting code
	 *
	 * @return string|null The sorting code
	 */
	public function get_sorting_code(): ?string {
		return $this->get_postal_address()['sortingCode'] ?? null;
	}

	/**
	 * Get the primary type/classification of the address
	 *
	 * @return string Returns one of: 'residential', 'business', 'po_box', 'landmark', 'unknown'
	 */
	public function get_address_type(): string {
		// Check PO Box first as it's most specific
		if ( $this->is_po_box() ) {
			return 'po_box';
		}

		// Check if it's a valid landmark
		if ( $this->is_valid_landmark() ) {
			return 'landmark';
		}

		// Check residential vs business
		if ( $this->is_residential() ) {
			return 'residential';
		}

		if ( $this->is_business() ) {
			return 'business';
		}

		// If no specific type is determined
		return 'unknown';
	}

	/**
	 * Get a human-readable description of the address type
	 *
	 * @return string Translated description of the address type
	 */
	public function get_address_type_label(): string {
		$type = $this->get_address_type();

		switch ( $type ) {
			case 'residential':
				return __( 'Residential Address', 'arraypress' );
			case 'business':
				return __( 'Business Address', 'arraypress' );
			case 'po_box':
				return __( 'PO Box', 'arraypress' );
			case 'landmark':
				return __( 'Landmark/Point of Interest', 'arraypress' );
			default:
				return __( 'Unknown Address Type', 'arraypress' );
		}
	}

	/**
	 * Get coordinates
	 *
	 * Returns an array containing latitude and longitude
	 *
	 * @return array{lat: float|null, lng: float|null} Coordinates array
	 */
	public function get_coordinates(): array {
		$geocode = $this->get_geocode();

		return [
			'lat' => $geocode['latitude'] ?? null,
			'lng' => $geocode['longitude'] ?? null
		];
	}

	/**
	 * Get address components
	 *
	 * @return array Array of address components
	 */
	public function get_address_components(): array {
		return $this->data['result']['address']['addressComponents'] ?? [];
	}

	/**
	 * Get specific address component
	 *
	 * @param string $type Component type to retrieve
	 *
	 * @return array|null Component data or null if not found
	 */
	public function get_address_component( string $type ): ?array {
		foreach ( $this->get_address_components() as $component ) {
			if ( $component['componentType'] === $type ) {
				return $component;
			}
		}

		return null;
	}

	/**
	 * Get missing component types
	 *
	 * @return array Array of missing component types
	 */
	public function get_missing_component_types(): array {
		return $this->data['result']['address']['missingComponentTypes'] ?? [];
	}

	/**
	 * Get unconfirmed component types
	 *
	 * @return array Array of unconfirmed component types
	 */
	public function get_unconfirmed_component_types(): array {
		return $this->data['result']['address']['unconfirmedComponentTypes'] ?? [];
	}

	/**
	 * Get unresolved tokens
	 *
	 * @return array Array of unresolved tokens
	 */
	public function get_unresolved_tokens(): array {
		return $this->data['result']['address']['unresolvedTokens'] ?? [];
	}

	/**
	 * Get geocode data
	 *
	 * @return array|null The geocode data including latitude and longitude
	 */
	public function get_geocode(): ?array {
		$location = $this->data['result']['geocode']['location'] ?? null;
		if ( $location ) {
			return [
				'latitude'  => $location['latitude'] ?? null,
				'longitude' => $location['longitude'] ?? null
			];
		}

		return null;
	}

	/**
	 * Get plus code
	 *
	 * @return array|null The plus code data
	 */
	public function get_plus_code(): ?array {
		return $this->data['result']['geocode']['plusCode'] ?? null;
	}

	/**
	 * Get viewport bounds
	 *
	 * @return array|null The viewport bounds data
	 */
	public function get_viewport(): ?array {
		return $this->data['result']['geocode']['bounds'] ?? null;
	}

	/**
	 * Get feature size in meters
	 *
	 * @return float|null The feature size in meters
	 */
	public function get_feature_size_meters(): ?float {
		return $this->data['result']['geocode']['featureSizeMeters'] ?? null;
	}

	/**
	 * Get place ID
	 *
	 * @return string|null The Google Place ID
	 */
	public function get_place_id(): ?string {
		return $this->data['result']['geocode']['placeId'] ?? null;
	}

	/**
	 * Get place types
	 *
	 * @return array Array of place types
	 */
	public function get_place_types(): array {
		return $this->data['result']['geocode']['placeTypes'] ?? [];
	}

	/**
	 * Get address metadata
	 *
	 * @return array|null The address metadata
	 */
	public function get_metadata(): ?array {
		return $this->data['result']['metadata'] ?? null;
	}

	/**
	 * Check if address is a business
	 *
	 * @return bool True if the address is a business location
	 */
	public function is_business(): bool {
		return $this->data['result']['metadata']['business'] ?? false;
	}

	/**
	 * Check if address is a PO Box
	 *
	 * @return bool True if the address is a PO Box
	 */
	public function is_po_box(): bool {
		return $this->data['result']['metadata']['poBox'] ?? false;
	}

	/**
	 * Check if address is residential
	 *
	 * @return bool True if the address is residential
	 */
	public function is_residential(): bool {
		return $this->data['result']['metadata']['residential'] ?? false;
	}

	/**
	 * Get USPS data
	 *
	 * @return array|null The USPS-specific address data
	 */
	public function get_usps_data(): ?array {
		return $this->data['result']['uspsData'] ?? null;
	}

	/**
	 * Get USPS standardized address
	 *
	 * @return array|null The USPS standardized address
	 */
	public function get_usps_standardized_address(): ?array {
		return $this->data['result']['uspsData']['standardizedAddress'] ?? null;
	}

	/**
	 * Get delivery point code
	 *
	 * @return string|null The USPS delivery point code
	 */
	public function get_delivery_point_code(): ?string {
		return $this->data['result']['uspsData']['deliveryPointCode'] ?? null;
	}

	/**
	 * Get carrier route
	 *
	 * @return string|null The USPS carrier route
	 */
	public function get_carrier_route(): ?string {
		return $this->data['result']['uspsData']['carrierRoute'] ?? null;
	}

	/**
	 * Check if address is a Commercial Mail Receiving Agency
	 *
	 * @return bool True if the address is a CMRA
	 */
	public function is_commercial_mail_receiver(): bool {
		return ( $this->data['result']['uspsData']['dpvCmra'] ?? '' ) === self::USPS_CONFIRMED;
	}

	/**
	 * Check if address is vacant
	 *
	 * @return bool True if the address is vacant
	 */
	public function is_vacant(): bool {
		return ( $this->data['result']['uspsData']['dpvVacant'] ?? '' ) === self::USPS_CONFIRMED;
	}

	/**
	 * Check if address is active
	 *
	 * @return bool True if the address is active
	 */
	public function is_active(): bool {
		return ( $this->data['result']['uspsData']['dpvNoStat'] ?? '' ) === self::USPS_NOT_CONFIRMED;
	}

	/**
	 * Get DPV confirmation status
	 *
	 * @return string|null The USPS DPV confirmation status
	 */
	public function get_dpv_confirmation(): ?string {
		return $this->data['result']['uspsData']['dpvConfirmation'] ?? null;
	}

	/**
	 * Get English Latin Address (if requested)
	 *
	 * @return array|null The address in English Latin format
	 */
	public function get_english_latin_address(): ?array {
		return $this->data['result']['englishLatinAddress'] ?? null;
	}

	/**
	 * Get complete standardized address components
	 *
	 * @return array{
	 *     address_lines: array,
	 *     administrative_area: string|null,
	 *     language_code: string|null,
	 *     locality: string|null,
	 *     postal_code: string|null,
	 *     region_code: string|null,
	 *     sorting_code: string|null,
	 *     sublocality: string|null,
	 *     formatted_address: string|null
	 * } Structured address components
	 */
	public function get_standardized_address(): array {
		$postal = $this->get_postal_address();

		return [
			'address_lines'       => $postal['addressLines'] ?? [],
			'administrative_area' => $postal['administrativeArea'] ?? null,
			'language_code'       => $postal['languageCode'] ?? null,
			'locality'            => $postal['locality'] ?? null,
			'postal_code'         => $postal['postalCode'] ?? null,
			'region_code'         => $postal['regionCode'] ?? null,
			'sorting_code'        => $postal['sortingCode'] ?? null,
			'sublocality'         => $postal['sublocality'] ?? null,
			'formatted_address'   => $this->get_formatted_address()
		];
	}

	/**
	 * Check the validity of the address with detailed analysis
	 *
	 * @return array{
	 *     is_valid: bool,
	 *     confidence_level: string,
	 *     issues: array<string>
	 * } Validation details including validity status, confidence level, and any issues
	 */
	public function check_validity(): array {
		// Use cache if available
		if ( isset( $this->cache['validity'] ) ) {
			return $this->cache['validity'];
		}

		$validity = [
			'is_valid'         => $this->is_address_complete() && ! $this->has_unconfirmed_components(),
			'confidence_level' => $this->determine_confidence_level(),
			'issues'           => $this->get_validity_issues()
		];

		// Cache the result
		$this->cache['validity'] = $validity;

		return $validity;
	}

	/**
	 * Determine the confidence level of the address validation
	 *
	 * @return string Confidence level (HIGH, MEDIUM, LOW, or UNCERTAIN)
	 */
	private function determine_confidence_level(): string {
		if ( $this->is_address_complete() && ! $this->has_unconfirmed_components() && ! $this->has_inferred_components() ) {
			return self::CONFIDENCE_HIGH;
		} elseif ( $this->is_address_complete() && ! $this->has_unconfirmed_components() ) {
			return self::CONFIDENCE_MEDIUM;
		} elseif ( ! $this->has_unconfirmed_components() ) {
			return self::CONFIDENCE_LOW;
		}

		return self::CONFIDENCE_UNCERTAIN;
	}

	/**
	 * Get a list of validity issues with the address
	 *
	 * @return array<string> List of validation issues
	 */
	private function get_validity_issues(): array {
		$issues = [];

		if ( ! $this->is_address_complete() ) {
			$issues[] = __( 'Address is incomplete', 'arraypress' );

			$missing = $this->get_missing_component_types();
			if ( ! empty( $missing ) ) {
				$issues[] = sprintf(
				/* translators: %s: comma-separated list of missing components */
					__( 'Missing components: %s', 'arraypress' ),
					implode( ', ', $missing )
				);
			}
		}

		if ( $this->has_unconfirmed_components() ) {
			$unconfirmed = $this->get_unconfirmed_component_types();
			$issues[]    = sprintf(
			/* translators: %s: comma-separated list of unconfirmed components */
				__( 'Has unconfirmed components: %s', 'arraypress' ),
				implode( ', ', $unconfirmed )
			);
		}

		if ( $this->has_inferred_components() ) {
			$issues[] = __( 'Contains inferred components', 'arraypress' );
		}

		if ( $this->has_replaced_components() ) {
			$issues[] = __( 'Contains replaced components', 'arraypress' );
		}

		return $issues;
	}

	/**
	 * Check if the address is a valid landmark
	 *
	 * Less strict validation for landmarks and points of interest
	 *
	 * @return bool True if valid as a landmark
	 */
	public function is_valid_landmark(): bool {
		return ! $this->has_unconfirmed_components() &&
		       $this->get_geocode() !== null &&
		       $this->get_place_id() !== null;
	}

	/**
	 * Check if the address is from the United States
	 *
	 * @return bool True if US address
	 */
	public function is_us_address(): bool {
		return $this->get_region_code() === 'US';
	}

	/**
	 * Check if the address is fully validated
	 *
	 * @return bool True if fully validated
	 */
	public function is_fully_validated(): bool {
		return $this->is_address_complete() &&
		       ! $this->has_unconfirmed_components() &&
		       ! $this->has_inferred_components() &&
		       ! $this->has_replaced_components();
	}

	/**
	 * Check if the address has high confidence validation
	 *
	 * @return bool True if high confidence
	 */
	public function is_high_confidence(): bool {
		return $this->determine_confidence_level() === self::CONFIDENCE_HIGH;
	}

	/**
	 * Check if the address has minimal valid components
	 *
	 * @return bool True if has minimal components
	 */
	public function has_minimal_components(): bool {
		return $this->get_postal_code() !== null &&
		       $this->get_locality() !== null &&
		       ! empty( $this->get_address_lines() );
	}

	/**
	 * Check if the address is an exact match
	 *
	 * @return bool True if exact match
	 */
	public function is_exact_match(): bool {
		return ! $this->has_inferred_components() &&
		       ! $this->has_replaced_components();
	}

	/**
	 * Check if the address is a minimal valid address
	 *
	 * @return bool True if minimally valid
	 */
	public function is_minimal_valid(): bool {
		return $this->get_formatted_address() !== null &&
		       $this->get_geocode() !== null;
	}

	/**
	 * Check if the address is deliverable
	 *
	 * @return bool True if deliverable
	 */
	public function is_deliverable(): bool {
		// If we have USPS data
		if ( $this->get_usps_data() !== null ) {
			return $this->get_dpv_confirmation() === self::USPS_CONFIRMED;
		}

		// For non-USPS addresses
		return $this->is_address_complete() &&
		       ! $this->has_unconfirmed_components() &&
		       $this->get_geocode() !== null;
	}

	/**
	 * Check if the address has precise coordinates
	 *
	 * @return bool True if precisely located
	 */
	public function is_precisely_located(): bool {
		return $this->get_geocode() !== null &&
		       $this->get_geocode_granularity() === self::GRANULARITY_PREMISE;
	}

	/**
	 * Check if the address is standardized
	 *
	 * @return bool True if standardized
	 */
	public function is_standardized(): bool {
		$standardized = $this->get_standardized_address();

		return ! empty( $standardized['postal_code'] ) &&
		       ! empty( $standardized['locality'] ) &&
		       ! empty( $standardized['formatted_address'] );
	}

	/**
	 * Check if the address is a valid shipping destination
	 *
	 * @return bool True if shippable
	 */
	public function is_shippable(): bool {
		// If we have USPS data
		if ( $this->get_usps_data() !== null ) {
			return $this->is_deliverable();
		}

		// For non-USPS addresses, check if we have the minimum required components
		return $this->get_postal_code() !== null &&
		       $this->get_locality() !== null &&
		       ! empty( $this->get_address_lines() ) &&
		       ! $this->has_unconfirmed_components();
	}

	/**
	 * Check if the address needs verification
	 *
	 * @return bool True if verification needed
	 */
	public function is_verification_needed(): bool {
		return $this->has_unconfirmed_components() ||
		       $this->has_inferred_components() ||
		       ! $this->is_address_complete();
	}

}