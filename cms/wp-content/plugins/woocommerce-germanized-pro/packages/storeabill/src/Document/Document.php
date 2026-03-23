<?php
/**
 * Abstract document
 *
 * @package Vendidero/StoreaBill
 * @version 1.0.0
 */
namespace Vendidero\StoreaBill\Document;

use Vendidero\StoreaBill\Countries;
use Vendidero\StoreaBill\Data;
use Vendidero\StoreaBill\DownloadManager;
use Vendidero\StoreaBill\Emails\Mailer;
use Vendidero\StoreaBill\Exceptions\DocumentRenderException;
use Vendidero\StoreaBill\ExternalSync\SyncData;
use Vendidero\StoreaBill\Interfaces\Customer;
use Vendidero\StoreaBill\Interfaces\ExternalSyncable;
use Vendidero\StoreaBill\Interfaces\SyncableReference;
use Vendidero\StoreaBill\Interfaces\Numberable;

use Vendidero\StoreaBill\Package;
use Vendidero\StoreaBill\UploadManager;
use WC_Data_Store;
use Exception;
use WC_DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * Document Class.
 */
abstract class Document extends Data implements Numberable, ExternalSyncable {

	use \Vendidero\StoreaBill\ExternalSync\ExternalSyncable;

	/**
	 * Stores data about status changes so relevant hooks can be fired.
	 *
	 * @var bool|array
	 */
	protected $status_transition = false;

	/**
	 * This is the name of this object type.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $object_type = '';

	/**
	 * Contains a reference to the data store for this class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	protected $data_store_name = 'document';

	/**
	 * Journal instance
	 *
	 * @var null|Journal
	 */
	protected $journal = null;

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $cache_group = 'documents';

	/**
	 * The contained items.
	 *
	 * @var array|Item[]
	 */
	protected $items = array();

	/**
	 * The contained notices.
	 *
	 * @var array|Notice[]
	 */
	protected $notices = array();

	/**
	 * @var null|Attachment[]
	 */
	protected $attachments = array();

	/**
	 * List of items to be deleted on save.
	 *
	 * @var Item[]
	 */
	protected $items_to_delete = array();

	/**
	 * List of notices to be deleted on save.
	 *
	 * @var Notice[]
	 */
	protected $notices_to_delete = array();

	/**
	 * List of attachments to be deleted on save.
	 *
	 * @var Attachment[]
	 */
	protected $attachments_to_delete = array();

	/**
	 * Indicator which stores whether a sequential number should be generated upon saving.
	 * @var bool
	 */
	protected $numbering_transition = false;

	/**
	 * @var bool|Template
	 */
	protected $template = null;

	/**
	 * @var bool|Template
	 */
	protected $first_page_template = null;

	/**
	 * @var Customer
	 */
	protected $customer = null;

	/**
	 * Stores document data.
	 *
	 * @var array
	 */
	protected $data = array(
		'date_created'           => null,
		'date_modified'          => null,
		'date_sent'              => null,
		'date_custom'            => null,
		'date_custom_extra'      => null,
		'parent_id'              => 0,
		'created_via'            => '',
		'version'                => '',
		'secret'                 => '',
		'revision'               => 0,
		'reference_id'           => '',
		'reference_type'         => '',
		'reference_number'       => '',
		'customer_id'            => 0,
		'author_id'              => 0,
		'journal_type'           => '',
		'country'                => '',
		'number'                 => '',
		'formatted_number'       => '',
		'status'                 => '',
		'address'                => array(),
		'external_sync_handlers' => array(),
		'relative_path'          => '',
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

		if ( $data instanceof Document ) {
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

	abstract public function get_type();

	/**
	 * Make sure extra data is replaced correctly
	 */
	public function apply_changes() {
		if ( function_exists( 'array_replace' ) ) {
			$this->data = array_replace( $this->data, $this->changes ); // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.array_replaceFound
		} else { // PHP 5.2 compatibility.
			foreach ( $this->changes as $key => $change ) {
				$this->data[ $key ] = $change;
			}
		}

		$this->changes = array();
	}

	/**
	 * Returns storable item types.
	 *
	 * @return string[]
	 */
	abstract public function get_item_types();

	public function get_line_item_types() {
		$default_line_item_types = $this->get_item_types();
		$main_line_item_types    = array();

		if ( $document_type = sab_get_document_type( $this->get_type() ) ) {
			$default_line_item_types = $document_type->default_line_item_types;
			$main_line_item_types    = $document_type->main_line_item_types;
		}

		/**
		 * Override with template data
		 */
		if ( $template = $this->get_template() ) {
			$default_line_item_types = $template->get_line_item_types();
		}

		/**
		 * Make sure to always include the main line item types e.g. product.
		 */
		$default_line_item_types = array_filter( array_unique( array_merge( $main_line_item_types, $default_line_item_types ) ) );

		return apply_filters( $this->get_hook_prefix() . 'line_item_types', $default_line_item_types, $this );
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
		return "storeabill_{$this->get_type()}_";
	}

	/**
	 * Get all class data in array format.
	 *
	 * @since 3.0.0
	 * @return array
	 */
	public function get_data() {
		$data = array_merge(
			array(
				'id' => $this->get_id(),
			),
			$this->data,
			array(
				'meta_data' => $this->get_meta_data(),
			),
			array(
				'attachments' => $this->get_attachments(),
			)
		);

		$item_data = array();

		foreach ( $this->get_item_types() as $item_type ) {
			$item_data[ $item_type . '_items' ] = $this->get_items( $item_type );
		}

		$data = array_merge( $data, $item_data );

		// Force core address data to exist
		$address_fields = apply_filters(
			"{$this->get_general_hook_prefix()}address_fields",
			array(
				'first_name' => '',
				'last_name'  => '',
				'company'    => '',
				'address_1'  => '',
				'address_2'  => '',
				'city'       => '',
				'state'      => '',
				'postcode'   => '',
				'country'    => '',
				'email'      => '',
				'phone'      => '',
				'vat_id'     => '',
			),
			$this
		);

		foreach ( $address_fields as $field => $default_value ) {
			if ( ! isset( $data['address'][ $field ] ) ) {
				$data['address'][ $field ] = $default_value;
			}
		}

		$data['formatted_address'] = $this->get_formatted_address();
		$data['path']              = $this->has_file() ? $this->get_path() : '';
		$data['primary_path']      = $this->has_primary_file() ? $this->get_primary_path() : '';

		unset( $data['external_sync_handlers'] );

		return $data;
	}

	/**
	 * Return the document statuses without internal prefix.
	 *
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		$status = $this->get_prop( 'status', $context );

		if ( empty( $status ) && 'view' === $context ) {

			/**
			 * Filters the default document status used as fallback.
			 *
			 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
			 * unique hook for a document type.
			 *
			 * Example hook name: woocommerce_gzd_shipment_get_default_shipment_status
			 *
			 * @param string $status Default fallback status.
			 *
			 * @since 1.0.0
			 * @package Vendidero/StoreaBill
			 */
			$status = $this->get_default_status();
		}

		return $status;
	}

	protected function get_default_status() {
		$default_status = 'draft';

		if ( $document_type = sab_get_document_type( $this->get_type() ) ) {
			$default_status = $document_type->default_status;
		}

		/**
		 * Filters the default document status used as fallback.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a document type.
		 *
		 * Example hook name: woocommerce_gzd_shipment_get_default_shipment_status
		 *
		 * @param string $status Default fallback status.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		return apply_filters( "{$this->get_hook_prefix()}}default_status", $default_status );
	}

	/**
	 * Checks whether the document has a specific status or not.
	 *
	 * @param  string|string[] $status The status to be checked against.
	 * @return boolean
	 */
	public function has_status( $status ) {
		/**
		 * Filter to decide whether a document has a certain status or not.
		 *
		 * @param boolean  $has_status Whether the Shipment has a status or not.
		 * @param Document $this The document object.
		 * @param string   $status The status to be checked against.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		return apply_filters( 'woocommerce_document_has_status', ( is_array( $status ) && in_array( $this->get_status(), $status, true ) ) || $this->get_status() === $status, $this, $status );
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
	 * Return the date this document was last modified.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Return the date this document was first sent via email.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_sent( $context = 'view' ) {
		return $this->get_prop( 'date_sent', $context );
	}

	/**
	 * Returns the custom date field.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_custom( $context = 'view' ) {
		return $this->get_prop( 'date_custom', $context );
	}

	/**
	 * Returns the custom extra date field.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_custom_extra( $context = 'view' ) {
		return $this->get_prop( 'date_custom_extra', $context );
	}

	/**
	 * Returns created via.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return integer
	 */
	public function get_created_via( $context = 'view' ) {
		return $this->get_prop( 'created_via', $context );
	}

	/**
	 * Returns the document version.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return integer
	 */
	public function get_version( $context = 'view' ) {
		return $this->get_prop( 'version', $context );
	}

	/**
	 * Returns the document secret.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return integer
	 */
	public function get_secret( $context = 'view' ) {
		return $this->get_prop( 'secret', $context );
	}

	/**
	 * Returns the document revision.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return integer
	 */
	public function get_revision( $context = 'view' ) {
		return $this->get_prop( 'revision', $context );
	}

	/**
	 * Returns the customer id.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return integer
	 */
	public function get_customer_id( $context = 'view' ) {
		return $this->get_prop( 'customer_id', $context );
	}

	/**
	 * Returns the author id.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return integer
	 */
	public function get_author_id( $context = 'view' ) {
		return $this->get_prop( 'author_id', $context );
	}

	/**
	 * Returns the document's relative path.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_relative_path( $context = 'view' ) {
		return $this->get_prop( 'relative_path', $context );
	}

	/**
	 * Whether this document has been sent to the customer or not.
	 *
	 * @return bool
	 */
	public function is_sent() {
		return ( $this->get_date_sent() ? true : false );
	}

	public function get_edit_url() {
		return false;
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

		if ( empty( $filename ) || ( $this->is_editable() && 'view' === $context ) ) {
			$filename = apply_filters( "{$this->get_hook_prefix()}filename", $this->generate_filename(), $this );
		}

		return sanitize_file_name( $filename );
	}

	public function get_primary_filename( $context = 'default' ) {
		$path     = $this->get_primary_path( $context );
		$filename = ( ! empty( $path ) ? basename( $path ) : '' );

		if ( empty( $filename ) ) {
			return $this->get_filename( $context );
		}

		return $filename;
	}

	/**
	 * Check if a document secret is valid.
	 *
	 * @param string $key Secret key.
	 * @return bool
	 */
	public function secret_is_valid( $secret ) {
		$equals = hash_equals( $this->get_secret(), $secret );

		if ( $equals && $this->secret_has_expired() ) {
			$equals = false;
		}

		return $equals;
	}

	public function secret_has_expired() {
		$secret              = $this->get_secret();
		$expiration_duration = apply_filters( 'storeabill_document_secret_expiration_time', DAY_IN_SECONDS * 30 );
		$has_expired         = false;

		if ( null !== $expiration_duration && false !== strpos( $secret, ':' ) ) {
			list( $secret_request_time, $secret_key ) = explode( ':', $secret, 2 );
			$expiration_time                          = (int) $secret_request_time + $expiration_duration;

			if ( $expiration_time && time() >= $expiration_time ) {
				$has_expired = true;
			}
		}

		return $has_expired;
	}

	public function supports_download_links() {
		return apply_filters( "{$this->get_general_hook_prefix()}supports_download_links", sab_document_type_supports( $this->get_type(), 'download_links' ), $this );
	}

	public function get_download_url( $args = array(), $legacy_allow_guest = null ) {
		if ( is_bool( $args ) ) {
			$args = array(
				'force_download' => $args,
			);
		}

		$args = wp_parse_args(
			$args,
			array(
				'force_download'  => false,
				'is_for_guests'   => $legacy_allow_guest,
				'attachment_type' => '',
				'attachment_id'   => 0,
				'is_primary'      => true,
			)
		);

		if ( null === $args['is_for_guests'] ) {
			$args['is_for_guests'] = ! is_user_logged_in();
		}

		$url        = false;
		$attachment = false;

		if ( $args['attachment_id'] > 0 || ! empty( $args['attachment_type'] ) ) {
			if ( $args['attachment_id'] > 0 ) {
				$attachment = $this->get_attachment( $args['attachment_id'] );
			} elseif ( ! empty( $args['attachment_type'] ) && array_key_exists( $args['attachment_type'], sab_get_document_attachment_types( $this->get_type() ) ) ) {
				$attachments = array_values( $this->get_attachments( $args['attachment_type'] ) );

				if ( ! empty( $attachments ) ) {
					$attachment = $attachments[0];
				}
			}
		}

		if ( $this->has_file() || $attachment ) {
			$allow_guest_download = $this->supports_download_links() && $args['is_for_guests'];

			if ( $allow_guest_download && ( ! $this->get_secret() || $this->secret_has_expired() ) ) {
				$this->set_secret( $this->data_store->generate_secret( $this ) );
				$this->save();
			}

			$download_args = array(
				'sab-document' => $this->get_id(),
				'force'        => sab_bool_to_string( $args['force_download'] ),
				'key'          => $allow_guest_download ? $this->get_secret() : '',
				'is-primary'   => sab_bool_to_string( $args['is_primary'] ),
			);

			if ( $attachment ) {
				$download_args['sab-attachment'] = $attachment->get_id();
				$download_args['is-primary']     = false;
			}

			$url = add_query_arg(
				$download_args,
				trailingslashit( home_url() )
			);
		}

		return apply_filters( "{$this->get_hook_prefix()}download_url", $url, $this, $args['force_download'], $args );
	}

	public function get_primary_path( $context = 'default' ) {
		return apply_filters( "{$this->get_hook_prefix()}primary_path", $this->get_path(), $this, $context );
	}

	public function get_path() {
		$file = $this->get_relative_path();

		if ( empty( $file ) ) {
			return false;
		}

		return sab_get_absolute_file_path( $file );
	}

	public function has_file() {
		$path = $this->get_path();

		if ( ! empty( $path ) && file_exists( $path ) ) {
			return true;
		}

		return false;
	}

	public function has_primary_file( $context = 'default' ) {
		$path = $this->get_primary_path( $context );

		if ( ! empty( $path ) && file_exists( $path ) ) {
			return true;
		}

		return false;
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

	/**
	 * Generates a new filename for the document.
	 *
	 * @return string
	 */
	protected function generate_filename() {
		$filename = $this->get_type_title();

		if ( $this->has_number() ) {
			/* translators: 1: filename 2: formatted document number */
			$filename = sprintf( _x( '%1$s %2$s', 'storeabill-document-filename', 'woocommerce-germanized-pro' ), $filename, $this->get_formatted_number() );
		} else {
			/* translators: 1: filename 2: document id */
			$filename = sprintf( _x( '%1$s %2$s', 'storeabill-document-without-number-filename', 'woocommerce-germanized-pro' ), $filename, $this->get_id() );
		}

		return sanitize_file_name( $filename . '.pdf' );
	}

	/**
	 * @return boolean|DefaultTemplate
	 */
	public function get_template() {
		if ( is_null( $this->template ) ) {
			$this->template = sab_get_default_document_template( $this->get_type() );
		}

		return apply_filters( "{$this->get_hook_prefix()}template", $this->template, $this );
	}

	public function get_html() {
		$needs_document_setup = ! Package::is_document_setup( $this );

		if ( $needs_document_setup ) {
			Package::setup_document_rendering( $this );
		}

		if ( ! $template = $this->get_template() ) {
			return false;
		}

		$document_type = str_replace( '_', '-', sanitize_key( $this->get_type() ) );

		ob_start();
		do_action( 'storeabill_before_document', $this );

		sab_get_template( "{$document_type}/page.php" );

		do_action( 'storeabill_after_document', $this );
		$html = ob_get_clean();

		if ( $needs_document_setup ) {
			Package::clear_document_rendering( $this );
		}

		return $html;
	}

	/**
	 * Render deferred via the Woo action scheduler.
	 * Optionally pass a callback which will be called after
	 * the render was successful.
	 *
	 * @param string $on_success_callback
	 */
	public function render_deferred( $on_success_callback = '' ) {
		$args = array(
			'document_id' => $this->get_id(),
		);

		if ( ! empty( $on_success_callback ) ) {
			$args['success_callback'] = $on_success_callback;
		}

		$queue = WC()->queue();

		/**
		 * Do only queue a new render event in case it has not yet been queued.
		 */
		if ( null === $queue->get_next( 'storeabill_document_render_callback', $args, 'storeabill-document-render' ) ) {
			$this->cancel_deferred_render( $args );

			$queue->schedule_single(
				time(),
				'storeabill_document_render_callback',
				$args,
				'storeabill-document-render'
			);
		}

		return true;
	}

	public function preview( $output = true ) {
		return $this->render( true, $output );
	}

	public function cancel_deferred_render( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'document_id' => $this->get_id(),
			)
		);

		$queue = WC()->queue();

		/**
		 * Cancel outstanding events and queue new.
		 */
		$queue->cancel_all( 'storeabill_document_render_callback', $args, 'storeabill-document-render' );
	}

	/**
	 * @param bool $is_preview     Whether to preview the document only.
	 *
	 * @return \WP_Error|string
	 */
	protected function get_render_stream( $is_preview = false ) {
		$needs_document_setup = ! Package::is_document_setup( $this );

		if ( $needs_document_setup ) {
			Package::setup_document_rendering( $this );
		}

		$error = new \WP_Error();

		try {
			/**
			 * In preview mode: Make sure to mark numbers as non-existent yet.
			 */
			if ( $is_preview && ! $this->get_number() ) {
				$this->set_formatted_number( _x( 'Not yet assigned', 'storeabill-core', 'woocommerce-germanized-pro' ) );
			}

			$html = $this->get_html();

			if ( strlen( $html ) > 100000 ) {
				sab_raise_pcre_backtrace_limit();
			}

			if ( false === $html ) {
				throw new Exception( _x( 'Error while retrieving document HTML. Maybe a template is missing?', 'storeabill-core', 'woocommerce-germanized-pro' ) );
			}

			$default_renderer = apply_filters( 'storeabill_default_document_pdf_renderer', '', $this );
			$renderer         = sab_get_pdf_renderer(
				$default_renderer,
				array(
					'template' => $this->get_template(),
				)
			);

			$html_parts = array(
				'wrapper_before'    => '',
				'header'            => '',
				'header_first_page' => '',
				'footer'            => '',
				'footer_first_page' => '',
				'content'           => '',
				'wrapper_after'     => '',
			);

			foreach ( $html_parts as $html_part => $value ) {
				$html_parts[ $html_part ] = sab_get_html_part( $html_part, $html );
			}

			foreach ( $html_parts as $html_part => $value ) {
				$setter = "set_{$html_part}";

				if ( is_callable( array( $renderer, $setter ) ) ) {
					$renderer->$setter( $value );
				}
			}

			$stream = $renderer->stream();
		} catch ( Exception $e ) {
			$error->add( 'render-error', $e->getMessage() );
		}

		if ( $needs_document_setup ) {
			Package::clear_document_rendering( $this );
		}

		if ( sab_wp_error_has_errors( $error ) ) {
			return $error;
		} else {
			return $stream;
		}
	}

	/**
	 * @param string $stream
	 *
	 * @return string|\WP_Error
	 */
	protected function update_file( $stream ) {
		$path = sab_upload_document( $this->get_filename(), $stream, true, $this->has_file() ? true : false );

		if ( is_wp_error( $path ) ) {
			return $path;
		}

		$this->set_relative_path( $path );

		return $path;
	}

	/**
	 * @param bool $is_preview     Whether to preview the document only.
	 * @param bool $preview_output Whether to directly sent the preview to the browser.
	 *
	 * @return \WP_Error|string
	 */
	public function render( $is_preview = false, $preview_output = true ) {
		Package::setup_document_rendering( $this, $is_preview );

		$error = new \WP_Error();

		try {
			/**
			 * Cancel outstanding deferred rendering events to prevent overrides.
			 */
			$this->cancel_deferred_render();

			$stream = $this->get_render_stream( $is_preview );

			if ( is_wp_error( $stream ) ) {
				throw new Exception( $stream->get_error_message() );
			}

			if ( $is_preview ) {
				if ( $preview_output ) {
					DownloadManager::stream_file( $stream, $this->get_filename() );
				} elseif ( empty( $stream ) ) {
					throw new Exception( _x( 'Missing stream while rendering document.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
				}
			} elseif ( ! empty( $stream ) ) {
				$path = $this->update_file( $stream );

				if ( is_wp_error( $path ) ) {
					throw new Exception( $path->get_error_message() );
				}

				$this->save();

				do_action( "{$this->get_general_hook_prefix()}rendered", $this, $path );
			} else {
				throw new Exception( _x( 'Missing stream while rendering document.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
			}
		} catch ( Exception $e ) {
			$error->add( 'render-error', $e->getMessage() );
			$this->create_notice( _x( 'An error occurred while rendering the document.', 'storeabill-core', 'woocommerce-germanized-pro' ), 'error' );

			Package::extended_log( 'Error while rendering ' . $this->get_title() . ': ' . $e->getMessage() );
		}

		Package::clear_document_rendering( $this, $is_preview );

		if ( sab_wp_error_has_errors( $error ) ) {
			return $error;
		} else {
			return $stream;
		}
	}

	/**
	 * Returns the address properties.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string[]
	 */
	public function get_address( $context = 'view' ) {
		return $this->get_prop( 'address', $context );
	}

	public function get_country( $context = 'view' ) {
		$country = $this->get_address_prop( 'country', $context );

		/**
		 * If country data is missing: Use base country instead.
		 */
		if ( 'view' === $context && '' === $country ) {
			$country = Countries::get_base_country();
		}

		return $country;
	}

	/**
	 * Returns the parent id.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_parent_id( $context = 'view' ) {
		return $this->get_prop( 'parent_id', $context );
	}

	/**
	 * Returns the reference id (e.g. order_id).
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_reference_id( $context = 'view' ) {
		return $this->get_prop( 'reference_id', $context );
	}

	/**
	 * Returns reference formatted number (e.g. order number).
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_reference_number( $context = 'view' ) {
		$number = $this->get_prop( 'reference_number', $context );

		if ( 'view' === $context && empty( $number ) ) {
			$number = $this->get_reference_id();
		}

		return $number;
	}

	/**
	 * Returns reference type (e.g. woo).
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_reference_type( $context = 'view' ) {
		return $this->get_prop( 'reference_type', $context );
	}

	public function get_external_sync_handlers( $context = 'view' ) {
		return $this->get_prop( 'external_sync_handlers', $context );
	}

	/**
	 * @return SyncableReference|boolean
	 */
	public function get_reference() {
		return false;
	}

	/**
	 * @return bool|Customer
	 */
	public function get_customer() {
		if ( is_null( $this->customer ) ) {
			if ( $this->get_customer_id() > 0 ) {
				$this->customer = \Vendidero\StoreaBill\References\Customer::get_customer( $this->get_customer_id(), $this->get_reference_type() );
			}

			if ( is_null( $this->customer ) ) {
				$this->customer = false;
			}
		}

		return $this->customer;
	}

	/**
	 * Returns the number.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_number( $context = 'view' ) {
		return $this->get_prop( 'number', $context );
	}

	/**
	 * Returns the formatted number.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_formatted_number( $context = 'view' ) {
		return $this->get_prop( 'formatted_number', $context );
	}

	/**
	 * Returns the journal type to determine which journal (number circle)
	 * is being used to generate numbers for the current type.
	 *
	 * @return string
	 */
	public function get_journal_type( $context = 'view' ) {
		$type = $this->get_prop( 'journal_type', $context );

		if ( 'view' === $context && empty( $type ) ) {
			$type = $this->get_type();
		}

		return $type;
	}

	public function get_journal() {
		if ( is_null( $this->journal ) ) {
			$this->journal = sab_get_journal( $this->get_journal_type() );
		}

		return $this->journal;
	}

	public function number_upon_save() {
		return true === $this->numbering_transition ? true : false;
	}

	/**
	 * Returns whether the current document has a number or not.
	 *
	 * @return boolean
	 */
	public function has_number() {
		$number     = $this->get_number();
		$has_number = ( empty( $number ) ? false : true );

		/**
		 * Filter to decide whether a document has a number or not.
		 *
		 * @param boolean  $has_number Whether the document has a number or not.
		 * @param Document $this The document object.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		return apply_filters( 'storeabill_document_has_number', $has_number, $this );
	}

	public function get_type_title( $label_type = 'singular' ) {
		$type_title = '';

		if ( $type = sab_get_document_type( $this->get_type() ) ) {
			$type_title = sab_get_document_type_label( $this->get_type(), $label_type );
		}

		return $type_title;
	}

	public function get_title( $with_type = true ) {
		$id = $this->get_id() > 0 ? $this->get_id() : '';

		if ( $this->has_number() ) {
			$title = $this->get_formatted_number();
		} else {
			$title = sprintf( _x( 'Draft %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $id );
		}

		if ( $with_type && ( $type_title = $this->get_type_title() ) ) {
			/**
			 * Do only include type title in case it is not already included
			 * within the formatted number title.
			 */
			if ( strpos( $title, $type_title ) === false ) {
				/* translators: 1: document type title 2: document title */
				$title = sprintf( _x( '%1$s %2$s', 'storeabill-document-type-title-format', 'woocommerce-germanized-pro' ), $type_title, $title );
			}
		}

		/**
		 * Filter to decide whether a document has a number or not.
		 *
		 * @param boolean  $has_number Whether the document has a number or not.
		 * @param Document $this The document object.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		return apply_filters( "{$this->get_hook_prefix()}title", $title, $with_type, $this );
	}

	public function get_formatted_identifier() {
		return $this->get_title();
	}

	/**
	 * Returns the formatted shipping address.
	 *
	 * @param  string $empty_content Content to show if no address is present.
	 * @return string
	 */
	public function get_formatted_address( $empty_content = '' ) {
		$address = Countries::get_formatted_address( $this->get_address() );

		return apply_filters( "{$this->get_hook_prefix()}formatted_address", ( $address ? $address : $empty_content ), $this );
	}

	/**
	 * Returns the shipment address phone number.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_phone( $context = 'view' ) {
		return $this->get_address_prop( 'phone', $context );
	}

	/**
	 * Returns the shipment address email.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_email( $context = 'view' ) {
		return $this->get_address_prop( 'email', $context );
	}

	/**
	 * Returns the shipment address first line.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_address_1( $context = 'view' ) {
		return $this->get_address_prop( 'address_1', $context );
	}

	/**
	 * Returns the shipment address second line.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_address_2( $context = 'view' ) {
		return $this->get_address_prop( 'address_2', $context );
	}

	/**
	 * Returns the shipment address company.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_company( $context = 'view' ) {
		return $this->get_address_prop( 'company', $context );
	}

	/**
	 * Returns the shipment address first name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_first_name( $context = 'view' ) {
		return $this->get_address_prop( 'first_name', $context );
	}

	/**
	 * Returns the shipment address last name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_last_name( $context = 'view' ) {
		return $this->get_address_prop( 'last_name', $context );
	}

	/**
	 * Returns the shipment address formatted full name.
	 *
	 * @return string
	 */
	public function get_formatted_full_name() {
		return sprintf( _x( '%1$s %2$s', 'storeabill-fullname', 'woocommerce-germanized-pro' ), $this->get_first_name(), $this->get_last_name() );
	}

	/**
	 * Returns the shipment address postcode.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_postcode( $context = 'view' ) {
		return $this->get_address_prop( 'postcode', $context );
	}

	/**
	 * Returns the shipment address city.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_city( $context = 'view' ) {
		return $this->get_address_prop( 'city', $context );
	}

	/**
	 * Returns the shipment address state.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_state( $context = 'view' ) {
		return $this->get_address_prop( 'state', $context );
	}

	/**
	 * Returns an address prop.
	 *
	 * @param string $prop
	 * @param string $context
	 *
	 * @return null|string
	 */
	protected function get_address_prop( $prop, $context = 'view' ) {
		$value = '';

		if ( isset( $this->changes['address'][ $prop ] ) || isset( $this->data['address'][ $prop ] ) ) {
			$value = isset( $this->changes['address'][ $prop ] ) ? $this->changes['address'][ $prop ] : $this->data['address'][ $prop ];
		}

		if ( 'view' === $context ) {
			/**
			 * Filter to adjust a document's address property e.g. first_name.
			 *
			 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
			 * unique hook for a document type. `$prop` refers to the actual address property e.g. first_name.
			 *
			 * Example hook name: storeabill_document_get_address_first_name
			 *
			 * @param string   $value The address property value.
			 * @param Document $this The document object.
			 *
			 * @since 1.0.0
			 * @package Vendidero/StoreaBill
			 */
			$value = apply_filters( "{$this->get_hook_prefix()}address_{$prop}", $value, $this );
		}

		return $value;
	}

	/**
	 * Returns whether the shipment is editable or not.
	 *
	 * @return boolean
	 */
	public function is_editable() {
		/**
		 * Filter to decide whether the current document is still editable or not.
		 *
		 * @param boolean  $is_editable Whether the Shipment is editable or not.
		 * @param Document $this The document object.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		return apply_filters( "storeabill_{$this->get_type()}_is_editable", ( $this->has_status( array( 'draft' ) ) || $this->get_id() <= 0 ), $this );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set document status.
	 *
	 * @param string  $new_status Status to change the document to.
	 * @param boolean $manual_update Whether it is a manual status update or not.
	 * @return array  details of change
	 */
	public function set_status( $new_status, $note = '', $manual_update = false ) {
		$old_status = $this->get_status();
		$new_status = 'sab-' === substr( $new_status, 0, 4 ) ? substr( $new_status, 4 ) : $new_status;

		$this->set_prop( 'status', $new_status );

		$result = array(
			'from' => $old_status,
			'to'   => $new_status,
		);

		if ( true === $this->object_read && ! empty( $result['from'] ) && $result['from'] !== $result['to'] ) {
			$this->status_transition = array(
				'from'   => ! empty( $this->status_transition['from'] ) ? $this->status_transition['from'] : $result['from'],
				'to'     => $result['to'],
				'manual' => (bool) $manual_update,
				'note'   => $note,
			);

			if ( $manual_update ) {
				/**
				 * Action that fires after a document status has been updated manually.
				 *
				 * @param integer $document_id The document id.
				 * @param string  $status The new document status.
				 *
				 * @since 1.0.0
				 * @package Vendidero/StoreaBill
				 */
				do_action( 'storeabill_document_edit_status', $this->get_id(), $result['to'] );
			}
		}

		$this->maybe_set_number( $new_status );

		return $result;
	}

	/**
	 * Updates status of shipment immediately.
	 *
	 * @uses Shipment::set_status()
	 *
	 * @param string $new_status    Status to change the shipment to. No internal gzd- prefix is required.
	 * @param bool   $manual        Is this a manual order status change?
	 * @return bool
	 */
	public function update_status( $new_status, $manual = false ) {
		if ( ! $this->get_id() ) {
			return false;
		}

		try {
			$this->set_status( $new_status, $manual );
			$this->save();
		} catch ( Exception $e ) {
			$logger = wc_get_logger();
			$logger->error(
				sprintf( 'Error updating status for document #%d', $this->get_id() ),
				array(
					'document' => $this,
					'error'    => $e,
				)
			);

			$this->create_notice( _x( 'Update status event failed.', 'storeabill-core', 'woocommerce-germanized-pro' ) . ' ' . $e->getMessage(), 'error' );
			return false;
		}
		return true;
	}

	protected function set_address_prop( $prop, $data ) {
		$address          = $this->get_address();
		$address[ $prop ] = $data;

		$this->set_prop( 'address', $address );
	}

	/**
	 * Set invoice phone.
	 *
	 * @param string $phone The phone number.
	 */
	public function set_phone( $phone ) {
		$this->set_address_prop( 'phone', $phone );
	}

	/**
	 * Set invoice email.
	 *
	 * @param string $email The email address.
	 */
	public function set_email( $email ) {
		$this->set_address_prop( 'email', $email );
	}

	/**
	 * Set invoice company.
	 *
	 * @param string $company The company.
	 */
	public function set_company( $company ) {
		$this->set_address_prop( 'company', $company );
	}

	/**
	 * Set invoice first name.
	 *
	 * @param string $first_name The first name.
	 */
	public function set_first_name( $first_name ) {
		$this->set_address_prop( 'first_name', $first_name );
	}

	/**
	 * Set invoice last name.
	 *
	 * @param string $last_name The last name.
	 */
	public function set_last_name( $last_name ) {
		$this->set_address_prop( 'last_name', $last_name );
	}

	/**
	 * Set address.
	 *
	 * @param string $address The address.
	 */
	public function set_address_1( $address ) {
		$this->set_address_prop( 'address_1', $address );
	}

	/**
	 * Set address 2.
	 *
	 * @param string $address The address.
	 */
	public function set_address_2( $address ) {
		$this->set_address_prop( 'address_2', $address );
	}

	/**
	 * Set postcode.
	 *
	 * @param string $postcode The postcode.
	 */
	public function set_postcode( $postcode ) {
		$this->set_address_prop( 'postcode', $postcode );
	}

	/**
	 * Set city.
	 *
	 * @param string $city The city.
	 */
	public function set_city( $city ) {
		$this->set_address_prop( 'city', $city );
	}

	/**
	 * Set state.
	 *
	 * @param string $state The state.
	 */
	public function set_state( $state ) {
		$this->set_address_prop( 'state', $state );
	}

	/**
	 * Set invoice country.
	 *
	 * @param string $country The country in ISO format.
	 */
	public function set_country( $country ) {
		$this->set_address_prop( 'country', substr( $country, 0, 2 ) );
	}

	/**
	 * Set the date this document was created.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Set the date this document was last modified.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_modified( $date = null ) {
		$this->set_date_prop( 'date_modified', $date );
	}

	/**
	 * Set the date this document was first sent via email.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_sent( $date = null ) {
		$this->set_date_prop( 'date_sent', $date );
	}

	/**
	 * Set the custom date.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_custom( $date = null ) {
		$this->set_date_prop( 'date_custom', $date );
	}

	/**
	 * Set the extra custom date.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_custom_extra( $date = null ) {
		$this->set_date_prop( 'date_custom_extra', $date );
	}

	/**
	 * Set the parent id.
	 *
	 * @param string $id The id.
	 */
	public function set_parent_id( $parent_id ) {
		$this->set_prop( 'parent_id', absint( $parent_id ) );
	}

	/**
	 * Set the customer id.
	 *
	 * @param string $id The id.
	 */
	public function set_customer_id( $customer_id ) {
		$this->set_prop( 'customer_id', absint( $customer_id ) );
	}

	/**
	 * Set created via.
	 *
	 * @param string $created_via The desc.
	 */
	public function set_created_via( $created_via ) {
		$this->set_prop( 'created_via', $created_via );
	}

	/**
	 * Set created via.
	 *
	 * @param string $version The version.
	 */
	public function set_version( $version ) {
		$this->set_prop( 'version', $version );
	}

	/**
	 * Set document secret
	 *
	 * @param string $secret The document secret.
	 */
	public function set_secret( $secret ) {
		$this->set_prop( 'secret', $secret );
	}

	/**
	 * Set the revision.
	 *
	 * @param integer $revision The revision.
	 */
	public function set_revision( $revision ) {
		$this->set_prop( 'revision', absint( $revision ) );
	}

	/**
	 * Set the author id.
	 *
	 * @param string $id The id.
	 */
	public function set_author_id( $author_id ) {
		$this->set_prop( 'author_id', absint( $author_id ) );
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
	 * Set the reference id.
	 *
	 * @param string $id The id.
	 */
	public function set_reference_id( $reference_id ) {
		$this->set_prop( 'reference_id', absint( $reference_id ) );
	}

	/**
	 * Set the reference number.
	 *
	 * @param string $reference_number The number.
	 */
	public function set_reference_number( $reference_number ) {
		$this->set_prop( 'reference_number', $reference_number );
	}

	/**
	 * Set the reference type.
	 *
	 * @param string $reference_type The type.
	 */
	public function set_reference_type( $reference_type ) {
		$this->set_prop( 'reference_type', $reference_type );
	}

	/**
	 * Set the document number.
	 *
	 * @param string $number The number.
	 */
	public function set_number( $number ) {
		$this->set_prop( 'number', $number );
	}

	/**
	 * Set the document journal type.
	 *
	 * @param string $type The type.
	 */
	public function set_journal_type( $type ) {
		$this->set_prop( 'journal_type', $type );

		$this->journal = null;
	}

	/**
	 * Set the document formatted number.
	 *
	 * @param string $number The number.
	 */
	public function set_formatted_number( $number ) {
		$this->set_prop( 'formatted_number', $number );
	}

	/**
	 * Set shipment address.
	 *
	 * @param string[] $address The address props.
	 */
	public function set_address( $address ) {
		$address = empty( $address ) ? array() : (array) $address;

		foreach ( $address as $prop => $value ) {
			$setter = "set_{$prop}";

			if ( is_callable( array( $this, $setter ) ) ) {
				$this->{$setter}( $value );
			} else {
				$this->set_address_prop( $prop, $value );
			}
		}
	}

	/**
	 * Set sync handlers-
	 *
	 * @param string[] $sync_handlers The sync handlers props.
	 */
	public function set_external_sync_handlers( $sync_handlers ) {
		$this->set_prop( 'external_sync_handlers', empty( $sync_handlers ) ? array() : (array) $sync_handlers );
	}

	public function has_changed() {
		$has_changed = parent::has_changed();

		foreach ( $this->get_items() as $item ) {
			if ( $item->has_changed() ) {
				$has_changed = true;
			}
		}

		if ( ! empty( $this->items_to_delete ) ) {
			$has_changed = true;
		}

		return $has_changed;
	}

	/**
	 * Return an array of items within this document.
	 *
	 * @return Item[]
	 */
	public function get_items( $types = '', $force_load_from_db = false ) {
		$items         = array();
		$types         = array_filter( (array) ( empty( $types ) ? $this->get_item_types() : $types ) );
		$document_type = $this->get_type();

		if ( $force_load_from_db ) {
			$this->items = array();
		}

		$types = array_map(
			function ( $type ) use ( $document_type ) {
				return sab_remove_document_item_type_prefix( $type, $document_type );
			},
			$types
		);

		foreach ( $types as $key => $type ) {
			if ( ! isset( $this->items[ $type ] ) ) {
				$this->items[ $type ] = array_filter( $this->data_store->read_items( $this, $type ) );
			}

			// Don't use array_merge here because keys are numeric.
			$items = $items + $this->items[ $type ];
		}

		// Refresh document reference
		foreach ( $items as $item ) {
			$item->set_document( $this );
		}

		/**
		 * Filter to adjust items belonging to a document.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a document type.
		 *
		 * Example hook name: storeabill_document_get_items
		 *
		 * @param string   $number The shipment number.
		 * @param Document $this The document object.
		 * @param string[] $types The item types.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		return apply_filters( "{$this->get_hook_prefix()}items", $items, $this, $types );
	}

	/**
	 * Return the item count included in this document.
	 *
	 * @return integer
	 */
	public function get_item_count( $types = '' ) {
		$count = 0;

		foreach ( $this->get_items( $types ) as $item ) {
			$count += $item->get_quantity();
		}

		return $count;
	}

	/**
	 * Get an item object.
	 *
	 * @param int $item_key Id of key of item to get.
	 *
	 * @return Item|false
	 */
	public function get_item( $item_key ) {
		// Search for item key.
		if ( $this->items ) {
			foreach ( $this->items as $group => $items ) {
				if ( isset( $items[ $item_key ] ) ) {
					$items[ $item_key ]->set_document( $this );
					return $items[ $item_key ];
				}
			}
		}

		// Load all items of type and cache.
		if ( is_numeric( $item_key ) ) {
			$type = Factory::get_document_item_type( $item_key );

			if ( ! $type ) {
				return false;
			}

			$items = $this->get_items( $type );

			return ! empty( $items[ $item_key ] ) ? $items[ $item_key ] : false;
		}

		return false;
	}

	/**
	 * Finds document item based on a reference id.
	 *
	 * @param integer $reference_id
	 *
	 * @return bool|Item
	 */
	public function get_item_by_reference_id( $reference_id ) {
		$items = $this->get_items();

		foreach ( $items as $item ) {
			if ( $item->get_reference_id() === (int) $reference_id ) {
				return $item;
			}
		}

		return false;
	}

	/**
	 * Remove item from the document.
	 *
	 * @param int $item_id Item ID to delete.
	 *
	 * @return false|void
	 */
	public function remove_item( $item_key ) {
		$item = $this->get_item( $item_key );

		if ( ! $item || ! ( $items_key = $this->get_items_key( $item ) ) ) {
			return false;
		}

		// Unset and remove later
		if ( is_numeric( $item_key ) ) {
			$this->items_to_delete[] = $item;
		}

		unset( $this->items[ $items_key ][ $item_key ] );
	}

	/**
	 * Returns the item type for a specific item.
	 *
	 * @param $item
	 *
	 * @return bool|string
	 */
	protected function get_items_key( $item ) {
		if ( ! in_array( $item->get_item_type(), $this->get_item_types(), true ) ) {
			return false;
		}

		return $item->get_item_type();
	}

	/**
	 * Returns the item type for a specific item.
	 *
	 * @param $item
	 *
	 * @return bool|string
	 */
	protected function get_notice_key( $notice ) {
		if ( ! in_array( $notice->get_type(), array_keys( sab_get_document_notice_types() ), true ) ) {
			return false;
		}

		return $notice->get_type();
	}

	/**
	 * Adds a document item to this document. The document item will not persist until save.
	 *
	 * @since 3.0.0
	 * @param Item $item Document item object.
	 *
	 * @return false|void
	 */
	public function add_item( $item ) {
		if ( ! $items_key = $this->get_items_key( $item ) ) {
			return false;
		}

		if ( ! in_array( $item->get_item_type(), $this->get_item_types(), true ) ) {
			return false;
		}

		// Make sure existing items are loaded so we can append this new one.
		if ( ! isset( $this->items[ $items_key ] ) ) {
			$this->items[ $items_key ] = $this->get_items( $item->get_item_type() );
		}

		if ( $item->get_reference_id() ) {
			$ref_item_exists = false;

			foreach ( $this->items[ $items_key ] as $existing_item ) {
				if ( $existing_item->get_reference_id() === $item->get_reference_id() ) {
					$ref_item_exists = true;
					break;
				}
			}

			if ( $ref_item_exists ) {
				return false;
			}
		}

		$children = $item->get_children();

		// Set parent.
		$item->set_document( $this );

		// Append new row with generated temporary ID
		if ( $item->get_id() ) {
			$this->items[ $items_key ][ $item->get_id() ] = $item;
		} else {
			$key = 'new_' . $items_key . ':' . count( $this->items[ $items_key ] ) . uniqid();

			$item->set_key( $key );

			$this->items[ $items_key ][ $key ] = $item;
		}

		// Add children to document
		foreach ( $children as $child ) {
			// Child has already been added
			if ( $child->get_id() > 0 && ( $exists = $this->get_item( $child->get_id() ) ) ) {
				continue;
			}

			$child->set_parent( $item );
			$this->add_item( $child );

			$item->reload_children();
		}
	}

	/**
	 * Return an array of notices within this document.
	 *
	 * @return Notice[]
	 */
	public function get_notices( $types = '' ) {
		$notices = array();
		$types   = array_filter( (array) ( empty( $types ) ? array_keys( sab_get_document_notice_types() ) : $types ) );

		foreach ( $types as $type ) {
			if ( ! isset( $this->notices[ $type ] ) ) {
				$this->notices[ $type ] = array_filter( $this->data_store->read_notices( $this, $type ) );
			}

			// Don't use array_merge here because keys are numeric.
			$notices = $notices + $this->notices[ $type ];
		}

		// Refresh document reference
		foreach ( $notices as $notice ) {
			$notice->set_document( $this );
		}

		/**
		 * Filter to adjust notices belonging to a document.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a document type.
		 *
		 * Example hook name: storeabill_document_get_notices
		 *
		 * @param Notice[] $notices The notices.
		 * @param Document $this The document object.
		 * @param string[] $types The document notice types.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		return apply_filters( "{$this->get_hook_prefix()}notices", $notices, $this, $types );
	}

	/**
	 * @param $text
	 * @param string $type
	 *
	 * @return bool
	 */
	public function create_notice( $text, $type = 'info' ) {
		$notice = sab_get_document_notice( 0, $type );

		if ( $notice ) {
			$notice->set_text( $text );

			$this->add_notice( $notice );
			$this->save();

			return $notice->get_id();
		}

		return false;
	}

	/**
	 * Adds a document notice to this document. The document notice will not persist until save.
	 *
	 * @since 3.0.0
	 * @param Notice $notice Document notice object.
	 *
	 * @return false|void
	 */
	public function add_notice( $notice ) {
		if ( ! $notice_key = $this->get_notice_key( $notice ) ) {
			return false;
		}

		if ( ! in_array( $notice->get_type(), array_keys( sab_get_document_notice_types() ), true ) ) {
			return false;
		}

		// Make sure existing items are loaded so we can append this new one.
		if ( ! isset( $this->notices[ $notice_key ] ) ) {
			$this->notices[ $notice_key ] = $this->get_notices( $notice->get_type() );
		}

		// Set parent.
		$notice->set_document( $this );

		// Append new row with generated temporary ID
		if ( $notice->get_id() ) {
			$this->notices[ $notice_key ][ $notice->get_id() ] = $notice;
		} else {
			$key = 'new_' . $notice_key . ':' . count( $this->notices[ $notice_key ] ) . uniqid();
			$notice->set_key( $key );

			$this->notices[ $notice_key ][ $key ] = $notice;
		}
	}

	/**
	 * Get a notice object.
	 *
	 * @param int $notice_key Id of key of notice to get.
	 *
	 * @return Notice|false
	 */
	public function get_notice( $notice_key ) {
		// Search for item key.
		if ( $this->notices ) {
			foreach ( $this->notices as $group => $notices ) {
				if ( isset( $notices[ $notice_key ] ) ) {
					$notices[ $notice_key ]->set_document( $this );
					return $notices[ $notice_key ];
				}
			}
		}

		// Load all items of type and cache.
		if ( is_numeric( $notice_key ) ) {
			$type = Factory::get_document_notice_type( $notice_key );

			if ( ! $type ) {
				return false;
			}

			$notices = $this->get_notices( $type );

			return ! empty( $notices[ $notice_key ] ) ? $notices[ $notice_key ] : false;
		}

		return false;
	}

	/**
	 * Remove item from the document.
	 *
	 * @param int $item_id Item ID to delete.
	 *
	 * @return bool
	 */
	public function remove_notice( $notice_key ) {
		$notice = $this->get_notice( $notice_key );

		if ( ! $notice ) {
			return false;
		}

		// Unset and remove later
		if ( is_numeric( $notice->get_key() ) ) {
			$this->notices_to_delete[] = $notice;
		}

		if ( isset( $this->notices[ $notice->get_type() ], $this->notices[ $notice->get_type() ][ $notice->get_key() ] ) ) {
			unset( $this->notices[ $notice->get_type() ][ $notice->get_key() ] );
		}

		return true;
	}

	/**
	 * Return an array of attachments within this document.
	 *
	 * @return Attachment[]
	 */
	public function get_attachments( $types = '' ) {
		$attachments = array();
		$types       = array_filter( (array) ( empty( $types ) ? array_keys( sab_get_document_attachment_types( $this->get_type() ) ) : $types ) );

		foreach ( $types as $type ) {
			if ( ! isset( $this->attachments[ $type ] ) ) {
				$this->attachments[ $type ] = array_filter( $this->data_store->read_attachments( $this, $type ) );
			}

			// Don't use array_merge here because keys are numeric.
			$attachments = $attachments + $this->attachments[ $type ];
		}

		// Refresh document reference
		foreach ( $attachments as $attachment ) {
			$attachment->set_document( $this );
		}

		/**
		 * Filter to adjust attachments belonging to a document.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a document type.
		 *
		 * Example hook name: storeabill_document_get_notices
		 *
		 * @param Attachment[] $attachments The attachments.
		 * @param Document $this The document object.
		 * @param string[] $types The document attachment types.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		return apply_filters( "{$this->get_hook_prefix()}attachments", $attachments, $this, $types );
	}

	/**
	 * Adds a document attachment to this document. The document attachment will not persist until save.
	 *
	 * @since 3.0.0
	 * @param Attachment $attachment Document attachment object.
	 *
	 * @return false|void
	 */
	public function add_attachment( $attachment ) {
		if ( ! in_array( $attachment->get_type(), array_keys( sab_get_document_attachment_types( $this->get_type() ) ), true ) ) {
			return false;
		}

		// Make sure existing items are loaded so we can append this new one.
		if ( ! isset( $this->attachments[ $attachment->get_type() ] ) ) {
			$this->attachments[ $attachment->get_type() ] = $this->get_attachments( $attachment->get_type() );
		}

		// Set parent.
		$attachment->set_document( $this );

		// Append new row with generated temporary ID
		if ( $attachment->get_id() ) {
			$this->attachments[ $attachment->get_type() ][ $attachment->get_id() ] = $attachment;
		} else {
			$key = 'new:' . count( $this->attachments[ $attachment->get_type() ] ) . uniqid();
			$attachment->set_key( $key );

			$this->attachments[ $attachment->get_type() ][ $key ] = $attachment;
		}
	}

	/**
	 * Get a attachment object.
	 *
	 * @param string|integer $key Attachment key or id.
	 *
	 * @return Attachment|false
	 */
	public function get_attachment( $attachment_key ) {
		if ( is_numeric( $attachment_key ) ) {
			$attachment_key = absint( $attachment_key );
		}

		foreach ( $this->get_attachments() as $attachment ) {
			if ( $attachment_key === $attachment->get_key() ) {
				$attachment->set_document( $this );

				return $attachment;
			}
		}

		return false;
	}

	/**
	 * Remove attachment from the document.
	 *
	 * @param string $type Type to delete
	 *
	 * @return boolean
	 */
	public function remove_attachment( $attachment_key ) {
		$attachment = $this->get_attachment( $attachment_key );

		if ( ! $attachment ) {
			return false;
		}

		// Unset and remove later
		if ( is_numeric( $attachment->get_key() ) ) {
			$this->attachments_to_delete[] = $attachment;
		}

		if ( isset( $this->attachments[ $attachment->get_type() ] ) ) {
			foreach ( $this->attachments[ $attachment->get_type() ] as $key => $inner_attachment ) {
				if ( $inner_attachment->get_key() === $attachment->get_key() ) {
					unset( $this->attachments[ $attachment->get_type() ][ $key ] );
				}
			}
		}

		return true;
	}

	protected function get_additional_number_placeholders() {
		return array();
	}

	protected function get_formatted_number_placeholders( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'number' => '',
			)
		);

		return apply_filters(
			"{$this->get_hook_prefix()}formatted_number_placeholders",
			array_merge(
				array(
					'{y}'      => $this->get_date_created()->date_i18n( 'y' ),
					'{Y}'      => $this->get_date_created()->date_i18n( 'Y' ),
					'{m}'      => $this->get_date_created()->date_i18n( 'm' ),
					'{n}'      => $this->get_date_created()->date_i18n( 'n' ),
					'{d}'      => $this->get_date_created()->date_i18n( 'd' ),
					'{j}'      => $this->get_date_created()->date_i18n( 'j' ),
					'{number}' => $args['number'],
				),
				$this->get_additional_number_placeholders()
			),
			$this,
			$args
		);
	}

	public function send_to_customer() {
		$errors = new \WP_Error();

		if ( ! $this->has_file() ) {
			$errors->add( 'email-error', _x( 'This document does not yet contain a file to be sent', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		} else {
			$result = Mailer::send( $this );

			if ( ! is_wp_error( $result ) ) {
				$this->maybe_set_date_sent();
			} else {
				foreach ( $result->get_error_messages() as $message ) {
					Package::extended_log( 'Error while sending ' . $this->get_title() . ' via email: ' . $message );
				}
			}

			return $result;
		}

		return $errors;
	}

	public function send_to_admin() {
		$errors = new \WP_Error();

		if ( ! $this->has_file() ) {
			$errors->add( 'email-error', _x( 'This document does not yet contain a file to be sent', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		} else {
			$result = Mailer::send( $this, true );

			return $result;
		}

		return $errors;
	}

	protected function maybe_set_date_sent() {
		$this->set_date_sent( time() );
		$this->save();
	}

	public function format_number( $number ) {
		$number_format = apply_filters( "{$this->get_general_hook_prefix()}default_number_format", '{number}', $this );

		if ( $journal = $this->get_journal() ) {
			$number_format = $journal->get_number_format();
			$min_size      = $journal->get_number_min_size();

			// Fill number with trailing zeros
			$number = sprintf( '%0' . $min_size . 'd', $number );
		}

		$number_format = apply_filters( "{$this->get_hook_prefix()}number_format", $number_format, $this, $number );
		$placeholders  = $this->get_formatted_number_placeholders( array( 'number' => $number ) );

		return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $number_format );
	}

	protected function maybe_set_number( $new_status ) {
		if ( ! $this->has_number() && 'closed' === $new_status && $this->get_journal() ) {
			$this->numbering_transition = true;
		}
	}

	/**
	 * Add a notice for status transition
	 *
	 * @since 1.0.0
	 * @param string $note       Note to be added giving status transition from and to details.
	 * @param bool   $transition Details of the status transition.
	 *
	 * @return integer The notice id.
	 */
	protected function add_status_transition_note( $note, $transition ) {
		$note = ( isset( $transition['note'] ) && ! empty( $transition['note'] ) ) ? $transition['note'] . ' ' . $note : $note;

		return $this->create_notice( trim( $note ), ( $transition['manual'] ? 'manual' : 'info' ) );
	}

	/**
	 * Handle the status transition.
	 */
	protected function status_transition() {
		$status_transition = $this->status_transition;

		// Reset status transition variable.
		$this->status_transition = false;

		if ( $status_transition ) {
			try {
				/**
				 * Action that fires before a document status transition happens.
				 *
				 * @param integer  $document_id The document id.
				 * @param Document $document The document object.
				 * @param array    $status_transition The status transition data.
				 *
				 * @since 1.0.0
				 * @package Vendidero/StoreaBill
				 */
				do_action( 'storeabill_document_before_status_change', $this->get_id(), $this, $this->status_transition );

				$status_to          = $status_transition['to'];
				$status_hook_prefix = 'storeabill_' . $this->get_type() . '_status';

				/**
				 * Action that indicates document status change to a specific status.
				 *
				 * The dynamic portion of the hook name, `$status_hook_prefix` constructs a unique prefix
				 * based on the document type. `$status_to` refers to the new document status.
				 *
				 * Example hook name: `storeabill_invoice_status_processing`
				 *
				 * @param integer  $document_id The document id.
				 * @param Document $document The document object.
				 *
				 * @since 1.0.0
				 * @package Vendidero/StoreaBill
				 */
				do_action( "{$status_hook_prefix}_$status_to", $this->get_id(), $this );

				if ( ! empty( $status_transition['from'] ) ) {
					$status_from = $status_transition['from'];

					/* translators: 1: old document status 2: new document status */
					$transition_note = sprintf( _x( 'Document status changed from %1$s to %2$s.', 'storeabill-core', 'woocommerce-germanized-pro' ), sab_get_document_status_name( $status_transition['from'], $this->get_type() ), sab_get_document_status_name( $status_transition['to'], $this->get_type() ) );

					// Note the transition occurred.
					$this->add_status_transition_note( $transition_note, $status_transition );

					/**
					 * Action that indicates document status change from a specific status to a specific status.
					 *
					 * The dynamic portion of the hook name, `$status_hook_prefix` constructs a unique prefix
					 * based on the document type. `$status_from` refers to the old document status.
					 * `$status_to` refers to the new status.
					 *
					 * Example hook name: `woocommerce_gzd_invoice_status_processing_to_paid`
					 *
					 * @param integer  $document_id The document id.
					 * @param Document $document The document object.
					 *
					 * @since 1.0.0
					 * @package Vendidero/StoreaBill
					 */
					do_action( "{$status_hook_prefix}_{$status_from}_to_{$status_to}", $this->get_id(), $this );

					/**
					 * Action that indicates shipment status change.
					 *
					 * @param integer  $document_id The document id.
					 * @param string   $status_from The old document status.
					 * @param string   $status_to The new document status.
					 * @param Document $document The document object.
					 *
					 * @since 1.0.0
					 * @package Vendidero/StoreaBill
					 */
					do_action( 'storeabill_document_status_changed', $this->get_id(), $status_from, $status_to, $this );
				} else {
					/* translators: %s: new document status */
					$transition_note = sprintf( _x( 'Document status set to %s.', 'storeabill-core', 'woocommerce-germanized-pro' ), sab_get_document_status_name( $status_transition['to'], $this->get_type() ) );

					// Note the transition occurred.
					$this->add_status_transition_note( $transition_note, $status_transition );
				}
			} catch ( Exception $e ) {
				$logger = wc_get_logger();
				$logger->error(
					sprintf( 'Status transition of document #%d errored!', $this->get_id() ),
					array(
						'document' => $this,
						'error'    => $e,
					)
				);

				$this->create_notice( _x( 'Error during status transition.', 'storeabill-core', 'woocommerce-germanized-pro' ) . ' ' . $e->getMessage(), 'error' );
			}
		}
	}

	/**
	 * Deletes items.
	 *
	 * @param string $type Either empty or a specific item type (e.g product).
	 */
	public function remove_items( $type = '' ) {
		if ( ! empty( $type ) ) {
			$this->data_store->delete_items( $this, $type );
			$this->items[ $type ] = null;
		} else {
			$this->data_store->delete_items( $this );
			$this->items = array();
		}
	}

	/**
	 * Save all items which are part of this document.
	 */
	protected function save_items() {
		$items_changed = false;

		foreach ( $this->items_to_delete as $item ) {
			$item->delete();
			$items_changed = true;
		}

		$children = array();

		// Add/save items
		foreach ( $this->items as $item_group => $items ) {
			$children[ $item_group ] = array();

			if ( is_array( $items ) ) {
				foreach ( $items as $item_key => $item ) {
					// Do not save children at this point
					if ( $item->get_parent() ) {
						$children[ $item_group ][ $item_key ] = $item;
						continue;
					}

					if ( $this->save_item( $item, $item_key, $item_group ) ) {
						$items_changed = true;
					}
				}
			}
		}

		/**
		 * Save children after their parents have already been saved.
		 * This way their parents do already own an id which is necessary to
		 * store the children -> parent relationship to db.
		 */
		if ( ! empty( $children ) ) {
			foreach ( $children as $item_group => $items ) {
				if ( is_array( $items ) ) {
					foreach ( $items as $item_key => $item ) {
						if ( $this->save_item( $item, $item_key, $item_group ) ) {
							$items_changed = true;
						}
					}
				}
			}
		}

		return $items_changed;
	}

	/**
	 * Save all notices which are part of this document.
	 */
	protected function save_notices() {
		$notices         = $this->get_notices();
		$notices_changed = false;

		foreach ( $this->notices_to_delete as $notice ) {
			$notice->delete();
			$notices_changed = true;
		}

		// Add/save items
		foreach ( $notices as $notice ) {
			$notice->set_document_id( $this->get_id() );
			$notice->save();
		}

		$this->notices = array();

		return $notices_changed;
	}

	/**
	 * Save all attachments which are part of this document.
	 */
	protected function save_attachments() {
		$attachments = $this->get_attachments();

		foreach ( $this->attachments_to_delete as $attachment ) {
			$attachment->delete();
		}

		// Add/save items
		foreach ( $attachments as $attachment ) {
			$attachment->set_document_id( $this->get_id() );
			$attachment->save();
		}

		$this->attachments = array();
	}

	/**
	 * @param Item $item
	 * @param $item_key
	 * @param $item_group
	 *
	 * @return bool
	 */
	private function save_item( $item, $item_key, $item_group ) {
		$item->set_document( $this );

		// Maybe update parent id.
		if ( $parent = $item->get_parent() ) {
			$item->set_parent_id( $parent->get_id() );
		}

		$item_changed = $item->has_changed();
		$item_id      = $item->save();

		// If ID changed (new item saved to DB)...
		if ( $item_id !== $item_key ) {
			$this->items[ $item_group ][ $item_id ] = $item;
			unset( $this->items[ $item_group ][ $item_key ] );

			$item_changed = true;
		}

		return $item_changed;
	}

	/**
	 * Save data to the database.
	 *
	 * @return integer|\WP_Error document id or error.
	 */
	public function save() {
		try {
			$is_new               = false;
			$revision_before_save = $this->get_revision();

			if ( $this->data_store ) {
				/**
				 * Action that fires before saving a certain document type to the db.
				 *
				 * The dynamic portion of the hook name, `$this->object_type` constructs a unique prefix
				 * based on the document type.
				 *
				 * Example hook name: `storeabill_before_invoice_object_save`
				 *
				 * @param Document      $document The document object.
				 * @param WC_Data_Store $data_store The data store object.
				 *
				 * @since 1.0.0
				 * @package Vendidero/StoreaBill
				 */
				do_action( "storeabill_before_{$this->object_type}_object_save", $this, $this->data_store );

				if ( $this->get_id() ) {
					if ( $this->data_store->get_document_revision( $this ) !== $this->get_revision() ) {
						throw new \Exception( sprintf( _x( 'This document is in a dirty state and could not be saved - a newer revision exists. Current changes: %s', 'storeabill-core', 'woocommerce-germanized-pro' ), sab_print_r( $this->get_changes(), true ) ) );
					}

					$this->data_store->update( $this );
				} else {
					/**
					 * Make sure to trigger numbering transitions for new
					 * documents with default closed status too.
					 */
					if ( 'closed' === $this->get_status() ) {
						$this->maybe_set_number( 'closed' );
					}

					$this->data_store->create( $this );
					$is_new = true;
				}
			}

			$this->numbering_transition = false;

			$items_changed = $this->save_items();

			/**
			 * Force updating revision in case items were removed or added.
			 */
			if ( true === $items_changed && $this->get_revision() <= $revision_before_save ) {
				$this->set_revision( $this->get_revision() + 1 );
				$this->data_store->update( $this );
			}

			$this->save_notices();
			$this->save_attachments();

			/**
			 * Action that fires after saving a certain document type to the db.
			 *
			 * The dynamic portion of the hook name, `$this->object_type` constructs a unique prefix
			 * based on the document type.
			 *
			 * Example hook name: `storeabill_before_invoice_object_save`
			 *
			 * @param Document      $document The document object.
			 * @param WC_Data_Store $data_store The data store object.
			 * @param boolean       $is_new Indicates whether this is a new document or not.
			 *
			 * @since 1.0.0
			 * @package Vendidero/StoreaBill
			 */
			do_action( "storeabill_after_{$this->object_type}_object_save", $this, $this->data_store, $is_new );

			$this->status_transition();
		} catch ( Exception $e ) {
			Package::log( sprintf( 'Error saving document #%d: %s', $this->get_id(), $e->getMessage() ), 'error' );

			return new \WP_Error( 500, $e->getMessage() );
		}

		return $this->get_id();
	}
}
