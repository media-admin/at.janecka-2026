<?php

namespace Vendidero\Shiptastic\Hermes\Api;

use Vendidero\Shiptastic\API\Auth\RESTAuth;

defined( 'ABSPATH' ) || exit;

class AuthByKey extends RESTAuth {

	public function get_type() {
		return 'hermes_body_auth';
	}

	public function auth() {}

	public function has_auth() {
		return true;
	}

	public function is_connected() {
		return true;
	}

	public function get_headers() {
		return array();
	}

	protected function get_api_key() {
		return '0f4fefdf-bc8b-4';
	}

	public function get_additional_args() {
		return array(
			'apiKey' => $this->get_api_key(),
		);
	}

	public function revoke() {}

	public function get_url() {
		return '';
	}
}
