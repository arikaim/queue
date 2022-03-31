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

use Arikaim\Core\Queue\Jobs\RecurringJob;
use Arikaim\Core\Interfaces\Job\RecurringJobInterface;
use Arikaim\Core\Interfaces\Job\JobInterface;

/**
 * Cron job
 */
class CronJob extends RecurringJob implements RecurringJobInterface, JobInterface
{
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
        
        $this->interval = '* * * * *';
    }

    /**
     * Job code
     *
     * @return void|mixed
     */
    public function execute()
    {
    }

    /**
     * Run every minute
     *
     * @param string|null $minutes
     * @return CronJob
     */
    public function runEveryMinute(?string $minutes = null)
    {
        $this->interval = (empty($minutes) == true) ? '* * * * *' : '*/' . $minutes . ' * * * *';
        return $this;
    }

    /**
     * Run every hour
     *
     * @return CronJob
     */
    public function runEveryHour()
    {
        $this->interval = '0 * * * *';
        return $this;
    }

    /**
     * Run every day
     *
     * @param string|null $time
     * @return CronJob
     */
    public function runEveryDay(?string $time = null)
    {
        if (empty($time) == false) {
            $tokens = \explode(':',$time);
            return $this->resolve(2,(int)$tokens[0])->resolve(1,\count($tokens) == 2 ? (int)$tokens[1] : '0');
        }
        $this->interval = '0 0 * * *';

        return $this;
    }

    /**
     * Run every week
     *
     * @return CronJob
     */
    public function runEveryWeek()
    {
        $this->interval = '0 0 * * 0';

        return $this;
    }

    /**
     * Run every month
     *
     * @return CronJob
     */
    public function runEveryMonth()
    {
        $this->interval = '0 0 1 * *';

        return $this;
    }

    /**
     * Run every year
     *
     * @return CronJob
     */
    public function runEveryYear()
    {
        $this->interval = '0 0 1 1 *';

        return $this;
    }

    /**
     * Resolve corn expression helper
     *
     * @param integer $position
     * @param mixed $value
     * @return CronJob
     */
    protected function resolve(int $position, $value)
    {
        $tokens = \explode(' ',$this->interval);
        $tokens[$position - 1] = $value;

        $this->interval = \implode(' ',$tokens);

        return $this;
    }
}
