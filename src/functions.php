<?php 
use Lonfo\Walker;

if(!function_exists('lonfo')){
    /**
     * Create a lonfo array
     *
     * @param array $target
     * @return Walker
     */
    function lonfo(array $target = []){
        return (new Walker($target));
    }
}



?>