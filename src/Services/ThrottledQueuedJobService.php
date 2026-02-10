<?php

namespace Restruct\Silverstripe\AdminTweaks\Services;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Injector\Injector;
use Symbiote\QueuedJobs\DataObjects\QueuedJobDescriptor;
use Symbiote\QueuedJobs\Services\QueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;

/**
 * Extended QueuedJobService that throttles broken job notifications.
 *
 * Problem: Default QueuedJobService logs "Broken jobs found" on EVERY cron run
 * (every minute) until broken jobs are manually fixed - causing email/SMS spam.
 *
 * Solution: Only notify about broken jobs at a configurable interval.
 *
 * ## Usage
 *
 * Enable via Injector in your project's YAML config:
 *
 * ```yaml
 * SilverStripe\Core\Injector\Injector:
 *   Symbiote\QueuedJobs\Services\QueuedJobService:
 *     class: Restruct\Silverstripe\AdminTweaks\Services\ThrottledQueuedJobService
 *
 * # Optional: adjust notification interval (default 1 hour)
 * Restruct\Silverstripe\AdminTweaks\Services\ThrottledQueuedJobService:
 *   broken_job_notify_interval: 7200  # 2 hours
 *
 * # Required: cache backend for throttling
 * Psr\SimpleCache\CacheInterface.queuedjobs:
 *   factory: SilverStripe\Core\Cache\CacheFactory
 *   constructor:
 *     namespace: 'queuedjobs'
 * ```
 *
 * @see https://github.com/symbiote/silverstripe-queuedjobs/issues/299
 */
class ThrottledQueuedJobService extends QueuedJobService
{
    /**
     * Minimum seconds between broken job notifications (default: 1 hour)
     */
    private static int $broken_job_notify_interval = 3600;

    /**
     * Override to throttle broken job notifications.
     * Copy of parent method with throttled logging at the end.
     */
    public function checkJobHealth($queue = null)
    {
        $queue = $queue ?: QueuedJob::QUEUED;

        // Select all jobs currently marked as running
        $runningJobs = QueuedJobDescriptor::get()
            ->filter([
                'JobStatus' => [
                    QueuedJob::STATUS_RUN,
                    QueuedJob::STATUS_INIT,
                ],
                'JobType' => $queue,
            ]);

        // If no steps processed since last run, consider it stalled
        $stalledJobs = $runningJobs
            ->filter('LastProcessedCount:GreaterThanOrEqual', 0)
            ->where('"StepsProcessed" = "LastProcessedCount"');

        foreach ($stalledJobs as $stalledJob) {
            $this->restartStalledJob($stalledJob);
        }

        // Mark jobs as having been checked
        foreach ($runningJobs as $job) {
            $job->LastProcessedCount = $job->StepsProcessed;
            $job->write();
        }

        // THROTTLED: Only notify about broken jobs at configured interval
        $this->notifyBrokenJobsThrottled();

        return $stalledJobs->count();
    }

    /**
     * Notify about broken jobs, but only once per interval.
     * Cache key includes job IDs, so new broken jobs trigger immediate notification.
     */
    protected function notifyBrokenJobsThrottled(): void
    {
        $brokenJobs = QueuedJobDescriptor::get()
            ->filter('JobStatus', QueuedJob::STATUS_BROKEN);

        if (!$brokenJobs->exists()) {
            return;
        }

        // Check cache for last notification time
        // Key includes job IDs so new broken jobs = new key = immediate notification
        $cache = Injector::inst()->get(CacheInterface::class . '.queuedjobs');
        $brokenIds = $brokenJobs->column('ID');
        sort($brokenIds);
        $cacheKey = 'broken_jobs_notified_' . md5(implode(',', $brokenIds));

        $lastNotified = $cache->get($cacheKey, 0);
        $interval = $this->config()->get('broken_job_notify_interval');

        if (time() - $lastNotified < $interval) {
            return; // Still within throttle window
        }

        // Log the error (triggers email/monitoring)
        $this->getLogger()->error(sprintf(
            'Broken jobs found in queue: %d job(s). IDs: %s',
            count($brokenIds),
            implode(', ', $brokenIds)
        ));

        // Cache for 2x interval to handle edge cases
        $cache->set($cacheKey, time(), $interval * 2);
    }
}
