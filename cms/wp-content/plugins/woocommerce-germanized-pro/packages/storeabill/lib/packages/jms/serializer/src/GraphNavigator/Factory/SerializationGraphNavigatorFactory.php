<?php

declare (strict_types=1);
namespace Vendidero\StoreaBill\Vendor\JMS\Serializer\GraphNavigator\Factory;

use Vendidero\StoreaBill\Vendor\JMS\Serializer\Accessor\AccessorStrategyInterface;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Accessor\DefaultAccessorStrategy;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\EventDispatcher\EventDispatcher;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\EventDispatcher\EventDispatcherInterface;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Expression\ExpressionEvaluatorInterface;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\GraphNavigator\SerializationGraphNavigator;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\GraphNavigatorInterface;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Handler\HandlerRegistryInterface;
use Vendidero\StoreaBill\Vendor\Metadata\MetadataFactoryInterface;
final class SerializationGraphNavigatorFactory implements GraphNavigatorFactoryInterface
{
    /**
     * @var \MetadataFactoryInterface
     */
    private $metadataFactory;
    /**
     * @var HandlerRegistryInterface
     */
    private $handlerRegistry;
    /**
     * @var AccessorStrategyInterface
     */
    private $accessor;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var ExpressionEvaluatorInterface
     */
    private $expressionEvaluator;
    public function __construct(MetadataFactoryInterface $metadataFactory, HandlerRegistryInterface $handlerRegistry, ?AccessorStrategyInterface $accessor = null, ?EventDispatcherInterface $dispatcher = null, ?ExpressionEvaluatorInterface $expressionEvaluator = null)
    {
        $this->metadataFactory = $metadataFactory;
        $this->handlerRegistry = $handlerRegistry;
        $this->accessor = $accessor ?: new DefaultAccessorStrategy();
        $this->dispatcher = $dispatcher ?: new EventDispatcher();
        $this->expressionEvaluator = $expressionEvaluator;
    }
    public function getGraphNavigator(): GraphNavigatorInterface
    {
        return new SerializationGraphNavigator($this->metadataFactory, $this->handlerRegistry, $this->accessor, $this->dispatcher, $this->expressionEvaluator);
    }
}