<?php

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Shared\Date as SpreadsheetDate;

class PMXI_XLSParser{

	public $csv_path;

	public $_filename;

	public $targetDir;

	public $xml;

	public function __construct($path, $targetDir = false){

		$this->_filename = $path;

		$wp_uploads = wp_upload_dir();

		$this->targetDir = ( ! $targetDir ) ? wp_all_import_secure_file($wp_uploads['basedir'] . DIRECTORY_SEPARATOR . PMXI_Plugin::UPLOADS_DIRECTORY ) : $targetDir;
	}

	public function parse(){		

        $tmpname = wp_unique_filename($this->targetDir,  preg_replace('%\W(xls|xlsx)$%i', ".csv", basename($this->_filename)));
        
        $this->csv_path = $this->targetDir  . '/' . wp_all_import_url_title($tmpname);               

        return $this->toXML();
	}

	protected function toXML(){

		// Check if alternative Excel processing is enabled for this import
		// Use the same import ID detection logic as PMXI_Upload class for cron/CLI compatibility
		$import_id = wp_all_import_get_import_id();
		$use_alternative = false;

		if ($import_id && $import_id !== 'new' && is_numeric($import_id)) {
			$import = new PMXI_Import_Record();
			$import->getById(intval($import_id));
			if (!$import->isEmpty()) {
				$use_alternative = !empty($import->options['use_alternative_excel_processing']);
			}
		}

		// For new imports, check session for the setting
		if (!$use_alternative && !empty(PMXI_Plugin::$session)) {
			$use_alternative = PMXI_Plugin::$session->get('use_alternative_excel_processing');
		}

		// Check for global flag (used during retry attempts)
		global $wp_all_import_force_alternative_excel;
		if (!empty($wp_all_import_force_alternative_excel)) {
			$use_alternative = true;
		}

		// Allow filter to override
		$use_alternative = apply_filters('wp_all_import_use_alternative_excel_processing', $use_alternative, $this->_filename);

		try {
			if ($use_alternative) {
				$objSpreadsheet = $this->load_excel_alternative_method($this->_filename);
			} else {
				// Use standard PhpSpreadsheet loading
				$objSpreadsheet = IOFactory::load($this->_filename);
			}
		} catch (Exception $e) {
			// If standard method failed and we haven't tried alternative yet, try it automatically
			if (!$use_alternative) {
				try {
					$objSpreadsheet = $this->load_excel_alternative_method($this->_filename);

					// If alternative method succeeded, enable it for this import
					if ($import_id) {
						$import = new PMXI_Import_Record();
						$import->getById($import_id);
						if (!$import->isEmpty()) {
							$options = $import->options;
							$options['use_alternative_excel_processing'] = 1;
							$import->set(array('options' => $options))->save();
						}
					}
				} catch (Exception $alternative_error) {
					// Both methods failed, throw the original error
					throw $e;
				}
			} else {
				// Alternative method was already being used and failed
				throw $e;
			}
		}

		// Check if alternative method already created CSV (objSpreadsheet will be null)
		if ($objSpreadsheet === null) {
			// CSV already created by alternative method
		} else {
			// Allow filters to modify the Spreadsheet object
			$objSpreadsheet = apply_filters('wp_all_import_phpexcel_object', $objSpreadsheet, $this->_filename);

			// Set the CSV delimiter; allow filters to modify it
			$spreadsheetDelimiter = ",";
			$spreadsheetDelimiter = apply_filters('wp_all_import_phpexcel_delimiter', $spreadsheetDelimiter, $this->_filename);

			// Instead of using the CSV writer which uses raw values, manually create CSV with formatted values
			// This preserves quotes and other formatting from Excel's custom number formats
			$worksheet = $objSpreadsheet->getActiveSheet();
			$highestRow = $worksheet->getHighestRow();
			$highestCol = $worksheet->getHighestDataColumn();
			$highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

			$csvHandle = fopen($this->csv_path, 'w');
			if ($csvHandle === false) {
				throw new Exception("Could not open CSV file for writing: " . $this->csv_path);
			}

			for ($row = 1; $row <= $highestRow; $row++) {
				$rowData = array();
				for ($col = 1; $col <= $highestColIndex; $col++) {
					$cell = $worksheet->getCellByColumnAndRow($col, $row);
					$isFormula = $cell->isFormula();
					$rawValue = $cell->getValue();

					if ($isFormula) {
						try {
							$rawValue = $cell->getCalculatedValue();
						} catch (\Throwable $calculationError) {
							$fallbackValue = method_exists($cell, 'getOldCalculatedValue') ? $cell->getOldCalculatedValue() : null;
							$rawValue = $fallbackValue !== null ? $fallbackValue : $rawValue;
						}
					}

					$formatCode = $cell->getStyle()->getNumberFormat()->getFormatCode();
					$isDateTime = false;

					try {
						$isDateTime = SpreadsheetDate::isDateTime($cell);
					} catch (Exception $e) {
						$isDateTime = false;
					}

					// Check if the format code contains quotes (indicating text should be added to display)
					// Excel custom formats use quotes to add literal text to the display
					if ($isDateTime) {
						// Prefer a full timestamp when the underlying serial includes a time portion,
						// because Excel can hide the time in display formats but keep it in the value.
						if (is_numeric($rawValue)) {
							// Detect a time component using a small epsilon to account for float conversion.
							$hasTimePart = abs($rawValue - floor($rawValue)) > 1e-9;
							$dateTime = SpreadsheetDate::excelToDateTimeObject($rawValue);
							$value = $dateTime->format($hasTimePart ? 'Y-m-d H:i:s' : 'Y-m-d');
						} else {
							$value = $cell->getFormattedValue();
						}
					} elseif (strpos($formatCode, '"') !== false && $formatCode !== 'General') {
						// Use formatted value to preserve the quotes added by custom format
						$value = $cell->getFormattedValue();
					} else {
						// Use raw value for normal cells
						$value = $rawValue;
					}

					$rowData[] = $value;
				}
				fputcsv($csvHandle, $rowData, $spreadsheetDelimiter, '"', '\\');
			}

			fclose($csvHandle);
		}

        include_once(PMXI_Plugin::ROOT_DIR . '/libraries/XmlImportCsvParse.php');

        $this->xml = new PMXI_CsvParser( array( 'filename' => $this->csv_path, 'targetDir' => $this->targetDir ) );

        @unlink($this->csv_path);

		return $this->xml->xml_path;

	}





	/**
	 * Alternative method to load problematic Excel files
	 * Uses a more basic approach that avoids PhpSpreadsheet's memory issues
	 *
	 * @param string $filename Path to Excel file
	 * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
	 */
	protected function load_excel_alternative_method($filename) {

		// Check if required extensions are available for manual extraction
		if (!class_exists('ZipArchive') || !extension_loaded('simplexml')) {
			// Fall back to basic PhpSpreadsheet with optimization
			return $this->load_excel_fallback_without_zip($filename);
		}

		// Try to extract and process the Excel file manually
		$temp_dir = sys_get_temp_dir() . '/wp_all_import_excel_' . uniqid();

		try {
			// Create temporary directory
			if (!mkdir($temp_dir, 0755, true)) {
				throw new Exception("Could not create temporary directory");
			}

			// Extract Excel file (it's a ZIP archive)
			$zip = new ZipArchive();
			if ($zip->open($filename) !== TRUE) {
				throw new Exception("Could not open Excel file as ZIP");
			}

			$zip->extractTo($temp_dir);
			$zip->close();

			// Read the worksheet data directly from XML
			$worksheet_file = $temp_dir . '/xl/worksheets/sheet1.xml';
			$shared_strings_file = $temp_dir . '/xl/sharedStrings.xml';

			if (!file_exists($worksheet_file)) {
				throw new Exception("Could not find worksheet data");
			}

			// Parse shared strings
			$shared_strings = array();
			if (file_exists($shared_strings_file)) {
				$shared_strings = $this->parse_shared_strings($shared_strings_file);
			}

			// Parse worksheet and create a simple CSV
			$csv_data = $this->parse_worksheet_to_csv($worksheet_file, $shared_strings);

			// Write CSV data to temporary file
			$temp_csv = $temp_dir . '/converted.csv';
			file_put_contents($temp_csv, $csv_data);

			// Copy to our target CSV path
			copy($temp_csv, $this->csv_path);

			// Clean up temporary directory
			$this->recursive_rmdir($temp_dir);

			// Return a dummy spreadsheet object since we've already created the CSV
			// We'll bypass the normal CSV conversion process
			return null; // Signal that CSV is already created

		} catch (Exception $e) {
			// Clean up on error
			if (is_dir($temp_dir)) {
				$this->recursive_rmdir($temp_dir);
			}
			throw $e;
		}
	}

	/**
	 * Fallback method when ZipArchive is not available
	 * Uses PhpSpreadsheet with basic optimization
	 *
	 * @param string $filename Path to Excel file
	 * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
	 */
	protected function load_excel_fallback_without_zip($filename) {

		try {
			// Use PhpSpreadsheet with basic memory optimization
			$reader = IOFactory::createReaderForFile($filename);

			// Apply basic optimizations if methods exist
			if (method_exists($reader, 'setReadDataOnly')) {
				$reader->setReadDataOnly(true);
			}

			if (method_exists($reader, 'setReadEmptyCells')) {
				$reader->setReadEmptyCells(false);
			}

			if (method_exists($reader, 'setIncludeCharts')) {
				$reader->setIncludeCharts(false);
			}

			// Try to load only the first sheet
			if (method_exists($reader, 'setLoadSheetsOnly')) {
				$reader->setLoadSheetsOnly(['Sheet1', 'Sheet 1', 'Worksheet', 'Data', 'Sheet']);
			}

			return $reader->load($filename);

		} catch (Exception $e) {
			// If all else fails, use basic IOFactory load
			error_log("WP All Import Excel: Fallback method failed, using basic load - " . $e->getMessage());
			return IOFactory::load($filename);
		}
	}

	/**
	 * Parse shared strings XML file
	 *
	 * @param string $file Path to sharedStrings.xml
	 * @return array
	 */
	protected function parse_shared_strings($file) {

		$shared_strings = array();

		try {
			$xml = simplexml_load_file($file);
			if ($xml !== false) {
				$index = 0;
				foreach ($xml->si as $si) {
						$shared_strings[$index] = (string)$si->t;
					$index++;
				}
			}
		} catch (Exception $e) {
			error_log("WP All Import Excel: Error parsing shared strings - " . $e->getMessage());
		}

		return $shared_strings;
	}

	/**
	 * Parse worksheet XML and convert to CSV
	 *
	 * @param string $file Path to worksheet XML
	 * @param array $shared_strings Shared strings array
	 * @return string CSV data
	 */
	protected function parse_worksheet_to_csv($file, $shared_strings) {

		$csv_data = '';
		$batch_size = 1000; // Process 1000 rows at a time
		$row_count = 0;
		$csv_rows = array();

		try {
			$xml = simplexml_load_file($file);
			if ($xml !== false) {

				// Process each row
				foreach ($xml->sheetData->row as $row) {
					$row_data = array();
					$row_num = (int)$row['r'];

					// Process each cell in the row
					foreach ($row->c as $cell) {
						$cell_ref = (string)$cell['r'];
						$cell_type = (string)$cell['t'];

						// Get cell value
						$value = '';
						if (isset($cell->v)) {
							$cell_value = (string)$cell->v;

							// If it's a shared string, look it up
							if ($cell_type === 's' && isset($shared_strings[$cell_value])) {
								$value = $shared_strings[$cell_value];
							} else {
								$value = $cell_value;
							}
						}

						// Extract column from cell reference (e.g., 'A1' -> 'A')
						preg_match('/([A-Z]+)/', $cell_ref, $matches);
						$col = $matches[1];
						$col_index = $this->column_letter_to_index($col);

						// Ensure row_data array is large enough
						while (count($row_data) <= $col_index) {
							$row_data[] = '';
						}

						$row_data[$col_index] = $value;
					}

					// Add row to current batch
					$csv_rows[$row_num] = $row_data;
					$row_count++;

					// Process batch if we've reached the limit
					if ($row_count % $batch_size === 0) {
						// Convert current batch to CSV and append to total
						$batch_csv = $this->convert_rows_to_csv($csv_rows);
						$csv_data .= $batch_csv;

						// Clear the batch array to free memory
						$csv_rows = array();

						// Force garbage collection to keep memory usage low
						if (function_exists('gc_collect_cycles')) {
							gc_collect_cycles();
						}
					}
				}

				// Process any remaining rows in the final batch
				if (!empty($csv_rows)) {
					$final_batch_csv = $this->convert_rows_to_csv($csv_rows);
					$csv_data .= $final_batch_csv;
				}
			}
		} catch (Exception $e) {
			error_log("WP All Import Excel: Error parsing worksheet - " . $e->getMessage());
		}

		return $csv_data;
	}

	/**
	 * Convert rows array to CSV format
	 *
	 * @param array $csv_rows Array of rows to convert
	 * @return string CSV data
	 */
	protected function convert_rows_to_csv($csv_rows) {

		$csv_data = '';
		ksort($csv_rows); // Sort by row number

		foreach ($csv_rows as $row_data) {
			$escaped_row = array_map(array($this, 'escape_csv_value'), $row_data);
			$csv_data .= implode(',', $escaped_row) . "\r\n";
		}

		return $csv_data;
	}

	/**
	 * Escape CSV value properly
	 *
	 * @param string $value Value to escape
	 * @return string Escaped value
	 */
	protected function escape_csv_value($value) {

		// Convert to string and handle null values
		$value = (string)$value;

		// If value contains comma, quote, or newline, wrap in quotes and escape quotes
		if (strpos($value, ',') !== false || strpos($value, '"') !== false ||
		    strpos($value, "\n") !== false || strpos($value, "\r") !== false) {
			return '"' . str_replace('"', '""', $value) . '"';
		}

		return $value;
	}

	/**
	 * Convert column letter to index (A=0, B=1, etc.)
	 *
	 * @param string $column Column letter(s)
	 * @return int
	 */
	protected function column_letter_to_index($column) {

		$index = 0;
		$length = strlen($column);

		for ($i = 0; $i < $length; $i++) {
			$index = $index * 26 + (ord($column[$i]) - ord('A') + 1);
		}

		return $index - 1; // Convert to 0-based index
	}

	/**
	 * Recursively remove directory
	 *
	 * @param string $dir Directory path
	 */
	protected function recursive_rmdir($dir) {

		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (is_dir($dir . "/" . $object)) {
						$this->recursive_rmdir($dir . "/" . $object);
					} else {
						unlink($dir . "/" . $object);
					}
				}
			}
			rmdir($dir);
		}
	}


}
