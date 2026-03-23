<?php declare(strict_types = 1);

namespace Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\Type;

use Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\ConstExpr\ConstFetchNode;
use Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\Node;
use Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\NodeAttributes;
use function sprintf;

class ArrayShapeItemNode implements Node
{

	use NodeAttributes;

	/** @var ConstExprIntegerNode|ConstExprStringNode|ConstFetchNode|IdentifierTypeNode|null */
	public $keyName;

	public bool $optional;

	public TypeNode $valueType;

	/**
	 * @param ConstExprIntegerNode|ConstExprStringNode|ConstFetchNode|IdentifierTypeNode|null $keyName
	 */
	public function __construct($keyName, bool $optional, TypeNode $valueType)
	{
		$this->keyName = $keyName;
		$this->optional = $optional;
		$this->valueType = $valueType;
	}


	public function __toString(): string
	{
		if ($this->keyName !== null) {
			return sprintf(
				'%s%s: %s',
				(string) $this->keyName,
				$this->optional ? '?' : '',
				(string) $this->valueType,
			);
		}

		return (string) $this->valueType;
	}

}
