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
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class NotBlankValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof NotBlank) {
            throw new UnexpectedTypeException($constraint, NotBlank::class);
        }

        if ($constraint->allowNull && null === $value) {
            return;
        }

        if (\is_string($value) && null !== $constraint->normalizer) {
            $value = ($constraint->normalizer)($value);
        }

        if (false === $value || (empty($value) && '0' != $value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(NotBlank::IS_BLANK_ERROR)
                ->addViolation();
        }
    }
}
