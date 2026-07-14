<?php

namespace Restruct\Silverstripe\AdminTweaks\Dev\Migration;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;

/**
 * SS3/SS4 -> SS5 migration repair: image files carried over with ClassName
 * `SilverStripe\Assets\File` instead of `Image` (or an Image subclass such as SVGImage).
 *
 * Symptoms:
 *  - the asset-admin grid shows the generic file icon instead of a preview, because a plain File has
 *    no resampling methods (the bytes are fine — only ClassName is wrong);
 *  - more seriously, a `has_one` *Image* relation pointing at such a record renders NOTHING on the
 *    front end, since Fill()/FitMax()/ScaleWidth() do not exist on File. That is a silent site defect,
 *    not just a CMS annoyance.
 *
 * Saving the file in the CMS already repairs it one at a time: AssetAdmin::save() re-derives the class
 * from the extension and calls newClassInstance() (asset-admin/code/Controller/AssetAdmin.php). This
 * task does exactly the same thing in bulk.
 *
 * The target class is resolved with File::get_class_for_file_extension() rather than hardcoding Image,
 * because the extension->class map is config (File.class_for_file_extension) and projects register
 * subclasses — e.g. .svg may map to a SVGImage class.
 *
 * Run BEFORE GenerateCmsThumbnailsTask: the thumbnail helper skips anything whose getIsImage() is
 * false, so a file still stuck on ClassName=File would be passed over.
 *
 * Dry-run by default; pass apply=1 to write.
 */
class FixMisclassifiedImagesTask extends BuildTask
{
    private static $segment = 'fix-misclassified-images';

    protected $title = 'Migration: fix misclassified image files (File -> Image)';

    protected $description = 'Reclassifies image files stored as plain File to Image/SVGImage (SS3->SS5 migration artifact). Dry-run unless apply=1.';

    public function run($request)
    {
        $apply = (bool) $request->getVar('apply');

        $this->out(sprintf('DB: %s', DB::get_conn()->getSelectedDatabase()));
        $this->out($apply ? 'mode: APPLY' : 'mode: DRY-RUN (pass apply=1 to write)');
        $this->out('');

        $files = $this->getMisclassifiedFiles();
        $this->out(sprintf('Found %d image file(s) with ClassName = %s', count($files), File::class));
        $this->out('');

        $byClass = [];
        $failed = 0;

        foreach ($files as $file) {
            $ext = strtolower(File::get_file_extension($file->Name));
            $newClass = File::get_class_for_file_extension($ext);

            # Only ever promote to an Image subclass. If the extension doesn't map to one, leave the
            # record alone — reclassifying to something unexpected is worse than a missing thumbnail.
            if (!$newClass || !is_a($newClass, Image::class, true)) {
                $this->out(sprintf(
                    '  SKIP  #%-6d %-44s .%s does not map to an Image class (%s)',
                    $file->ID,
                    $file->Name,
                    $ext,
                    $newClass ?: 'none'
                ));
                continue;
            }

            $byClass[$newClass] = ($byClass[$newClass] ?? 0) + 1;

            if (!$apply) {
                continue;
            }

            try {
                # newClassInstance() rewrites ClassName and returns a re-hydrated object of the new
                # class; write() then versions it as normal.
                $file->newClassInstance($newClass)->write();
            } catch (\Throwable $e) {
                $failed++;
                $this->out(sprintf('  FAIL  #%-6d %-44s %s', $file->ID, $file->Name, $e->getMessage()));
            }
        }

        $this->out('');
        foreach ($byClass as $class => $n) {
            $this->out(sprintf(
                '  %s%-44s %d',
                $apply ? 'reclassified to ' : 'would reclassify to ',
                $class,
                $n
            ));
        }
        if ($failed) {
            $this->out(sprintf('  FAILURES: %d', $failed));
        }

        if ($apply) {
            $this->out('');
            $this->out(sprintf('Remaining misclassified after run: %d', count($this->getMisclassifiedFiles())));
            $this->out('NEXT: run dev/tasks/generate-cms-thumbnails to create the CMS grid variants.');
        }
    }

    /**
     * Image-extension files still sitting on the generic File class.
     *
     * @return File[]
     */
    private function getMisclassifiedFiles(): array
    {
        # Read the extension->class map from config rather than assuming a fixed list, so projects
        # that register extra image types (or an SVG image class) are handled correctly.
        $imageExtensions = [];
        foreach ((array) Config::inst()->get(File::class, 'class_for_file_extension') as $ext => $class) {
            if ($ext !== '*' && is_a($class, Image::class, true)) {
                $imageExtensions[] = '.' . strtolower($ext);
            }
        }

        if (!$imageExtensions) {
            return [];
        }

        # File::get() is polymorphic (it returns Image rows too), so filter ClassName down to the
        # generic File. NB: no Versioned::get_by_stage() — a project may have removed Draft/Live
        # staging from files, in which case there are no stage tables.
        return File::get()
            ->filter([
                'ClassName' => File::class,
                'Name:EndsWith' => $imageExtensions,
            ])
            ->toArray();
    }

    private function out(string $line): void
    {
        echo $line . (Director::is_cli() ? "\n" : "<br>\n");
    }
}
