<?php

/**
 * Filters or not soft deleted record using the filter api
 */
class SoftDeleteOnlySearchFilter extends SoftDeleteSearchFilter
{
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
