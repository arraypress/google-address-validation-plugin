<?php
/**
 * ArrayPress - Google Address Validation Tester
 *
 * @package     ArrayPress\Google\AddressValidation
 * @author      David Sherlock
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @link        https://arraypress.com/
 * @since       1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:         ArrayPress - Google Address Validation Tester
 * Plugin URI:          https://github.com/arraypress/google-address-validation-plugin
 * Description:         A plugin to test and demonstrate the Google Address Validation API integration.
 * Version:             1.0.0
 * Requires at least:   6.7.1
 * Requires PHP:        7.4
 * Author:              David Sherlock
 * Author URI:          https://arraypress.com/
 * License:             GPL v2 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:         arraypress-address-validation
 * Domain Path:         /languages
 * Network:             false
 * Update URI:          false
 */

declare( strict_types=1 );

namespace ArrayPress\Google\AddressValidation;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class Plugin {

	/**
	 * API Client instance
	 *
	 * @var Client|null
	 */
	private ?Client $client = null;

	/**
	 * Hook name for the admin page.
	 *
	 * @var string
	 */
	const MENU_HOOK = 'google_page_arraypress-google-address-validation';

	/**
	 * Plugin constructor
	 */
	public function __construct() {
		// Load text domain for translations
		add_action( 'init', [ $this, 'load_textdomain' ] );

		// Admin hooks
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

		// Initialize client if API key exists
		$api_key = get_option( 'google_address_validation_api_key' );
		if ( ! empty( $api_key ) ) {
			$this->client = new Client(
				$api_key,
				(bool) get_option( 'google_address_validation_enable_cache', true ),
				(int) get_option( 'google_address_validation_cache_duration', 86400 )
			);
		}
	}

	/**
	 * Load plugin text domain
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'arraypress-address-validation',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	/**
	 * Registers the Google menu and timezone detection submenu page in the WordPress admin.
	 *
	 * This method handles the creation of a shared Google menu across plugins (if it doesn't
	 * already exist) and adds the Timezone Detection tool as a submenu item. It also removes
	 * the default submenu item to prevent a blank landing page.
	 *
	 * @return void
	 */
	public function add_menu_page(): void {
		// Only add the main Google menu if it doesn't exist yet
		global $admin_page_hooks;

		if ( ! isset( $admin_page_hooks['arraypress-google'] ) ) {
			add_menu_page(
				__( 'Google', 'arraypress-google-address-validation' ),
				__( 'Google', 'arraypress-google-address-validation' ),
				'manage_options',
				'arraypress-google',
				null,
				'dashicons-google',
				30
			);
		}

		// Add the address validation submenu
		add_submenu_page(
			'arraypress-google',
			__( 'Address Validation', 'arraypress-google-address-validation' ),
			__( 'Address Validation', 'arraypress-google-address-validation' ),
			'manage_options',
			'arraypress-google-address-validation',
			[ $this, 'render_test_page' ]
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings(): void {
		register_setting( 'address_validation_settings', 'google_address_validation_api_key' );
		register_setting( 'address_validation_settings', 'google_address_validation_enable_cache', 'bool' );
		register_setting( 'address_validation_settings', 'google_address_validation_cache_duration', 'int' );
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets( $hook ): void {
		if ( $hook !== self::MENU_HOOK ) {
			return;
		}

		wp_enqueue_style(
			'google-address-validation-test-admin',
			plugins_url( 'assets/css/admin.css', __FILE__ ),
			[],
			'1.0.0'
		);

		wp_enqueue_script(
			'google-address-validation-test-admin',
			plugins_url( 'assets/js/admin.js', __FILE__ ),
			[ 'jquery' ],
			'1.0.0',
			true
		);
	}

	/**
	 * Render settings form
	 */
	private function render_settings_form(): void {
		?>
        <h2><?php _e( 'Settings', 'arraypress-address-validation' ); ?></h2>
        <form method="post" class="validation-form">
			<?php wp_nonce_field( 'address_validation_api_key' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="google_address_validation_api_key"><?php _e( 'API Key', 'arraypress-address-validation' ); ?></label>
                    </th>
                    <td>
                        <input type="text" name="google_address_validation_api_key"
                               id="google_address_validation_api_key"
                               class="regular-text"
                               value="<?php echo esc_attr( get_option( 'google_address_validation_api_key' ) ); ?>"
                               placeholder="<?php esc_attr_e( 'Enter your Google Address Validation API key...', 'arraypress-address-validation' ); ?>">
                        <p class="description">
							<?php _e( 'Your Google Address Validation API key. Required for making API requests.', 'arraypress-address-validation' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="google_address_validation_enable_cache"><?php _e( 'Enable Cache', 'arraypress-address-validation' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="google_address_validation_enable_cache"
                                   id="google_address_validation_enable_cache"
                                   value="1" <?php checked( get_option( 'google_address_validation_enable_cache', true ) ); ?>>
							<?php _e( 'Cache validation results', 'arraypress-address-validation' ); ?>
                        </label>
                        <p class="description">
							<?php _e( 'Caching results can help reduce API usage and improve performance.', 'arraypress-address-validation' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="google_address_validation_cache_duration"><?php _e( 'Cache Duration', 'arraypress-address-validation' ); ?></label>
                    </th>
                    <td>
                        <input type="number" name="google_address_validation_cache_duration"
                               id="google_address_validation_cache_duration"
                               class="regular-text"
                               value="<?php echo esc_attr( get_option( 'google_address_validation_cache_duration', 86400 ) ); ?>"
                               min="300" step="300">
                        <p class="description">
							<?php _e( 'How long to cache results in seconds. Default is 86400 (24 hours).', 'arraypress-address-validation' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
			<?php submit_button(
				empty( get_option( 'google_address_validation_api_key' ) )
					? __( 'Save Settings', 'arraypress-address-validation' )
					: __( 'Update Settings', 'arraypress-address-validation' ),
				'primary',
				'submit_api_key'
			); ?>
        </form>
		<?php
	}

	/**
	 * Process form submissions
	 */
	private function process_form_submissions(): array {
		$results = [
			'validation' => null,
			'metadata'   => null
		];

		if ( isset( $_POST['submit_api_key'] ) ) {
			check_admin_referer( 'address_validation_api_key' );
			$api_key        = sanitize_text_field( $_POST['google_address_validation_api_key'] );
			$enable_cache   = isset( $_POST['google_address_validation_enable_cache'] );
			$cache_duration = (int) sanitize_text_field( $_POST['google_address_validation_cache_duration'] );

			update_option( 'google_address_validation_api_key', $api_key );
			update_option( 'google_address_validation_enable_cache', $enable_cache );
			update_option( 'google_address_validation_cache_duration', $cache_duration );

			$this->client = new Client( $api_key, $enable_cache, $cache_duration );
		}

		if ( ! $this->client ) {
			return $results;
		}

		// Process address validation test
		if ( isset( $_POST['submit_validation'] ) && isset( $_POST['address'] ) ) {
			check_admin_referer( 'address_validation_test' );

			$address = sanitize_text_field( $_POST['address'] );
			$options = [
				'enable_usps' => isset( $_POST['enable_usps'] ),
			];

			if ( isset( $_POST['language_code'] ) && ! empty( $_POST['language_code'] ) ) {
				$options['language_options'] = [
					'languageCode' => sanitize_text_field( $_POST['language_code'] )
				];
			}

			$results['validation'] = $this->client->validate( $address, $options );
		}

		// Clear cache if requested
		if ( isset( $_POST['clear_cache'] ) ) {
			check_admin_referer( 'address_validation_test' );
			$this->client->clear_cache();
			add_settings_error(
				'address_validation_test',
				'cache_cleared',
				__( 'Cache cleared successfully', 'arraypress-address-validation' ),
				'success'
			);
		}

		return $results;
	}

	/**
	 * Render validation details
	 */
	private function render_validation_details( $result ): void {
		if ( is_wp_error( $result ) ) {
			?>
            <div class="notice notice-error">
                <p><?php echo esc_html( $result->get_error_message() ); ?></p>
            </div>
			<?php
			return;
		}

		// Get validation details upfront
		$validity = $result->check_validity();
		?>
        <table class="widefat striped">
            <tbody>

            <!-- Add Score Section at the top -->
            <tr>
                <th><?php _e( 'Validation Score', 'arraypress-address-validation' ); ?></th>
                <td>
					<?php
					$score  = $result->get_score();
					$rating = $result->get_rating();

					// Determine color class based on score
					$color_class = 'score-low';
					if ( $score >= 90 ) {
						$color_class = 'score-high';
					} elseif ( $score >= 75 ) {
						$color_class = 'score-good';
					} elseif ( $score >= 50 ) {
						$color_class = 'score-fair';
					}
					?>
                    <div class="validation-score <?php echo esc_attr( $color_class ); ?>">
                        <span class="score-number"><?php echo esc_html( $score ); ?>/100</span>
                        <span class="score-rating"><?php echo esc_html( $rating ); ?></span>
                    </div>
                </td>
            </tr>

            <tr>
                <th><?php _e( 'Response ID', 'arraypress-address-validation' ); ?></th>
                <td><?php echo esc_html( $result->get_response_id() ); ?></td>
            </tr>
            <tr>
                <th><?php _e( 'Formatted Address', 'arraypress-address-validation' ); ?></th>
                <td><?php echo esc_html( $result->get_formatted_address() ); ?></td>
            </tr>

            <!-- Validation Status Section -->
            <tr>
                <th><?php _e( 'Validation Status', 'arraypress-address-validation' ); ?></th>
                <td>
                    <dl class="address-components">
                        <dt><?php _e( 'Confidence Level', 'arraypress-address-validation' ); ?>:</dt>
                        <dd><?php echo esc_html( $validity['confidence_level'] ); ?></dd>

                        <dt><?php _e( 'Is Fully Validated', 'arraypress-address-validation' ); ?>:</dt>
                        <dd><?php echo $result->is_fully_validated() ? 'Yes' : 'No'; ?></dd>

                        <dt><?php _e( 'Is High Confidence', 'arraypress-address-validation' ); ?>:</dt>
                        <dd><?php echo $result->is_high_confidence() ? 'Yes' : 'No'; ?></dd>

                        <dt><?php _e( 'Is Minimal Valid', 'arraypress-address-validation' ); ?>:</dt>
                        <dd><?php echo $result->is_minimal_valid() ? 'Yes' : 'No'; ?></dd>

                        <dt><?php _e( 'Is Standardized', 'arraypress-address-validation' ); ?>:</dt>
                        <dd><?php echo $result->is_standardized() ? 'Yes' : 'No'; ?></dd>

                        <dt><?php _e( 'Is Exact Match', 'arraypress-address-validation' ); ?>:</dt>
                        <dd><?php echo $result->is_exact_match() ? 'Yes' : 'No'; ?></dd>

                        <dt><?php _e( 'Needs Verification', 'arraypress-address-validation' ); ?>:</dt>
                        <dd><?php echo $result->is_verification_needed() ? 'Yes' : 'No'; ?></dd>

						<?php if ( $result->is_us_address() ): ?>
                            <dt><?php _e( 'Is Deliverable', 'arraypress-address-validation' ); ?>:</dt>
                            <dd><?php echo $result->is_deliverable() ? 'Yes' : 'No'; ?></dd>
						<?php endif; ?>

                        <dt><?php _e( 'Is Shippable', 'arraypress-address-validation' ); ?>:</dt>
                        <dd><?php echo $result->is_shippable() ? 'Yes' : 'No'; ?></dd>

                        <dt><?php _e( 'Is Landmark', 'arraypress-address-validation' ); ?>:</dt>
                        <dd><?php echo $result->is_valid_landmark() ? 'Yes' : 'No'; ?></dd>
                    </dl>

					<?php if ( ! empty( $validity['issues'] ) ): ?>
                        <h4><?php _e( 'Validation Issues', 'arraypress-address-validation' ); ?></h4>
                        <ul>
							<?php foreach ( $validity['issues'] as $issue ): ?>
                                <li><?php echo esc_html( $issue ); ?></li>
							<?php endforeach; ?>
                        </ul>
					<?php endif; ?>
                </td>
            </tr>

            <tr>
                <th><?php _e( 'Input Granularity', 'arraypress-address-validation' ); ?></th>
                <td><?php echo esc_html( $result->get_input_granularity() ); ?></td>
            </tr>
            <tr>
                <th><?php _e( 'Validation Granularity', 'arraypress-address-validation' ); ?></th>
                <td><?php echo esc_html( $result->get_validation_granularity() ); ?></td>
            </tr>
            <tr>
                <th><?php _e( 'Geocode Granularity', 'arraypress-address-validation' ); ?></th>
                <td><?php echo esc_html( $result->get_geocode_granularity() ); ?></td>
            </tr>
            <tr>
                <th><?php _e( 'Has Unconfirmed Components', 'arraypress-address-validation' ); ?></th>
                <td><?php echo $result->has_unconfirmed_components() ? 'Yes' : 'No'; ?></td>
            </tr>
            <tr>
                <th><?php _e( 'Has Inferred Components', 'arraypress-address-validation' ); ?></th>
                <td><?php echo $result->has_inferred_components() ? 'Yes' : 'No'; ?></td>
            </tr>
            <tr>
                <th><?php _e( 'Has Replaced Components', 'arraypress-address-validation' ); ?></th>
                <td><?php echo $result->has_replaced_components() ? 'Yes' : 'No'; ?></td>
            </tr>
            <tr>
                <th><?php _e( 'Geocode', 'arraypress-address-validation' ); ?></th>
                <td>
					<?php
					$geocode = $result->get_geocode();
					if ( $geocode ) {
						printf(
							__( 'Lat: %1$s, Lng: %2$s', 'arraypress-address-validation' ),
							esc_html( $geocode['latitude'] ),
							esc_html( $geocode['longitude'] )
						);
					} else {
						_e( 'N/A', 'arraypress-address-validation' );
					}
					?>
                </td>
            </tr>
            <tr>
                <th><?php _e( 'Place ID', 'arraypress-address-validation' ); ?></th>
                <td><?php echo esc_html( $result->get_place_id() ); ?></td>
            </tr>
            <tr>
                <th><?php _e( 'Address Components', 'arraypress-address-validation' ); ?></th>
                <td>
					<?php
					$address = $result->get_standardized_address();
					echo '<dl class="address-components">';
					foreach ( $address as $key => $value ) {
						if ( $value ) {
							$display_value = is_array( $value ) ? implode( ', ', $value ) : $value;
							printf(
								'<dt>%s:</dt><dd>%s</dd>',
								esc_html( ucwords( str_replace( '_', ' ', $key ) ) ),
								esc_html( $display_value )
							);
						}
					}
					echo '</dl>';
					?>
                </td>
            </tr>
			<?php if ( $result->get_usps_data() ): ?>
                <tr>
                    <th><?php _e( 'USPS Data', 'arraypress-address-validation' ); ?></th>
                    <td>
                        <dl class="address-components">
                            <dt><?php _e( 'Carrier Route', 'arraypress-address-validation' ); ?>:</dt>
                            <dd><?php echo esc_html( $result->get_carrier_route() ); ?></dd>

                            <dt><?php _e( 'Delivery Point Code', 'arraypress-address-validation' ); ?>:</dt>
                            <dd><?php echo esc_html( $result->get_delivery_point_code() ); ?></dd>

                            <dt><?php _e( 'DPV Confirmation', 'arraypress-address-validation' ); ?>:</dt>
                            <dd><?php echo esc_html( $result->get_dpv_confirmation() ); ?></dd>

                            <dt><?php _e( 'Is Commercial Mail Receiver', 'arraypress-address-validation' ); ?>:</dt>
                            <dd><?php echo $result->is_commercial_mail_receiver() ? 'Yes' : 'No'; ?></dd>

                            <dt><?php _e( 'Is Vacant', 'arraypress-address-validation' ); ?>:</dt>
                            <dd><?php echo $result->is_vacant() ? 'Yes' : 'No'; ?></dd>

                            <dt><?php _e( 'Is Active', 'arraypress-address-validation' ); ?>:</dt>
                            <dd><?php echo $result->is_active() ? 'Yes' : 'No'; ?></dd>
                        </dl>
                    </td>
                </tr>
			<?php endif; ?>
            </tbody>
        </table>
		<?php
	}

	/**
	 * Render test page
	 */
	public function render_test_page(): void {
		$results = $this->process_form_submissions();
		?>
        <div class="wrap address-validation-test">
            <h1><?php _e( 'Google Address Validation API Test', 'arraypress-address-validation' ); ?></h1>

			<?php settings_errors( 'address_validation_test' ); ?>

			<?php if ( empty( get_option( 'google_address_validation_api_key' ) ) ): ?>
                <!-- API Key Form -->
                <div class="notice notice-warning">
                    <p><?php _e( 'Please enter your Google Address Validation API key to begin testing.', 'arraypress-address-validation' ); ?></p>
                </div>
				<?php $this->render_settings_form(); ?>
			<?php else: ?>
                <!-- Test Forms -->
                <div class="address-validation-test-container">
                    <!-- Address Validation -->
                    <div class="address-validation-test-section">
                        <h2><?php _e( 'Address Validation', 'arraypress-address-validation' ); ?></h2>
                        <form method="post" class="validation-form">
							<?php wp_nonce_field( 'address_validation_test' ); ?>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="address"><?php _e( 'Address', 'arraypress-address-validation' ); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" name="address" id="address" class="regular-text"
                                               value="1600 Amphitheatre Parkway, Mountain View, CA"
                                               placeholder="<?php esc_attr_e( 'Enter address...', 'arraypress-address-validation' ); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="enable_usps"><?php _e( 'USPS Data', 'arraypress-address-validation' ); ?></label>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="enable_usps" id="enable_usps" value="1">
											<?php _e( 'Enable USPS data validation (US addresses only)', 'arraypress-address-validation' ); ?>
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="language_code"><?php _e( 'Language', 'arraypress-address-validation' ); ?></label>
                                    </th>
                                    <td>
                                        <select name="language_code" id="language_code" class="regular-text">
                                            <option value=""><?php _e( 'Default', 'arraypress-address-validation' ); ?></option>
                                            <option value="en">English</option>
                                            <option value="fr">French</option>
                                            <option value="de">German</option>
                                            <option value="es">Spanish</option>
                                            <option value="it">Italian</option>
                                            <option value="pt">Portuguese</option>
                                            <option value="nl">Dutch</option>
                                            <option value="pl">Polish</option>
                                            <option value="ja">Japanese</option>
                                            <option value="ko">Korean</option>
                                            <option value="zh">Chinese</option>
                                        </select>
                                        <p class="description">
											<?php _e( 'Select preferred language for address formatting', 'arraypress-address-validation' ); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
							<?php submit_button( __( 'Validate Address', 'arraypress-address-validation' ), 'primary', 'submit_validation' ); ?>
                        </form>

						<?php if ( $results['validation'] ): ?>
                            <h3><?php _e( 'Validation Results', 'arraypress-address-validation' ); ?></h3>
							<?php $this->render_validation_details( $results['validation'] ); ?>
						<?php endif; ?>
                    </div>
                </div>

                <!-- Cache Management -->
                <div class="address-validation-test-section">
                    <h2><?php _e( 'Cache Management', 'arraypress-address-validation' ); ?></h2>
                    <form method="post" class="validation-form">
						<?php wp_nonce_field( 'address_validation_test' ); ?>
                        <p class="description">
							<?php _e( 'Clear the cached address validation results. This will force new API requests for subsequent lookups.', 'arraypress-address-validation' ); ?>
                        </p>
						<?php submit_button( __( 'Clear Cache', 'arraypress-address-validation' ), 'delete', 'clear_cache' ); ?>
                    </form>
                </div>

                <!-- Settings -->
                <div class="address-validation-test-section">
					<?php $this->render_settings_form(); ?>
                </div>
			<?php endif; ?>
        </div>
		<?php
	}

}

new Plugin();