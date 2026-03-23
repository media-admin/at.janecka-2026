<?php

namespace Vendidero\StoreaBill\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PDFMerge Interface
 *
 * @package  Germanized/StoreaBill/Interfaces
 * @version  1.0.0
 */
interface PDFMerge {

	public function __construct();

	public function add( $path, $pages = array(), $width = 210 );

	public function output( $filename );

	public function stream();
}
