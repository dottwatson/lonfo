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
        $this->__construct($this->data,$this->parent);
    }


    /**
     * Returns an item from array by its key
     *
     * @param string|int|float $key
     * @return Walker|Value
     */
    public function get($key){
        $key = (string)$key;
        if($this->has($key)){
            $clsName = (is_iterable($this->data[$key]))
                ?static::class
                :Value::class;
            
            return (new $clsName($this->data[$key],$this,$key));
        }
    }

    /**
     * Returns the full data path , included current key 
     *
     * @param string $separator
     * @return void
     */
    public function xpath(string $separator = '/'){
        $path    = ($this->key() !== null)?[$this->key()]:[];
        $current = $this;
        var_dump($current->value());
        while($parent = $current->parent()){
            if($parent->key() !== null){
                array_unshift($path,$parent->key());
            }
            $current = $parent;
        }

        return implode($separator,$path);
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


    public function value(){
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
        return in_array($key,$this->keys);
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
    
        $item = $this->first()->all();       

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
    
        $item = $this->last()->all();       

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
    public function set($key,$value = null){
        $this->data[(string)$key] = $value;

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
