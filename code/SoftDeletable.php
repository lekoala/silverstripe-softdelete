<?php

use SilverStripe\ORM\DataQuery;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Security;
use LeKoala\CmsActions\CustomAction;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\Core\Config\Configurable;

/**
 * Soft delete extension
 *
 * @author Koala
 * @property DataObject|SoftDeletable $owner
 * @property string $Deleted
 * @property int $DeletedByID
 */
class SoftDeletable extends DataExtension
{
    use Configurable;

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

    /**
     * @var array<string,string>
     */
    private static $db = array(
        'Deleted' => "Datetime",
        'DeletedByID' => "Int", // Somehow relation to member class causes circular dependency
    );
    /**
     * @var array<string,string>
     */
    private static $has_one = array(
        // 'DeletedBy' => Member::class
    );
    /**
     * @var array<string,string>
     */
    private static $defaults = array(
        'DeletedByID' => '-1' // Use -1 to distinguish null from 0
    );

    /**
     * @return array<int|string,string|mixed>
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
     * @param array<string,mixed> $fields
     * @return void
     */
    public function updateSearchableFields(array &$fields)
    {
        /*
^ array:4 [▼
  "Title" => array:2 [▼
    "title" => "Name"
    "filter" => "PartialMatchFilter"
  ]
  ...
        */
        $fields['IncludeDeleted'] = [
            'filter' => SoftDeleteSearchFilter::class,
            'field' => CheckboxField::class,
            'title' => 'Include deleted',
            'general' => false, //4.12+
        ];
        $fields['OnlyDeleted'] = [
            'filter' => SoftDeleteOnlySearchFilter::class,
            'field' => CheckboxField::class,
            'title' => 'Only deleted',
            'general' => false, //4.12+
        ];
    }

    /**
     * Update any requests to hide deleted records
     * @return void
     */
    public function augmentSQL(SQLSelect $query, DataQuery $dataQuery = null)
    {
        // Filters are disabled globally
        if (self::$disable) {
            return;
        }
        // Filters are disabled for this query
        if ($dataQuery) {
            /** @var string|bool $filter */
            $filter = $dataQuery->getQueryParam('SoftDeletable.filter');
            if ($filter == false || $filter == 'false') {
                return;
            }
        }

        $froms = $query->getFrom();
        $froms = array_keys($froms);
        $tableName = array_shift($froms);

        // Don't run if querying by ID on base table because it's much more convenient
        // Don't use filtersOnID as it will return true when filtering a relation by ID as well
        if (self::config()->check_filters_on_id) {
            foreach ($query->getWhereParameterised($parameters) as $predicate) {
                $filtered = str_replace(['"', '`', ' ', 'IN'], ['', '', '', '='], $predicate);
                // Where must contain a clause with Table.ID = or Table.ID IN
                if (strpos($filtered, $tableName . ".ID=") === 0) {
                    return;
                }
            }
        }
        $query->addWhere("\"$tableName\".\"Deleted\" IS NULL");
    }

    /**
     * @param FieldList $fields
     * @return void
     */
    public function updateCMSFields(FieldList $fields)
    {
        //@phpstan-ignore-next-line
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

    /**
     * @param FieldList $actions
     * @return void
     */
    public function updateCMSActions(FieldList $actions)
    {
        // Hide delete for new records
        if (!$this->owner->ID) {
            return;
        }

        // Hide on ProfileController
        if (Controller::has_curr()) {
            $className = get_class(Controller::curr());
            if (strpos($className, 'CMSProfileController') !== false) {
                return;
            }
        }

        // Use canDelete
        if (!$this->owner->canDelete()) {
            return;
        }

        if ($this->owner->Deleted) {
            $undoDelete = new CustomAction('undoDelete', 'Undo Delete');
            $actions->push($undoDelete);

            $forceDelete = new CustomAction('forceDelete', 'Really Delete');
            $forceDelete->setButtonType('outline-danger');
            $forceDelete->addExtraClass('btn-hide-outline');
            $forceDelete->setConfirmation("Are you sure? There is no undo");
            $actions->push($forceDelete);
        } else {
            $softDelete = new CustomAction('softDelete', 'Delete');
            $softDelete->setButtonType('outline-danger');
            $softDelete->addExtraClass('btn-hide-outline');
            $softDelete->addExtraClass('font-icon-trash-bin');
            $actions->push($softDelete);

            //@phpstan-ignore-next-line
            if ($this->owner->hasMethod('getDeleteButtonTitle')) {
                //@phpstan-ignore-next-line
                $softDelete->setTitle($this->owner->getDeleteButtonTitle());
            }
        }
    }

    /**
     * @param FieldList $actions
     * @return void
     */
    public function onAfterUpdateCMSActions($actions)
    {
        $RightGroup = $actions->fieldByName('RightGroup');
        $deleteAction = $actions->fieldByName('action_doDelete');
        $undoDelete = $actions->fieldByName('action_doCustomAction[undoDelete]');
        $forceDelete = $actions->fieldByName('action_doCustomAction[forceDelete]');
        $softDelete = $actions->fieldByName('action_doCustomAction[softDelete]');
        if ($softDelete) {
            if ($deleteAction) {
                $actions->remove($deleteAction);
            }
            // Move at the end of the stack
            $actions->remove($softDelete);
            $actions->push($softDelete);
        }
        if ($forceDelete) {
            if ($deleteAction) {
                $actions->remove($deleteAction);
            }
            // Move at the end of the stack
            $actions->remove($forceDelete);
            $actions->push($forceDelete);
        }
    }

    /**
     * @return void
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Check if this we could a duplicated email with a deleted member
        if ($this->owner instanceof Member && $this->owner->Email) {
            $list = Member::get()->filter('Email', $this->owner->Email)->exclude('ID', $this->owner->ID);
            $list = $list->alterDataQuery(function (DataQuery $dq) {
                $dq->setQueryParam('SoftDeletable.filter', 'false');
            });
            $count = $list->count();
            if ($count > 1) {
                throw new Exception("There is already a deleted member with this email");
            }
        }
    }

    /**
     * @return void
     */
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
        /** @var DataObject $owner */
        $owner = $this->owner;
        if ($owner->Deleted) {
            throw new LogicException("DataObject::softDelete() called on a DataObject already soft deleted");
        }
        $result = $owner->extend('onBeforeSoftDelete', $this->owner);
        foreach ($result as $resultRow) {
            if ($resultRow === false) {
                return;
            }
        }

        if (!$owner->ID) {
            throw new LogicException("DataObject::softDelete() called on a DataObject without an ID");
        }

        $member = Security::getCurrentUser();
        $owner->Deleted = date('Y-m-d H:i:s');
        if ($member) {
            $owner->DeletedByID = $member->ID;
        }
        $owner->write();

        $owner->extend('onAfterSoftDelete', $owner);
    }

    /**
     * Undo delete
     *
     * @return void
     */
    public function undoDelete()
    {
        /** @var DataObject $owner */
        $owner = $this->owner;
        $owner->Deleted = null;
        $owner->DeletedByID = -1;
        $owner->write();
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

        /** @var DataObject $owner */
        $owner = $this->owner;
        $owner->delete();

        self::$prevent_delete = $status;
    }

    /**
     * @return ?Member
     */
    public function DeletedBy()
    {
        //@phpstan-ignore-next-line
        return DataObject::get_by_id(Member::class, $this->owner->DeletedByID);
    }
}
