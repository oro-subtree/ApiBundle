<?php

namespace Oro\Bundle\ApiBundle\Processor\Get;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizer;

class NormalizeData implements ProcessorInterface
{
    /** @var ObjectNormalizer */
    protected $objectNormalizer;

    /**
     * @param ObjectNormalizer $objectNormalizer
     */
    public function __construct(ObjectNormalizer $objectNormalizer)
    {
        $this->objectNormalizer = $objectNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        if (!$context->hasResult()) {
            // no result
            return;
        }

        $data = $context->getResult();
        if (empty($data)) {
            // nothing to do because of empty result
            return;
        }

        $context->setResult($this->objectNormalizer->normalizeItem($data));
    }
}
