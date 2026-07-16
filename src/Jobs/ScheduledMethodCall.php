<?php

namespace Restruct\Silverstripe\AdminTweaks\Jobs;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;

//use SilverStripe\GraphQL\TypeCreator;
//if (!class_exists(TypeCreator::class)) {
//    return;
//}

if(!ClassInfo::exists(AbstractQueuedJob::class)) {
    return;
}

/**
 * A universal ScheduledExecution_Job that gets scheduled and executed once, async (eg for sending out an email)
 * Calls the $method argument on the $object argument, optionally with $arguments
 */
/**
 * @deprecated CONSOLIDATION PENDING: the canonical home for this job is restruct/silverstripe-queuedjobs-enhancements.
 * This copy stays for now because (as of 2026-07-16) the enhancements copy LACKS the 3.15.0 rethrow fix
 * (jobStatus-assignment no-op -> infinite process() loop / message-log OOM) and the 3.16.0 deleted-record
 * fail-fast. Once those are ported there: switch consumers' imports (fuse DocSys_Document), then remove
 * this class (no QueuedJobDescriptor rows reference this FQCN on fuse local/prod, verified 2026-07-16).
 */
class ScheduledMethodCall
    extends AbstractQueuedJob
{
    /**
     * Static method to schedule a ScheduledMethodCall
     *
     * @params {@see __construct()}
     * @param int|string $when to schedule
     *
     * @return int ID of created \Symbiote\QueuedJobs\DataObjects\QueuedJobDescriptor
     */
    public static function schedule($ObjectOrClass, string $method, array $args = [], array $options = [], $when = 'now')
    {
        return QueuedJobService::singleton()->queueJob(
            Injector::inst()->create(ScheduledMethodCall::class, $ObjectOrClass, $method, $args, $options),
            DBDatetime::create()->setValue(strtotime($when))->Rfc2822()
        );
    }

    /**
     * ScheduledMethodCall constructor
     * @param DataObject|object|string $ObjectOrClass to call method on (if object, will be instantiated as singleton)
     * @param string $method method to call each time this job gets processed
     * @param array $args arguments for the called method
     * @param null $options [
     *      'description' => '[ no description ]',
     *      'jobType' => QueuedJob::QUEUED,
     *      'totalSteps' => 1,
     *      'ignoreIdentical' => false // add even if an identical job is already queued
     * ]
     */
    public function __construct($ObjectOrClass = null, $method = null, $args = [], $options = [])
    {
        // doesn't do anything really, just prevents IDE warning
        parent::__construct();

        if($ObjectOrClass && $method){ // initialize (job data is serialized between calls)
            if(is_a($ObjectOrClass, DataObject::class)){
                # 3.16.0: fail FAST on an unwritten record. With ID=0 the stored objectID is falsy, so
                # process() would silently degrade to a STATIC call and fatal at cron time ("method
                # cannot be called statically") — far from the actual cause. Hit on DHUB prod:
                # a record scheduled a method on itself from onBeforeWrite of its INITIAL save.
                if (!$ObjectOrClass->ID) {
                    throw new InvalidArgumentException(sprintf(
                        'ScheduledMethodCall: cannot schedule %s::%s() on an UNWRITTEN record (ID=0) — write() it first (or schedule from onAfterWrite)',
                        $ObjectOrClass->ClassName,
                        $method
                    ));
                }
                $this->objectID = $ObjectOrClass->ID;
                $this->objectClass = $ObjectOrClass->ClassName;
            } elseif (is_object($ObjectOrClass)) {
                $this->objectClass = get_class($ObjectOrClass);
            } else {
                $this->objectClass = $ObjectOrClass;
            }

            $this->method = $method;
            $this->arguments = $args;

            $mergedOptions = array_merge(
                [
                    'description' => '[ no description ]',
                    'jobType' => QueuedJob::QUEUED,
                    'totalSteps' => 1,
                    'ignoreIdentical' => false
                ],
                $options
            );
            $this->description = $mergedOptions['description'];
            $this->jobType = $mergedOptions['jobType'];
            $this->totalSteps = $mergedOptions['totalSteps'];
            $this->appendSig = $mergedOptions['ignoreIdentical'] ? $this->randomSignature() : '';

            $this->addMessage('Job will call ' . $this->getContextDescription());
        }
    }

    private function getContextDescription()
    {
        return sprintf(
            "method %s on %s (%s)",
            $this->method,
            $this->objectClass,
            $this->objectID ? "ID:{$this->objectID}" : 'statically'
        );
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->description;
    }

    /**
     * Send to the 'immediate' queue (handling not set up but may be later)
     */
    public function getJobType()
    {
        return $this->jobType;
    }

    public function getSignature()
    {
        return parent::getSignature() . ($this->appendSig ?: '');
    }

    public function process()
    {
        $this->currentStep++;

        $objectOrClassName = $this->objectClass;
        if($this->objectID){
            $objectOrClassName = DataObject::get_by_id($this->objectClass, $this->objectID);
            # 3.16.0: a deleted record used to fatal on the next line (property-set on null); throw a
            # clear failure instead so the service marks the job Broken with an attributable message.
            if (!$objectOrClassName) {
                throw new RuntimeException(sprintf(
                    'ScheduledMethodCall: %s #%d no longer exists — cannot call %s() (record deleted since scheduling?)',
                    $this->objectClass,
                    $this->objectID,
                    $this->method
                ));
            }
            $objectOrClassName->scheduled_job_instance = $this;
        }

        try {
            $result = call_user_func_array([$objectOrClassName, $this->method], $this->arguments);
            if($result) {
                $this->addMessage($result);
            } else {
                $this->addMessage(sprintf(
                    "%s::%s call returned no result, may indicate that method doesnt exist",
                    $this->objectClass,
                    $this->method
                ), 'WARNING');
            }

        } catch(Throwable $e){
            $this->addMessage(sprintf(
                "%s::%s ERROR: %s (%s)",
                $this->objectClass,
                $this->method,
                $e->getCode(),
                $e->getMessage()
            ));
            # 3.15.0: RE-THROW instead of `$this->jobStatus = self::STATUS_BROKEN; return;`. That assignment
            # only wrote a magic jobData property QueuedJobService never reads — the service saw no exception
            # and no isComplete, so it looped process() indefinitely, with addMessage() re-serialised onto the
            # descriptor every pass (DHUB prod 2026-07-15: descriptor #152177 reached step 2563/1 and OOM'd the
            # runner at ~400MB). Throwing is the documented failure signal: runJob() catches Throwable, logs
            # the error against the descriptor and marks it Broken (no retry loop). Also catch Throwable, not
            # Exception, so Errors (eg a TypeError from a bad call target) get the job-context message too.
            //            $this->jobStatus = self::STATUS_BROKEN;
            //            return;
            throw $e;
        }

//        $this->addMessage('Called: ' . $this->getContextDescription());

        if($this->currentStep >= $this->totalSteps){
            $this->isComplete = true;
        }
    }
}
