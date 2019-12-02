<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\Queue;

use Arikaim\Core\Collection\Arrays;
use Arikaim\Core\Utils\Factory;
use Arikaim\Core\Interfaces\Events\EventDispatcherInterface;
use Arikaim\Core\Interfaces\Job\QueueStorageInterface;
use Arikaim\Core\Interfaces\Job\JobInterface;
use Arikaim\Core\Queue\Cron;
use Arikaim\Core\Queue\QueueWorker;

/**
 * Queue manager
 */
class QueueManager 
{
    /**
     * Queue storage driver
     *
     * @var QueueStorageInterface
     */
    protected $driver;

    /**
     * Event Dispatcher
     *
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * Constructor
     *
     * @param QueueStorageInterface $driver
     */
    public function __construct(QueueStorageInterface $driver, EventDispatcherInterface $eventDispatcher)
    {       
        $this->setDriver($driver);
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Create cron scheduler
     *
     * @return object
     */
    public function createScheduler()
    {
        return new Cron();
    }

    /**
     * Create queue worker
     *
     * @return object
     */
    public function createWorker()
    {
        return new QueueWorker();
    }

    /**
     * Set queue storage driver
     *
     * @param QueueStorageInterface $driver
     * @return void
     */
    public function setDriver(QueueStorageInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Get queue storage driver
     *
     * @return QueueStorageInterface
     */
    public function getQueue()
    {
        return $this->driver;
    }

    /**
     * Return tru if job exist
     *
     * @param mixed $id
     * @return boolean
     */
    public function has($id)
    {
        return $this->driver->hasJob($id);
    }

    /**
     * Delete jobs
     *
     * @param array $filter
     * @return boolean
     */
    public function deleteJobs($filter = [])
    {
        return $this->driver->deleteJobs($filter);
    }

    /**
     * Create job obj from jobs queue
     *
     * @param string $name
     * @return JobInterface|false
     */
    public function create($name)
    {
        $model = $this->findJobNyName($name);
        if (is_object($model) == false) {
            return false;
        }

        return Factory::createJobFromArray($model->toArray(),$model->handler_class);
    }

    /**
     * Get recurring jobs
     *
     * @param string|null $extension
     * @return array
     */
    public function getRecuringJobs($extension = null)
    {   
        $filter = [
            'recuring_interval' => '*',
            'extension_name'    => $extension    
        ];       
        $jobs = $this->driver->getJobs($filter);
        
        return $jobs;
    }

    /**
     * Get not scheduled or recurrnign jobs
     *
     * @param string $extension
     * @param integer $status
     * @param boolean $queryOnly
     * @return Model|Bulder|null
     */
    public function getNotScheduledJobs($extension = null, $status = null, $queryOnly = true)
    {
        $model = Model::Jobs()->whereNull('recuring_interval')->whereNull('schedule_time'); 
       
        if ($extension != null) {
            $model = $model->where('extenion_name','=',$extension); 
        }
        if ($status != null) {
            $model = $model->where('status','=',$status); 
        }
        $model = $model->orderBy('priority','desc');

        if ($queryOnly == false) {
            $model = $model->get();
        }
     
        return (is_object($model) == true) ? $model : null;
    }

    /**
     * Get all jobs due
     * 
     * @return array
     */
    public function getJobsDue()
    {
        return $this->driver->getJobsDue();
    }

    /**
     * Add job
     *
     * @param JobInterface $job
     * @param string|null $extension
     * @return bool
     */
    public function addJob(JobInterface $job, $extension = null)
    {       
        $info = [
            'priority'       => $job->getPriority(),
            'name'           => $job->getName(),
            'handler_class'  => get_class($job),         
            'extension_name' => (empty($extension) == true) ? $job->getExtensionName() : $extension,    
            'status'         => 1,
            'uuid'           => $job->getId()
        ];

        return $this->driver->addJob($job->getId(),$info);      
    }

    /**
     * Delete job
     *
     * @param string|integer $id Job id, uiid
     * @return bool
     */
    public function deleteJob($id)
    {
        return $this->driver->deleteJob($id);
    }

    /**
     * Delete all jobs
     *    
     * @return boolean
     */
    public function clear()
    {
        return $this->driver->deleteJobs();
    }

    /**
     * Get next job
     *
     * @return JobInterface|null
     */
    public function getNext()
    {
        $jobData = $this->driver->getNext();
        if ($jobData === false) {
            return false;
        }

        return Factory::createJob($jobData['handler_class'],$jobData['extension_name'],$jobData['name'],$jobData['priority']);     
    }

    /**
     * Run job
     *
     * @param JobInterface|string|integer $job
     * @return void
     */
    public function executeJob($job)
    {
        if (is_string($job) == true || is_numeric($job) == true) {

        }
        // before run job event
        if ($this->eventDispatcher != null) {
            $this->eventDispatcher->dispatch('core.jobs.before.execute',Arrays::convertToArray($job));
        }
      
        try {
            $job->execute();
            $this->driver->updateExecutionStatus($job);
        } catch (\Exception $e) {
            return false;
        }

        // after run job event
        if ($this->eventDispatcher != null) {
            $this->eventDispatcher->dispatch('core.jobs.after.execute',Arrays::convertToArray($job));
        }

        return true;
    }
}
