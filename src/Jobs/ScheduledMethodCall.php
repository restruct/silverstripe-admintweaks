<?php

namespace Restruct\Silverstripe\AdminTweaks\Jobs;

use Exception;
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
            DBDatetime::create()->setValue(strtotime((string) $when))->Rfc2822()
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
                $this->objectID = $ObjectOrClass->ID;
                $this->objectClass = $ObjectOrClass->ClassName;
            } elseif (is_object($ObjectOrClass)) {
                $this->objectClass = $ObjectOrClass::class;
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
            $this->objectID ? 'ID:' . $this->objectID : 'statically'
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

        } catch(Exception $exception){
            $this->addMessage(sprintf(
                "%s::%s ERROR: %s (%s)",
                $this->objectClass,
                $this->method,
                $exception->getCode(),
                $exception->getMessage()
            ));
            $this->jobStatus = self::STATUS_BROKEN;
            return;
        }

//        $this->addMessage('Called: ' . $this->getContextDescription());

        if($this->currentStep >= $this->totalSteps){
            $this->isComplete = true;
        }
    }
}
