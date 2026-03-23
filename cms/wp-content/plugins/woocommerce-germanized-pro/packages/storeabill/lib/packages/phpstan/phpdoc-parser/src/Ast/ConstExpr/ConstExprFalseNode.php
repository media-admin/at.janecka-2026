<?php declare(strict_types = 1);

namespace Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\ConstExpr;

use Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\NodeAttributes;

class ConstExprFalseNode implements ConstExprNode
{

	use NodeAttributes;

	public function __toString(): string
	{
		return 'false';
	}

}
