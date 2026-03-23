<?php

declare(strict_types=1);

namespace Vendidero\StoreaBill\Vendor\JMS\Serializer\ContextFactory;

use Vendidero\StoreaBill\Vendor\JMS\Serializer\DeserializationContext;

/**
 * Default Deserialization Context Factory.
 */
final class DefaultDeserializationContextFactory implements DeserializationContextFactoryInterface
{
    public function createDeserializationContext(): DeserializationContext
    {
        return new DeserializationContext();
    }
}
