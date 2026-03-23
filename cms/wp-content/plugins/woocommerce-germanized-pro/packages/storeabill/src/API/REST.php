<?php

namespace Vendidero\StoreaBill\API;

use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

abstract class REST {

	abstract public function get_url();

	protected function get_basic_auth() {
		return false;
	}

	protected function get_timeout( $request_type = 'GET' ) {
		return 'GET' === $request_type ? 30 : 100;
	}

	protected function get_content_type() {
		return 'application/json';
	}

	protected function maybe_encode_body( $body_args, $content_type = '' ) {
		if ( empty( $content_type ) ) {
			$content_type = $this->get_content_type();
		}

		if ( 'application/json' === $content_type ) {
			return wp_json_encode( $body_args, JSON_PRETTY_PRINT );
		}

		return $body_args;
	}

	protected function get_file_mime_type( $file ) {
		$mime_type      = 'application/pdf';
		$mime_type_info = wp_check_filetype(
			$file,
			array(
				'pdf' => 'application/pdf',
				'xml' => 'application/xml',
			)
		);

		if ( false !== $mime_type_info['type'] ) {
			$mime_type = $mime_type_info['type'];
		}

		return $mime_type;
	}

	protected function build_multipart_data( $boundary, $fields, $files ) {
		$data = '';
		$eol  = "\r\n";

		$delimiter = '--' . $boundary;

		foreach ( $fields as $name => $content ) {
			$data .= $delimiter . "\r\nContent-Disposition: form-data; name=\"{$name}\"{$eol}{$eol}{$content}\r\n";
		}

		foreach ( $files as $name => $file_data ) {
			$param_name = $name;
			$filename   = $name;
			$binary     = $file_data;

			if ( is_array( $file_data ) ) {
				$file_data = wp_parse_args(
					$file_data,
					array(
						'filename' => '',
						'binary'   => '',
					)
				);

				$filename = $file_data['filename'];
				$binary   = $file_data['binary'];
			}

			$data .= $delimiter . $eol . 'Content-Disposition: form-data; name="' . $param_name . '"; filename="' . $filename . '"' . $eol . 'Content-Transfer-Encoding: binary' . $eol;
			$data .= $eol;
			$data .= $binary . $eol;
		}

		$data .= $delimiter . '--' . $eol;

		return $data;
	}

	protected function get_response( $url, $type = 'GET', $body_args = array(), $header = array() ) {
		$header   = $this->get_header( $header );
		$response = false;

		if ( 'GET' === $type ) {
			$response = wp_remote_get(
				esc_url_raw( $url ),
				array(
					'headers' => $header,
					'timeout' => $this->get_timeout( $type ),
				)
			);
		} elseif ( 'POST' === $type ) {
			$response = wp_remote_post(
				esc_url_raw( $url ),
				array(
					'headers' => $header,
					'timeout' => $this->get_timeout( $type ),
					'body'    => $this->maybe_encode_body( $body_args, $header['Content-Type'] ),
				)
			);
		} elseif ( 'PUT' === $type ) {
			$response = wp_remote_request(
				esc_url_raw( $url ),
				array(
					'headers' => $header,
					'timeout' => $this->get_timeout( $type ),
					'body'    => $this->maybe_encode_body( $body_args, $header['Content-Type'] ),
					'method'  => 'PUT',
				)
			);
		}

		if ( false !== $response ) {
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$code    = wp_remote_retrieve_response_code( $response );
			$body    = wp_remote_retrieve_body( $response );
			$headers = wp_remote_retrieve_headers( $response );

			$rest_response = new RESTResponse( $code, $body, $headers, $type );

			if ( $rest_response->is_error() ) {
				Package::extended_log( sprintf( 'Error %s %s: %s. Request and response:', $type, $url, $code ), 'api' );
				Package::extended_log( wc_print_r( $body_args, true ), 'api' );
				Package::extended_log( $rest_response->get_body_raw(), 'api' );
			}

			return $rest_response;
		}

		return new \WP_Error( 'rest-error', sprintf( _x( 'Error while trying to perform REST request to %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $url ) );
	}

	protected function get_request_url( $endpoint = '', $query_args = array() ) {
		if ( strpos( $endpoint, 'http://' ) === false && strpos( $endpoint, 'https://' ) === false ) {
			$endpoint = trailingslashit( $this->get_url() ) . $endpoint;
		}

		return add_query_arg( $query_args, $endpoint );
	}

	/**
	 * @param string $endpoint
	 * @param array  $query_args
	 *
	 * @return RESTResponse|\WP_Error
	 */
	public function get( $endpoint = '', $query_args = array(), $header = array() ) {
		return $this->get_response( $this->get_request_url( $endpoint, $query_args ), 'GET', array(), $header );
	}

	/**
	 * @param string $endpoint
	 * @param array  $query_args
	 *
	 * @return RESTResponse|\WP_Error
	 */
	public function post( $endpoint = '', $body_args = array(), $header = array() ) {
		return $this->get_response( $this->get_request_url( $endpoint ), 'POST', $body_args, $header );
	}

	protected function uses_curl() {
		global $wp_version;

		if ( class_exists( '\WpOrg\Requests\Transport\Curl' ) ) {
			return \WpOrg\Requests\Transport\Curl::test();
		} elseif ( version_compare( $wp_version, '6.4.0', '<' ) ) {
			return \WP_Http_Curl::test(); // phpcs:ignore WordPress.WP.DeprecatedClasses.wp_http_curlFound
		}

		return false;
	}

	/**
	 * @param string $endpoint
	 * @param array  $query_args
	 *
	 * @return RESTResponse|\WP_Error
	 */
	public function put( $endpoint = '', $body_args = array(), $header = array() ) {
		return $this->get_response( $this->get_request_url( $endpoint ), 'PUT', $body_args, $header );
	}

	protected function get_header( $header = array() ) {
		$headers = array();

		$headers['Content-Type'] = $this->get_content_type();
		$headers['Accept']       = 'application/json';

		if ( $this->get_basic_auth() ) {
			$headers['Authorization'] = $this->get_basic_auth();
		}

		$headers['User-Agent'] = 'StoreaBill/' . Package::get_version();

		/**
		 * Optionally replace request headers lazily.
		 */
		$headers = array_replace_recursive( $headers, $header );

		return $headers;
	}
}
