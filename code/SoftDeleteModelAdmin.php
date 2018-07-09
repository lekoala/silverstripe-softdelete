<?php

use SilverStripe\ORM\DataQuery;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;

/**
 * More friendly usage in model admin
 *
 * @author Koala
 * @property ModelAdmin $owner
 */
class SoftDeleteModelAdmin extends Extension
{

    public function updateList(&$list)
    {
        if ($this->filtersOnDeleted()) {
            $list = $list->alterDataQuery(function (DataQuery $dq) {
                $dq->setQueryParam('SoftDeletable.filter', false);
            });
            if ($this->onlyDeletedFilter()) {
                $list = $list->where('Deleted IS NOT NULL');
            }
        }
    }

    public function filtersOnDeleted()
    {
        $params = $this->owner->getRequest()->requestVar('q');

        if (!empty($params['IncludeDeleted'])) {
            return true;
        }
        if (!empty($params['OnlyDeleted'])) {
            return true;
        }
        return false;
    }

    public function onlyDeletedFilter()
    {
        $params = $this->owner->getRequest()->requestVar('q');

        if (!empty($params['OnlyDeleted'])) {
            return true;
        }
        return false;
    }

    protected function getSanistedModelClass()
    {
        return str_replace('\\', '-', $this->owner->modelClass);
    }

    public function updateSearchContext(&$context)
    {
        $fields = $context->getFields();

        $singl = singleton($this->owner->modelClass);

        if ($singl->hasExtension('SoftDeletable')) {
            $fields->push(new CheckboxField('q[IncludeDeleted]', 'Include deleted'));
            $fields->push(new CheckboxField('q[OnlyDeleted]', 'Only deleted'));
        }
    }

    public function updateEditForm($form)
    {
        $singl = singleton($this->owner->modelClass);

        if ($singl->hasExtension('SoftDeletable')) {
            /* @var $gridfield GridField */
            $gridfield = $form->Fields()->dataFieldByName($this->getSanistedModelClass());
            $config = $gridfield->getConfig();

            $config->removeComponentsByType(GridFieldDeleteAction::class);
            if ($this->owner->config()->softdelete_from_list) {
                $config->addComponent(new GridFieldSoftDeleteAction());
            }

            if ($this->filtersOnDeleted()) {
                /* @var $cols GridFieldDataColumns */
                $cols = $gridfield->getConfig()->getComponentByType(GridFieldDataColumns::class);
                $displayedFields = $cols->getDisplayFields($gridfield);
                $displayedFields['Deleted'] = 'Deleted';
                $cols->setDisplayFields($displayedFields);
            }
        }


        return $form;
    }
}
