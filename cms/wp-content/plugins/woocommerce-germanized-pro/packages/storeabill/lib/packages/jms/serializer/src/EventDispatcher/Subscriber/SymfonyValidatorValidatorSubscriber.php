<?php

declare(strict_types=1);

namespace Vendidero\StoreaBill\Vendor\JMS\Serializer\EventDispatcher\Subscriber;

use Vendidero\StoreaBill\Vendor\JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\EventDispatcher\ObjectEvent;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Exception\ValidationFailedException;
use Vendidero\StoreaBill\Vendor\Symfony\Component\Validator\Validator\ValidatorInterface;

final class SymfonyValidatorValidatorSubscriber implements EventSubscriberInterface
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ['event' => 'serializer.post_deserialize', 'method' => 'onPostDeserialize'],
        ];
    }

    public function onPostDeserialize(ObjectEvent $event): void
    {
        $context = $event->getContext();

        if ($context->getDepth() > 0) {
            return;
        }

        $validator = $this->validator;
        $groups = $context->hasAttribute('validation_groups') ? $context->getAttribute('validation_groups') : null;

        if (!$groups) {
            return;
        }

        $constraints = $context->hasAttribute('validation_constraints') ? $context->getAttribute('validation_constraints') : null;

        $list = $validator->validate($event->getObject(), $constraints, $groups);

        if ($list->count() > 0) {
            throw new ValidationFailedException($list);
        }
    }
}
