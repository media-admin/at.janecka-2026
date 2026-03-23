<?php declare(strict_types = 1);

namespace Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\NodeVisitor;

use Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\AbstractNodeVisitor;
use Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\Attribute;
use Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\Node;

final class CloningVisitor extends AbstractNodeVisitor
{

	public function enterNode(Node $originalNode): Node
	{
		$node = clone $originalNode;
		$node->setAttribute(Attribute::ORIGINAL_NODE, $originalNode);

		return $node;
	}

}
