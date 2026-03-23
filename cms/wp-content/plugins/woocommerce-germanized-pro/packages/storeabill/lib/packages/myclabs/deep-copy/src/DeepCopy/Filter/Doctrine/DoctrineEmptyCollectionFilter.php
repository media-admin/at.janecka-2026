<?php

namespace Vendidero\StoreaBill\Vendor\DeepCopy\Filter\Doctrine;

use Vendidero\StoreaBill\Vendor\DeepCopy\Filter\Filter;
use Vendidero\StoreaBill\Vendor\DeepCopy\Reflection\ReflectionHelper;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @final
 */
class DoctrineEmptyCollectionFilter implements Filter
{
    /**
     * Sets the object property to an empty doctrine collection.
     *
     * @param object   $object
     * @param string   $property
     * @param callable $objectCopier
     */
    public function apply($object, $property, $objectCopier)
    {
        $reflectionProperty = ReflectionHelper::getProperty($object, $property);
        if (PHP_VERSION_ID < 80100) {
            $reflectionProperty->setAccessible(true);
        }

        $reflectionProperty->setValue($object, new ArrayCollection());
    }
} 