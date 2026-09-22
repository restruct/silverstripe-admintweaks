<?php

namespace Restruct\Silverstripe\AdminTweaks\Dev\Migration;

use SilverStripe\Assets\Folder;
use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;

/**
 * SS3/SS4 -> SS5 migration repair: FOLDER rows carrying a `FileFilename` value.
 *
 * On a vanilla install `File.FileFilename` is EMPTY for folder rows (a folder derives its path from
 * the parent chain). Some migrations populate it, and asset-admin then blows up with:
 *
 *     HashFileIDHelper::buildFileID requires an $hash value
 *
 * because resolving a folder's `visibility` builds a file ID from (FileFilename, FileHash) — and a
 * folder has a filename but no hash. The result is that /admin/assets is completely unusable.
 *
 * Nulling the column is safe: folders do not use it.
 *
 * Also reports (and, with apply=1, does NOT touch) non-folder rows with an empty FileHash — those
 * trip the same exception via their `url`, but deleting file records is not something a task should
 * do unattended: clear any has_one references first, then remove the File + File_Versions rows by
 * hand.
 *
 * Dry-run by default; pass apply=1 to write.
 */
class FixFolderFileFilenameTask extends BuildTask
{
    private static $segment = 'fix-folder-filefilename';

    protected $title = 'Migration: clear FileFilename on Folder rows';

    protected $description = 'Folders must not carry a FileFilename — a populated value breaks /admin/assets with "buildFileID requires an $hash value". Dry-run unless apply=1.';

    /**
     * Versioned tables that may also carry the artifact. File_Live is absent on projects that removed
     * Draft/Live staging from files, so each table is checked for existence first.
     */
    private const TABLES = ['File', 'File_Live', 'File_Versions'];

    public function run($request)
    {
        $apply = (bool) $request->getVar('apply');

        $this->out(sprintf('DB: %s', DB::get_conn()->getSelectedDatabase()));
        $this->out($apply ? 'mode: APPLY' : 'mode: DRY-RUN (pass apply=1 to write)');
        $this->out('');

        foreach (self::TABLES as $table) {
            if (!DB::query(sprintf("SHOW TABLES LIKE '%s'", $table))->value()) {
                $this->out(sprintf('  %-14s (table absent — skipped)', $table));
                continue;
            }

            $affected = (int) DB::query(sprintf(
                'SELECT COUNT(*) FROM "%s"
                 WHERE "ClassName" LIKE \'%%Folder%%\'
                   AND "FileFilename" IS NOT NULL AND "FileFilename" <> \'\'',
                $table
            ))->value();

            $this->out(sprintf('  %-14s folder rows with FileFilename (should be 0): %d', $table, $affected));

            if ($affected && $apply) {
                DB::query(sprintf(
                    'UPDATE "%s" SET "FileFilename" = NULL WHERE "ClassName" LIKE \'%%Folder%%\'',
                    $table
                ));
                $this->out(sprintf('  %-14s -> cleared %d row(s)', $table, DB::affected_rows()));
            }
        }

        # Sibling artifact, reported only — see the class docblock for why this task won't delete.
        $orphans = (int) DB::query(
            'SELECT COUNT(*) FROM "File"
             WHERE "ClassName" NOT LIKE \'%Folder%\' AND ("FileHash" IS NULL OR "FileHash" = \'\')'
        )->value();
        $this->out('');
        $this->out(sprintf('  non-folder File rows with an empty FileHash: %d %s', $orphans, $orphans
            ? '<-- these trip the same exception via their url; inspect and clean up by hand'
            : ''));

        if ($apply) {
            $this->out('');
            $this->out(sprintf('Folders resolving correctly after run: %s', $this->verifyFolders()));
        }
    }

    /**
     * Exercise the code path that was actually throwing — resolving each folder's visibility/filename.
     */
    private function verifyFolders(): string
    {
        $ok = $failed = 0;
        foreach (Folder::get() as $folder) {
            try {
                $folder->getVisibility();
                $folder->getFilename();
                $ok++;
            } catch (\Throwable $e) {
                $failed++;
                $this->out(sprintf('  FAIL folder #%d %s -> %s', $folder->ID, $folder->Name, $e->getMessage()));
            }
        }

        return sprintf('%d ok, %d failed', $ok, $failed);
    }

    private function out(string $line): void
    {
        echo $line . (Director::is_cli() ? "\n" : "<br>\n");
    }
}
