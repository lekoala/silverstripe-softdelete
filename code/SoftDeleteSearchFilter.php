<?php

use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\Filters\SearchFilter;

/**
 * Filters or not soft deleted record using the filter api
 */
class SoftDeleteSearchFilter extends SearchFilter
{
    /**
     * @return array<string>
     */
    public function getSupportedModifiers()
    {
        return ['only'];
    }

    /**
     * @return bool
     */
    protected function getOnlyDeleted()
    {
        $modifiers = $this->getModifiers();
        if (in_array('only', $modifiers)) {
            return true;
        }
        return false;
    }

    protected function applyOne(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $schema = DataObjectSchema::singleton();
        $tableName = $schema->tableForField($this->model, 'Deleted');

        $query = $query->setQueryParam("SoftDeletable.filter", 'false');
        if ($this->getOnlyDeleted() || $this->name == "OnlyDeleted") {
            $query = $query->where("\"$tableName\".\"Deleted\" IS NOT NULL");
        }

        return $query;
    }

    protected function applyMany(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $schema = DataObjectSchema::singleton();
        $tableName = $schema->tableForField($this->model, 'Deleted');

        $query = $query->setQueryParam("SoftDeletable.filter", 'false');
        if ($this->getOnlyDeleted() || $this->name == "OnlyDeleted") {
            $query = $query->where("\"$tableName\".\"Deleted\" IS NOT NULL");
        }

        return $query;
    }

    protected function excludeOne(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $schema = DataObjectSchema::singleton();
        $tableName = $schema->tableForField($this->model, 'Deleted');

        $query = $query->setQueryParam("SoftDeletable.filter", 'false');
        if ($this->getOnlyDeleted() || $this->name == "OnlyDeleted") {
            $query = $query->where("\"$tableName\".\"Deleted\" IS NULL");
        }

        return $query;
    }

    protected function excludeMany(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $schema = DataObjectSchema::singleton();
        $tableName = $schema->tableForField($this->model, 'Deleted');

        $query = $query->setQueryParam("SoftDeletable.filter", 'false');
        if ($this->getOnlyDeleted() || $this->name == "OnlyDeleted") {
            $query = $query->where("\"$tableName\".\"Deleted\" IS NULL");
        }

        return $query;
    }

    /**
     * @return boolean
     */
    public function isEmpty()
    {
        /** @var null|array<mixed>|string $v */
        $v = $this->getValue();
        return $v === array() || $v === null || $v === '';
    }
}
