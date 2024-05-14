<?php

use SilverStripe\Forms\Form;
use SilverStripe\Core\Extension;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Admin\SecurityAdmin;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;

/**
 * More friendly usage in model admin
 *
 * @author Koala
 * @property ModelAdmin $owner
 */
class SoftDeleteModelAdmin extends Extension
{

    /**
     * @return string
     */
    protected function getSanistedModelClass()
    {
        return str_replace('\\', '-', $this->owner->getField('modelTab'));
    }

    /**
     * @param Form $form
     * @return Form|null
     */
    public function updateEditForm($form)
    {
        $modelClass = $this->owner->getModelClass();
        $singl = singleton($modelClass);

        /** @var ModelAdmin|SecurityAdmin $owner */
        $owner = $this->owner;

        // Already done in own extension for SS5
        if ($owner instanceof SecurityAdmin) {
            return null;
        }

        if ($singl->hasExtension(SoftDeletable::class)) {
            /** @var GridField|null $gridfield */
            $gridfield = $form->Fields()->dataFieldByName($this->getSanistedModelClass());
            if ($gridfield) {
                $config = $gridfield->getConfig();

                $editable = $config->getComponentByType(\Symbiote\GridFieldExtensions\GridFieldEditableColumns::class);
                if (!$editable) {
                    $config->removeComponentsByType(GridFieldDeleteAction::class);
                }
                if ($owner::config()->softdelete_from_list) {
                    $exclude = $this->owner->config()->softdelete_from_list_exclude;
                    if ($exclude && !in_array($modelClass, $exclude)) {
                        $config->addComponent(new GridFieldSoftDeleteAction());
                    }
                }
            }
        }

        return $form;
    }
}
