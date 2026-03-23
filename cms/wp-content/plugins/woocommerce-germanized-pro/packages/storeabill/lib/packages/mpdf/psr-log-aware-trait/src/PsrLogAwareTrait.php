<?php

namespace Vendidero\StoreaBill\Vendor\Mpdf\PsrLogAwareTrait;

use Vendidero\StoreaBill\Vendor\Psr\Log\LoggerInterface;

trait PsrLogAwareTrait 
{

	/**
	 * @var \Vendidero\StoreaBill\Vendor\Psr\Log\LoggerInterface
	 */
	protected $logger;

	public function setLogger(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}
	
}
