<?php

namespace Restruct\Silverstripe\AdminTweaks\Dev;

use SilverStripe\Assets\Image;
use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\Versioned\Versioned;

class FocusPointInvertYaxisTask
    extends BuildTask

{
    protected static string $commandName = 'focuspoint-invert-yaxis';
    protected string $title = 'Invert all Focus-Point Y-Axis values (v2↔v3)';
    protected static string $description = 'Y-axis normally gets auto-migrated by FocusPointMigrationTask on dev/build. This tasks just inverts all Y values, eg in case the migration did not succeed or run correctly.';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $schema = DataObject::getSchema();
        $imageTable = $schema->tableName(Image::class);

        // Check the TABLE before asking for its columns. Image carries no fields of its own, so
        // it has no table unless something added one - FocusPoint's FocusPointX/Y is normally what
        // does. Without that, DB::field_list() raises a DatabaseException ("Table ... doesn't
        // exist") before the missing-column guard below can report the far friendlier no-op.
        if (!DB::get_schema()->hasTable($imageTable)) {
            $output->writeln("<comment>There is no \"$imageTable\" table - the FocusPoint module is not installed. Nothing to do.</>");
            return Command::SUCCESS;
        }

        $fields = DB::field_list($imageTable);

        if (!isset($fields["FocusPointY"])) {
            // Nothing to migrate: the FocusPoint module is not installed, or dev/build has not run.
            // This is not a failure - the task is simply a no-op here.
            $output->writeln("<comment>$imageTable has no \"FocusPointY\" column - nothing to do.</>");
            return Command::SUCCESS;
        }

        // Safety net
        // The guard above already returns on a missing column, so this second check could never
        // fire. Left commented rather than deleted: it records the intended "did you run dev/build?"
        // diagnostic, which the early return above now covers by reporting instead of throwing.
//        if (!isset($fields["FocusPointY"])) {
//            throw new \Exception("$imageTable table does not have \"FocusPointY\" fields. Did you run dev/build?");
//        }

        // Update all Image tables
        $imageTables = [
            $imageTable,
            $imageTable . "_" . Versioned::LIVE,
            $imageTable . "_Versions",
        ];

        $succeeded = false;
        DB::get_conn()->withTransaction(function() use ($imageTables, $output, &$succeeded) {
            foreach ($imageTables as $imageTable) {
                // NOTE the DOUBLE quotes around the column name. The framework connects with
                // sql_mode=ANSI (MySQLDatabase::$sql_mode), under which "FocusPointY" is an
                // IDENTIFIER - but 'FocusPointY' is a string literal in every MySQL mode, and
                // 'FocusPointY' * -1 evaluates to 0. The previous single-quoted form therefore
                // zeroed every focus point instead of inverting it.
                //        ->assignSQL('FocusPointY', "'FocusPointY' * -1")
                SQLUpdate::create("\"$imageTable\"")
                    ->assignSQL('FocusPointY', '"FocusPointY" * -1')
                    ->execute();
            }

            $output->writeln('Inverted FocusPointY values in tables ' . implode(', ', $imageTables));
            $succeeded = true;
        } , function () use ($output) {
            $output->writeln('<error>Failed to alter FocusPoint fields</>');
        }, false, true);

        return $succeeded ? Command::SUCCESS : Command::FAILURE;
    }
}
