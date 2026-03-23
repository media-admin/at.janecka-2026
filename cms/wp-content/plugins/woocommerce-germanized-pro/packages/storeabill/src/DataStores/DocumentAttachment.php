<?php

namespace Vendidero\StoreaBill\DataStores;

use WC_Data;
use Exception;
use WC_Data_Store_WP;
use WC_Object_Data_Store_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Document Item Data Store
 *
 * @version 1.0.0
 */
class DocumentAttachment extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

	/**
	 * Data stored in meta keys, but not considered "meta" for an order.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array();

	protected $core_props = array(
		'document_id',
		'extension',
		'date_created',
		'type',
		'relative_path',
		'name',
	);

	/**
	 * Meta type. This should match up with
	 * the types available at https://developer.wordpress.org/reference/functions/add_metadata/.
	 * WP defines 'post', 'user', 'comment', and 'term'.
	 *
	 * @var string
	 */
	protected $meta_type = 'storeabill_document_attachment';

	/**
	 * Create a new document attachment in the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Attachment $attachment Document attachment object.
	 *
	 * @since 3.0.0
	 */
	public function create( &$attachment ) {
		global $wpdb;

		$attachment->set_date_created( time() );

		$wpdb->insert(
			$wpdb->storeabill_document_attachments,
			array(
				'document_id'                          => $attachment->get_document_id(),
				'document_attachment_type'             => $attachment->get_type(),
				'document_attachment_name'             => $attachment->get_name(),
				'document_attachment_extension'        => $attachment->get_extension(),
				'document_attachment_relative_path'    => $attachment->get_relative_path(),
				'document_attachment_date_created'     => gmdate( 'Y-m-d H:i:s', $attachment->get_date_created( 'edit' )->getOffsetTimestamp() ),
				'document_attachment_date_created_gmt' => gmdate( 'Y-m-d H:i:s', $attachment->get_date_created( 'edit' )->getTimestamp() ),
			)
		);

		$attachment->set_id( $wpdb->insert_id );
		$this->save_attachment_data( $attachment );
		$attachment->save_meta_data();
		$attachment->apply_changes();
		$this->clear_cache( $attachment );

		/**
		 * Action that indicates that a new document attachment has been created in the DB.
		 *
		 * @param integer                                   $document_notice_id The document notice id.
		 * @param \Vendidero\StoreaBill\Document\Attachment $attachment The document attachment object.
		 * @param integer                                   $document_id The document id.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		do_action( 'storeabill_new_document_attachment', $attachment->get_id(), $attachment, $attachment->get_document_id() );
	}

	/**
	 * Update a document attachmetn in the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Attachment $attachment Document attachment object.
	 *
	 *@since 3.0.0
	 */
	public function update( &$attachment ) {
		global $wpdb;

		$core_props      = $this->core_props;
		$changed_props   = array_keys( $attachment->get_changes() );
		$attachment_data = array();

		foreach ( $changed_props as $prop ) {
			if ( ! in_array( $prop, $core_props, true ) ) {
				continue;
			}

			switch ( $prop ) {
				case 'document_id':
					$attachment_data['document_id'] = absint( $attachment->get_document_id() );
					break;
				case 'date_created':
					if ( is_callable( array( $attachment, 'get_' . $prop ) ) ) {
						$attachment_data[ 'document_attachment_' . $prop ]          = $attachment->{'get_' . $prop}( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $attachment->{'get_' . $prop}( 'edit' )->getOffsetTimestamp() ) : null;
						$attachment_data[ 'document_attachment_' . $prop . '_gmt' ] = $attachment->{'get_' . $prop}( 'edit' ) ? gmdate( 'Y-m-d H:i:s', $attachment->{'get_' . $prop}( 'edit' )->getTimestamp() ) : null;
					}
					break;
				default:
					if ( is_callable( array( $attachment, 'get_' . $prop ) ) ) {
						$attachment_data[ 'document_attachment_' . $prop ] = $attachment->{'get_' . $prop}( 'edit' );
					}
					break;
			}
		}

		if ( ! empty( $attachment_data ) ) {
			$wpdb->update(
				$wpdb->storeabill_document_attachments,
				$attachment_data,
				array( 'document_attachment_id' => $attachment->get_id() )
			);
		}

		$this->save_attachment_data( $attachment );
		$attachment->save_meta_data();
		$attachment->apply_changes();
		$this->clear_cache( $attachment );

		/**
		 * Action that indicates that a document attachment has been updated in the DB.
		 *
		 * @param integer                                   $document_attachment_id The document attachment id.
		 * @param \Vendidero\StoreaBill\Document\Attachment $attachment The document attachment object.
		 * @param integer                                   $document_id The document id.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		do_action( 'storeabill_document_attachment_updated', $attachment->get_id(), $attachment, $attachment->get_document_id() );
	}

	/**
	 * Remove a document attachment from the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Attachment $attachment Document attachment object.
	 * @param array                                     $args Array of args to pass to the delete method.
	 *
	 *@since 1.0.0
	 */
	public function delete( &$attachment, $args = array() ) {
		if ( $attachment->get_id() ) {
			global $wpdb;

			/**
			 * Action that fires before deleting a document attachment from the DB.
			 *
			 * @param integer $document_attachment_id The document attachment id.
			 *
			 * @since 1.0.0
			 * @package Vendidero/StoreaBill
			 */
			do_action( 'storeabill_before_delete_document_attachment', $attachment->get_id() );

			$wpdb->delete( $wpdb->storeabill_document_attachments, array( 'document_attachment_id' => $attachment->get_id() ) );
			$wpdb->delete( $wpdb->storeabill_document_attachmentmeta, array( 'storeabill_document_attachment_id' => $attachment->get_id() ) );

			if ( $attachment->has_file() ) {
				wp_delete_file( $attachment->get_path() );
			}

			/**
			 * Action that indicates that a document attachment has been deleted from the DB.
			 *
			 * @param integer                                   $document_attachment_id The document attachment id.
			 * @param \Vendidero\StoreaBill\Document\Attachment $attachment The document attachment object.
			 *
			 * @since 1.0.0
			 * @package Vendidero/StoreaBill
			 */
			do_action( 'storeabill_delete_document_attachment', $attachment->get_id(), $attachment );
			$this->clear_cache( $attachment );
		}
	}

	/**
	 * Read a document attachment from the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Attachment $attachment Document attachment object.
	 *
	 * @throws Exception If invalid document attachment.
	 * @since 1.0.0
	 */
	public function read( &$attachment ) {
		global $wpdb;

		$attachment->set_defaults();

		// Get from cache if available.
		$data = wp_cache_get( 'attachment-' . $attachment->get_id(), 'document-attachments' );

		if ( false === $data ) {
			$data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->storeabill_document_attachments} WHERE document_attachment_id = %d LIMIT 1;", $attachment->get_id() ) );
			wp_cache_set( 'attachment-' . $attachment->get_id(), $data, 'document-attachments' );
		}

		if ( ! $data ) {
			throw new Exception( esc_html_x( 'Invalid document attachment.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		$attachment->set_props(
			array(
				'document_id'   => $data->document_id,
				'type'          => $data->document_attachment_type,
				'extension'     => $data->document_attachment_extension,
				'name'          => $data->document_attachment_name,
				'relative_path' => $data->document_attachment_relative_path,
				'date_created'  => sab_is_valid_mysql_datetime( $data->document_attachment_date_created_gmt ) ? wc_string_to_timestamp( $data->document_attachment_date_created_gmt ) : null,
			)
		);

		$this->read_attachment_data( $attachment );
		$this->read_extra_data( $attachment );

		$attachment->read_meta_data();
		$attachment->set_object_read( true );
	}

	/**
	 * Read extra data associated with the document attachment.
	 *
	 * @param \Vendidero\StoreaBill\Document\Attachment $attachment Document attachment object.
	 *
	 * @since 3.0.0
	 */
	protected function read_attachment_data( &$attachment ) {
		$props = array();

		foreach ( $this->internal_meta_keys as $meta_key ) {
			$props[ substr( $meta_key, 1 ) ] = get_metadata( 'storeabill_document_attachment', $attachment->get_id(), $meta_key, true );
		}

		$attachment->set_props( $props );
	}

	/**
	 * Read extra data associated with the document.
	 *
	 * @param \Vendidero\StoreaBill\Document\Attachment $attachment Attachment object.
	 * @since 1.0.0
	 */
	protected function read_extra_data( &$attachment ) {
		foreach ( $attachment->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;

			if ( is_callable( array( $attachment, $function ) ) ) {
				$attachment->{$function}( get_metadata( 'storeabill_document_attachment', $attachment->get_id(), '_' . $key, true ) );
			}
		}
	}

	/**
	 * Update meta data in, or delete it from, the database.
	 *
	 * Avoids storing meta when it's either an empty string or empty array.
	 * Other empty values such as numeric 0 and null should still be stored.
	 * Data-stores can force meta to exist using `must_exist_meta_keys`.
	 *
	 * Note: WordPress `get_metadata` function returns an empty string when meta data does not exist.
	 *
	 * @param WC_Data $object The WP_Data object (WC_Coupon for coupons, etc).
	 * @param string   $meta_key Meta key to update.
	 * @param mixed    $meta_value Value to save.
	 *
	 * @since 3.6.0 Added to prevent empty meta being stored unless required.
	 *
	 * @return bool True if updated/deleted.
	 */
	protected function update_or_delete_meta( $object, $meta_key, $meta_value ) {
		if ( in_array( $meta_value, array( array(), '' ), true ) && ! in_array( $meta_key, $this->must_exist_meta_keys, true ) ) {
			$updated = delete_metadata( 'storeabill_document_attachment', $object->get_id(), $meta_key );
		} else {
			$updated = update_metadata( 'storeabill_document_attachment', $object->get_id(), $meta_key, $meta_value );
		}

		return (bool) $updated;
	}

	/**
	 * Saves a attachment's data to the database / meta.
	 * Ran after both create and update, so $notice->get_id() will be set.
	 *
	 * @param \Vendidero\StoreaBill\Document\Attachment $attachment Document attachment object.
	 *
	 * @since 1.0.0
	 */
	public function save_attachment_data( &$attachment ) {
		$updated_props     = array();
		$meta_key_to_props = array();

		foreach ( $this->internal_meta_keys as $meta_key ) {
			$prop_name = substr( $meta_key, 1 );

			if ( in_array( $prop_name, $this->core_props, true ) ) {
				continue;
			}

			$meta_key_to_props[ $meta_key ] = $prop_name;
		}

		// Make sure to take extra data into account.
		$extra_data_keys = $attachment->get_extra_data_keys();

		foreach ( $extra_data_keys as $key ) {
			$meta_key_to_props[ '_' . $key ] = $key;
		}

		$props_to_update = $this->get_props_to_update( $attachment, $meta_key_to_props, 'storeabill_document_attachment' );

		foreach ( $props_to_update as $meta_key => $prop ) {
			$getter = "get_$prop";

			if ( ! is_callable( array( $attachment, $getter ) ) ) {
				continue;
			}

			$value = $attachment->{"get_$prop"}( 'edit' );

			if ( is_bool( $value ) ) {
				$value = wp_slash( sab_bool_to_string( $value ) );
			} elseif ( is_string( $value ) ) {
				$value = wp_slash( $value );
			}

			$updated = $this->update_or_delete_meta( $attachment, $meta_key, $value );

			if ( $updated ) {
				$updated_props[] = $prop;
			}
		}

		/**
		 * Action that fires after updating a document attachment's properties.
		 *
		 * @param \Vendidero\StoreaBill\Document\Attachment $attachment The document attachment object.
		 * @param array                                     $changed_props The updated properties.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		do_action( 'storeabill_document_attachment_object_updated_props', $attachment, $updated_props );
	}

	/**
	 * Clear meta cache.
	 *
	 * @param \Vendidero\StoreaBill\Document\Attachment $attachment Document attachment object.
	 */
	public function clear_cache( &$attachment ) {
		wp_cache_delete( 'attachment-' . $attachment->get_id(), 'document-attachments' );
		wp_cache_delete( 'document-attachments-' . $attachment->get_document_id(), 'documents' );
		wp_cache_delete( $attachment->get_id(), $this->meta_type . '_meta' );
	}

	/**
	 * Table structure is slightly different between meta types, this function will return what we need to know.
	 *
	 * @since  3.0.0
	 * @return array Array elements: table, object_id_field, meta_id_field
	 */
	protected function get_db_info() {
		global $wpdb;

		$meta_id_field   = 'meta_id'; // for some reason users calls this umeta_id so we need to track this as well.
		$table           = $wpdb->storeabill_document_attachmentmeta;
		$object_id_field = $this->meta_type . '_id';

		if ( ! empty( $this->object_id_field_for_meta ) ) {
			$object_id_field = $this->object_id_field_for_meta;
		}

		return array(
			'table'           => $table,
			'object_id_field' => $object_id_field,
			'meta_id_field'   => $meta_id_field,
		);
	}
}
