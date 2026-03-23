<?php declare(strict_types = 1);

namespace Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\PhpDoc;

use Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\NodeAttributes;
use Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use function trim;

class ExtendsTagValueNode implements PhpDocTagValueNode
{

	use NodeAttributes;

	public GenericTypeNode $type;

	/** @var string (may be empty) */
	public string $description;

	public function __construct(GenericTypeNode $type, string $description)
	{
		$this->type = $type;
		$this->description = $description;
	}


	public function __toString(): string
	{
		return trim("{$this->type} {$this->description}");
	}

}
