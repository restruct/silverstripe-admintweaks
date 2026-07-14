<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use SilverStripe\Core\Extension;
use Symbiote\QueuedJobs\Services\QueuedJob;

/**
 * Makes a fatal inside the queue runner attributable to a job.
 *
 * The problem: when a queued job dies (typically a PHP fatal such as an OOM), the alert email and
 * ss_error.log report only "GET dev/tasks/ProcessJobQueueTask" — which names the RUNNER, not the
 * job. The runner processes every job on the queue, so that tells you nothing: no job, no step, no
 * memory figure. And because the fatal kills the process, the job itself never gets to log anything.
 *
 * The fix: record the job currently being processed in $_SERVER, where the error reporter can find
 * it. EnhancedErrorFormatter renders whitelisted $_SERVER keys in its Details section, so adding
 * QUEUED_JOB / QUEUED_JOB_MEMORY to `server_vars` is all that is needed to surface it.
 *
 * $_SERVER on purpose:
 *  - it survives into the shutdown handler that renders the report, and
 *  - reading it needs no allocation — which matters, because the failure we most want to attribute
 *    IS memory exhaustion. (A DB lookup at that point may itself fail for want of memory.)
 *
 * Why an Extension on the DESCRIPTOR rather than on the service: QueuedJobService has no extension
 * hooks at all (no ->extend() calls in it), so hooking it would mean subclassing and overriding
 * internals — brittle against upstream. But the service calls copyJobToDescriptor() + write() on the
 * descriptor after EVERY processed step, so onAfterWrite() here is called exactly as often as the
 * job makes progress, for every job (including jobs shipped by other modules), using only a
 * supported API.
 */
class QueuedJobContextExtension extends Extension
{
    /**
     * The descriptor is written by QueuedJobService after every step, so this keeps the recorded
     * context as granular as the job's own progress.
     */
    public function onAfterWrite()
    {
        $descriptor = $this->getOwner();

        # only a RUNNING job is the thing to blame for a fatal happening right now. When the job
        # finishes (or is paused/broken) clear the context, so a later, unrelated error in the same
        # long-lived runner process is not misattributed to it.
        if ($descriptor->JobStatus !== QueuedJob::STATUS_RUN) {
            unset($_SERVER['QUEUED_JOB'], $_SERVER['QUEUED_JOB_MEMORY']);

            return;
        }

        $_SERVER['QUEUED_JOB'] = sprintf(
            '#%d %s | %s | step %s/%s | queue %s',
            $descriptor->ID,
            $descriptor->Implementation ?: '(unknown implementation)',
            $descriptor->JobTitle,
            $descriptor->StepsProcessed,
            $descriptor->TotalSteps,
            $descriptor->JobType
        );

        # The memory figures are the point when the failure is an OOM: they show how far the job had
        # already climbed at its last completed step, and whether QueuedJobService's own guard — which
        # is only checked BETWEEN steps — ever had a chance to park the job before PHP fatally
        # allocated. A single step that jumps the gap will show a last-known usage well under the
        # limit, which is itself the diagnosis.
        $_SERVER['QUEUED_JOB_MEMORY'] = sprintf(
            'used %s / peak %s | php.ini memory_limit %s',
            $this->formatBytes(memory_get_usage()),
            $this->formatBytes(memory_get_peak_usage()),
            ini_get('memory_limit') ?: '(unknown)'
        );
    }

    private function formatBytes(float $bytes): string
    {
        return round($bytes / 1048576, 1) . ' MB';
    }
}
