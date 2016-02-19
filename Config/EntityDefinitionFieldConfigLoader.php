<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class EntityDefinitionFieldConfigLoader extends AbstractConfigLoader implements
    ConfigLoaderInterface,
    ConfigLoaderFactoryAwareInterface
{
    /** @var array */
    protected $methodMap = [
        ConfigUtil::EXCLUDE          => 'setExcluded',
        ConfigUtil::COLLAPSE         => 'setCollapsed',
        ConfigUtil::PROPERTY_PATH    => 'setPropertyPath',
        ConfigUtil::DATA_TRANSFORMER => 'setDataTransformers',
        ConfigUtil::LABEL            => 'setLabel',
        ConfigUtil::DESCRIPTION      => 'setDescription',
    ];

    /** @var array */
    protected $targetEntityMethodMap = [
        ConfigUtil::EXCLUSION_POLICY     => 'setExclusionPolicy',
        ConfigUtil::DISABLE_PARTIAL_LOAD => ['disablePartialLoad', 'enablePartialLoad'],
        ConfigUtil::ORDER_BY             => 'setOrderBy',
        ConfigUtil::MAX_RESULTS          => 'setMaxResults',
        ConfigUtil::HINTS                => 'setHints',
        ConfigUtil::POST_SERIALIZE       => 'setPostSerializeHandler',
        ConfigUtil::LABEL                => 'setLabel',
        ConfigUtil::PLURAL_LABEL         => 'setPluralLabel',
        ConfigUtil::DESCRIPTION          => 'setDescription',
    ];

    /** @var ConfigLoaderFactory */
    protected $factory;

    /**
     * {@inheritdoc}
     */
    public function setConfigLoaderFactory(ConfigLoaderFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $config)
    {
        $field = new EntityDefinitionFieldConfig();
        $this->loadField($field, $config);

        return $field;
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     * @param array|null                  $config
     */
    protected function loadField(EntityDefinitionFieldConfig $field, $config)
    {
        if (empty($config)) {
            return;
        }

        foreach ($config as $key => $value) {
            if (isset($this->targetEntityMethodMap[$key])) {
                $this->callSetter($field->getOrCreateTargetEntity(), $this->targetEntityMethodMap[$key], $value);
            } elseif (isset($this->methodMap[$key])) {
                $this->callSetter($field, $this->methodMap[$key], $value);
            } elseif (ConfigUtil::FIELDS === $key) {
                $this->loadTargetFields($field, $value);
            } elseif (ConfigUtil::DEFINITION === $key) {
                $this->loadField($field, $value);
            } elseif (ConfigUtil::FILTERS === $key) {
                $this->loadTargetFilters($field, $value);
            } elseif (ConfigUtil::SORTERS === $key) {
                $this->loadTargetSorters($field, $value);
            } else {
                $this->setValue($field, $key, $value);
            }
        }
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     * @param array|string|null           $fields
     */
    protected function loadTargetFields(EntityDefinitionFieldConfig $field, $fields)
    {
        if (!empty($fields)) {
            $targetEntity = $field->getOrCreateTargetEntity();
            if (is_string($fields)) {
                $field->setCollapsed();
                $targetEntity->addField($fields);
            } else {
                foreach ($fields as $name => $config) {
                    $targetEntity->addField(
                        $name,
                        $this->factory->getLoader(ConfigUtil::FIELDS)->load(null !== $config ? $config : [])
                    );
                }
            }
        }
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     * @param array|null                  $config
     */
    protected function loadTargetFilters(EntityDefinitionFieldConfig $field, $config)
    {
        if (!empty($config)) {
            /** @var FiltersConfig $filters */
            $filters = $this->factory->getLoader(ConfigUtil::FILTERS)->load($config);
            if (!$filters->isEmpty()) {
                $this->setValue($field->getOrCreateTargetEntity(), ConfigUtil::FILTERS, $filters);
            }
        }
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     * @param array|null                  $config
     */
    protected function loadTargetSorters(EntityDefinitionFieldConfig $field, $config)
    {
        if (!empty($config)) {
            /** @var SortersConfig $sorters */
            $sorters = $this->factory->getLoader(ConfigUtil::SORTERS)->load($config);
            if (!$sorters->isEmpty()) {
                $this->setValue($field->getOrCreateTargetEntity(), ConfigUtil::SORTERS, $sorters);
            }
        }
    }
}
