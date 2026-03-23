<?php declare(strict_types = 1);

namespace Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\PhpDoc;

use Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\NodeAttributes;
use Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\Type\TypeNode;
use function trim;

class ParamOutTagValueNode implements PhpDocTagValueNode
{

	use NodeAttributes;

	public TypeNode $type;

	public string $parameterName;

	/** @var string (may be empty) */
	public string $description;

	public function __construct(TypeNode $type, string $parameterName, string $description)
	{
		$this->type = $type;
		$this->parameterName = $parameterName;
		$this->description = $description;
	}

	public function __toString(): string
	{
		return trim("{$this->type} {$this->parameterName} {$this->description}");
	}

}
