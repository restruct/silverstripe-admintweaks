<?php

namespace Restruct\Silverstripe\AdminTweaks\Dev;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Dev\BuildTask;
use Symbiote\QueuedJobs\DataObjects\QueuedJobDescriptor;
use Symbiote\QueuedJobs\Services\QueuedJob;

if (!ClassInfo::exists(QueuedJobDescriptor::class)) {
    return;
}

/**
 * Delete all broken queued jobs
 *
 * Usage: vendor/bin/sake dev/tasks/cleanup-broken-jobs
 */
class CleanupBrokenJobsTask extends BuildTask
{
    private static $segment = 'cleanup-broken-jobs';

    protected $title = 'Cleanup Broken Jobs';

    protected $description = 'Deletes all queued jobs with status "Broken"';

    public function run($request)
    {
        $broken = QueuedJobDescriptor::get()->filter('JobStatus', QueuedJob::STATUS_BROKEN);
        $count = $broken->count();

        if ($count === 0) {
            echo "No broken jobs found.\n";
            return;
        }

        echo "Found {$count} broken job(s). Deleting...\n";

        foreach ($broken as $job) {
            echo "  - Deleting: {$job->JobTitle} (ID: {$job->ID})\n";
            $job->delete();
        }

        echo "Done. Deleted {$count} broken job(s).\n";
    }
}