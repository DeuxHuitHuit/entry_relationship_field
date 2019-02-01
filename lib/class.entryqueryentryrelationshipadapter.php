<?php

/**
 * @package toolkit
 */
/**
 * Specialized EntryQueryFieldAdapter that facilitate creation of queries filtering/sorting data from
 * an entryrelationship Field.
 * @see FieldTextarea
 * @since Symphony 3.0.0
 */
class EntryQueryEntryrelationshipAdapter extends EntryQueryFieldAdapter
{
    public function createFilterIncludes($filter, array $columns)
    {
        $field_id = General::intval($this->field->get('id'));
        $filter = $this->field->cleanValue($filter);

        $conditions = $this->field->generateWhereFilter($filter, 'f' . $field_id);
        return $conditions;
    }

    /**
     * @see EntryQueryFieldAdapter::filterSingle()
     *
     * @param EntryQuery $query
     * @param string $filter
     * @return array
     */
    protected function filterSingle(EntryQuery $query, $filter)
    {
        General::ensureType([
            'filter' => ['var' => $filter, 'type' => 'string'],
        ]);
        if ($this->isFilterRegex($filter)) {
            return $this->createFilterRegexp($filter, $this->getFilterColumns());
        } elseif ($this->isFilterSQL($filter)) {
            return $this->createFilterSQL($filter, $this->getFilterColumns());
        }
        return $this->createFilterIncludes($filter, $this->getFilterColumns());
    }

    public function getFilterColumns()
    {
        return ['entries'];
    }

    public function getSortColumns()
    {
        return ['entries'];
    }
}
