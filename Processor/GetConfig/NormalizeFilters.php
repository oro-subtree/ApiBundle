<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class NormalizeFilters implements ProcessorInterface
{
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
        /** @var ConfigContext $context */

        $filters = $context->getFilters();
        if (empty($filters)) {
            // nothing to normalize
            return;
        }

        $fields = !empty($filters['fields'])
            ? $filters['fields']
            : [];

        if (isset($filters['exclusion_policy']) && $filters['exclusion_policy'] === 'all') {
            $fields = $this->removeExclusions($fields);
        } else {
            $entityClass = $context->getClassName();
            if ($entityClass && $this->doctrineHelper->isManageableEntity($entityClass)) {
                $fields = $this->removeExclusions(
                    $this->completeFilters($fields, $entityClass)
                );
            }
        }

        $context->setFilters(
            [
                'exclusion_policy' => 'all',
                'fields'           => $fields
            ]
        );
    }

    /**
     * @param array  $filters
     * @param string $entityClass
     *
     * @return array
     */
    protected function completeFilters(array $filters, $entityClass)
    {
        $metadata = $this->doctrineHelper->getEntityMetadata($entityClass);

        $filters = $this->getFieldFilters($filters, $metadata);
        $filters = $this->getAssociationFilters($filters, $metadata);

        return $filters;
    }

    /**
     * @param array         $filters
     * @param ClassMetadata $metadata
     *
     * @return array
     */
    protected function getFieldFilters(array $filters, ClassMetadata $metadata)
    {
        $indexedColumns = [];
        if (isset($metadata->table['indexes'])) {
            foreach ($metadata->table['indexes'] as $index) {
                $indexedColumns[reset($index['columns'])] = true;
            }
        }
        $fieldNames = array_diff($metadata->getFieldNames(), $metadata->getIdentifierFieldNames());
        foreach ($fieldNames as $fieldName) {
            if (isset($filters[$fieldName])) {
                // already defined
                continue;
            }

            $mapping  = $metadata->getFieldMapping($fieldName);
            $hasIndex = false;
            if (isset($mapping['unique']) && true === $mapping['unique']) {
                $hasIndex = true;
            } elseif (isset($indexedColumns[$mapping['columnName']])) {
                $hasIndex = true;
            }
            if ($hasIndex) {
                $filters[$fieldName] = [
                    'data_type' => $mapping['type']
                ];
            }
        }

        return $filters;
    }

    /**
     * @param array         $filters
     * @param ClassMetadata $metadata
     *
     * @return array
     */
    protected function getAssociationFilters(array $filters, ClassMetadata $metadata)
    {
        $fieldNames = $metadata->getAssociationNames();
        foreach ($fieldNames as $fieldName) {
            if (isset($filters[$fieldName])) {
                // already defined
                continue;
            }
            $mapping  = $metadata->getAssociationMapping($fieldName);
            $hasIndex = false;
            if ($mapping['type'] & ClassMetadata::TO_ONE) {
                $hasIndex = true;
            }
            if ($hasIndex) {
                $targetMetadata     = $this->doctrineHelper->getEntityMetadata($mapping['targetEntity']);
                $targetIdFieldNames = $targetMetadata->getIdentifierFieldNames();
                if (count($targetIdFieldNames) === 1) {
                    $filters[$fieldName] = [
                        'data_type'   => $targetMetadata->getTypeOfField(reset($targetIdFieldNames)),
                        'allow_array' => true
                    ];
                }
            }
        }

        return $filters;
    }

    /**
     * @param array $filters
     *
     * @return array
     */
    protected function removeExclusions(array $filters)
    {
        return array_filter(
            $filters,
            function (array $config) {
                return !isset($config['exclude']) || !$config['exclude'];
            }
        );
    }
}
