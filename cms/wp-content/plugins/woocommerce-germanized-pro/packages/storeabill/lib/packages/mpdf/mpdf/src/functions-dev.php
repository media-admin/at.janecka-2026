<?php

if (!function_exists('storeabill_vendor_dd')) {
	function storeabill_vendor_dd(...$args)
	{
		if (function_exists('dump')) {
			dump(...$args);
		} else {
			var_dump(...$args);
		}
		die;
	}
}
