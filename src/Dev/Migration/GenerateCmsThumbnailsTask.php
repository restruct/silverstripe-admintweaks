<?php

namespace Restruct\Silverstripe\AdminTweaks\Dev\Migration;

use Psr\Log\LoggerInterface;
use SilverStripe\AssetAdmin\Helper\ImageThumbnailHelper;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Storage\AssetStore;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;

/**
 * Generate the CMS thumbnail variants for existing image files.
 *
 * Why this is needed at all:
 * the asset-admin file grid is served by GraphQL, and asset-admin deliberately injects a
 * NON-generating thumbnail generator for it (asset-admin/_config/config.yml):
 *
 *     SilverStripe\AssetAdmin\Model\ThumbnailGenerator.graphql:
 *       properties:
 *         Generates: false
 *
 * i.e. the grid emits the `__FitMax[...]` thumbnail URL but never creates the variant file. That is
 * fine for normal uploads, because the variants are generated when the file is uploaded/saved through
 * the CMS (AssetAdmin::save() -> generateThumbnails(), with the *generating* generator).
 *
 * Files carried over by an SS3/SS4 -> SS5 migration were never uploaded through SS5, so their variants
 * do not exist and the grid shows a blank tile (the <img> 404s). SS4 solved this by running
 * ImageThumbnailHelper as part of MigrateFileTask — but SS5 REMOVED MigrateFileTask while keeping the
 * helper. This task just runs the helper.
 *
 * Run AFTER FixMisclassifiedImagesTask: the helper skips anything whose getIsImage() is false, so a
 * file still stuck on ClassName=File would be passed over.
 *
 * Dry-run by default (reports what is missing); pass apply=1 to actually generate.
 */
class GenerateCmsThumbnailsTask extends BuildTask
{
    private static $segment = 'generate-cms-thumbnails';

    protected $title = 'Migration: generate CMS thumbnails for existing images';

    protected $description = 'Creates the asset-admin thumbnail variants for migrated images (the CMS grid does not generate them). Dry-run unless apply=1.';

    /**
     * ImageThumbnailHelper ships with silverstripe/asset-admin, which this module does not require
     * (it only requires silverstripe/framework). Hide the task rather than fatal on a framework-only
     * install.
     */
    public function isEnabled(): bool
    {
        return parent::isEnabled() && class_exists(ImageThumbnailHelper::class);
    }

    public function run($request)
    {
        $apply = (bool) $request->getVar('apply');
        $this->out($apply ? 'mode: APPLY' : 'mode: DRY-RUN (pass apply=1 to generate)');

        [$present, $missing] = $this->countGridThumbnails();
        $this->out(sprintf(
            'images: %d   grid thumbnail present: %d   MISSING: %d',
            $present + $missing,
            $present,
            $missing
        ));

        if (!$apply) {
            return;
        }

        # The helper generates BOTH sizes the CMS uses (UploadField's small thumb + the grid thumb),
        # skips non-images and oversized files, and only writes variants that don't already exist.
        $helper = Injector::inst()->create(ImageThumbnailHelper::class);
        $helper->setLogger(Injector::inst()->get(LoggerInterface::class));
        $generated = $helper->run();

        $this->out(sprintf('generated thumbnails for %d file(s)', $generated));

        [, $stillMissing] = $this->countGridThumbnails();
        $this->out(sprintf('grid thumbnails still missing after run: %d', $stillMissing));
    }

    /**
     * Count images whose asset-admin grid variant does/doesn't exist — WITHOUT creating it.
     *
     * Careful: calling $image->FitMax(352, 264) would GENERATE the variant as a side effect (that is
     * exactly what this task does in apply mode), which would make a dry-run silently do the work and
     * always report "MISSING: 0". So ask the asset store whether the variant is already stored.
     *
     * @return array{0:int,1:int} [present, missing]
     */
    private function countGridThumbnails(): array
    {
        $store = Injector::inst()->get(AssetStore::class);
        $present = $missing = 0;

        foreach (Image::get() as $image) {
            $hash = $image->getHash();
            if (!$hash) {
                # no stored asset at all — nothing to make a thumbnail from
                $missing++;
                continue;
            }
            # the variant key asset-admin's grid asks for (thumbnail_width/height = 352x264)
            $variant = $image->variantName('FitMax', 352, 264);
            $store->exists($image->getFilename(), $hash, $variant) ? $present++ : $missing++;
        }

        return [$present, $missing];
    }

    private function out(string $line): void
    {
        echo $line . (Director::is_cli() ? "\n" : "<br>\n");
    }
}
