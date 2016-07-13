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
        $params = $this->owner->getRequest()->requestVar('q');

        if (!empty($params['IncludeDeleted'])) {
            $list = $list->alterDataQuery(function(DataQuery $dq) {
                $dq->setQueryParam('SoftDeletable.filter', false);
            });
        }
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

             /* @var $cols GridFieldDataColumns */
            $cols = $gridfield->getConfig()->getComponentByType('GridFieldDataColumns');
            $displayedFields = $cols->getDisplayFields($gridfield);
            $displayedFields['Deleted'] = 'Deleted';
            $cols->setDisplayFields($displayedFields);
        }

        return $form;
    }
}