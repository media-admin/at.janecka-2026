<?php

declare(strict_types=1);

namespace Vendidero\StoreaBill\Vendor\JMS\Serializer\Exclusion;

use Vendidero\StoreaBill\Vendor\JMS\Serializer\Context;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Metadata\ClassMetadata;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Metadata\PropertyMetadata;

final class VersionExclusionStrategy implements ExclusionStrategyInterface
{
    /**
     * @var string
     */
    private $version;

    public function __construct(string $version)
    {
        $this->version = $version;
    }

    public function shouldSkipClass(ClassMetadata $metadata, Context $navigatorContext): bool
    {
        return false;
    }

    public function shouldSkipProperty(PropertyMetadata $property, Context $navigatorContext): bool
    {
        if ((null !== $version = $property->sinceVersion) && version_compare($this->version, $version, '<')) {
            return true;
        }

        return (null !== $version = $property->untilVersion) && version_compare($this->version, $version, '>');
    }
}
