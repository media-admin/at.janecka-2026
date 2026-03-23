<?php

namespace Vendidero\StoreaBill\Document;

defined( 'ABSPATH' ) || exit;

class FileExporter extends Exporter {

	protected $document_type = '';

	protected $files = array();

	protected $limit = 25;

	/**
	 * Filename to export to.
	 *
	 * @var string
	 */
	protected $filename = 'sab-export.zip';

	public function __construct( $document_type = '' ) {
		if ( empty( $document_type ) ) {
			$document_type = 'invoice';
		}

		$this->document_type = $document_type;
	}

	public function get_title() {
		return sprintf( _x( 'Export %s', 'storeabill-core', 'woocommerce-germanized-pro' ), sab_get_document_type_label( $this->get_document_type_object(), 'plural' ) );
	}

	public function get_description() {
		return sprintf( _x( 'This tool allows you to generate and download a ZIP file containing PDF files', 'storeabill-core', 'woocommerce-germanized-pro' ) );
	}

	public function get_file_extension() {
		return 'zip';
	}

	public function get_document_type() {
		return $this->document_type;
	}

	public function render_filters() {
		$attachment_types = sab_get_document_attachment_types( $this->get_document_type() );

		if ( empty( $attachment_types ) ) {
			return;
		}
		?>
		<tr>
			<th scope="row">
				<label for="sab-exporter-attachment-types"><?php echo esc_html_x( 'Which attachments to export?', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></label>
			</th>
			<td>
				<select id="sab-exporter-attachment-types" name="attachment_types[]" class="sab-exporter-attachment-types sab-enhanced-select" style="width:100%;" multiple data-sortable="true" data-placeholder="<?php echo esc_html_x( 'Do not export attachments', 'storeabill-core', 'woocommerce-germanized-pro' ); ?>">
					<?php
					$default_attachment_types = $this->get_default_filter_setting( 'attachment_types', array() );

					foreach ( $attachment_types as $attachment_type => $attachment_type_title ) {
						echo '<option value="' . esc_attr( $attachment_type ) . '" ' . selected( $attachment_type, in_array( $attachment_type, $default_attachment_types, true ) ? $attachment_type : '', false ) . '>' . esc_html( $attachment_type_title ) . '</option>';
					}
					?>
				</select>
			</td>
		</tr>
		<?php
	}

	public function prepare_data_to_export() {
		$result           = $this->get_documents();
		$attachment_types = $this->get_attachment_types();
		$this->total      = $result['total'];
		$this->files      = array();

		foreach ( $result['documents'] as $document ) {
			if ( ! empty( $document['primary_path'] ) && file_exists( $document['primary_path'] ) ) {
				$this->files[ $document['primary_path'] ] = basename( $document['primary_path'] );
			}

			foreach ( $document['attachments'] as $attachment ) {
				if ( in_array( $attachment['type'], $attachment_types, true ) ) {
					if ( file_exists( $attachment['path'] ) ) {
						$this->files[ $attachment['path'] ] = basename( $attachment['path'] );
					}
				}
			}

			++$this->total_exported;
		}
	}

	public function get_attachment_types() {
		return array_filter( (array) $this->get_filter( 'attachment_types' ) );
	}

	protected function get_file_size() {
		$file_size = @filesize( $this->get_file_path() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( false === $file_size ) {
			$file_size = 0;
		}

		return $file_size;
	}

	public function send_headers() {
		if ( function_exists( 'gc_enable' ) ) {
			gc_enable(); // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.gc_enableFound
		}
		if ( function_exists( 'apache_setenv' ) ) {
			@apache_setenv( 'no-gzip', 1 ); // @codingStandardsIgnoreLine
		}
		ob_clean();
		ob_end_flush();
		@ini_set( 'zlib.output_compression', 'Off' ); // @codingStandardsIgnoreLine
		@ini_set( 'output_buffering', 'Off' ); // @codingStandardsIgnoreLine
		@ini_set( 'output_handler', '' ); // @codingStandardsIgnoreLine
		ignore_user_abort( true );
		wc_set_time_limit( 0 );
		wc_nocache_headers();
		header( 'Content-Type: application/octet-stream; charset=utf-8' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Content-Length: ' . $this->get_file_size() );
		header( 'Content-Disposition: attachment; filename=' . $this->get_filename() );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
	}

	public function get_type() {
		return 'file';
	}

	protected function send_content() {
		@readfile( $this->get_file_path() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_readfile
	}

	public function get_file() {
		if ( ! class_exists( 'ZipArchive' ) ) {
			$this->add_error( _x( 'Please make sure to install the PHP zip package. Ask your webhoster for further information.', 'storeabill-core', 'woocommerce-germanized-pro' ) );

			return false;
		}

		$file = new \ZipArchive();

		if ( @file_exists( $this->get_file_path() ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			$result = $file->open( $this->get_file_path() );
		} else {
			$result = $file->open( $this->get_file_path(), \ZipArchive::CREATE );
			/**
			 * Add an empty placeholder file to the zip to prevent missing zip files
			 * for exports which do not contain any documents. $zip->close() will delete
			 * zip files automatically in case no file is included.
			 */
			$file->addFromString( '.', '' );
		}

		if ( true !== $result ) {
			$this->add_error( _x( 'Error while reading or creating ZIP file.', 'storeabill-core', 'woocommerce-germanized-pro' ) );

			return false;
		}

		return $file;
	}

	protected function write_data() {
		if ( $zip = $this->get_file() ) {
			foreach ( $this->files as $file => $filename ) {
				$zip->addFile( $file, $filename );
			}

			$zip->close();
		}
	}
}
