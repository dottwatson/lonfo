<?php 
namespace Lonfo;


class Value{
    
    /**
     * The current item
     *
     * @var mixed
     */
    protected $item;
    /**
     * The array node parent
     *
     * @var Walker|null
     */
    protected $parent = null;

    /**
     * Undocumented variable
     *
     * @var string
     */
    protected $type;

    /**
     * Current item key
     *
     * @var string
     */
    protected $key;
    
    /**
     * Initialize
     *
     * @param mixed $item
     * @param Walker|null $parent
     * @param string $key
     */
    public function __construct(&$item,$parent = null,string $key = null){
        $this->item     = &$item;
        $this->key      = $key;
        $this->parent   = $parent;
        $this->type     = gettype($this->item);
    }

    /**
     * Returns current item value
     *
     * @return mixed
     */
    public function value(){
        return $this->item;
    }

    /**
     * Returns current item value
     *
     * @return mixed
     */
    public function key(){
        return $this->key;
    }

    /**
     * Returns the parent object
     *
     * @return Walker|null
     */
    public function parent(){
        return $this->parent;
    }

    /**
     * Returns the value type
     *
     * @return void
     */
    public function type(){
        return $this->type;
    }

    /**
     * Returns the data path, starting from 
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
     * Tells if is a valid array (a traversable Walker) or a value
     *
     * @return boolean
     */
    public function iterable(){
        return false;
    }

}
?>