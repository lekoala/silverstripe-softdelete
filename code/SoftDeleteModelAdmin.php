<?php

/**
 * More friendly usage in model admin
 *
 * @author Koala
 */
class SoftDeleteModelAdmin extends Extension
{

    public function updateList(&$list)
    {
        if ($this->filtersOnDeleted()) {
            $list = $list->alterDataQuery(function(DataQuery $dq) {
                $dq->setQueryParam('SoftDeletable.filter', false);
            });
        }
    }

    public function filtersOnDeleted()
    {
        $params = $this->owner->getRequest()->requestVar('q');

        if (!empty($params['IncludeDeleted'])) {
            return true;
        }
        return false;
    }

    public function updateSearchContext(&$context)
    {
        $fields = $context->getFields();

        $singl = singleton($this->owner->modelClass);

        if ($singl->hasExtension('SoftDeletable')) {
            $fields->push(new CheckboxField('q[IncludeDeleted]',
                'Include deleted'));
        }
    }

    public function updateEditForm($form)
    {
        $singl = singleton($this->owner->modelClass);

        if ($singl->hasExtension('SoftDeletable')) {
            /* @var $gridfield GridField */
            $gridfield = $form->Fields()->dataFieldByName($this->owner->modelClass);
            $config    = $gridfield->getConfig();

            $config->removeComponentsByType('GridFieldDeleteAction');
            $config->addComponent(new GridFieldSoftDeleteAction());

            $bulkManager = $config->getComponentByType('GridFieldBulkManager');
            if ($bulkManager) {
                $bulkManager->removeBulkAction('delete');
                $bulkManager->addBulkAction('softDelete', 'delete (soft)',
                    'GridFieldBulkSoftDeleteEventHandler');
            }

            if ($this->filtersOnDeleted()) {
                /* @var $cols GridFieldDataColumns */
                $cols                       = $gridfield->getConfig()->getComponentByType('GridFieldDataColumns');
                $displayedFields            = $cols->getDisplayFields($gridfield);
                $displayedFields['Deleted'] = 'Deleted';
                $cols->setDisplayFields($displayedFields);
            }
        }


        return $form;
    }
}