<?php

declare(strict_types=1);

namespace Vendidero\StoreaBill\Vendor\JMS\Serializer\Visitor\Factory;

use Vendidero\StoreaBill\Vendor\JMS\Serializer\Visitor\DeserializationVisitorInterface;

/**
 * @author Asmir Mustafic <goetas@gmail.com>
 */
interface DeserializationVisitorFactory
{
    public function getVisitor(): DeserializationVisitorInterface;
}
