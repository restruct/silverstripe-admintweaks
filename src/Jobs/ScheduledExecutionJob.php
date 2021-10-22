<?php

namespace Restruct\Silverstripe\AdminTweaks\Jobs;

use Exception;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;

if(ClassInfo::exists('Symbiote\QueuedJobs\Services\AbstractQueuedJob')){

    /**
     * A universal ScheduledExecution_Job that gets scheduled and executed once, async (eg for sending out an email)
     * Calls the $method argument on the $object argument, optionally with $arguments
     */
    class ScheduledExecutionJob
        extends \Symbiote\QueuedJobs\Services\AbstractQueuedJob
    {
        /**
         * ScheduledExecutionJob constructor
         * @param DataObject $object object to call method on
         * @param string $method method to call each time this job gets processed
         * @param null $description of this job
         * @param null|int $when timestamp of when to execute this job (eg strtotime('tomorrow 8:00')
         * @param null|array $arguments
         * @param null|string $jobType
         * @param null|int $totalSteps
         * @param null|bool $generateRandomSignature
         */
        public function __construct(DataObject $object, $method, $description = null, $arguments=null, $jobType=null, $totalSteps=null, $generateRandomSignature=null)
        {
            // doesn't do anything really, just prevents IDE warning
            parent::__construct();

            if($object && $method){ // initialize (job data is serialized between calls)
//                $this->object = $object;
                $this->objectID = $object->ID;
                $this->objectType = $object->ClassName;
                $this->method = $method;
                $this->description = ($description ?: '[ no description ]');
                $this->arguments = $arguments ?: [];
                $this->jobType = $jobType ?: \Symbiote\QueuedJobs\Services\QueuedJob::QUEUED;
                $this->totalSteps = $totalSteps ?: 1;
                $this->appendSig = $generateRandomSignature ? $this->randomSignature() : '';
            }
        }

        /**
         * @return DataObject
         */
        public function getDataObject()
        {
            return DataObject::get_by_id($this->objectType, $this->objectID);
        }

        /**
         * ScheduledExecutionJob constructor
         * @param object $object object to call method on
         * @param string $method method to call each time this job gets processed
         * @param null $description of this job
         * @param null|int $when timestamp of when to execute this job (eg strtotime('tomorrow 8:00')
         * @param null|array $arguments
         * @param null|string $jobType
         * @param null|int $totalSteps
         * @param null|bool $generateRandomSignature
         *
         * @return int ID of created \Symbiote\QueuedJobs\DataObjects\QueuedJobDescriptor
         */
        public static function schedule($object, $method, $description = null, $when = null, $arguments=null, $jobType=null, $totalSteps=null, $generateRandomSignature=null)
        {
            // using full namespaced classnames because qjobs module may not be installed (so we cannot use imports)
            return singleton(\Symbiote\QueuedJobs\Services\QueuedJobService::class)->queueJob(
                new ScheduledExecutionJob($object, $method, $description = null, $arguments=null, $jobType=null, $totalSteps=null, $generateRandomSignature),
                $when ? date('Y-m-d H:i:s', $when) : null
            );
        }

        /**
         * @return string
         */
        public function getTitle() {
            return $this->description;
        }

        /**
         * Send to the 'immediate' queue (handling not set up but may be later)
         */
        public function getJobType() {
            return $this->jobType;
        }

        public function getSignature(){
            return parent::getSignature() . ($this->appendSig ?: '');
        }

        public function process() {
            $this->currentStep = (int) $this->currentStep +1;
            $object = $this->getDataObject();
            if ($object) {
                $object->scheduled_job_instance = $this;
                try {
                    $result = call_user_func_array([$object, $this->method], $this->arguments);
                    if($result) $this->addMessage(print_r($result, true));
                } catch(Exception $e){
                    $this->addMessage(sprintf("%s::%s ERROR: %s (%s)", get_class($this->object), $this->method, $e->getCode(), $e->getMessage()));
                }
                $this->addMessage(sprintf("%s::%s done", get_class($this->object), $this->method));
            } else {
                $this->addMessage(sprintf("%s::%s ERROR: NO OBJECT", get_class($this->object), $this->method));
//                $this->jobStatus = self::STATUS_BROKEN;
            }
            if($this->currentStep >= $this->totalSteps){
                $this->isComplete = true;
            }
        }
    }

}