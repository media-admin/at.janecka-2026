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

/**
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class CompoundValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Compound) {
            throw new UnexpectedTypeException($constraint, Compound::class);
        }

        $context = $this->context;

        $validator = $context->getValidator()->inContext($context);

        $validator->validate($value, $constraint->constraints);
    }
}
