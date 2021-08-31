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

use Cron\CronExpression;

use Arikaim\Core\Utils\DateTime;
use Arikaim\Core\Utils\TimeInterval;
use Arikaim\Core\Queue\Jobs\Job;
use Arikaim\Core\Interfaces\Job\JobInterface;
use Arikaim\Core\Interfaces\Job\RecurringJobInterface;

/**
 * Base class for all Recurring jobs
 */
abstract class RecurringJob extends Job implements JobInterface, RecurringJobInterface
{
    /**
     * Recuring interval
     *
     * @var string|null
     */
    protected $interval = null;
    
    /**
     * Constructor
     *
     * @param string|null $extension
     * @param string|null $name
     * @param array $params
     */
    public function __construct(?string $extension = null, ?string $name = null, array $params = [])
    {
        parent::__construct($extension,$name,$params);
    }

    /**
     * Convert to array
     *
     * @return array
    */
    public function toArray(): array
    {
        $result = parent::toArray();
        $result['recuring_interval'] = $this->getRecurringInterval();
        $result['next_run_date'] = $this->getDueDate();
        
        return $result;
    }

    /**
     * Return true if job is due
     *
     * @return boolean
    */
    public function isDue(): bool
    {
        return ($this->getDueDate() <= DateTime::getCurrentTimestamp());
    } 

    /**
     * Get next run date
     *
     * @param string $interval
     * @param int|null $dateLastExecution
     * @return integer|false
     */
    public static function getNextRunDate(string $interval, ?int $dateLastExecution = null)
    {
        $dateLastExecution = empty($dateLastExecution) ? DateTime::getCurrentTimestamp() : $dateLastExecution;       
        $dateTime = DateTime::create('@' . (string)$dateLastExecution);

        if (CronExpression::isValidExpression($interval) == true) {
            return CronExpression::factory($interval)->getNextRunDate($dateTime,0,false,DateTime::getTimeZoneName())->getTimestamp();
        }

        if (TimeInterval::isDurationInverval($interval) == true) {
            $interval = TimeInterval::create($interval);
        
            return $dateTime->add($interval)->getTimestamp();
        }

        return false;
    }
    
    /**
     * Get next run date time timestamp
     *
     * @return integer
     */
    public function getDueDate()
    {       
        return Self::getNextRunDate($this->interval,$this->getDateExecuted());
    }

    /**
     * RecurringJobInterface implementation function
     *
     * @return string|null
     */
    public function getRecurringInterval(): ?string
    {
        return $this->interval;
    }

    /**
     * Set recurring interval
     *
     * @param string $interval
     * @return void
     */
    public function setRecurringInterval(string $interval): void
    {
        $this->interval = $interval;
    }
}
