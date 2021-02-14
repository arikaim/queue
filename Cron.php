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

use Arikaim\Core\System\Process;
use Arikaim\Core\Collection\Arrays;

/**
 * Cron jobs 
 */
class Cron
{
    /**
     * Cron command 
     *
     * @var string
     */
    private static $command = 'cli scheduler >> /dev/null 2>&1';

    /**
     * Get cron command
     * 
     * @param string $minutes
     * @return string
     */
    public static function getCronCommand(string $minutes = '5'): string
    {
        $php = (Process::findPhp() === false) ? 'php' : Process::findPhp();
        
        return "*/$minutes * * * * " . $php . ' ' . ROOT_PATH . BASE_PATH . '/'. Self::$command;      
    }
    
    /**
     * Retrun true if command is crontab command
     *
     * @param string $command
     * @return boolean
     */
    public static function isCronCommand(string $command): bool
    {
        $len = \strlen(Self::$command);

        return (substr($command,-$len) == Self::$command);
    }

    /**
     * Reinstall cron entry for scheduler
     *
     * @return mixed
     */
    public function reInstall()
    {    
        $this->unInstall();

        return $this->install();
    }

    /**
     * Add cron entry for scheduler
     *
     * @return bool
     */
    public function install(): bool
    {    
        $this->addJob(Self::getCronCommand());

        return $this->hasJob(Self::getCronCommand());
    }

    /**
     * Remove cron entry for scheduler
     *
     * @return mixed
     */
    public function unInstall()
    {
        $jobs = $this->getJobs();
        foreach ($jobs as $command) {
            if (Self::isCronCommand($command) == true) {
                $this->removeJob($command);  
            }
        }

        return !$this->isInstalled();
    }

    /**
     * Return true if crontab entry is exists
     *
     * @return boolean
     */
    public function isInstalled(): bool
    {
        $jobs = $this->getJobs();
        foreach ($jobs as $command) {
            if (Self::isCronCommand($command) == true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return true if crontab have jobs
     *
     * @param array $jobs
     * @return boolean
     */
    public function hasJobs($jobs): bool
    {
        $msg = 'no crontab for';
        
        return (empty($jobs) == true || \preg_match("/{$msg}/i", $jobs[0]) == true) ? false : true;
    }
    
    /**
     * Get crontab jobs
     *
     * @return array
     */
    public function getJobs(): array 
    {
        $output = Process::run('crontab -l');

        $output = (empty($output) == true) ? [] : $output;
        $jobs = Arrays::toArray($output);
    
        return ($this->hasJobs($jobs) == true) ? $jobs : [];
    }

    /**
     * Return true if crontab have job
     *
     * @param string $command
     * @return boolean
     */
    public function hasJob(string $command): bool
    {
        $commands = $this->getJobs();  

        return \in_array($command,$commands); 
    }   

    /**
     * Add cron tab job
     *
     * @param string $command
     * @return void
     */
    public function addJob(string $command)
    {
        if ($this->hasJob($command) == true) {
            return true;
        }
    
        $jobs = $this->getJobs();
        \array_push($jobs,$command);

        return $this->addJobs($jobs);
    }

    /**
     * Add cron tab jobs
     *
     * @param array $commands
     * @return mixed
     */
    public function addJobs(array $commands) 
    {
        foreach ($commands as $key => $command) {
            if (empty($command) == true) {
                unset($commands[$key]);
            }
        }
        $text = \trim(Arrays::toString($commands));
        if (empty($text) == false) {
            return Process::run('echo "'. $text .'" | crontab -');
        } 

        return $this->removeAll();       
    }

    /**
     * Remove all job from crontab
     *
     * @return mixed
     */
    public function removeAll()
    {
        return Process::run('crontab -r');
    }

    /**
     * Delete crontab job
     *
     * @param string $command
     * @return bool
     */
    public function removeJob($command): bool 
    {
        if ($this->hasJob($command) == true) {
            $jobs = $this->getJobs();
            unset($jobs[\array_search($command,$jobs)]);
            $this->addJobs($jobs);

            return true;
        }
        
        return true;
    }

    /**
     * get cron details.
     *
     * @return array
     */
    public function getServiceDetails(): array
    {
        return [
            'name'       => 'Cron',
            'installed'  => $this->isInstalled(),
            'jobs'       => $this->getJobs(),
            'user'       => Process::getCurrentUser()['name']
        ];
    }
}
