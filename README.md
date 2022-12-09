# SilverStripe Soft Delete Module

Add a soft delete behaviour to your dataobjects. Objects are simply marked as deleted and kept in the database.

Soft delete will follow the same permissions patterns as delete.

ModelAdmin and SecurityAdmin are extended to add a new GridField action that replace the default delete action

This module depends on [lekoala/silverstripe-cms-actions](https://github.com/lekoala/silverstripe-cms-actions) for displaying delete buttons

# How to use

Simply replace your calls from delete to softDelete.

New extensions hooks are provided to avoid side effects (onBeforeSoftDelete, onAfterSoftDelete).
These are ideals if you have child records that need to be soft deleted with their parent.

# Config options

```yml
SilverStripe\Admin\ModelAdmin:
    softdelete_from_list: true
    softdelete_from_list_exclude: []
    extensions:
        - SoftDeleteModelAdmin
SilverStripe\Admin\SecurityAdmin:
    softdelete_from_list: true
    softdelete_from_list_exclude: []
    extensions:
        - SoftDeleteSecurityAdmin
```

You can configure:

-   softdelete_from_list: show delete button on a line. Enabled by default.
-   softdelete_from_list_exclude: hide the delete button for these classes even if enabled globally

# Prevent accidental deletion

By default, the module will prevent any delete from happening. To allow deletion, you must set

```php
SoftDeletable:$prevent_delete = false
```

The only way from the CMS UI to delete a record is to go to a soft deleted record
and click "Really delete" which will call "forceDelete" on the record.

# Disable filtering

You can disable filtering globally, using

```php
SoftDeletable::$disable = true
```

Or at query level

```php
$dataQuery->setQueryParam('SoftDeletable.filter',false)
```

# Filtering on ids

By default, this module will let you return soft deleted records if you
ask them specifically by ID. This is by design to prevent things breaking accidentally.
If you want to make sure you don't display soft deleted records, make
sure to implement a proper canView() method that fits your usage.

An alternative option, is to disable that feature:

```yml
SoftDeletable:
  check_filters_on_id: false
```

Keep in mind that `DataObject::get_by_id();` can get cached and it can lead to tricky scenarios.

# Compatibility

Tested with 4.4+

# Maintainer

LeKoala - thomas@lekoala.be
