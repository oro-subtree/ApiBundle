<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\Criteria;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\FieldVisitor;

class CompleteCriteria implements ProcessorInterface
{
    const JOIN_ALIAS_TEMPLATE = 'alias%d';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $entityClass = $context->getClassName();
        if (!$entityClass || !$this->doctrineHelper->isManageableEntity($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $criteria = $context->getCriteria();
        $this->setJoinAliases($criteria);
        $this->completeJoins($criteria);
    }

    /**
     * Sets missing join aliases
     *
     * @param Criteria $criteria
     */
    protected function setJoinAliases(Criteria $criteria)
    {
        $counter = 0;
        $joins   = $criteria->getJoins();
        foreach ($joins as $join) {
            $counter++;
            if (!$join->getAlias()) {
                $join->setAlias(sprintf(self::JOIN_ALIAS_TEMPLATE, $counter));
            }
        }
    }

    /**
     * Adds missing joins
     *
     * @param Criteria $criteria
     */
    protected function completeJoins(Criteria $criteria)
    {
        $pathMap = $this->getJoinPathMap($criteria);
        if (!empty($pathMap)) {
            $this->sortJoinPathMap($pathMap);
            foreach ($pathMap as $path => $item) {
                if (!$criteria->hasJoin($path)) {
                    $parentAlias = empty($item['parent'])
                        ? Criteria::ROOT_ALIAS_PLACEHOLDER
                        : $criteria->getJoin(implode(Criteria::FIELD_DELIMITER, $item['parent']))->getAlias();
                    $criteria
                        ->addLeftJoin($path, $parentAlias . Criteria::FIELD_DELIMITER . $item['field'])
                        ->setAlias($item['field']);
                }
            }
        }
    }

    /**
     * Gets all join paths required for the given Criteria object
     *
     * @param Criteria $criteria
     *
     * @return array [path => ['field' => string, 'parent' => [...]], ...]
     */
    protected function getJoinPathMap(Criteria $criteria)
    {
        $whereExpr = $criteria->getWhereExpression();
        if (!$whereExpr) {
            return [];
        }

        $visitor = new FieldVisitor();
        $visitor->dispatch($whereExpr);

        $fields = $visitor->getFields();

        $pathMap = [];
        foreach ($fields as $field) {
            $lastDelimiter = strrpos($field, Criteria::FIELD_DELIMITER);
            if (false !== $lastDelimiter) {
                $path = substr($field, 0, $lastDelimiter);
                if (!isset($pathMap[$path])) {
                    $pathMap[$path] = $this->buildJoinPathMapValue($path);
                }
            }
        }
        $joinPaths = array_keys($criteria->getJoins());
        foreach ($joinPaths as $path) {
            if (!isset($pathMap[$path])) {
                $pathMap[$path] = $this->buildJoinPathMapValue($path);
            }
        }

        return $pathMap;
    }

    /**
     * @param string $path
     *
     * @return array
     */
    protected function buildJoinPathMapValue($path)
    {
        $lastDelimiter = strrpos($path, Criteria::FIELD_DELIMITER);
        if (false === $lastDelimiter) {
            return [
                'field'  => $path,
                'parent' => []
            ];
        } else {
            return [
                'field'  => substr($path, $lastDelimiter + 1),
                'parent' => explode(Criteria::FIELD_DELIMITER, $path)
            ];
        }
    }

    /**
     * @param array $pathMap
     */
    protected function sortJoinPathMap(array &$pathMap)
    {
        uasort(
            $pathMap,
            function (array $a, array $b) {
                $aCount = count($a['parent']);
                $bCount = count($b['parent']);
                if ($aCount === $bCount) {
                    return 0;
                }

                return ($aCount < $bCount) ? -1 : 1;
            }
        );
    }
}