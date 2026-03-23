<?php

namespace Vendidero\StoreaBill\Vendor\Mpdf\File;

class LocalContentLoader implements \Vendidero\StoreaBill\Vendor\Mpdf\File\LocalContentLoaderInterface
{

	public function load($path)
	{
		return file_get_contents($path);
	}

}
