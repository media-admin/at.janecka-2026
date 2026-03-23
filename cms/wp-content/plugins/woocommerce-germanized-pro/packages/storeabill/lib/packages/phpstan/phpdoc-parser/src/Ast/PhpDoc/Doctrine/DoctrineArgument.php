<?php declare(strict_types = 1);

namespace Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\PhpDoc\Doctrine;

use Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNode;
use Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\Node;
use Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\NodeAttributes;
use Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

/**
 * @phpstan-type ValueType = DoctrineAnnotation|IdentifierTypeNode|DoctrineArray|ConstExprNode
 */
class DoctrineArgument implements Node
{

	use NodeAttributes;

	public ?IdentifierTypeNode $key = null;

	/** @var ValueType */
	public $value;

	/**
	 * @param ValueType $value
	 */
	public function __construct(?IdentifierTypeNode $key, $value)
	{
		$this->key = $key;
		$this->value = $value;
	}


	public function __toString(): string
	{
		if ($this->key === null) {
			return (string) $this->value;
		}

		return $this->key . '=' . $this->value;
	}

}
