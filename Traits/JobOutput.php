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
 * Job result output
*/
trait JobOutput
{  
    /**
     * Add output item
     *
     * @param string $label
     * @param mixed $value
     * @param string|null $key
     * @return void
     */
    public function addOutput($label, $value, $key = null)
    {
        $item = [
            'label' => $label,
            'value' => $value
        ];
        if (empty($key) == false) {
            $this->output[$key] = $item;
        } else {
            $this->output[] = $item;
        }
       
    }

    /**
     * Get output
     *
     * @return array
     */
    public function getOutput()
    {
        return $this->output ?? [];
    }

    /**
     * Render output
     *
     * @return void
     */
    public function render()
    {
        echo "\n";
        foreach($this->output as $item) {
            echo $item['label'] . ' ' . $item['value'] . "\n";
        }
        echo "\n";
    }
}
