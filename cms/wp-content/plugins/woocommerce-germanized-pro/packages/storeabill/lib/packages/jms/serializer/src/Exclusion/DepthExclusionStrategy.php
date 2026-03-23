<?php

declare(strict_types=1);

namespace Vendidero\StoreaBill\Vendor\JMS\Serializer\Exclusion;

use Vendidero\StoreaBill\Vendor\JMS\Serializer\Context;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Metadata\ClassMetadata;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Metadata\PropertyMetadata;

/**
 * @author Adrien Brault <adrien.brault@gmail.com>
 */
final class DepthExclusionStrategy implements ExclusionStrategyInterface
{
    public function shouldSkipClass(ClassMetadata $metadata, Context $context): bool
    {
        return $this->isTooDeep($context);
    }

    public function shouldSkipProperty(PropertyMetadata $property, Context $context): bool
    {
        return $this->isTooDeep($context);
    }

    private function isTooDeep(Context $context): bool
    {
        $relativeDepth = 0;

        foreach ($context->getMetadataStack() as $metadata) {
            if (!$metadata instanceof PropertyMetadata) {
                continue;
            }

            $relativeDepth++;

            if (0 === $metadata->maxDepth && $context->getMetadataStack()->top() === $metadata) {
                continue;
            }

            if (null !== $metadata->maxDepth && $relativeDepth > $metadata->maxDepth) {
                return true;
            }
        }

        return false;
    }
}
