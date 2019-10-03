<?php

use SilverStripe\ORM\DataQuery;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\Security\Member;
use SilverStripe\ORM\DataObject;
use LeKoala\Base\Actions\CustomAction;

/**
 * Soft delete extension
 *
 * @author Koala
 * @property Member|SoftDeletable $owner
 * @property string $Deleted
 * @property int $DeletedByID
 * @method Member DeletedBy()
 */
class SoftDeletable extends DataExtension
{
    /**
     * Disable the filtering
     *
     * @var boolean
     */
    public static $disable = false;

    /**
     * Disable accidental deletation prevention
     *
     * @var boolean
     */
    public static $prevent_delete = true;

    private static $db = array(
        'Deleted' => "Datetime",
        'DeletedByID' => "Int", // Somehow relation to member class causes circular dependency
    );
    private static $has_one = array(
        // 'DeletedBy' => Member::class
    );
    private static $defaults = array(
        'DeletedByID' => '-1' // Use -1 to distinguish null from 0
    );
    private static $better_buttons_actions = array(
        'softDelete',
        'forceDelete',
        'undoDelete',
    );

    /**
     * @return array
     */
    public static function listSoftDeletableClasses()
    {
        $arr = array();
        $dataobjects = ClassInfo::subclassesFor(DataObject::class);
        foreach ($dataobjects as $dataobject) {
            $singl = singleton($dataobject);
            if ($singl->hasExtension(self::class)) {
                $arr[$dataobject] = $dataobject;
            }
        }
        return $arr;
    }

    /**
     * Update any requests to hide deleted records
     */
    public function augmentSQL(SQLSelect $query, DataQuery $dataQuery = null)
    {
        // Filters are disabled globally
        if (self::$disable) {
            return;
        }
        // Filters are disabled for this query
        if ($dataQuery->getQueryParam('SoftDeletable.filter') === false) {
            return;
        }
        // Don't run if querying by ID
        if ($query->filtersOnID()) {
            return;
        }

        $froms = $query->getFrom();
        $froms = array_keys($froms);
        $tableName = array_shift($froms);
        $query->addWhere("\"$tableName\".\"Deleted\" IS NULL");
    }

    public function updateCMSFields(FieldList $fields)
    {
        if (!$this->owner->Deleted) {
            $fields->removeByName('Deleted');
            $fields->removeByName('DeletedByID');
        } else {
            $Deleted = $fields->dataFieldByName('Deleted');
            $DeletedByID = $fields->dataFieldByName('DeletedByID');

            if ($Deleted) {
                $fields->makeFieldReadonly('Deleted');
            }

            if ($DeletedByID) {
                $fields->makeFieldReadonly('DeletedByID');
            }
        }
    }

    public function updateCMSActions(FieldList $actions)
    {
        if (!class_exists(CustomAction::class)) {
            // Need CustomAction (from lekoala/silverstripe-base) for this to work;
            return;
        }
        // Hide delete for new records
        if (!$this->owner->ID) {
            return;
        }
        if ($this->owner->Deleted) {
            $undoDelete = new CustomAction('undoDelete', 'Undo Delete');
            $actions->push($undoDelete);

            $forceDelete = new CustomAction('forceDelete', 'Really Delete');
            $forceDelete->addExtraClass('btn-danger');
            $forceDelete->setConfirmation("Are you sure? There is no undo");
            $actions->push($forceDelete);
        } else {
            $softDelete = new CustomAction('softDelete', 'Delete');
            $actions->push($softDelete);
        }
    }

    public function updateBetterButtonsActions(FieldList $actions)
    {
        foreach ($actions as $action) {
            if (get_class($action) == 'BetterButton_Delete') {
                $actions->remove($action);

                if ($this->owner->Deleted) {
                    $actions->push($undo = new BetterButtonCustomAction(
                        'undoDelete',
                        'Undo delete'
                    ));

                    $actions->push($delete = new BetterButtonCustomAction(
                        'forceDelete',
                        'Really Delete'
                    ));
                    $delete->setConfirmation("Are you sure? There is no undo");

                    $delete->addExtraClass('gridfield-better-buttons-delete');
                } else {
                    $actions->push($delete = new BetterButtonCustomAction(
                        'softDelete',
                        'Delete'
                    ));

                    $delete->addExtraClass('gridfield-better-buttons-delete');
                }
            }
        }
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Check if this we could a duplicated email with a deleted member
        if ($this->owner instanceof Member && $this->owner->Email) {
            $list = Member::get()->filter('Email', $this->owner->Email)->exclude('ID', $this->owner->ID);
            $list = $list->alterDataQuery(function (DataQuery $dq) {
                $dq->setQueryParam('SoftDeletable.filter', false);
            });
            $count = $list->count();
            if ($count > 1) {
                throw new Exception("There is already a deleted member with this email");
            }
        }
    }

    public function onBeforeDelete()
    {
        if (self::$prevent_delete) {
            throw new Exception("Tried to delete a DataObject, but data deletion is currently active");
        }
        parent::onBeforeDelete();
    }

    /**
     * Soft delete a records (set Deleted and DeletedByID)
     *
     * @return void
     */
    public function softDelete()
    {
        if ($this->owner->Deleted) {
            throw new LogicException("DataObject::softDelete() called on a DataObject already soft deleted");
        }
        $result = $this->owner->extend('onBeforeSoftDelete', $this->owner);
        if ($result === false) {
            return;
        }
        if (!$this->owner->ID) {
            throw new LogicException("DataObject::softDelete() called on a DataObject without an ID");
        }

        $this->owner->Deleted = date('Y-m-d H:i:s');
        $this->owner->DeletedByID = Member::currentUserID();
        $this->owner->write();

        $this->owner->extend('onAfterSoftDelete', $this->owner);
    }

    /**
     * Undo delete
     *
     * @return void
     */
    public function undoDelete()
    {
        $this->owner->Deleted = null;
        $this->owner->DeletedByID = -1;
        $this->owner->write();
    }

    /**
     * Do a real delete, overcoming prevent_delete state
     *
     * @return void
     */
    public function forceDelete()
    {
        $status = self::$prevent_delete;

        self::$prevent_delete = false;

        $result = $this->owner->delete();

        self::$prevent_delete = $status;
    }

    /**
     * @return Member
     */
    public function DeletedBy()
    {
        return DataObject::get_by_id(Member::class, $this->owner->DeletedByID);
    }
}
