<?php declare(strict_types = 1);

namespace Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\PhpDoc;

use Vendidero\StoreaBill\Vendor\PHPStan\PhpDocParser\Ast\NodeAttributes;
use function trim;

class DeprecatedTagValueNode implements PhpDocTagValueNode
{

	use NodeAttributes;

	/** @var string (may be empty) */
	public string $description;

	public function __construct(string $description)
	{
		$this->description = $description;
	}


	public function __toString(): string
	{
		return trim($this->description);
	}

}
