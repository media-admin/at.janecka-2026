<?php

declare(strict_types=1);

namespace Vendidero\StoreaBill\Vendor\JMS\Serializer\Accessor;

use Vendidero\StoreaBill\Vendor\JMS\Serializer\DeserializationContext;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Metadata\PropertyMetadata;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\SerializationContext;

/**
 * @author Asmir Mustafic <goetas@gmail.com>
 */
interface AccessorStrategyInterface
{
    /**
     * @return mixed
     */
    public function getValue(object $object, PropertyMetadata $metadata, SerializationContext $context);

    /**
     * @param mixed $value
     */
    public function setValue(object $object, $value, PropertyMetadata $metadata, DeserializationContext $context): void;
}
