<?php

declare(strict_types=1);

namespace Vendidero\StoreaBill\Vendor\Metadata\Driver;

interface FileLocatorInterface
{
    public function findFileForClass(\ReflectionClass $class, string $extension): ?string;
}
