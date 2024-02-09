<?php

use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;

/**
 * A GridField action to handle soft delete
 */
class GridFieldSoftDeleteAction implements GridField_ColumnProvider, GridField_ActionProvider
{

    /**
     * Add a column 'Delete'
     *
     * @param GridField $gridField
     * @param array<string> $columns
     * @return void
     */
    public function augmentColumns($gridField, &$columns)
    {
        if (!in_array('Actions', $columns)) {
            $columns[] = 'Actions';
        }
    }

    /**
     * Return any special attributes that will be used for FormField::create_tag()
     *
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return array<string,string>
     */
    public function getColumnAttributes($gridField, $record, $columnName)
    {
        return array('class' => 'grid-field__col-compact');
    }

    /**
     * Add the title
     *
     * @param GridField $gridField
     * @param string $columnName
     * @return array<string,string>|null
     */
    public function getColumnMetadata($gridField, $columnName)
    {
        if ($columnName == 'Actions') {
            return array('title' => '');
        }
        return null;
    }

    /**
     * Which columns are handled by this component
     *
     * @param GridField $gridField
     * @return array<string>
     */
    public function getColumnsHandled($gridField)
    {
        return array('Actions');
    }

    /**
     * Which GridField actions are this component handling
     *
     * @param GridField $gridField
     * @return array<string>
     */
    public function getActions($gridField)
    {
        return array('softdeleterecord');
    }

    /**
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return string|DBHTMLText|null - the HTML for the column
     */
    public function getColumnContent($gridField, $record, $columnName)
    {
        if (!$record->canDelete()) {
            return null;
        }

        $field = GridField_FormAction::create(
            $gridField,
            'SoftDeleteRecord' . $record->ID,
            false,
            "softdeleterecord",
            array('RecordID' => $record->ID)
        )
            ->addExtraClass('gridfield-button-delete btn--icon-md font-icon-trash-bin btn--no-text grid-field__icon-action')
            ->setAttribute('title', _t(__CLASS__ . '.Delete', "Delete"))
            ->setDescription(_t(__CLASS__ . '.DELETE_DESCRIPTION', 'Delete'));

        return $field->Field();
    }

    /**
     * @param GridField $gridField
     * @return DataList|SS_List
     */
    protected function getListFromGridField($gridField)
    {
        return $gridField->getList();
    }

    /**
     * Handle the actions and apply any changes to the GridField
     *
     * @param GridField $gridField
     * @param string $actionName
     * @param mixed $arguments
     * @param array<mixed> $data - form data
     * @return void
     */
    public function handleAction(
        GridField $gridField,
        $actionName,
        $arguments,
        $data
    ) {
        if ($actionName == 'softdeleterecord') {
            /** @var DataList $list */
            $list = $this->getListFromGridField($gridField);
            /** @var DataObject|null $item */
            $item = $list->byID($arguments['RecordID']);
            if (!$item) {
                return;
            }

            if (!$item->canDelete()) {
                throw new ValidationException(
                    _t(
                        'GridFieldAction_Delete.DeletePermissionsFailure',
                        "No delete permissions"
                    ),
                    0
                );
            }

            // If you replaced by mistake, it should still delete
            if ($item->hasMethod('softDelete')) {
                //@phpstan-ignore-next-line
                $item->softDelete();
            } else {
                $item->delete();
            }

            $list = $gridField->getList();
            // Remove from the list if it's a non destructive operation
            if ($list instanceof HasManyList || $list instanceof ManyManyList) {
                $list->remove($item);
            }
        }
    }
}
