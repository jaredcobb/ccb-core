<?php
/**
 * Communicate with the CCB API
 *
 * @link       https://www.wpccb.com
 * @since      1.0.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 */

/**
 * Makes GET and POST requests to the CCB API and returns a
 * standardized response object.
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_API {

	/**
	 * Whether or not the API is ready for use
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      bool    $initialized
	 */
	public $initialized = false;

	/**
	 * The subdomain of the ccb church installation
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $subdomain
	 */
	protected $subdomain;

	/**
	 * The ccb api username
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $username
	 */
	protected $username;

	/**
	 * The ccb api password
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $password
	 */
	protected $password;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		// Wait to initialize the API credentials until after WordPress
		// has loaded pluggable.php because we are using some WordPress helper functions.
		add_action( 'plugins_loaded', [ $this, 'initialize_credentials' ] );
	}

	/**
	 * Once the plugin is loaded, initialize the credentials
	 *
	 * @return void
	 */
	public function initialize_credentials() {
		$settings = CCB_Core_Helpers::instance()->get_options();

		if (
			! empty( $settings['subdomain'] )
			&& ! empty( $settings['credentials']['username'] )
			&& ! empty( $settings['credentials']['password'] )
		) {
			$this->subdomain = $settings['subdomain'];
			$this->username = $settings['credentials']['username'];
			$this->password = CCB_Core_Helpers::instance()->decrypt( $settings['credentials']['password'] );
			if ( ! empty( $this->password ) && ! is_wp_error( $this->password ) ) {
				$this->initialized = true;
			}
		}
	}

	/**
	 * Sends a GET request to the CCB API
	 *
	 * @param   string $service The service to request.
	 * @param   array  $data Optional parameters to send with the request.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @return   array The API response data.
	 */
	public function get( $service, $data = array() ) {
		// Get the API response for the service.
		return $this->request( 'GET', $service, $data );
	}

	/**
	 * Sends a POST request to the CCB API
	 *
	 * @param   string $service The service to request.
	 * @param   array  $data Optional parameters to send with the request.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @return   array The API response data.
	 */
	public function post( $service, $data = array() ) {
		// Get the API response for the service.
		return $this->request( 'POST', $service, $data );
	}

	/**
	 * Executes a request against the Optimizely X API.
	 *
	 * @param   string $method GET or POST.
	 * @param   string $service The API service to execute against.
	 * @param   array  $data An optional array of data to include with the request.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   array
	 */
	private function request( $method, $service, $data = array() ) {

		if ( ! $this->initialized ) {
			return array(
				'code' => 401,
				'error' => esc_html__( 'You are missing a subdomain, username, or password in the settings.', 'ccb-core' ),
				'status' => 'ERROR',
			);
		}

		$url = esc_url_raw( sprintf( 'https://%s.ccbchurch.com/api.php?srv=%s', $this->subdomain, $service ) );
		if ( empty( $url ) ) {
			return array(
				'code' => 404,
				'error' => esc_html__( 'Invalid API URL.', 'ccb-core' ),
				'status' => 'ERROR',
			);
		}

		// Add authentication header to the request object.
		$request = array(
			'timeout' => 60,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode(
					sprintf(
						'%s:%s',
						sanitize_text_field( $this->username ),
						sanitize_text_field( $this->password )
					)
				),
			),
		);

		if ( 'POST' === $method ) {
			$request['body'] = $data;
		} elseif ( 'GET' === $method && ! empty( $data ) ) {
			$url .= '&' . http_build_query( $data );
		}

		switch ( $method ) {
			case 'GET':
				$response = wp_safe_remote_get( $url, $request );
				break;
			case 'POST':
				$response = wp_safe_remote_post( $url, $request );
				break;
			default:
				return array(
					'code' => 403,
					'error' => esc_html__( 'Invalid request method.', 'ccb-core' ),
					'status' => 'ERROR',
				);
		}

		// Check for WordPress HTTP errors.
		if ( is_wp_error( $response ) ) {
			return [
				'code' => 500,
				'error' => esc_html( $response->get_error_message() ),
				'status' => 'ERROR',
			];
		}

		// Build result object.
		$result = array(
			'xml' => wp_remote_retrieve_body( $response ),
			'code' => absint( wp_remote_retrieve_response_code( $response ) ),
			'headers' => wp_remote_retrieve_headers( $response ),
		);

		// Verify there are no HTTP errors.
		if ( empty( $response )
			|| $result['code'] < 200
			|| $result['code'] > 204
		) {
			$result['status'] = 'ERROR';
			$result['error'] = esc_html( sprintf( __( 'The API returned an empty response or an error code: %s', 'ccb-core' ), $result['code'] ) );
			return $result;
		}

		try {
			libxml_use_internal_errors( true );
			$parsed_response = simplexml_load_string( $result['xml'] );
			if ( false === $parsed_response ) {
				$result['error'] = esc_html__( 'Could not parse the XML response', 'ccb-core' );
				$result['status'] = 'ERROR';
			} else {

				$result['body'] = $parsed_response;

				// We successfully parsed the XML response, however the
				// response may contain error messages from CCB.
				if ( isset( $parsed_response->response->errors->error ) ) {
					$result['error'] = esc_html( sprintf(
						__( 'The CCB API replied with an error: %s', 'ccb-core' ),
						$parsed_response->response->errors->error
					) );
					$result['status'] = 'ERROR';
				} else {
					$result['status'] = 'SUCCESS';
				}

			}
		} catch ( Exception $ex ) {
			$result['error'] = esc_html( sprintf( __( 'Could not parse the XML response: %s', 'ccb-core' ), $ex->getMessage() ) );
			$result['status'] = 'ERROR';
		}

		return $result;
	}

}
