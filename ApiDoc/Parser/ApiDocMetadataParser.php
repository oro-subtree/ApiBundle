<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Parser;

use Nelmio\ApiDocBundle\Parser\ParserInterface;

use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * Parse fields definition by given ApiDocMetadata information
 */
class ApiDocMetadataParser implements ParserInterface
{
    /** @var ValueNormalizer */
    protected $valueNormalizer;

    public function __construct(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(array $item)
    {
        return
            array_key_exists('options', $item)
            && array_key_exists('metadata', $item['options'])
            && is_a($item['class'], ApiDocMetadata::class, true)
            &&  $item['options']['metadata'] instanceof ApiDocMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(array $item)
    {
        /** @var ApiDocMetadata $data */
        $data = $item['options']['metadata'];

        return $this->getApiDocFieldsDefinition(
            $data->getMetadata(),
            $data->getConfig(),
            $data->getAction(),
            $data->getRequestType()
        );
    }

    /**
     * @param EntityMetadata         $metadata
     * @param EntityDefinitionConfig $config
     * @param string                 $action
     * @param RequestType            $requestType
     *
     * @return array ['fieldName' => [field config array]]
     */
    protected function getApiDocFieldsDefinition(
        EntityMetadata $metadata,
        EntityDefinitionConfig $config,
        $action,
        RequestType $requestType
    ) {
        $identifiersData = [];
        $fields = [];
        $relations = [];

        $addIdentifiers = ApiActions::isIdentifierNeededForAction($action);
        $identifiers = $metadata->getIdentifierFieldNames();
        // process fields
        foreach ($metadata->getFields() as $fieldName => $fieldMetadata) {
            $fieldData = [];

            $fieldData['required'] = !$fieldMetadata->isNullable();
            $fieldData['dataType'] = $fieldMetadata->getDataType();
            $fieldData['description'] = $config->getField($fieldName)->getDescription();
            $fieldData['readonly'] = !$addIdentifiers && in_array($fieldName, $identifiers, true);
            $fieldData['isRelation'] = false;
            $fieldData['isCollection'] = false;

            if (in_array($fieldName, $identifiers, true)) {
                $identifiersData[$fieldName] = $fieldData;
            } else {
                $fields[$fieldName] = $fieldData;
            }
        }
        // process relations
        foreach ($metadata->getAssociations() as $associationName => $associationMetadata) {
            $fieldData = [];

            $fieldData['required'] = !$associationMetadata->isNullable();
            $fieldData['description'] = $config->getField($associationName)->getDescription();
            $fieldData['readonly'] = !$addIdentifiers && in_array($associationName, $identifiers, true);
            $fieldData['isCollection'] = $associationMetadata->isCollection();

            $fieldData['dataType'] = $this->getEntityType($associationMetadata->getTargetClassName(), $requestType);
            $fieldData['isRelation'] = true;

            if (in_array($associationName, $identifiers, true)) {
                $identifiersData[$associationName] = $fieldData;
            } else {
                if (DataType::isAssociationAsField($associationMetadata->getDataType())) {
                    $fieldData['dataType'] = $associationMetadata->getDataType();
                    $fieldData['isRelation'] = false;
                    $fields[$associationName] = $fieldData;
                } else {
                    $relations[$associationName] = $fieldData;
                }
            }
        }

        ksort($identifiersData);
        ksort($fields);
        ksort($relations);

        return array_merge($identifiersData, $fields, $relations);
    }

    /**
     * @param string      $entityClass
     * @param RequestType $requestType
     *
     * @return string|null
     */
    protected function getEntityType($entityClass, RequestType $requestType)
    {
        return ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            $entityClass,
            $requestType,
            false
        );
    }
}
