<?php

declare(strict_types=1);

namespace Vendidero\StoreaBill\Vendor\JMS\Serializer\GraphNavigator\Factory;

use Vendidero\StoreaBill\Vendor\JMS\Serializer\GraphNavigatorInterface;

interface GraphNavigatorFactoryInterface
{
    public function getGraphNavigator(): GraphNavigatorInterface;
}
