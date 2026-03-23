<?php
/**
 * Abstract document
 *
 * @package Vendidero/StoreaBill
 * @version 1.0.0
 */
namespace Vendidero\StoreaBill\eInvoice;

defined( 'ABSPATH' ) || exit;

/**
 * DocumentAttachment Class.
 */
class Attachment extends \Vendidero\StoreaBill\Document\Attachment {

	/**
	 * Stores document data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'specification' => '',
		'profile'       => '',
		'merge_pdf'     => false,
	);

	public function get_type( $context = 'view' ) {
		return 'einvoice';
	}

	public function get_specification( $context = 'view' ) {
		return $this->get_prop( 'specification', $context );
	}

	public function get_profile( $context = 'view' ) {
		return $this->get_prop( 'profile', $context );
	}

	public function get_merge_pdf( $context = 'view' ) {
		return $this->get_prop( 'merge_pdf', $context );
	}

	public function has_pdf_merge( $context = 'view' ) {
		return true === $this->get_merge_pdf();
	}

	public function set_specification( $specification ) {
		$this->set_prop( 'specification', $specification );
	}

	public function set_profile( $profile ) {
		$this->set_prop( 'profile', $profile );
	}

	public function set_merge_pdf( $merge_pdf ) {
		$this->set_prop( 'merge_pdf', sab_string_to_bool( $merge_pdf ) );
	}

	protected function get_additional_fields() {
		$additional_fields = array();

		if ( $profile_data = Helper::get_profile( $this->get_specification(), $this->get_profile() ) ) {
			$fields = $profile_data['fields'];

			foreach ( $fields as $field_name => $field ) {
				$field = wp_parse_args(
					$field,
					array(
						'id' => '',
					)
				);

				if ( $meta_value = $this->get_meta( $field['id'] ) ) {
					$additional_fields[ $field['id'] ] = $meta_value;
				}
			}
		}

		return $additional_fields;
	}

	/**
	 * @param string $pdf_stream
	 *
	 * @return Invoice|\WP_Error
	 */
	protected function get_einvoice( $pdf_stream = '' ) {
		$error         = new \WP_Error();
		$specification = Helper::get_specification( $this->get_specification() );

		if ( ! $specification ) {
			$error->add( 'einvoice_generate', 'Missing specification' );
			return $error;
		}

		$classname          = $specification['class_name'];
		$available_profiles = $specification['profiles'];

		if ( ! class_exists( $classname ) ) {
			$error->add( 'einvoice_generate', sprintf( 'Missing classname for %s', $specification['title'] ) );
			return $error;
		}

		if ( ! array_key_exists( $this->get_profile(), $available_profiles ) ) {
			$error->add( 'einvoice_generate', sprintf( 'Missing profile for %s', $specification['title'] ) );
			return $error;
		}

		$e_invoice = Helper::get_specification_instance( $this->get_specification(), $this->get_profile(), $this->get_document()->get_type() );

		if ( ! $e_invoice ) {
			$error->add( 'einvoice_generate', sprintf( 'Error creating eInvoice instance for %s', $specification['title'] ) );
			return $error;
		}

		try {
			$e_invoice->from_invoice( $this->get_document(), $this->get_additional_fields(), $pdf_stream );
		} catch ( \Throwable $e ) {
			$error->add( 'einvoice_generate', $e->getMessage() );
			return $error;
		} catch ( \Exception $e ) {
			$error->add( 'einvoice_generate', $e->getMessage() );
			return $error;
		}

		return $e_invoice;
	}

	public function get_title() {
		$title = $this->get_name();

		if ( empty( $title ) ) {
			$title = Helper::get_title( $this->get_specification(), $this->get_profile() );
		}

		return $title;
	}

	/**
	 * Generates a new filename for the document.
	 *
	 * @return string
	 */
	protected function generate_filename() {
		$filename = $this->get_document() ? pathinfo( $this->get_document()->get_filename(), PATHINFO_FILENAME ) : $this->get_id();

		return sanitize_file_name( $filename . '-' . $this->get_specification() . '-' . $this->get_profile() . '.' . $this->get_extension() );
	}

	public function save() {
		if ( ! $this->get_extension() ) {
			if ( $profile_data = Helper::get_profile( $this->get_specification(), $this->get_profile() ) ) {
				$this->set_extension( $profile_data['extension'] );
			}
		}

		return parent::save();
	}

	public function get_merged_pdf( $pdf_stream = '', $pdf_attachment = '' ) {
		$pdf_stream     = empty( $pdf_stream ) ? $this->get_document()->get_stream() : $pdf_stream;
		$e_invoice      = $this->get_einvoice( $pdf_stream );
		$error          = new \WP_Error();
		$pdf_attachment = empty( $pdf_attachment ) ? $this->get_stream() : $pdf_attachment;

		if ( ! $pdf_stream ) {
			$error->add( 'einvoice_generate', 'PDF stream is missing' );
			return $error;
		}

		if ( is_wp_error( $e_invoice ) ) {
			return $e_invoice;
		}

		if ( ! $e_invoice->supports_pdf_attachment() ) {
			$error->add( 'einvoice_generate', 'eInvoice does not support PDF attachments.' );

			return $error;
		}

		return $e_invoice->get_pdf( $pdf_stream, $pdf_attachment );
	}

	public function supports_preview() {
		return true;
	}

	/**
	 * @param string $pdf_stream
	 * @param bool $is_preview
	 *
	 * @return string|\WP_Error
	 */
	public function generate( $pdf_stream = '', $is_preview = false ) {
		$e_invoice = $this->get_einvoice( $pdf_stream );
		$error     = new \WP_Error();

		if ( is_wp_error( $e_invoice ) ) {
			return $e_invoice;
		}

		try {
			$stream   = $e_invoice->get_xml();
			$is_valid = $e_invoice->validate( $stream );

			if ( is_wp_error( $is_valid ) ) {
				foreach ( $is_valid->get_error_messages() as $msg ) {
					$error->add( 'einvoice_generate', $msg );
				}

				return $error;
			} elseif ( ! $is_preview ) {
				/**
				 * Force deleting the attachment file to prevent wrong (preview) filenames.
				 */
				if ( $this->get_filename( 'edit' ) && $this->has_file() ) {
					wp_delete_file( $this->get_path() );

					$this->set_relative_path( '' );
				}

				$path = sab_upload_document( $this->get_filename(), $stream, true, true );

				if ( is_wp_error( $path ) ) {
					throw new \Exception( $path->get_error_message() );
				}

				$this->set_relative_path( $path );
				$this->save();

				do_action( "{$this->get_general_hook_prefix()}generated", $this, $path );

				return $stream;
			} else {
				return $stream;
			}
		} catch ( \Exception $e ) {
			$error->add( 'einvoice_generate', $e->getMessage() );

			return $error;
		}
	}
}
