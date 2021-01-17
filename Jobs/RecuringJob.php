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
use Arikaim\Core\Interfaces\Job\RecuringJobInterface;

/**
 * Base class for all Recurring jobs
 */
abstract class RecuringJob extends Job implements JobInterface, RecuringJobInterface
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
     */
    public function __construct(?string $extension = null, ?string $name = null)
    {
        parent::__construct($extension,$name);

        $this->interval = null;
    }

    /**
     * Convert to array
     *
     * @return array
    */
    public function toArray(): array
    {
        $result = parent::toArray();
        $result['recuring_interval'] = $this->getRecuringInterval();
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
        $dateExecuted = $this->getDateExecuted();
        if (empty($dateExecuted) == true) {         
            return true;
        }

        return ($this->getDueDate() <= DateTime::getTimestamp());
    } 

    /**
     * Get next run date
     *
     * @param string $interval
     * @param int|null $dateLastExecution
     * @return integer|false
     */
    public static function getNextRunDate(string $interval,?int $dateLastExecution = null)
    {
        if (empty($dateLastExecution) == true) {
            $dateTime = DateTime::getDateTime();
        } else {
            $dateTime = DateTime::create('@' . (string)$dateLastExecution);
        }

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
     * RecuringJobInterface implementation function
     *
     * @return string
     */
    public function getRecuringInterval(): string
    {
        return $this->interval;
    }

    /**
     * Set recuring interval
     *
     * @param string $interval
     * @return void
     */
    public function setRecuringInterval(string $interval): void
    {
        $this->interval = $interval;
    }
}
