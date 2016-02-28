<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Component\EntitySerializer\EntityConfig;

/**
 * Represents a configuration of all filters for an entity.
 */
class FiltersConfig implements EntityConfigInterface
{
    use Traits\ConfigTrait;
    use Traits\ExclusionPolicyTrait;

    /** a list of filters */
    const FIELDS = EntityConfig::FIELDS;

    /** a type of the exclusion strategy that should be used for the filters */
    const EXCLUSION_POLICY = EntityConfig::EXCLUSION_POLICY;

    /** exclude all fields are not configured explicitly */
    const EXCLUSION_POLICY_ALL = EntityConfig::EXCLUSION_POLICY_ALL;

    /** exclude only fields are marked as excluded */
    const EXCLUSION_POLICY_NONE = EntityConfig::EXCLUSION_POLICY_NONE;

    /** @var array */
    protected $items = [];

    /** @var FilterFieldConfig[] */
    private $fields = [];

    /**
     * Gets a native PHP array representation of the configuration.
     *
     * @return array
     */
    public function toArray()
    {
        $result = $this->items;
        $this->removeItemWithDefaultValue($result, self::EXCLUSION_POLICY, self::EXCLUSION_POLICY_NONE);

        if (!empty($this->fields)) {
            foreach ($this->fields as $fieldName => $field) {
                $fieldConfig                      = $field->toArray();
                $result[self::FIELDS][$fieldName] = !empty($fieldConfig) ? $fieldConfig : null;
            }
        }

        return $result;
    }

    /**
     * Indicates whether the entity does not have a configuration.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return
            empty($this->items)
            && empty($this->fields);
    }

    /**
     * Checks whether the configuration of at least one field exists.
     *
     * @return bool
     */
    public function hasFields()
    {
        return !empty($this->fields);
    }

    /**
     * Gets the configuration for all fields.
     *
     * @return FilterFieldConfig[] [field name => config, ...]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Checks whether the configuration of the filter exists.
     *
     * @param string $fieldName
     *
     * @return bool
     */
    public function hasField($fieldName)
    {
        return isset($this->fields[$fieldName]);
    }

    /**
     * Gets the configuration of the filter.
     *
     * @param string $fieldName
     *
     * @return FilterFieldConfig|null
     */
    public function getField($fieldName)
    {
        return isset($this->fields[$fieldName])
            ? $this->fields[$fieldName]
            : null;
    }

    /**
     * Gets the configuration of existing filter or adds new filter for a given field.
     *
     * @param string $fieldName
     *
     * @return FilterFieldConfig
     */
    public function getOrAddField($fieldName)
    {
        $field = $this->getField($fieldName);
        if (null === $field) {
            $field = $this->addField($fieldName);
        }

        return $field;
    }

    /**
     * Adds the configuration of the filter.
     *
     * @param string                 $fieldName
     * @param FilterFieldConfig|null $field
     *
     * @return FilterFieldConfig
     */
    public function addField($fieldName, $field = null)
    {
        if (null === $field) {
            $field = new FilterFieldConfig();
        }

        $this->fields[$fieldName] = $field;

        return $field;
    }

    /**
     * Removes the configuration of the filter.
     *
     * @param string $fieldName
     */
    public function removeField($fieldName)
    {
        unset($this->fields[$fieldName]);
    }
}
