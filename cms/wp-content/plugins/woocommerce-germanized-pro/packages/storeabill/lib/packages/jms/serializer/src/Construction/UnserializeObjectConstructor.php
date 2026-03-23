<?php

declare(strict_types=1);

namespace Vendidero\StoreaBill\Vendor\JMS\Serializer\Construction;

use Vendidero\StoreaBill\Vendor\Doctrine\Instantiator\Instantiator;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\DeserializationContext;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Metadata\ClassMetadata;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Visitor\DeserializationVisitorInterface;

final class UnserializeObjectConstructor implements ObjectConstructorInterface
{
    /** @var Instantiator */
    private $instantiator;

    /**
     * {@inheritdoc}
     */
    public function construct(DeserializationVisitorInterface $visitor, ClassMetadata $metadata, $data, array $type, DeserializationContext $context): ?object
    {
        return $this->getInstantiator()->instantiate($metadata->name);
    }

    private function getInstantiator(): Instantiator
    {
        if (null === $this->instantiator) {
            $this->instantiator = new Instantiator();
        }

        return $this->instantiator;
    }
}
