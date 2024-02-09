<?php

/**
 * Filters or not soft deleted record using the filter api
 */
class SoftDeleteOnlySearchFilter extends SoftDeleteSearchFilter
{
    /**
     * @return array<string>
     */
    public function getSupportedModifiers()
    {
        return [];
    }

    /**
     * @return bool
     */
    protected function getOnlyDeleted()
    {
        return true;
    }
}
