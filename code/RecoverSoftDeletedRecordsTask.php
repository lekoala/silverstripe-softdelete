<?php

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;

/**
 * @author Koala
 */
class RecoverSoftDeletedRecordsTask extends BuildTask
{
    private static $segment = 'RecoverSoftDeletedRecordsTask';
    protected $title = 'Recover or Clean Soft Deleted Records';
    protected $description = 'Helps you to track and potentially recover or clean up any soft deleted record';

    public function run($request)
    {
        $classes = SoftDeletable::listSoftDeletableClasses();

        if (empty($classes)) {
            DB::alteration_message("No softDeletable classes", "error");
            return;
        }

        $selectedClass = $request->getVar('class');
        $recover = $request->getVar('recover');
        $cleanup = $request->getVar('cleanup');

        if (!$selectedClass) {
            DB::alteration_message("Please choose any of the following class and pass it as 'class' in the url.");
            foreach ($classes as $cl) {
                DB::alteration_message("<a href=\"/dev/tasks/RecoverSoftDeletedRecordsTask?class=$cl\">$cl</a>");
            }
            return;
        }

        if (!in_array($selectedClass, $classes)) {
            DB::alteration_message("$selectedClass is not valid", "error");
            return;
        }

        if ($recover && $cleanup) {
            DB::alteration_message("Cannot recover and cleanup at the same time", "error");
            return;
        }

        if ($cleanup) {
            SoftDeletable::$prevent_delete = false;
        }

        $toRecover = array();
        if ($recover) {
            if ($recover == 'all' || $cleanup) {
                // keep all
            } else {
                $toRecover = array_map('trim', explode(',', $recover));
            }
        }

        SoftDeletable::$disable = true;
        $records = $selectedClass::get()->where('Deleted IS NOT NULL');
        if (!$records->count()) {
            DB::alteration_message("No soft deleted records");
        }
        foreach ($records as $record) {
            if ($recover == 'all' || ($recover && in_array($record->ID, $toRecover))) {
                $record->undoDelete();
                DB::alteration_message(
                    $record->getTitle() . " (#" . $record->ID . ") has been recovered",
                    'repaired'
                );
            } elseif ($cleanup) {
                DB::alteration_message("Deleting " . $record->getTitle());
                $record->delete();
            } else {
                $DeletedBy = $record->DeletedBy();
                $Deleter = "Unknown";
                if ($DeletedBy) {
                    $Deleter = $DeletedBy->getTitle();
                }
                DB::alteration_message($record->getTitle() . " (#" . $record->ID . ") has been deleted at " . $record->Deleted . ' by ' . $Deleter);
            }
        }
        if ($recover) {
            DB::alteration_message("Recovery complete");
        } elseif ($cleanup) {
            DB::alteration_message("Cleanup complete");
        } else {
            DB::alteration_message("Recover all of of list of records by passing ?recover=all or ?recover=id,id2,id3 in the url or clean them by passing ?cleanup=1");
        }
    }
}
