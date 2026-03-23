<?php

namespace Vendidero\StoreaBill;

use Vendidero\StoreaBill\Document\Attachment;
use Vendidero\StoreaBill\Document\Document;

defined( 'ABSPATH' ) || exit;

class DownloadManager {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		if ( isset( $_GET['sab-document'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			add_action( 'init', array( __CLASS__, 'download_document' ) );
		}

		if ( isset( $_GET['action'], $_GET['object_type'], $_GET['bulk_action'], $_GET['_wpnonce'] ) && 'sab-download-bulk-documents' === $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			add_action( 'admin_init', array( __CLASS__, 'download_bulk_documents' ) );
		}

		add_action( 'storeabill_download_file_redirect', array( __CLASS__, 'download_file_redirect' ), 10, 2 );
		add_action( 'storeabill_download_file_xsendfile', array( __CLASS__, 'download_file_xsendfile' ), 10, 2 );
		add_action( 'storeabill_download_file_force', array( __CLASS__, 'download_file_force' ), 10, 2 );
		add_action( 'storeabill_download_file_inline', array( __CLASS__, 'download_file_inline' ), 10, 2 );
	}

	public static function download_bulk_documents() {
		$object_type = sab_clean( wp_unslash( $_GET['object_type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$bulk_action = sab_clean( wp_unslash( $_GET['bulk_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated

		if ( empty( $object_type ) ) {
			self::download_error( _x( 'Invalid object type.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		if ( ! wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'sab-download-bulk-documents' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			self::download_error( _x( 'Invalid download url.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		if ( ! $bulk_handler = \Vendidero\StoreaBill\Admin\Admin::get_bulk_action_handler( $bulk_action, $object_type ) ) {
			self::download_error( _x( 'Invalid bulk action.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		if ( ! is_a( $bulk_handler, '\Vendidero\StoreaBill\Document\BulkMerge' ) ) {
			self::download_error( _x( 'Invalid bulk action.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		if ( ! $bulk_handler->can_download() ) {
			self::download_error( _x( 'You are not allowed to view that file.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		$download_method = isset( $_GET['force'] ) && sab_string_to_bool( sab_clean( wp_unslash( $_GET['force'] ) ) ) ? 'force' : 'inline';
		$file            = $bulk_handler->get_file();

		if ( ! $file || ! file_exists( $file ) ) {
			self::download_error( _x( 'Sorry but the file does not exist any longer.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		// Add action to prevent issues in IE.
		add_action( 'nocache_headers', array( __CLASS__, 'ie_nocache_headers_fix' ) );

		// Trigger download via one of the methods.
		do_action( 'storeabill_download_file_' . $download_method, $file, 'bulk.pdf' );
	}

	/**
	 * Check if we need to download a file and check validity.
	 */
	public static function download_document() {
		$document_id = absint( $_GET['sab-document'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.NonceVerification.Recommended

		if ( empty( $document_id ) ) {
			self::download_error( _x( 'Invalid document.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		$document        = sab_get_document( $document_id );
		$download_method = isset( $_GET['force'] ) && sab_string_to_bool( sab_clean( wp_unslash( $_GET['force'] ) ) ) ? 'force' : 'inline'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$attachment_id   = isset( $_GET['sab-attachment'] ) ? absint( $_GET['sab-attachment'] ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$secret          = isset( $_GET['key'] ) ? sab_clean( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$attachment_type = isset( $_GET['sab-attachment-type'] ) ? sab_clean( wp_unslash( $_GET['sab-attachment-type'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_primary      = isset( $_GET['is-primary'] ) && sab_string_to_bool( sab_clean( wp_unslash( $_GET['is-primary'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $document ) {
			self::download_error( _x( 'Invalid document.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		$can_download = current_user_can( 'view_' . $document->get_type(), $document->get_id() ) || current_user_can( 'read_' . $document->get_type(), $document->get_id() );

		if ( ! $can_download && ! empty( $secret ) && $document->supports_download_links() ) {
			$can_download = $document->secret_is_valid( $secret );
		}

		$attachment = $attachment_id ? $document->get_attachment( $attachment_id ) : false;

		/**
		 * Retrieve the first attachment by type.
		 */
		if ( ! empty( $attachment_type ) && array_key_exists( $attachment_type, sab_get_document_attachment_types( $document->get_type() ) ) ) {
			$attachments = array_values( $document->get_attachments( $attachment_type ) );

			if ( ! empty( $attachments ) ) {
				$attachment = $attachments[0];
			}
		}

		/**
		 * By default, do only allow admins to download attachments + non-primary files.
		 */
		if ( $attachment || ! $is_primary ) {
			if ( $attachment ) {
				$can_download = apply_filters( 'storeabill_allow_customer_document_attachment_download', current_user_can( 'manage_storeabill' ), $attachment, $document );
			} else {
				$can_download = apply_filters( 'storeabill_allow_customer_non_primary_download', current_user_can( 'manage_storeabill' ), $document );
			}
		}

		if ( $can_download ) {
			if ( $attachment ) {
				self::download_attachment( $attachment->get_path(), $attachment, $download_method );
			} else {
				self::download( $is_primary ? $document->get_primary_path( 'download' ) : $document->get_path(), $document, $download_method, $is_primary );
			}
		} else {
			self::download_error( _x( 'Invalid document.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}
	}

	/**
	 * Download a file - hook into init function.
	 *
	 * @param string  $file_path  URL to file.
	 * @param Document $document The document.
	 */
	public static function download( $file_path, $document, $download_method = 'inline', $is_primary = true ) {
		if ( ! $file_path ) {
			self::download_error( _x( 'No file defined', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		$filename             = apply_filters( 'storeabill_document_download_filename', $is_primary ? $document->get_primary_filename( 'download' ) : $document->get_filename(), $document->get_id() );
		$file_download_method = apply_filters( 'storeabill_document_download_method', $download_method, $document->get_id() );

		// Add action to prevent issues in IE.
		add_action( 'nocache_headers', array( __CLASS__, 'ie_nocache_headers_fix' ) );

		// Trigger download via one of the methods.
		do_action( 'storeabill_download_file_' . $file_download_method, $file_path, $filename );
	}

	/**
	 * Download a file - hook into init function.
	 *
	 * @param string  $file_path  URL to file.
	 * @param Attachment $attachment The attachment.
	 */
	public static function download_attachment( $file_path, $attachment, $download_method = 'inline' ) {
		if ( ! $file_path ) {
			self::download_error( _x( 'No file defined', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		$filename             = apply_filters( 'storeabill_document_attachment_download_filename', $attachment->get_filename(), $attachment->get_id() );
		$file_download_method = apply_filters( 'storeabill_document_attachment_download_method', $download_method, $attachment->get_id() );

		// Add action to prevent issues in IE.
		add_action( 'nocache_headers', array( __CLASS__, 'ie_nocache_headers_fix' ) );

		// Trigger download via one of the methods.
		do_action( 'storeabill_download_file_' . $file_download_method, $file_path, $filename );
	}

	/**
	 * Redirect to a file to start the download.
	 *
	 * @param string $file_path File path.
	 * @param string $filename  File name.
	 */
	public static function download_file_redirect( $file_path, $filename = '' ) {
		header( 'Location: ' . $file_path );
		exit;
	}

	/**
	 * Parse file path and see if its remote or local.
	 *
	 * @param  string $file_path File path.
	 * @return array
	 */
	public static function parse_file_path( $file_path ) {
		$wp_uploads     = UploadManager::get_upload_dir();
		$wp_uploads_dir = $wp_uploads['basedir'];
		$wp_uploads_url = $wp_uploads['baseurl'];

		/**
		 * Replace uploads dir, site url etc with absolute counterparts if we can.
		 * Note the str_replace on site_url is on purpose, so if https is forced
		 * via filters we can still do the string replacement on a HTTP file.
		 */
		$replacements = array(
			$wp_uploads_url                  => $wp_uploads_dir,
			network_site_url( '/', 'https' ) => ABSPATH,
			str_replace( 'https:', 'http:', network_site_url( '/', 'http' ) ) => ABSPATH,
			site_url( '/', 'https' )         => ABSPATH,
			str_replace( 'https:', 'http:', site_url( '/', 'http' ) ) => ABSPATH,
		);

		$count            = 0;
		$file_path        = str_replace( array_keys( $replacements ), array_values( $replacements ), $file_path, $count );
		$parsed_file_path = wp_parse_url( $file_path );
		$remote_file      = null === $count || 0 === $count; // Remote file only if there were no replacements.

		// Paths that begin with '//' are always remote URLs.
		if ( '//' === substr( $file_path, 0, 2 ) ) {
			return array(
				'remote_file' => true,
				'file_path'   => is_ssl() ? 'https:' . $file_path : 'http:' . $file_path,
			);
		}

		// See if path needs an abspath prepended to work.
		if ( file_exists( ABSPATH . $file_path ) ) {
			$remote_file = false;
			$file_path   = ABSPATH . $file_path;

		} elseif ( '/wp-content' === substr( $file_path, 0, 11 ) ) {
			$remote_file = false;
			$file_path   = realpath( WP_CONTENT_DIR . substr( $file_path, 11 ) );

			// Check if we have an absolute path.
		} elseif ( ( ! isset( $parsed_file_path['scheme'] ) || ! in_array( $parsed_file_path['scheme'], array( 'http', 'https', 'ftp' ), true ) ) && isset( $parsed_file_path['path'] ) && file_exists( $parsed_file_path['path'] ) ) {
			$remote_file = false;
			$file_path   = $parsed_file_path['path'];
		}

		return array(
			'remote_file' => $remote_file,
			'file_path'   => $file_path,
		);
	}

	/**
	 * Download a file using X-Sendfile, X-Lighttpd-Sendfile, or X-Accel-Redirect if available.
	 *
	 * @param string $file_path File path.
	 * @param string $filename  File name.
	 */
	public static function download_file_xsendfile( $file_path, $filename ) {
		$parsed_file_path = self::parse_file_path( $file_path );

		/**
		 * Fallback on force download method for remote files. This is because:
		 * 1. xsendfile needs proxy configuration to work for remote files, which cannot be assumed to be available on most hosts.
		 * 2. Force download method is more secure than redirect method if `allow_url_fopen` is enabled in `php.ini`. We fallback to redirect method in force download method anyway in case `allow_url_fopen` is not enabled.
		 */
		if ( $parsed_file_path['remote_file'] && ! apply_filters( 'storeabill_use_xsendfile_for_remote', false ) ) {
			do_action( 'storeabill_download_file_force', $file_path, $filename );
			return;
		}

		if ( function_exists( 'apache_get_modules' ) && in_array( 'mod_xsendfile', apache_get_modules(), true ) ) {
			self::download_headers( $parsed_file_path['file_path'], $filename );
			$filepath = apply_filters( 'storeabill_download_file_xsendfile_file_path', $parsed_file_path['file_path'], $file_path, $filename, $parsed_file_path );
			header( 'X-Sendfile: ' . $filepath );
			exit;
		} elseif ( stristr( getenv( 'SERVER_SOFTWARE' ), 'lighttpd' ) ) {
			self::download_headers( $parsed_file_path['file_path'], $filename );
			$filepath = apply_filters( 'storeabill_download_file_xsendfile_lighttpd_file_path', $parsed_file_path['file_path'], $file_path, $filename, $parsed_file_path );
			header( 'X-Lighttpd-Sendfile: ' . $filepath );
			exit;
		} elseif ( stristr( getenv( 'SERVER_SOFTWARE' ), 'nginx' ) || stristr( getenv( 'SERVER_SOFTWARE' ), 'cherokee' ) ) {
			self::download_headers( $parsed_file_path['file_path'], $filename );
			$xsendfile_path = trim( preg_replace( '`^' . str_replace( '\\', '/', getcwd() ) . '`', '', $parsed_file_path['file_path'] ), '/' );
			$xsendfile_path = apply_filters( 'storeabill_download_file_xsendfile_x_accel_redirect_file_path', $xsendfile_path, $file_path, $filename, $parsed_file_path );
			header( "X-Accel-Redirect: /$xsendfile_path" );
			exit;
		}

		// Fallback.
		self::download_file_force( $file_path, $filename );
	}

	/**
	 * Parse the HTTP_RANGE request from iOS devices.
	 * Does not support multi-range requests.
	 *
	 * @param int $file_size Size of file in bytes.
	 * @return array {
	 *     Information about range download request: beginning and length of
	 *     file chunk, whether the range is valid/supported and whether the request is a range request.
	 *
	 *     @type int  $start            Byte offset of the beginning of the range. Default 0.
	 *     @type int  $length           Length of the requested file chunk in bytes. Optional.
	 *     @type bool $is_range_valid   Whether the requested range is a valid and supported range.
	 *     @type bool $is_range_request Whether the request is a range request.
	 * }
	 */
	protected static function get_download_range( $file_size ) {
		$start          = 0;
		$download_range = array(
			'start'            => $start,
			'is_range_valid'   => false,
			'is_range_request' => false,
		);

		if ( ! $file_size ) {
			return $download_range;
		}

		$end                      = $file_size - 1;
		$download_range['length'] = $file_size;

		if ( isset( $_SERVER['HTTP_RANGE'] ) ) { // @codingStandardsIgnoreLine.
			$http_range                         = sanitize_text_field( wp_unslash( $_SERVER['HTTP_RANGE'] ) ); // WPCS: input var ok.
			$download_range['is_range_request'] = true;

			$c_start = $start;
			$c_end   = $end;
			// Extract the range string.
			list( , $range ) = explode( '=', $http_range, 2 );
			// Make sure the client hasn't sent us a multibyte range.
			if ( strpos( $range, ',' ) !== false ) {
				return $download_range;
			}

			/*
			 * If the range starts with an '-' we start from the beginning.
			 * If not, we forward the file pointer
			 * and make sure to get the end byte if specified.
			 */
			if ( '-' === $range[0] ) {
				// The n-number of the last bytes is requested.
				$c_start = $file_size - substr( $range, 1 );
			} else {
				$range   = explode( '-', $range );
				$c_start = ( isset( $range[0] ) && is_numeric( $range[0] ) ) ? (int) $range[0] : 0;
				$c_end   = ( isset( $range[1] ) && is_numeric( $range[1] ) ) ? (int) $range[1] : $file_size;
			}

			/*
			 * Check the range and make sure it's treated according to the specs: http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html.
			 * End bytes can not be larger than $end.
			 */
			$c_end = ( $c_end > $end ) ? $end : $c_end;
			// Validate the requested range and return an error if it's not correct.
			if ( $c_start > $c_end || $c_start > $file_size - 1 || $c_end >= $file_size ) {
				return $download_range;
			}
			$start  = $c_start;
			$end    = $c_end;
			$length = $end - $start + 1;

			$download_range['start']          = $start;
			$download_range['length']         = $length;
			$download_range['is_range_valid'] = true;
		}
		return $download_range;
	}

	/**
	 * Force download.
	 *
	 * @param string $file_path File path.
	 * @param string $filename  File name.
	 */
	public static function download_file_force( $file_path, $filename, $download_type = 'attachment' ) {
		$parsed_file_path = self::parse_file_path( $file_path );
		$download_range   = self::get_download_range( @filesize( $parsed_file_path['file_path'] ) ); // @codingStandardsIgnoreLine.

		self::download_headers( $parsed_file_path['file_path'], $filename, $download_range, $download_type );

		$start  = isset( $download_range['start'] ) ? $download_range['start'] : 0;
		$length = isset( $download_range['length'] ) ? $download_range['length'] : 0;
		if ( ! self::readfile_chunked( $parsed_file_path['file_path'], $start, $length ) ) {
			if ( $parsed_file_path['remote_file'] ) {
				self::download_file_redirect( $file_path );
			} else {
				self::download_error( _x( 'File not found', 'storeabill-core', 'woocommerce-germanized-pro' ) );
			}
		}

		exit;
	}

	public static function is_file( $maybe_file_path ) {
		try {
			return @is_file( $maybe_file_path ) || wp_http_validate_url( $maybe_file_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		} catch ( \TypeError $e ) {
			return false;
		} catch ( \ValueError $e ) {
			return false;
		}
	}

	/**
	 * Stream.
	 *
	 * @param string $file_path File path.
	 * @param string $filename  File name.
	 */
	public static function stream_file( $stream, $filename, $download_type = 'inline' ) {
		sab_clean_buffers();
		self::download_headers( $stream, $filename, array(), $download_type );

		echo $stream; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Inline download - this is the default method.
	 *
	 * @param string $file_path File path.
	 * @param string $filename  File name.
	 */
	public static function download_file_inline( $file_path, $filename ) {
		self::download_file_force( $file_path, $filename, 'inline' );
	}

	/**
	 * Get content type of a download.
	 *
	 * @param  string $file_path File path.
	 * @return string
	 */
	private static function get_download_content_type( $file_path ) {
		$file_extension = strtolower( substr( strrchr( $file_path, '.' ), 1 ) );
		$ctype          = 'application/force-download';

		foreach ( get_allowed_mime_types() as $mime => $type ) {
			$mimes = explode( '|', $mime );
			if ( in_array( $file_extension, $mimes, true ) ) {
				$ctype = $type;
				break;
			}
		}

		return $ctype;
	}

	/**
	 * Set headers for the download.
	 *
	 * @param string $file_path      File path.
	 * @param string $filename       File name.
	 * @param array  $download_range Array containing info about range download request (see {@see get_download_range} for structure).
	 */
	private static function download_headers( $file_path, $filename, $download_range = array(), $download_type = 'attachment' ) {
		self::check_server_config();
		self::clean_buffers();
		wc_nocache_headers();

		if ( self::is_file( $file_path ) ) {
			$content_type = self::get_download_content_type( $file_path );
			$file_size    = @filesize( $file_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		} else {
			$content_type = self::get_download_content_type( $filename );
			$file_size    = strlen( $file_path );
		}

		if ( 'inline' === $download_type && in_array( $content_type, array( 'application/pdf' ), true ) ) {
			$download_type = 'inline';
		} else {
			$download_type = 'attachment';
		}

		header( 'X-Robots-Tag: noindex, nofollow', true );
		header( 'Content-Type: ' . $content_type );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: ' . $download_type . '; filename="' . $filename . '";' );
		header( 'Content-Transfer-Encoding: binary' );

		if ( ! $file_size ) {
			return;
		}

		if ( isset( $download_range['is_range_request'] ) && true === $download_range['is_range_request'] ) {
			if ( false === $download_range['is_range_valid'] ) {
				header( 'HTTP/1.1 416 Requested Range Not Satisfiable' );
				header( 'Content-Range: bytes 0-' . ( $file_size - 1 ) . '/' . $file_size );
				exit;
			}

			$start  = $download_range['start'];
			$end    = $download_range['start'] + $download_range['length'] - 1;
			$length = $download_range['length'];

			header( 'HTTP/1.1 206 Partial Content' );
			header( "Accept-Ranges: 0-$file_size" );
			header( "Content-Range: bytes $start-$end/$file_size" );
			header( "Content-Length: $length" );
		} else {
			header( 'Content-Length: ' . $file_size );
		}
	}

	/**
	 * Check and set certain server config variables to ensure downloads work as intended.
	 */
	private static function check_server_config() {
		wc_set_time_limit( 0 );
		if ( function_exists( 'apache_setenv' ) ) {
			@apache_setenv( 'no-gzip', '1' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_apache_setenv
		}
		@ini_set( 'zlib.output_compression', 'Off' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.IniSet.Risky
		@session_write_close(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	/**
	 * Clean all output buffers.
	 *
	 * Can prevent errors, for example: transfer closed with 3 bytes remaining to read.
	 */
	private static function clean_buffers() {
		if ( ob_get_level() ) {
			$levels = ob_get_level();
			for ( $i = 0; $i < $levels; $i++ ) {
				@ob_end_clean(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}
		} else {
			@ob_end_clean(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
	}

	/**
	 * Read file chunked.
	 *
	 * Reads file in chunks so big downloads are possible without changing PHP.INI - http://codeigniter.com/wiki/Download_helper_for_large_files/.
	 *
	 * @param  string $file   File.
	 * @param  int    $start  Byte offset/position of the beginning from which to read from the file.
	 * @param  int    $length Length of the chunk to be read from the file in bytes, 0 means full file.
	 * @return bool Success or fail
	 */
	public static function readfile_chunked( $file, $start = 0, $length = 0 ) {
		if ( ! defined( 'WC_CHUNK_SIZE' ) ) {
			define( 'WC_CHUNK_SIZE', 1024 * 1024 );
		}
		$handle = @fopen( $file, 'r' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		if ( false === $handle ) {
			return false;
		}

		if ( ! $length ) {
			$length = @filesize( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		$read_length = (int) WC_CHUNK_SIZE;

		if ( $length ) {
			$end = $start + $length - 1;

			@fseek( $handle, $start ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			$p = @ftell( $handle ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

			while ( ! @feof( $handle ) && $p <= $end ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				// Don't run past the end of file.
				if ( $p + $read_length > $end ) {
					$read_length = $end - $p + 1;
				}

				echo @fread( $handle, $read_length ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.AlternativeFunctions.file_system_operations_fread
				$p = @ftell( $handle ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

				if ( ob_get_length() ) {
					ob_flush();
					flush();
				}
			}
		} else {
			while ( ! @feof( $handle ) ) { // @codingStandardsIgnoreLine.
				echo @fread( $handle, $read_length ); // @codingStandardsIgnoreLine.
				if ( ob_get_length() ) {
					ob_flush();
					flush();
				}
			}
		}

		return @fclose( $handle ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	}

	/**
	 * Filter headers for IE to fix issues over SSL.
	 *
	 * IE bug prevents download via SSL when Cache Control and Pragma no-cache headers set.
	 *
	 * @param array $headers HTTP headers.
	 * @return array
	 */
	public static function ie_nocache_headers_fix( $headers ) {
		if ( is_ssl() && ! empty( $GLOBALS['is_IE'] ) ) {
			$headers['Cache-Control'] = 'private';
			unset( $headers['Pragma'] );
		}
		return $headers;
	}

	/**
	 * Die with an error message if the download fails.
	 *
	 * @param string  $message Error message.
	 * @param string  $title   Error title.
	 * @param integer $status  Error status.
	 */
	private static function download_error( $message, $title = '', $status = 404 ) {
		if ( ! strstr( $message, '<a ' ) ) {
			$message .= ' <a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '" class="wc-forward">' . esc_html_x( 'Go to shop', 'storeabill-core', 'woocommerce-germanized-pro' ) . '</a>';
		}
		wp_die( wp_kses_post( $message ), esc_html( $title ), array( 'response' => esc_html( $status ) ) );
	}
}
