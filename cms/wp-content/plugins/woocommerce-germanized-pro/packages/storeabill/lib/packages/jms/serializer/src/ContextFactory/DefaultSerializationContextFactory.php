<?php

declare(strict_types=1);

namespace Vendidero\StoreaBill\Vendor\JMS\Serializer\ContextFactory;

use Vendidero\StoreaBill\Vendor\JMS\Serializer\SerializationContext;

/**
 * Default Serialization Context Factory.
 */
final class DefaultSerializationContextFactory implements SerializationContextFactoryInterface
{
    public function createSerializationContext(): SerializationContext
    {
        return new SerializationContext();
    }
}
