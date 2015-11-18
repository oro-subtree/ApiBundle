<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class BuildCriteria implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var GetListContext $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $criteria     = $context->getCriteria();
        $filterValues = $context->getFilterValues();
        $filters      = $context->getFilters();
        foreach ($filters as $filterKey => $filter) {
            $filterValue = null;
            if ($filterValues->has($filterKey)) {
                $filterValue = $filterValues->get($filterKey);
            }
            $filter->apply($criteria, $filterValue);
        }
    }
}
