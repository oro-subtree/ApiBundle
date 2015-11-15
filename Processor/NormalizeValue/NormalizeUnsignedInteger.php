<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class NormalizeUnsignedInteger implements ProcessorInterface
{
    const REQUIREMENT = '\d+';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var NormalizeValueContext $context */

        if (!$context->hasRequirement()) {
            $context->setRequirement(self::REQUIREMENT);
        }
        if ($context->hasResult()) {
            $value = $context->getResult();
            if (null !== $value && is_string($value)) {
                $normalizedValue = (int)$value;
                if (((string)$normalizedValue) !== $value || $normalizedValue < 0) {
                    throw new \RuntimeException(
                        sprintf('Expected unsigned integer value. Given "%s".', $value)
                    );
                }
                $context->setResult($normalizedValue);
            }
        }
    }
}
