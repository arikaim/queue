<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\Queue\Traits;

/**
 * Job log 
*/
trait JobLog
{  
    /**
     * Get log message
     *
     * @return string
     */
    public function getLogMessage()
    {
        return $this->logMessage ?? 'Job executed.';
    }

    /**
     * Set log messge
     *
     * @param string $message
     * @return void
     */
    public function setLogMessage($message)
    {
        $this->logMessage = $message;
    }

    /**
     * Get log context
     *
     * @return array
     */
    public function getLogContext()
    {
        $output = $this->output ?? [];
        $context = [];
        foreach($output as $key => $value) {
            $context[$key] = $value['value'];
        }

        return $context;
    }

}
