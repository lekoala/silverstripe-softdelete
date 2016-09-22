<?php

/**
 * A GridField action to handle soft delete
 */
class GridFieldSoftDeleteAction implements GridField_ColumnProvider, GridField_ActionProvider
{

    /**
     * Add a column 'Delete'
     *
     * @param GridField $gridField
     * @param array $columns
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
     * @return array
     */
    public function getColumnAttributes($gridField, $record, $columnName)
    {
        return array('class' => 'col-buttons');
    }

    /**
     * Add the title
     *
     * @param GridField $gridField
     * @param string $columnName
     * @return array
     */
    public function getColumnMetadata($gridField, $columnName)
    {
        if ($columnName == 'Actions') {
            return array('title' => '');
        }
    }

    /**
     * Which columns are handled by this component
     *
     * @param GridField $gridField
     * @return array
     */
    public function getColumnsHandled($gridField)
    {
        return array('Actions');
    }

    /**
     * Which GridField actions are this component handling
     *
     * @param GridField $gridField
     * @return array
     */
    public function getActions($gridField)
    {
        return array('softdeleterecord');
    }

    /**
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return string - the HTML for the column
     */
    public function getColumnContent($gridField, $record, $columnName)
    {
        if (!$record->canDelete()) return;

        $field = GridField_FormAction::create($gridField,
                'SoftDeleteRecord'.$record->ID, false, "softdeleterecord",
                array('RecordID' => $record->ID))
            ->addExtraClass('gridfield-button-delete')
            ->setAttribute('title', _t('GridAction.Delete', "Delete"))
            ->setAttribute('data-icon', 'cross-circle')
            ->setDescription(_t('GridAction.DELETE_DESCRIPTION', 'Delete'));

        return $field->Field();
    }

    /**
     * Handle the actions and apply any changes to the GridField
     *
     * @param GridField $gridField
     * @param string $actionName
     * @param mixed $arguments
     * @param array $data - form data
     * @return void
     */
    public function handleAction(GridField $gridField, $actionName, $arguments,
                                 $data)
    {
        if ($actionName == 'softdeleterecord') {
            $item = $gridField->getList()->byID($arguments['RecordID']);
            if (!$item) {
                return;
            }

            if (!$item->canDelete()) {
                throw new ValidationException(
                _t('GridFieldAction_Delete.DeletePermissionsFailure',
                    "No delete permissions"), 0);
            }

            $item->softDelete();

            $list = $gridField->getList();
            // Remove from the list if it's a non destructive operation
            if ($list instanceof HasManyList || $list instanceof ManyManyList) {
                $list->remove($item);
            }
        }
    }
}