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

use Arikaim\Core\Utils\Factory;
use Arikaim\Core\Interfaces\Job\QueueStorageInterface;
use Arikaim\Core\Interfaces\Job\JobInterface;
use Arikaim\Core\Interfaces\Job\RecuringJobInterface;
use Arikaim\Core\Interfaces\Job\ScheduledJobInterface;
use Arikaim\Core\Interfaces\QueueInterface;
use Arikaim\Core\Queue\Cron;

/**
 * Queue manager
 */
class QueueManager implements QueueInterface
{
    /**
     * Queue storage driver
     *
     * @var QueueStorageInterface
     */
    protected $driver;

    /**
     * Constructor
     *
     * @param QueueStorageInterface $driver
     */
    public function __construct(QueueStorageInterface $driver)
    {       
        $this->setDriver($driver);           
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
    public function getStorageDriver()
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
     * @param string|integer $name
     * @return JobInterface|false
     */
    public function create($name)
    {
        $jobInfo = $this->getJob($name);
        
        return ($jobInfo === false) ? false : $this->createJobFromArray($jobInfo,$jobInfo['handler_class']);    
    }

    /**
     * Create job intence from array 
     *
     * @param array $data
     * @param string|null $class
     * @return object|null
     */
    public function createJobFromArray(array $data, $class = null)
    {      
        $class = $class ?? $data['class'];
        $instance = Factory::createJob($class);

        if ($instance == null) {
            return null;
        }

        foreach ($data as $key => $value) {
            $instance->{$key} = $value;
        }

        return $instance;
    }

    /**
     * Find job by name, id or uuid
     *
     * @param string|integer $id Job id, uiid or name
     * @return array|false
     */
    public function getJob($id)
    {
        return $this->driver->getJob($id);    
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
            'extension_name'    => (empty($extension) == true) ? '*' : $extension    
        ];       

        return $this->driver->getJobs($filter);        
    }

    /**
     * Get jobs
     *
     * @param array $filter
     * @return array
     */
    public function getJobs($filter = [])
    {  
        return $this->driver->getJobs($filter);   
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
            'priority'          => $job->getPriority(),
            'name'              => $job->getName(),
            'handler_class'     => \get_class($job),         
            'extension_name'    => $extension ?? $job->getExtensionName(),
            'status'            => 1,
            'recuring_interval' => ($job instanceof RecuringJobInterface) ? $job->getRecuringInterval() : null,
            'schedule_time'     => ($job instanceof ScheduledJobInterface) ? $job->getScheduleTime() : null,
            'uuid'              => $job->getId()
        ];

        return $this->driver->addJob($info);      
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
     * @return boolean
     */
    public function executeJob($job)
    {
        if (\is_string($job) == true || \is_numeric($job) == true) {
            $job = $this->create($job);
        }

        try {
            $job->execute();
            $this->driver->updateExecutionStatus($job);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }   
}
