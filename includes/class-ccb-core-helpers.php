<?php
/**
 * Static class for all plugin files to access
 *
 * @link       https://www.wpccb.com
 * @since      1.0.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 */

/**
 * Used to store helpful properties and
 * define some helpful utility methods
 *
 * @since      1.0.0
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_Helpers {

	const SYNC_STATUS_KEY = 'ccb_core_sync_in_progress';

	/**
	 * Instance of the Helper class
	 *
	 * @var      CCB_Core_Helpers
	 * @access   private
	 * @static
	 */
	private static $instance;

	/**
	 * The options set by the user
	 *
	 * @var   array
	 */
	private $plugin_options = [];

	/**
	 * Unused constructor in the singleton pattern
	 *
	 * @access   public
	 * @return   void
	 */
	public function __construct() {
		// Initialize this class with the instance() method.
	}

	/**
	 * Returns the instance of the class
	 *
	 * @access   public
	 * @static
	 * @return   CCB_Core_Helpers
	 */
	public static function instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new CCB_Core_Helpers();
			static::$instance->setup();
		}
		return static::$instance;
	}

	/**
	 * Initial setup of the singleton
	 *
	 * @access   private
	 * @return   void
	 */
	private function setup() {
		// Get any options the user may have set.
		$this->plugin_options = get_option( 'ccb_core_settings' );
		// Ensure we refresh this singleton's options whenever the options
		// get updated (so that other callbacks have accurate values).
		add_action( 'update_option_ccb_core_settings', [ $this, 'refresh_options' ], 5, 2 );
	}

	/**
	 * Get any options stored by the user
	 *
	 * @return   array
	 */
	public function get_options() {
		return $this->plugin_options;
	}

	/**
	 * Callback method to detect when the settings have changed.
	 *
	 * Ensures that this singleton's `get_options()` method always
	 * returns accurate settings based on the latest changes.
	 *
	 * @param    array $old_value The old settings array.
	 * @param    array $new_value The new settings array.
	 * @return   void
	 */
	public function refresh_options( $old_value, $new_value ) {
		$this->plugin_options = $new_value;
	}

	/**
	 * Encrypts and base64_encodes a string safe for serialization in WordPress
	 *
	 * @since    1.0.0
	 * @access   public
	 * @param    string $data The data to be encrypted.
	 * @return   string
	 */
	public function encrypt( $data ) {
		if ( ! function_exists( 'sodium_crypto_secretbox' ) ) {
			return $this->legacy_encrypt( $data );
		}

		$encrypted_value = false;
		if ( ! empty( $data ) ) {
			try {
				// Create a one-time random nonce and salt.
				$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
				$salt = random_bytes( SODIUM_CRYPTO_PWHASH_SALTBYTES );
				// Create a unique key that is seeded from the site's AUTH_KEY constant.
				$key = sodium_crypto_pwhash(
					SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
					AUTH_KEY,
					$salt,
					SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
					SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
				);
				// Encrypt the data with the nonce and salt prepended so that we can
				// use them to decrypt the value later.
				$encrypted_value = base64_encode( $nonce . $salt . sodium_crypto_secretbox( $data, $nonce, $key ) );
			} catch ( Exception $ex ) {
				return new WP_Error( 'encrypt_failure', __( 'The string could not be encrypted via Sodium', 'ccb-core' ) );
			}

		}

		return $encrypted_value;
	}

	/**
	 * Encrypts and base64_encodes a string safe for serialization in WordPress
	 * when the Sodium functions are not available (typically below PHP 7.2)
	 *
	 * @param string $data The data to be encrypted.
	 *
	 * @return string
	 */
	private function legacy_encrypt( $data ) {
		$encrypted_value = false;
		$key = hash_hmac( 'sha512', AUTH_SALT, AUTH_KEY );

		if ( ! empty( $data ) ) {
			try {
				$e = new CCB_Core_Vendor_Encryption( MCRYPT_BlOWFISH, MCRYPT_MODE_CBC );
				$encrypted_value = base64_encode( $e->encrypt( $data, $key ) );
			} catch ( Exception $ex ) {
				return new WP_Error( 'encrypt_failure', __( 'The string could not be encrypted', 'ccb-core' ) );
			}

		}

		return $encrypted_value;
	}

	/**
	 * Decrypts and base64_decodes a string
	 *
	 * @since    1.0.0
	 * @access   public
	 * @param    string $data The data to be decrypted.
	 * @return   string
	 */
	public function decrypt( $data ) {
		if ( ! function_exists( 'sodium_crypto_secretbox_open' ) ) {
			return $this->legacy_decrypt( $data );
		}

		$decrypted_value = false;
		if ( ! empty( $data ) ) {
			try {
				// Decode the stored value.
				$decoded_data = base64_decode( $data );
				// Get the stored nonce from the beginning of the multibyte string.
				$nonce = mb_substr(
					$decoded_data,
					0,
					SODIUM_CRYPTO_SECRETBOX_NONCEBYTES,
					'8bit'
				);
				// Get the stored salt from the middle of the multibyte string.
				$salt = mb_substr(
					$decoded_data,
					SODIUM_CRYPTO_SECRETBOX_NONCEBYTES,
					SODIUM_CRYPTO_PWHASH_SALTBYTES,
					'8bit'
				);
				// Get the encrypted data from the end of the multibyte string.
				$cipher = mb_substr(
					$decoded_data,
					SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_PWHASH_SALTBYTES,
					null,
					'8bit'
				);
				// Generate the known key from the stored salt and site's AUTH_KEY constant.
				$key = sodium_crypto_pwhash(
					SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
					AUTH_KEY,
					$salt,
					SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
					SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
				);
				// Decrypt the data using the stored nonce.
				$decrypted_value = sodium_crypto_secretbox_open( $cipher, $nonce, $key );
			} catch ( Exception $ex ) {
				return new WP_Error( 'decrypt_failure', __( 'The string could not be decrypted', 'ccb-core' ) );
			}

		}

		return $decrypted_value;
	}

	/**
	 * Decrypts and base64_decodes a string when the Sodium functions
	 * are not available (typically below PHP 7.2)
	 *
	 * @since    1.0.0
	 * @access   public
	 * @param    string $data The data to be decrypted.
	 * @return   string
	 */
	public function legacy_decrypt( $data ) {

		$decrypted_value = false;
		$key = hash_hmac( 'sha512', AUTH_SALT, AUTH_KEY );

		if ( ! empty( $data ) ) {
			try {
				$e = new CCB_Core_Vendor_Encryption( MCRYPT_BlOWFISH, MCRYPT_MODE_CBC );
				$decrypted_value = $e->decrypt( base64_decode( $data ), $key );
			} catch ( Exception $ex ) {
				return new WP_Error( 'decrypt_failure', __( 'The string could not be decrypted', 'ccb-core' ) );
			}

		}

		return $decrypted_value;
	}

	/**
	 * Responds to the client with a json response
	 * but allows the script to continue
	 *
	 * @access   public
	 * @since    1.0.0
	 * @param    array $data Optional data to send back.
	 * @return   bool
	 */
	public function send_non_blocking_json_success( $data = [] ) {

		ignore_user_abort( true );
		ob_start();

		header( 'Content-Type: application/json' );
		header( 'Content-Encoding: none' );

		echo wp_json_encode(
			[
				'success' => true,
				'data' => $data,
			]
		);

		header( 'Connection: close' );
		header( 'Content-Length: ' . ob_get_length() );

		ob_end_flush();
		ob_flush();
		flush();

		// Some environments may be running PHP-FPM.
		if ( function_exists( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request();
		}

		return true;

	}

	/**
	 * Downloads an image from a URL, uploads it to the Media Library,
	 * and then optionally attaches it to a post.
	 *
	 * We are using this custom function instead of media_sideload_image
	 * because images with dynamic URLs (like those on S3) do not have
	 * file extensions and core ticket #18730 will never be resolved.
	 * https://core.trac.wordpress.org/ticket/18730
	 *
	 * @param    string $image_url The URL of the image.
	 * @param    string $filename Optional.
	 * @param    int    $post_id Optional.
	 * @access   public
	 * @return   mixed  Returns a media id or false on failure
	 */
	public function download_image( $image_url, $filename = '', $post_id = 0 ) {

		// When in a WP Cron context these helper functions may not be loaded.
		if ( ! function_exists( 'download_url' ) ) {
			include_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! function_exists( 'wp_read_image_metadata' ) ) {
			include_once ABSPATH . 'wp-admin/includes/image.php';
		}
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			include_once ABSPATH . 'wp-admin/includes/media.php';
		}

		// Fetch the image and store temporarily.
		$temp_file = download_url( $image_url );

		if ( is_wp_error( $temp_file ) ) {
			return false;
		}

		// Attempt to detect the mimetype based on the available functions.
		$extension = false;
		if ( function_exists( 'exif_imagetype' ) && function_exists( 'image_type_to_extension' ) ) {
			// Open with exif.
			$image_type = exif_imagetype( $temp_file );
			if ( $image_type ) {
				$extension = image_type_to_extension( $image_type );
			}
		} elseif ( function_exists( 'getimagesize' ) && function_exists( 'image_type_to_extension' ) ) {
			// Open with gd.
			$file_size = getimagesize( $temp_file );
			if ( isset( $file_size[2] ) ) {
				$extension = image_type_to_extension( $file_size[2] );
			}
		} elseif ( function_exists( 'finfo_open' ) ) {
			// Open with fileinfo.
			$resource = finfo_open( FILEINFO_MIME_TYPE );
			$mimetype = finfo_file( $resource, $temp_file );
			finfo_close( $resource );
			if ( $mimetype ) {
				$mimetype_array = explode( '/', $mimetype );
				$extension = '.' . $mimetype_array[1];
			}
		}

		// If we were able to determine the extension, move it
		// to the Media Library.
		if ( $extension ) {

			$filename = ! empty( $filename ) ? sanitize_file_name( $filename ) : 'ccb_' . crc32( $image_url );

			$file_array = [
				'name' => $filename . $extension,
				'tmp_name' => $temp_file,
			];

			add_filter( 'upload_dir', [ $this, 'custom_uploads_directory' ] );
			$media_id = media_handle_sideload( $file_array, $post_id );
			remove_filter( 'upload_dir', [ $this, 'custom_uploads_directory' ] );

			// phpcs:ignore
			@unlink( $temp_file );

			if ( ! is_wp_error( $media_id ) ) {
				// Also attach the media to the post if a post id exists.
				if ( $post_id ) {
					set_post_thumbnail( $post_id, $media_id );
				}

				// Set post meta on the image to allow anyone to
				// query for ccb specific images in the future.
				update_post_meta( $media_id, 'ccb_core', true );

				return $media_id;
			}

		}

		return false;
	}

	/**
	 * Override the default uploads directory location
	 * for CCB images. Allows for a convenient was to
	 * isolate the CCB uploads so they are not mixed in
	 * with other media assets.
	 *
	 * @param    array $upload An array of upload paths.
	 * @return   array
	 */
	public function custom_uploads_directory( $upload ) {
		/**
		 * Allow for the ability to enable / disable custom upload path.
		 *
		 * @since 1.0.0
		 *
		 * @param   bool $allowed Whether this plugin is allowed to use custom upload paths.
		 */
		if ( apply_filters( 'ccb_core_allow_custom_uploads_directory', true ) ) {
			$upload['path'] = trailingslashit( $upload['basedir'] ) . 'ccb';
			$upload['url'] = $upload['baseurl'] . '/ccb';
			$upload['subdir'] = '/ccb';
		}
		return $upload;
	}

}
