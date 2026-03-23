<?php

declare(strict_types=1);

namespace Vendidero\StoreaBill\Vendor\JMS\Serializer\Exclusion;

use Vendidero\StoreaBill\Vendor\JMS\Serializer\Context;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Expression\CompilableExpressionEvaluatorInterface;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Expression\Expression;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Expression\ExpressionEvaluatorInterface;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Metadata\ClassMetadata;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Metadata\PropertyMetadata;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\SerializationContext;

/**
 * Exposes an exclusion strategy based on the Symfony's expression language.
 * This is not a standard exclusion strategy and cannot be used in user applications.
 *
 * @internal
 *
 * @author Asmir Mustafic <goetas@gmail.com>
 */
final class ExpressionLanguageExclusionStrategy
{
    /**
     * @var ExpressionEvaluatorInterface
     */
    private $expressionEvaluator;

    public function __construct(ExpressionEvaluatorInterface $expressionEvaluator)
    {
        $this->expressionEvaluator = $expressionEvaluator;
    }

    public function shouldSkipClass(ClassMetadata $class, Context $navigatorContext): bool
    {
        if (null === $class->excludeIf) {
            return false;
        }

        $variables = [
            'context' => $navigatorContext,
            'class_metadata' => $class,
        ];
        if ($navigatorContext instanceof SerializationContext) {
            $variables['object'] = $navigatorContext->getObject();
        } else {
            $variables['object'] = null;
        }

        if (($class->excludeIf instanceof Expression) && ($this->expressionEvaluator instanceof CompilableExpressionEvaluatorInterface)) {
            return $this->expressionEvaluator->evaluateParsed($class->excludeIf, $variables);
        }

        return $this->expressionEvaluator->evaluate($class->excludeIf, $variables);
    }

    public function shouldSkipProperty(PropertyMetadata $property, Context $navigatorContext): bool
    {
        if (null === $property->excludeIf) {
            return false;
        }

        $variables = [
            'context' => $navigatorContext,
            'property_metadata' => $property,
        ];
        if ($navigatorContext instanceof SerializationContext) {
            $variables['object'] = $navigatorContext->getObject();
        } else {
            $variables['object'] = null;
        }

        if (($property->excludeIf instanceof Expression) && ($this->expressionEvaluator instanceof CompilableExpressionEvaluatorInterface)) {
            return $this->expressionEvaluator->evaluateParsed($property->excludeIf, $variables);
        }

        return $this->expressionEvaluator->evaluate($property->excludeIf, $variables);
    }
}
