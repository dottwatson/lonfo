<?php 
namespace Lonfo;

use Lonfo\Value;

class Walker{
    /**
     * Internal data
     *
     * @var array
     */
    protected $data         = [];
    
    /**
     * The parent array node
     *
     * @var Walker
     */
    protected $parent       = null;

    /**
     * The keys map
     *
     * @var array
     */
    protected $keys         = [];

    /**
     * The last Key Index
     *
     * @var int|null
     */
    protected $lastKeyIndex;

    /**
     * The current key index
     *
     * @var int|null
     */
    protected $currentKeyIndex;


    /**
     * The current key name
     *
     * @var string
     */
    protected $currentKey;


    /**
     * Initialize
     *
     * @param array $data
     * @param Walker|null $parent
     */
    public function __construct(array &$data = [],Walker &$parent = null,$currentKey = null){
        $this->data         = &$data;
        $this->currentKey   = $currentKey;
        $this->parent       = &$parent;
        $this->keys         = array_keys($this->data);

        if($this->keys){
            $this->lastKeyIndex = count($this->keys)-1;
        }
    }

    /**
     * Reset data and indexes where items are added removed
     *
     * @param array $data
     * @return void
     */
    private function reload(&$data = []){
        $this->keys             = [];
        $this->lastKeyIndex     = null;
        $this->currentKeyIndex  = null;
        $this->currentKey       = null;
        $this->__construct($data,$this->parent);
    }


    /**
     * Returns an item from array by its key
     *
     * @param string|int|float $key
     * @return Walker|Value|null
     */
    public function get($key){
        $key        = (string)$key;
        $keyInfo    = $this->parseKey($key);
        $fn         = strtolower($keyInfo['fn']);
        $fnValue    = $keyInfo['value'];
        $currentKey = $keyInfo['key'];

        if($this->has($currentKey)){
            if($fn && in_array(strtolower($fn),['parent','ntChild','first','last'])){
                $item = $this->get($currentKey);
                return ($item && $item->iterable() )
                    ?call_user_func([$item,$fn],$fnValue)
                    :null;
            }

            if(
                is_object($this->data[$currentKey]) && 
                (is_a($this->data[$currentKey],static::class) || is_a($this->data[$currentKey],Value::class))
                ){
                
                return $this->data[$currentKey];
            }
            
            $clsName = (is_iterable($this->data[$currentKey]))
                ?static::class
                :Value::class;
            
            return (new $clsName($this->data[$currentKey],$this,$currentKey));
        }
    }

    /**
     * parse a requested array key and evaluitate if is comprensive of pseudo selectors
     *
     * @param string $key
     * @return array
     */
    protected function parseKey($key){
        preg_match('#^(?<key>.+)(::(?P<pseudo_rule>(?P<fn>.+)\((?P<value>.*)\)))?$#U',$key,$info);
        return [
            'key'   =>$info['key'],
            'fn'    =>(isset($info['fn']))?$info['fn']:false,
            'value' =>(isset($info['value']))?$info['value']:null,
        ];
        
    }


    /**
     * Returns the full data path , included current key 
     *
     * @param string $separator
     * @return string
     */
    public function xpath(string $separator = '/'){
        $path    = ($this->key() !== null)?[$this->key()]:[];
        $current = $this;
        while($parent = $current->parent()){
            if($parent->key() !== null){
                array_unshift($path,$parent->key());
            }
            $current = $parent;
        }

        return implode($separator,$path);
    }

    /**
     * Built full paths for collection result
     *
     * @param array $pathBits
     * @param string $separator
     * @return array
     */
    private function buildFullPaths(array $pathBits,string $separator = '/'){
        $arrayPaths = [];
        $node       = $this;
        $k          = 0;
        $cntBits    = count($pathBits);

        while($bit = array_shift($pathBits)){
            $arrayPaths[$k] = [];
            if($bit == '*'){
                if(!$node || !$node->iterable()){
                }
                else{
                    $arrayPaths[$k] = array_merge($arrayPaths[$k],$node->keys());
                    $tmp = [];
                    foreach($node->items() as $item){
                        if($item->iterable()){
                            $tmp = array_merge($tmp,$item->items());
                        }
                        else{
                            $tmp = array_merge($tmp,[$item]);
                        }
                    }
                    $node = lonfo($tmp);
                }
            }
            else{
                $arrayPaths[$k] = [$bit];
                $node           = $node->get($bit);
            }
            $k++;
        }
    
        $paths  = [];
        foreach($arrayPaths as $k=>$arrayPath){
            foreach($arrayPath as $pathItem){
                if($k == 0){
                    $paths[]=$pathItem;
                }
                else{
                    foreach($paths as $path){
                        $paths[] = $path.=$separator.$pathItem;
                    }
                }
            }
        }
    
        foreach($paths as $i=>$path){
            $cntPathPcs = explode($separator,$path);
            $cntPath    = count($cntPathPcs);

            if($cntPath != $cntBits || $this->xfind($path,$separator) === null){
                unset($paths[$i]);
            }
        }

        return $paths;
    }


    /**
     * Returns an item or null, based on its xpath relative to current element where search starts
     *
     * @param string $path
     * @param string $separator
     * @return Walker
     */
    public function xfind(string $path,string $separator = '/'){
        if(!$path){ 
            return null;            
        }
        elseif($path == '*'){
            return $this->iterable()?$this:null;
        }

        $bits = explode($separator,$path);

        if(in_array('*',$bits)){
            $paths = $this->buildFullPaths($bits,$separator);
            $results = [];

            foreach($paths as $path){
                $value = $this->xfind($path,$separator);

                if(!is_null($value)){
                    $results[] =$value;
                }
            }

            return lonfo($results);
        }
        else{
            $item = $this;
            while(($bit = array_shift($bits)) !== null){
                $currentItem = $item->get($bit);
    
                if(count($bits) == 0){
                    return $currentItem;
                }
                elseif( $currentItem === null || !$currentItem->iterable() ){
                    return null;
                }
    
                $item = $currentItem;
            }
            
            return null;
        }
    }

    /**
     * Merge an array or a Waker object into current item
     *
     * @param array|Walker
     * @return self
     */
    public function merge($data){
        if(is_object($data) && is_a($data,static::class)){
            $data = $data->value();
        }
        elseif(!is_array($data)){
            $data = [$data];
        }

        $finalData = array_merge_recursive($this->data,$data);
        $this->reload($finalData);
        return $this;
    }

    /**
     * Get indexed items in current array
     *
     * @return array
     */
    public function items(){
        $results = [];
        foreach($this->keys as $key){
            $results[$key] = $this->get($key);
        }

        return $results;
    }


    /**
     * Returns the array items count
     *
     * @return int
     */
    public function count(){
        return count($this->data);
    }

    /**
     * Returns the array keys
     *
     * @return array
     */
    public function keys(){
        return $this->keys;
    }

    /**
     * Returns the primitive item value
     *
     * @return mixed
     */
    public function value(){
        return $this->data;
    }

    /**
     * Returns the primitive value, deeply converted
     *
     * @return void
     */
    public function primitiveValue(){
        if($this->iterable()){
            $output     = $this->data;
            $clsName    = static::class;
            $clsValue   = Value::class;
            array_walk_recursive($output,function(&$item) use ($clsName,$clsValue){
                if(is_object($item) && get_class($item) == $clsName){
                    $item = $item->primitiveValue();
                }
                elseif(is_object($item) && get_class($item) == $clsValue){
                    $item = $item->primitiveValue();
                }
            });
        
            return $output;
        }

        return $this->data;
    }

    /**
     * Returns the parent node in the array
     *
     * @return Walker|null
     */
    public function parent(){
        return $this->parent;
    }

    /**
     * Check if key exists in array
     *
     * @param string $key
     * @return boolean
     */
    public function has(string $key){
        return in_array($key,$this->keys,false);
    }

    /**
     * Move internal pointer to the previouse item and returns it if exists
     *
     * @return Walker|null
     */
    public function prev(){
        if(!$this->keys || (int)$this->currentKeyIndex === 0){
            return null;
        }

        $this->currentKeyIndex -=1;
        $key = $this->keys[$this->currentKeyIndex];

        return $this->get($key);
    }

    /**
     * Move internal pointer to the previouse item and returns it if exists
     *
     * @return Walker|null
     */
    public function next(){
        if(!$this->keys){
            return null;
        }
        
        if(is_null($this->currentKeyIndex)){
            $this->currentKeyIndex = 0;
            return $this->get($this->keys[0]);
        }
        
        if($this->currentKeyIndex === $this->lastKeyIndex){
            return null;
        }

        $this->currentKeyIndex +=1;
        $key = $this->keys[$this->currentKeyIndex];

        return $this->get($key);
    }

    /**
     * Returns the current pointer item key
     *
     * @return string|int|float|null
     */
    public function key(){
        return $this->currentKey;
    }

    /**
     * Reset current internal pointer
     *
     * @return self
     */
    public function rewind(){
        $this->currentKeyIndex = null;

        return $this;
    }
    

    /**
     * Returns the first item in the array if exists
     *
     * @return Walker|Value|null
     */
    public function first(){
        if(!$this->keys){
            return null;
        }

        $key = $this->keys[0];

        return $this->get($key);
    }


    /**
     * Returns the last item in the array if exists
     *
     * @return Walker|Value|null
     */
    public function last(){
        if(!$this->keys){
            return null;
        }

        $key = $this->keys[$this->lastKeyIndex];

        return $this->get($key);
    }


    /**
     * Remove the first item in the array if exists and returns it
     *
     * @return mixed
     */
    public function shift(){
        if(!$this->keys){
            return null;
        }
    
        $item = $this->first()->value();       

        array_shift($this->data);

        $this->reload($this->data);

        return $item;
    }



    /**
     * Remove the last item in the array if exists and returns it
     *
     * @return mixed
     */
    public function pop(){
        if(!$this->keys){
            return null;
        }
    
        $item = $this->last()->value();       

        array_pop($this->data);

        $this->reload($this->data);

        return $item;
    }


    /**
     * Set a pair key => value item in teh array. If exists, will be overwritten
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function set($key,$value = null,string $separator = '/'){
        $bits = explode($separator,(string)$key);

        $currentNode = &$this->data;
        while($bit = array_shift($bits)){
            if(!$bits){
                $currentNode[$bit] = $value;
            }
            else{
                $currentNode[$bit] = [];
            }
            $currentNode = &$currentNode[$bit];
        }

        $this->reload($this->data);

        return $this;
    }

    /**
     * Appends all the items, passed ss arguments, to the array
     *
     * @return self
     */
    public function append(){
        $args = func_get_args();
        foreach($args as $item){
            $this->data[] = $item;
        }

        $this->reload($this->data);

        return $this;
    }


    /**
     * Prepends all items, passed as arguments, to the array
     *
     * @return self
     */
    public function prepend(){
        $args = func_get_args();
        array_unshift($this->data,...$args);

        $this->reload($this->data);

        return $this;
    }

    /**
     * Retrieve a child from its numeric index or a closure. 
     * The childs counters starts from 1
     * If $keyNumber is a Closure, the other extra parameters will be sent to the closure 
     *
     * @param int|Closure $keyNumber
     * @return Walker|Value|null
     */
    public function nthChild($keyNumber){
        $args           = func_get_args();
        $keyNumber      = $this->realKeyClosure(...$args);
        $keyNumber -=   1;      

        return(isset($this->keys[$keyNumber]))
            ?$this->get($this->keys[$keyNumber])
            :null;
    }

    /**
     * Check if variable 
     *
     * @param int|Closure $keyNumber
     * @return string
     */
    protected function realKeyClosure($key){
        $args   = func_get_args();
        if(is_object($key) && is_a($key,\Closure::class)){
            $args = func_get_args();
            $closure = array_shift($args);
            
            $key = $closure(...$args);
        }

        return $key;
    }

    /**
     * Tell if is a valid array (a traversable Walker)
     *
     * @return boolean
     */
    public function iterable(){
        return true;
    }
}
