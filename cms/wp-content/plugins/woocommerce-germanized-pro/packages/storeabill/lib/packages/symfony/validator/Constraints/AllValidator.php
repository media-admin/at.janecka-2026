<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vendidero\StoreaBill\Vendor\Symfony\Component\Validator\Constraints;

use Vendidero\StoreaBill\Vendor\Symfony\Component\Validator\Constraint;
use Vendidero\StoreaBill\Vendor\Symfony\Component\Validator\ConstraintValidator;
use Vendidero\StoreaBill\Vendor\Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Vendidero\StoreaBill\Vendor\Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class AllValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof All) {
            throw new UnexpectedTypeException($constraint, All::class);
        }

        if (null === $value) {
            return;
        }

        if (!\is_array($value) && !$value instanceof \Traversable) {
            throw new UnexpectedValueException($value, 'iterable');
        }

        $context = $this->context;

        $validator = $context->getValidator()->inContext($context);

        foreach ($value as $key => $element) {
            $validator->atPath('['.$key.']')->validate($element, $constraint->constraints);
        }
    }
}
