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

use Vendidero\StoreaBill\Vendor\Symfony\Component\Validator\Exception\ConstraintDefinitionException;

storeabill_vendor_trigger_deprecation('symfony/validator', '5.2', '%s is deprecated.', NumberConstraintTrait::class);

/**
 * @author Jan Schädlich <jan.schaedlich@sensiolabs.de>
 *
 * @deprecated since Symfony 5.2
 */
trait NumberConstraintTrait
{
    private function configureNumberConstraintOptions($options): array
    {
        if (null === $options) {
            $options = [];
        } elseif (!\is_array($options)) {
            $options = [$this->getDefaultOption() => $options];
        }

        if (isset($options['propertyPath'])) {
            throw new ConstraintDefinitionException(sprintf('The "propertyPath" option of the "%s" constraint cannot be set.', static::class));
        }

        if (isset($options['value'])) {
            throw new ConstraintDefinitionException(sprintf('The "value" option of the "%s" constraint cannot be set.', static::class));
        }

        $options['value'] = 0;

        return $options;
    }
}
