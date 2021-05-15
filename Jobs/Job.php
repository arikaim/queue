<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\Queue\Jobs;

use Arikaim\Core\Interfaces\Job\JobInterface;
use Arikaim\Core\Utils\Utils;

/**
 * Base class for all jobs
 */
abstract class Job implements JobInterface
{
    /**
     * Unique job id 
     *
     * @var string|integer|null
     */
    protected $id = null;

    /**
     * Job name
     *
     * @var string|null
     */
    protected $name = null;

    /**
     * Priority
     *
     * @var integer
     */
    protected $priority = 0;

    /**
     * Extension name
     *
     * @var string|null
     */
    protected $extension = null;
  
    /**
     * Job status
     *
     * @var int
     */
    protected $status = JobInterface::STATUS_CREATED;

    /**
     * Execution errors
     *
     * @var array
    */
    protected $errors = [];

    /**
     * Execution timestamp 
     *
     * @var int|null
     */
    protected $dateExecuted = null;

    /**
     * Queue name
     *
     * @var string|null
     */
    protected $queue = null;

    /**
     * Job params
     *
     * @var array
     */
    protected $params = [];

    /**
     * Job code
     *
     * @return mixed
     */
    abstract public function execute();

    /**
     * Constructor
     *
     * @param string|null $extension
     * @param string|null $name
     * @param array $params
     */
    public function __construct(?string $extension = null, ?string $name = null, array $params = [])
    {
        $this->setExtensionName($extension);
        $this->setName($name);
        $this->setPriority(0);
        $this->dateExecuted = null;
        $this->status = JobInterface::STATUS_CREATED;
        $this->errors = [];
        $this->params = $params;
        $this->id = null;

        $this->init();
    }

    /**
     * Set param
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function setParam(string $name, $value): void
    {
        $this->params[$name] = $value;
    } 

    /**
     * Get param value
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getParam(string $name, $default = null)
    {
        return $this->params[$name] ?? $default;
    } 

    /**
     * Init job
     *
     * @return void
     */
    public function init(): void
    {        
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id'             => $this->getId(),
            'name'           => $this->getName(),
            'priority'       => $this->getPriority(),
            'status'         => $this->getStatus(),
            'date_executed'  => $this->getDateExecuted(),          
            'extension_name' => $this->getExtensionName(),
            'errors'         => $this->getErrors(),
            'handler_class'  => \get_class(),
            'queue'          => $this->getQueue(),
        ];
    }

    /**
     * Get execution errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Add error
     *
     * @param string $errorMessage
     * @return void
     */
    public function addError(string $errorMessage): void
    {
        $this->errors[] = $errorMessage;
    }

    /**
     * Return true if job is executed successful
     *
     * @return boolean
     */
    public function hasSuccess(): bool
    {
        return (\count($this->errors) == 0);
    }

    /**
     * Get execution timestamp
     *   
     * @return int
    */
    public function getDateExecuted(): ?int
    {
        return $this->dateExecuted;
    }

    /**
     * Set execution date
     *   
     * @param int|null $time  timestamp
     * @return void
    */
    public function setDateExecuted(?int $time): void
    {
        $this->dateExecuted = $time;
    }

    /**
     * Get job status
     *   
     * @return int
    */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Set job status
     *
     * @param integer $status
     * @return void
     */
    public function setStatus(int $status): void
    {
        if ($status == JobInterface::STATUS_CREATED) {
            $this->errors = [];
        }
        $this->status = $status;
    }

    /**
     * Set
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value): void
    {
        $this->$name = $value;
    }

    /**
     * Set id
     *
     * @param string|null $id
     * @return void
     */
    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    /**
     * Get id
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Get extension name
     *
     * @return string|null
     */
    public function getExtensionName(): ?string 
    {
        return $this->extension;
    }

    /**
     * Get name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return (empty($this->name) == true) ? Utils::getBaseClassName($this) : $this->name;
    }

    /**
     * Get priority
     *
     * @return integer
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Set name
     *
     * @param string|null $name
     * @return void
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * Set priority
     *
     * @param integer $priority
     * @return void
     */
    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * Set extension name
     *
     * @param string|null $name
     * @return void
     */
    public function setExtensionName(?string $name): void
    {
        $this->extension = $name;
    }

    /**
     * Set executuion Queue (null for any)
     *
     * @param string|null $name
     * @return void
     */
    public function setQueue(?string $name): void
    {
        $this->queue = $name;
    }

    /**
     * Get queue
     *
     * @return string|null
     */
    public function getQueue(): ?string
    {
        return $this->queue;
    }
}
