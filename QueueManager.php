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
use Arikaim\Core\Queue\Cron;

use Arikaim\Core\Interfaces\ConfigPropertiesInterface;
use Arikaim\Core\Interfaces\Job\QueueStorageInterface;
use Arikaim\Core\Interfaces\Job\JobInterface;
use Arikaim\Core\Interfaces\Job\RecurringJobInterface;
use Arikaim\Core\Interfaces\Job\ScheduledJobInterface;
use Arikaim\Core\Interfaces\Job\JobProgressInterface;
use Arikaim\Core\Interfaces\Job\SaveJobConfigInterface;
use Arikaim\Core\Interfaces\Job\JobLogInterface;
use Arikaim\Core\Interfaces\QueueInterface;
use Arikaim\Core\Interfaces\LoggerInterface;
use Arikaim\Core\Interfaces\WorkerManagerInterface;

use Closure;
use Exception;

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
     * Logger
     *
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param QueueStorageInterface $driver
     */
    public function __construct(QueueStorageInterface $driver, ?LoggerInterface $logger = null)
    {       
        $this->setDriver($driver);       
        $this->logger = $logger;    
    }

    /**
     * Create worker manager
     *
     * @param string|null $name
     * @param array|null $args
     * @return \Arikaim\Core\Interfaces\WorkerManagerInterface|null;
     */
    public function createWorkerManager(?string $name = null, ?array $args = null)
    {
        if (empty($name) == true || $name == 'cron') {
            return new Cron();
        }
        
        $manager = Factory::createInstance($name,$args);
        if (\is_null($manager) == true) {
            throw new Exception('Not valid queue worker class',1);
            return null;
        }
        if (($manager instanceof WorkerManagerInterface) == false) {
            throw new Exception('Not valid queue worker class',1);
            return null;
        }

        return $manager;
    }

    /**
     * Set queue storage driver
     *
     * @param QueueStorageInterface $driver
     * @return void
     */
    public function setDriver(QueueStorageInterface $driver): void
    {
        $this->driver = $driver;
    }

    /**
     * Get queue storage driver
     *
     * @return QueueStorageInterface
     */
    public function getStorageDriver(): QueueStorageInterface
    {
        return $this->driver;
    }

    /**
     * Return tru if job exist
     *
     * @param mixed $id
     * @return boolean
     */
    public function has($id): bool
    {
        return $this->driver->hasJob($id);
    }

    /**
     * Delete jobs
     *
     * @param array $filter
     * @return boolean
     */
    public function deleteJobs(array $filter = []): bool
    {
        return $this->driver->deleteJobs($filter);
    }

    /**
     * Create job obj
     *
     * @param string|int $name Job class or name
     * @param string|null $extension
     * @param array $params
     * @return JobInterface|null
     */
    public function create($name, ?string $extension = null, array $params = []): ?JobInterface
    {       
        $job = Factory::createJob($name,$extension,null,$params);
        if (empty($job) == true) {
            // load from db
            $jobInfo = $this->getJob($name);
            $job = $this->createJobFromArray($jobInfo);    
            if (empty($job) == true) {
                return null;
            }            
        }
        
        return $job;
    }

    /**
     * Run job
     *
     * @param string|int $name Job class or name
     * @param string|null $extension
     * @param array $params
     * @return JobInterface|false
    */
    public function execute($name, ?string $extension = null, array $params = [])
    {
        $job = $this->create($name,$extension,$params);
        if (\is_object($job) == true) {
            return $this->executeJob($job);
        }

        return false;
    }

    /**
     * Create job intence from array 
     *
     * @param array $data  
     * @return JobInterface|null
     */
    public function createJobFromArray(array $data): ?JobInterface
    {      
        $class = $data['handler_class'] ?? null;
        $extension = $data['extension_name'] ?? null;
        $job = Factory::createJob($class,$extension);       
        if ($job == null) {
            return null;
        }
       
        $job->setId($data['uuid'] ?? null);
        $job->setName($data['name'] ?? null);
        $job->setStatus($data['status'] ?? JobInterface::STATUS_CREATED);
        $job->setPriority($data['priority'] ?? 0);
        $job->setExtensionName($data['extension_name'] ?? null);
        $job->setDateExecuted($data['date_executed'] ?? null);
        $job->setQueue($data['queue'] ?? null);

        if ($job instanceof ScheduledJobInterface) {
            $job->setScheduleTime($data['schedule_time'] ?? 0);
        }
        if ($job instanceof RecurringJobInterface) {
            $job->setRecurringInterval($data['recuring_interval'] ?? '');
        }
     
        if ($job instanceof ConfigPropertiesInterface) {
            $config = $data['config'] ?? [];
            $job->setConfigProperties($config);
        }

        return $job;
    }

    /**
     * Save job config
     *
     * @param string|int $id
     * @param array $config
     * @return boolean
     */
    public function saveJobConfig($id, array $config): bool
    {
        return $this->driver->saveJobConfig($id,$config);    
    }

    /**
     * Find job by name, id or uuid
     *
     * @param string|integer $id Job id, uiid or name
     * @return array|null
     */
    public function getJob($id): ?array
    {
        $job = $this->driver->getJob($id);  
     
        if (empty($job) == false && \is_array($job['config']) == false) {
            $job['config'] = (empty($job['config']) == false) ? \json_decode($job['config'],true) : null;
        }
        
        return $job;
    }

    /**
     * Get recurring jobs
     *
     * @param string|null $extension
     * @return array|null
     */
    public function getRecuringJobs(?string $extension = null): ?array
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
     * @return array|null
     */
    public function getJobs(array $filter = []): ?array
    {  
        return $this->driver->getJobs($filter);   
    }

    /**
     * Get all jobs due
     * 
     * @return array|null
     */
    public function getJobsDue(): ?array
    {
        return $this->driver->getJobsDue();
    }

    /**
     * Push job to queue
     *
     * @param string|int $name
     * @param string|null $extension
     * @param array $params
     * @return bool
     */
    public function push($name, ?string $extension = null, array $params = []): bool
    {
        $job = $this->create($name,$extension,$params);
        if (\is_object($job) == false) {
            return false;
        }

        return $this->addJob($job,$extension);
    }

    /**
     * Add job to queue
     *
     * @param JobInterface $job
     * @param string|null $extension
     * @param bool $disabled
     * @param string|null $recuringInterval
     * @param int|null $scheduleTime
     * @param array|null $config
     * @return bool
     */
    public function addJob(
        JobInterface $job, 
        ?string $extension = null, 
        bool $disabled = false,
        ?string $recuringInterval = null,
        ?int $scheduleTime = null,
        ?array $config = null
    ): bool
    {             
        if ($job instanceof ConfigPropertiesInterface && \is_array($config) == false) {
            $config = $job->createConfigProperties();
        }
       
        $info = [
            'priority'          => $job->getPriority(),
            'name'              => $job->getName(),
            'handler_class'     => \get_class($job),         
            'extension_name'    => $extension ?? $job->getExtensionName(),
            'status'            => ($disabled == false) ? JobInterface::STATUS_PENDING : JobInterface::STATUS_SUSPENDED,
            'recuring_interval' => ($job instanceof RecurringJobInterface) ? $job->getRecurringInterval() : $recuringInterval,
            'schedule_time'     => ($job instanceof ScheduledJobInterface) ? $job->getScheduleTime() : $scheduleTime,
            'config'            => (\is_array($config) == true) ? \json_encode($config) : null,
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
    public function deleteJob($id): bool
    {
        return $this->driver->deleteJob($id);
    }

    /**
     * Delete all jobs
     *    
     * @return boolean
     */
    public function clear(): bool
    {
        return $this->driver->deleteJobs();
    }

    /**
     * Get next job
     *
     * @return JobInterface|null
     */
    public function getNext(): ?JobInterface
    {
        $jobData = $this->driver->getNext();

        return (empty($jobData) == true) ? null : $this->createJobFromArray($jobData);                 
    }

    /**
     * Run job
     *
     * @param JobInterface|string|int $name
     * @param Closure|null $onJobProgress
     * @param Closure|null $onJobProgressError
     * @return JobInterface|null
     */
    public function run($name, ?Closure $onJobProgress = null, ?Closure $onJobProgressError = null): ?JobInterface
    {
        if (\is_object($name) == false) {
            $job = $this->create($name);
        }
        if (empty($job) == true) {
            return null;
        }

        return $this->executeJob($job,$onJobProgress,$onJobProgressError);
    }

    /**
     * Execute job
     *
     * @param JobInterface $job
     * @param Closure|null $onJobProgress
     * @param Closure|null $onJobProgressError
     * @return JobInterface|null
    */
    public function executeJob(JobInterface $job, ?Closure $onJobProgress = null, ?Closure $onJobProgressError = null): ?JobInterface
    {
        if ($job instanceof JobProgressInterface) {
            $job->onJobProgress($onJobProgress);
            $job->onJobProgressError($onJobProgressError);
        }
        if ($job->getStatus() == JobInterface::STATUS_SUSPENDED) {
            $job->addError('Job is suspended.');
            return $job;
        }

        try {
            $job->execute();
            $job->setStatus(JobInterface::STATUS_EXECUTED);          
            $this->driver->updateExecutionStatus($job);
            
            if ($job instanceof SaveJobConfigInterface) {
                // save job config properties after executing job
                $config = $job->getConfigProperties()->toArray();
                $id = (empty($job->getId()) == true) ? $job->getName() : $job->getId();              
                $this->saveJobConfig($id,$config);
            }

            if (($job instanceof JobLogInterface) && (empty($this->logger) == false)) {
                $this->logger->info($job->getLogMessage(),['job-name' => $job->getName() ]);
            }
        } catch (\Exception $e) {
            $job->addError($e->getMessage());     
            if (($job instanceof JobLogInterface) && (empty($this->logger) == false)) {              
                $this->logger->error($e->getMessage(),$job->toArray());                
            }     
        }
      
        return $job;
    }   
}
