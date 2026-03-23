<?php declare(strict_types = 1);

namespace Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\Type;

use Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\NodeAttributes;

class ThisTypeNode implements TypeNode
{

	use NodeAttributes;

	public function __toString(): string
	{
		return '$this';
	}

}
