<?php

use SilverStripe\Forms\Form;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Admin\SecurityAdmin;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;

/**
 * Add gridfield action to SecurityAdmin
 *
 * @author Koala
 * @property SecurityAdmin $owner
 */
class SoftDeleteSecurityAdmin extends Extension
{
    /**
     * @param Form $form
     * @return void
     */
    public function updateEditForm(Form $form)
    {
        /** @var SecurityAdmin $owner */
        $owner = $this->owner;

        $memberSingl = singleton(Member::class);
        $groupSingl = singleton(Group::class);

        if ($memberSingl->hasExtension('SoftDeletable')) {
            /** @var GridField|null $gridfield */
            $gridfield = $form->Fields()->dataFieldByName('Members');
            //SS5 compat
            if (!$gridfield) {
                /** @var GridField|null $gridfield */
                $gridfield = $form->Fields()->dataFieldByName('users');
            }
            if ($gridfield) {
                $config = $gridfield->getConfig();

                $config->removeComponentsByType(GridFieldDeleteAction::class);
                if ($owner::config()->softdelete_from_list) {
                    $exclude = $this->owner->config()->softdelete_from_list_exclude;
                    if ($exclude && !in_array($this->owner->modelClass, $exclude)) {
                        $config->addComponent(new GridFieldSoftDeleteAction());
                    }
                }

                // No caution because soft :-)
                $form->Fields()->removeByName('MembersCautionText');
            }
        }

        if ($groupSingl->hasExtension('SoftDeletable')) {
            $gridfield = $form->Fields()->dataFieldByName('Groups');
            //SS5 compat
            if (!$gridfield) {
                /** @var GridField|null $gridfield */
                $gridfield = $form->Fields()->dataFieldByName('groups');
            }
            if ($gridfield) {
                /** @var GridField|null $gridfield */
                $config = $gridfield->getConfig();

                $config->removeComponentsByType(GridFieldDeleteAction::class);
                if ($owner::config()->softdelete_from_list) {
                    $config->addComponent(new GridFieldSoftDeleteAction());
                }
            }
        }
    }
}
