SilverStripe Soft Delete Module
==================

Add a soft delete behaviour to your dataobjects. Objects are simply marked as deleted and kept in the database.

Soft delete will follow the same permissions patterns as delete.

ModelAdmin and SecurityAdmin are extended to add a new GridField action that replace the default delete action

BetterButtons are supported as well.

How to use
==================

Simply replace your calls from delete to softDelete.

New extensions hooks are provided to avoid side effects (onBeforeSoftDelete, onAfterSoftDelete).
These are ideals if you have child records that need to be soft deleted with their parent.

Prevent accidental deletion
==================

By default, the module will prevent any delete from happening. To allow deletion, you must set

    SoftDeletable:$prevent_delete = false

The only way from the CMS UI to delete a record is to go to a soft deleted record
and click "Really delete" which will call "forceDelete" on the record.

Compatibility
==================
Tested with 4.x

Maintainer
==================
LeKoala - thomas@lekoala.be
