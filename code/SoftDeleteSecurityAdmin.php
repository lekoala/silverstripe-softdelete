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
    protected function getSanistedClass($class)
    {
        return str_replace('\\', '-', $class);
    }

    /**
     * @return SecurityAdmin
     */
    protected function getSecurityAdmin()
    {
        return $this->owner;
    }

    function updateEditForm(Form $form)
    {
        /* @var $owner SecurityAdmin */
        $owner = $this->owner;

        $memberSingl = singleton(Member::class);
        $groupSingl = singleton(Group::class);

        if ($memberSingl->hasExtension('SoftDeletable')) {
            $gridfield = $form->Fields()->dataFieldByName('Members');
            //SS5 compat
            if (!$gridfield) {
                $gridfield = $form->Fields()->dataFieldByName('users');
            }
            if ($gridfield) {
                $config = $gridfield->getConfig();

                $config->removeComponentsByType(GridFieldDeleteAction::class);
                if ($this->owner->config()->softdelete_from_list) {
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
                $gridfield = $form->Fields()->dataFieldByName('groups');
            }
            if ($gridfield) {
                $config = $gridfield->getConfig();

                $config->removeComponentsByType(GridFieldDeleteAction::class);
                if ($this->owner->config()->softdelete_from_list) {
                    $config->addComponent(new GridFieldSoftDeleteAction());
                }
            }
        }
    }
}
