<?php

namespace Vendidero\StoreaBill\Vendor\Mpdf\Http;

use Vendidero\StoreaBill\Vendor\Psr\Http\Message\RequestInterface;

interface ClientInterface
{

	public function sendRequest(RequestInterface $request);

}
