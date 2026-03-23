<?php

declare(strict_types=1);

namespace Vendidero\StoreaBill\Vendor\JMS\Serializer\ContextFactory;

use Vendidero\StoreaBill\Vendor\JMS\Serializer\SerializationContext;

/**
 * Serialization Context Factory Interface.
 */
interface SerializationContextFactoryInterface
{
    public function createSerializationContext(): SerializationContext;
}
