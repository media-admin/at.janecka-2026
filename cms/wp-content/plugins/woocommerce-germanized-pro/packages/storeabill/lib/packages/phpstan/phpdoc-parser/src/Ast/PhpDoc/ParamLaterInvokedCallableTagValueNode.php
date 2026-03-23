<?php declare(strict_types = 1);

namespace Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\PhpDoc;

use Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\NodeAttributes;
use function trim;

class ParamLaterInvokedCallableTagValueNode implements PhpDocTagValueNode
{

	use NodeAttributes;

	public string $parameterName;

	/** @var string (may be empty) */
	public string $description;

	public function __construct(string $parameterName, string $description)
	{
		$this->parameterName = $parameterName;
		$this->description = $description;
	}

	public function __toString(): string
	{
		return trim("{$this->parameterName} {$this->description}");
	}

}
