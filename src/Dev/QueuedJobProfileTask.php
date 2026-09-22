<?php

namespace Restruct\Silverstripe\AdminTweaks\Dev;

use SilverStripe\Control\Director;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Dev\BuildTask;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;

/**
 * In-process profiler for queued jobs: answers "does this job accumulate, and where?".
 *
 * Drives a job's setup() + process() loop inside a real CLI bootstrap and samples, per process()
 * call: memory usage (+ delta), peak, the real currentStep, the serialized jobData size and the
 * message count — i.e. the per-step cost the descriptor write pays.
 *
 * Call with no `class` to list the queued jobs it can profile. `maxcalls` caps the run (default 500).
 *
 * NB the arguments differ by context — on the CLI they are SPACE-SEPARATED, not a query string
 * (`?class=…` is silently ignored there, and you just get the job list back):
 *
 *   CLI  php cli-script.php dev/tasks/qjob-profile class=My\Jobs\SyncJob maxcalls=50
 *   web  dev/tasks/qjob-profile?class=My\Jobs\SyncJob&maxcalls=50
 *
 * Run it with the ceiling the CRON actually has, or the result means nothing — a job that only
 * survives because *you* ran it with `-d memory_limit=2G` will still die on the scheduled run:
 *
 *   php -d memory_limit=512M vendor/silverstripe/framework/cli-script.php dev/tasks/qjob-profile class=...
 *
 * Reading the output:
 *  - Δmem ≈ 0 across calls  -> the job does NOT leak. Stop hunting for one; if it still OOMs, the
 *    fatal is thrown somewhere else (a single query fetching too many rows, say) — look at the DATA.
 *  - jobData / msgs climbing -> THERE is your accumulator. Both are re-serialised and written to the
 *    DB after EVERY step, so their cost is size x steps — quadratic. An unbounded message log or a
 *    big array parked on the job is the usual culprit.
 *
 * NB the reflection: currentStep is a REAL protected property on AbstractQueuedJob, whereas
 * job-specific state lives in jobData behind the magic __get. Mixing the two up silently reads 0 and
 * makes a leaking job look flat — which is exactly the false negative this task exists to prevent.
 *
 * Dev-only: it EXECUTES the job, so it writes whatever the job writes.
 */
class QueuedJobProfileTask extends BuildTask
{
    private static $segment = 'qjob-profile';

    protected $title = 'Profile a queued job (memory + jobData per process() call)';

    protected $description = 'Runs a job in-process and reports per-call memory/step/jobData/message stats, to find what a job accumulates. Dev-only (it really runs the job).';

    public function run($request)
    {
        if (!Director::isDev()) {
            echo "Dev-only: this task actually RUNS the job (and writes whatever the job writes).\n";

            return;
        }

        $class = (string) $request->getVar('class');
        if (!$class) {
            $this->listJobClasses();

            return;
        }

        if (!class_exists($class) || !is_subclass_of($class, AbstractQueuedJob::class)) {
            echo "Not a queued job class: {$class}\n\n";
            $this->listJobClasses();

            return;
        }

        $maxCalls = max(1, (int) ($request->getVar('maxcalls') ?: 500));

        printf("Profiling %s (php memory_limit=%s, max %d process() calls)\n\n", $class, ini_get('memory_limit'), $maxCalls);
        printf("%-5s %-24s %-10s %-10s %-10s %-10s %s\n", 'call', 'phase/step', 'mem', 'delta', 'peak', 'jobData', 'msgs');

        /** @var AbstractQueuedJob $job */
        $job = new $class();
        $job->setup();

        # currentStep is a REAL protected property (see class docblock) — reading it off jobData
        # would silently return 0 and make any job look like it never progresses.
        $stepProp = new \ReflectionProperty(AbstractQueuedJob::class, 'currentStep');
        $stepProp->setAccessible(true);

        $mb = fn($bytes) => number_format($bytes / 1048576, 1) . 'M';
        $prev = memory_get_usage(true);
        $start = microtime(true);
        $call = 0;

        while (!$job->jobFinished() && $call < $maxCalls) {
            $call++;
            $job->process();

            $mem = memory_get_usage(true);
            $data = $job->getJobData();
            $phase = $data->jobData->phase ?? '';

            printf("%-5d %-24s %-10s %-10s %-10s %-10s %d\n",
                $call,
                substr($phase . '/' . $stepProp->getValue($job), 0, 23),
                $mb($mem),
                number_format(($mem - $prev) / 1024, 0) . 'K',
                $mb(memory_get_peak_usage(true)),
                number_format(strlen(serialize($data->jobData)) / 1024, 1) . 'K',
                count((array) $data->messages)
            );
            $prev = $mem;
        }

        printf("\n%s after %d call(s) in %.1fs — peak %s, final jobData %sK, %d message(s)\n",
            $job->jobFinished() ? 'COMPLETE' : 'STOPPED (maxcalls reached)',
            $call,
            microtime(true) - $start,
            $mb(memory_get_peak_usage(true)),
            number_format(strlen(serialize($job->getJobData()->jobData)) / 1024, 1),
            count((array) $job->getJobData()->messages)
        );
    }

    private function listJobClasses(): void
    {
        echo "Usage: dev/tasks/qjob-profile?class=<FQCN>[&maxcalls=N]\n\nQueued jobs found:\n";
        foreach (ClassInfo::subclassesFor(AbstractQueuedJob::class, false) as $jobClass) {
            echo "  {$jobClass}\n";
        }
    }
}
