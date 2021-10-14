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

use Arikaim\Core\Queue\Jobs\Job;
use Arikaim\Core\Utils\DateTime;
use Arikaim\Core\Interfaces\Job\ScheduledJobInterface;
use Arikaim\Core\Interfaces\Job\JobInterface;

/**
 * Base class for all scheduled jobs
 */
abstract class ScheduledJob extends Job implements ScheduledJobInterface, JobInterface
{
    /**
     * Scheduled date time (timestamp)
     *
     * @var integer
     */
    protected $scheduleTime = 0;
 
    /**
     * Constructor
     *  
     * @param string?null $extension
     * @param string|null $name
     * @param array $params
     */
    public function __construct(?string $extension, ?string $name = null, array $params = [])
    {
        parent::__construct($extension,$name,$params);

        $this->scheduleTime = 0;
    }

    /**
     * Convert to array
     *
     * @return array
    */
    public function toArray(): array
    {
        $result = parent::toArray();
        $result['schedule_time'] = $this->getScheduleTime();
        
        return $result;
    }

    /**
     * ScheduledJobInterface implementation
     *
     * @return integer
     */
    public function getScheduleTime(): int
    {
        return $this->scheduleTime;
    }

    /**
     * Set scheduled time (timestamp)
     *
     * @param integer $timestamp
     * @return ScheduledJob
     */
    public function setScheduleTime(int $timestamp)
    {
        $this->scheduleTime = $timestamp;

        return $this;
    }

    /**
     * Set scheduled time
     *
     * @param string $date
     * @return ScheduledJob
     */
    public function runAt(string $date)
    {
        return $this->setScheduleTime(DateTime::toTimestamp($date));
    }

    /**
     * Return true if job is due
     *
     * @return boolean
     */
    public function isDue(): bool
    {
        if (empty($this->getScheduleTime()) == true) {
            return false;
        }

        return ($this->scheduleTime <= DateTime::getCurrentTimestamp());
    }
}
