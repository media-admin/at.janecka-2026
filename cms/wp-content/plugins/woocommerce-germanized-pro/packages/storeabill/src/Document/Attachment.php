<?php
/**
 * Abstract document
 *
 * @package Vendidero/StoreaBill
 * @version 1.0.0
 */
namespace Vendidero\StoreaBill\Document;

use Vendidero\StoreaBill\Data;

use Vendidero\StoreaBill\UploadManager;
use WC_Data;
use WC_Data_Store;
use Exception;
use WC_DateTime;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * DocumentAttachment Class.
 */
class Attachment extends Data {

	/**
	 * This is the name of this object type.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $object_type = 'document_attachment';

	/**
	 * Contains a reference to the data store for this class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	protected $data_store_name = 'document_attachment';

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $cache_group = 'document-attachments';

	protected $document = null;

	protected $key = '';

	/**
	 * Stores document data.
	 *
	 * @var array
	 */
	protected $data = array(
		'type'          => 'other',
		'name'          => '',
		'extension'     => '',
		'date_created'  => null,
		'document_id'   => '',
		'relative_path' => '',
	);

	/**
	 * Get the document if ID is passed, otherwise the document is new and empty.
	 * This class should NOT be instantiated, but the `` function should be used.
	 *
	 * @param int|object|Document $document Document to read.
	 */
	public function __construct( $data = 0 ) {
		$this->object_type = $this->get_type();

		parent::__construct( $data );

		if ( $data instanceof Attachment ) {
			$this->set_id( absint( $data->get_id() ) );
		} elseif ( is_numeric( $data ) ) {
			$this->set_id( $data );
		}

		$this->data_store = sab_load_data_store( $this->data_store_name );

		// If we have an ID, load the user from the DB.
		if ( $this->get_id() ) {
			try {
				$this->data_store->read( $this );
			} catch ( Exception $e ) {
				$this->set_id( 0 );
				$this->set_object_read( true );
			}
		} else {
			$this->set_object_read( true );
		}
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @return string
	 */
	protected function get_hook_prefix() {
		return "{$this->get_general_hook_prefix()}get_";
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @return string
	 */
	protected function get_general_hook_prefix() {
		return 'storeabill_document_attachment_';
	}

	public function get_key() {
		return ( $this->get_id() > 0 ) ? $this->get_id() : $this->key;
	}

	public function set_key( $key ) {
		$this->key = $key;
	}

	public function get_type( $context = 'view' ) {
		return $this->get_prop( 'type', $context );
	}

	public function get_title() {
		$title = $this->get_name();

		if ( empty( $title ) ) {
			$title = sprintf( _x( '%1$s %2$s', 'storeabill-attachment-name', 'woocommerce-germanized-pro' ), $this->get_type(), $this->get_id() );
		}

		return $title;
	}

	public function get_name( $context = 'view' ) {
		return $this->get_prop( 'name', $context );
	}

	public function get_relative_path( $context = 'view' ) {
		return $this->get_prop( 'relative_path', $context );
	}

	public function get_extension( $context = 'view' ) {
		return $this->get_prop( 'extension', $context );
	}

	public function get_path() {
		$file = $this->get_relative_path();

		if ( empty( $file ) ) {
			return false;
		}

		return sab_get_absolute_file_path( $file );
	}

	/**
	 * Returns the (real) filename of this document.
	 * In case another context is provided, the filename is being regenerated
	 * based on current document data (e.g. for direct browser output).
	 *
	 * The real filename might include postfixes e.g. invoice-12-1.pdf to make sure
	 * no files are being overridden.
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_filename( $context = 'view' ) {
		$path     = $this->get_relative_path();
		$filename = ( ! empty( $path ) ? basename( $path ) : '' );

		if ( empty( $filename ) && 'view' === $context ) {
			$filename = apply_filters( "{$this->get_hook_prefix()}filename", $this->generate_filename(), $this );
		}

		return sanitize_file_name( $filename );
	}

	public function get_download_url( $args = array() ) {
		$url = false;

		if ( is_bool( $args ) ) {
			$args = array(
				'force_download' => $args,
			);
		}

		$args = wp_parse_args(
			$args,
			array(
				'force_download' => false,
				'is_primary'     => false,
			)
		);

		if ( $this->has_file() ) {
			$args['attachment_id'] = $this->get_id();

			$url = $this->get_document()->get_download_url( $args );
		}

		return apply_filters( "{$this->get_hook_prefix()}download_url", $url, $this, $args['force_download'], $args );
	}

	/**
	 * Generates a new filename for the document.
	 *
	 * @return string
	 */
	protected function generate_filename() {
		$filename = $this->get_document() ? pathinfo( $this->get_document()->get_filename(), PATHINFO_FILENAME ) : $this->get_id();

		return sanitize_file_name( $filename . '-' . $this->get_type() . '.' . $this->get_extension() );
	}

	/**
	 * @param $stream
	 *
	 * @return true|WP_Error
	 */
	public function upload( $stream ) {
		$error = new \WP_Error();
		$path  = sab_upload_document( $this->get_filename(), $stream, true, $this->has_file() ? true : false );

		if ( is_wp_error( $path ) ) {
			$error->add( 'upload-error', $path->get_error_message() );

			return $error;
		} else {
			$this->set_relative_path( $path );
			$this->save();

			return $this->get_id();
		}
	}

	public function has_file() {
		$path = $this->get_path();

		if ( ! empty( $path ) && file_exists( $path ) ) {
			return true;
		}

		return false;
	}

	public function get_document_id( $context = 'view' ) {
		return $this->get_prop( 'document_id', $context );
	}

	/**
	 * Get parent document object.
	 *
	 * @return Document|boolean
	 */
	public function get_document() {
		if ( is_null( $this->document ) && 0 < $this->get_document_id() ) {
			$this->document = sab_get_document( $this->get_document_id() );
		}

		return $this->document ? $this->document : false;
	}

	/**
	 * Return the date this document was created.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Set the date this document was created.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	public function set_type( $type ) {
		$this->set_prop( 'type', $type );
	}

	/**
	 * Set the relative path.
	 *
	 * @param string $path The path.
	 */
	public function set_relative_path( $path ) {
		$path = ! empty( $path ) ? UploadManager::get_relative_upload_dir( $path ) : $path;

		$this->set_prop( 'relative_path', $path );
	}

	/**
	 * Set the name.
	 *
	 * @param string $name The name.
	 */
	public function set_name( $name ) {
		$this->set_prop( 'name', $name );
	}

	/**
	 * Set the file extension.
	 *
	 * @param string $extension The extension.
	 */
	public function set_extension( $extension ) {
		$this->set_prop( 'extension', $extension );
	}

	/**
	 * Set document id.
	 *
	 * @param int $value document id.
	 */
	public function set_document_id( $value ) {
		$this->document = null;

		$this->set_prop( 'document_id', absint( $value ) );
	}

	/**
	 * @param Document $document
	 */
	public function set_document( $document ) {
		$this->set_document_id( $document->get_id() );

		$this->document = $document;
	}

	public function supports_preview() {
		return false;
	}

	/**
	 * @param string $pdf_stream
	 * @param boolean $is_preview
	 *
	 * @return string|WP_Error
	 */
	public function generate( $pdf_stream = '', $is_preview = false ) {
		return new WP_Error( 'document-attachment', _x( 'Dynamic generation is not supported', 'storeabill-core', 'woocommerce-germanized-pro' ) );
	}

	public function get_stream() {
		if ( ! $this->has_file() ) {
			return '';
		}

		try {
			$result = file_get_contents( $this->get_path() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		} catch ( \Exception $e ) {
			$result = '';
		}

		if ( ! is_string( $result ) ) {
			$result = '';
		}

		return $result;
	}

	public function get_data() {
		$data = array_merge(
			array(
				'id' => $this->get_id(),
			),
			$this->data,
			array(
				'meta_data' => $this->get_meta_data(),
			)
		);

		$data['path'] = $this->has_file() ? $this->get_path() : '';

		return $data;
	}
}
