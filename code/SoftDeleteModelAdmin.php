<?php

use SilverStripe\Core\Extension;
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

    protected function getSanistedModelClass()
    {
        return str_replace('\\', '-', $this->owner->modelClass);
    }

    public function updateEditForm($form)
    {
        $singl = singleton($this->owner->modelClass);

        if ($singl->hasExtension(SoftDeletable::class)) {
            /** @var GridField $gridfield */
            $gridfield = $form->Fields()->dataFieldByName($this->getSanistedModelClass());
            if ($gridfield) {
                $config = $gridfield->getConfig();

                $config->removeComponentsByType(GridFieldDeleteAction::class);
                if ($this->owner->config()->softdelete_from_list) {
                    $exclude = $this->owner->config()->softdelete_from_list_exclude;
                    if ($exclude && !in_array($this->owner->modelClass, $exclude)) {
                        $config->addComponent(new GridFieldSoftDeleteAction());
                    }
                }
            }
        }

        return $form;
    }
}
