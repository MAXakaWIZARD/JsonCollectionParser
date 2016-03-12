<?php
namespace JsonCollectionParser;

class Listener implements \JsonStreamingParser\Listener
{
    /**
     * @var array
     */
    protected $stack;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var array
     */
    protected $keys;

    /**
     * @var integer
     */
    protected $level;

    /**
     * @var integer
     */
    protected $objectLevel;

    /**
     * @var array
     */
    protected $objectKeys;

    /**
     * @var callable
     */
    protected $callback;

    /**
     * @param callable $callback the function called when a json object has been fully parsed
     */
    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    public function start_document()
    {
        $this->stack = array();
        $this->key = null;
        $this->keys = array();
        $this->objectLevel = 0;
        $this->level = 0;
        $this->objectKeys = array();
    }

    public function end_document()
    {
        $this->stack = array();
        $this->keys = array();
    }

    public function start_object()
    {
        $this->objectLevel++;

        $this->start_array_common();
    }

    public function end_object()
    {
        $this->end_array_common();

        $this->objectLevel--;
        if ($this->objectLevel === 0) {
            $obj = $this->stack[0][0];
            array_shift($this->stack[0]);
            call_user_func($this->callback, $obj);
        }
    }

    public function start_array()
    {
        $this->start_array_common();
    }

    public function start_array_common()
    {
        $this->level++;
        $this->objectKeys[$this->level] = ($this->key) ? $this->key : null;
        $this->key = null;
        array_push($this->stack, array());
    }

    public function end_array()
    {
        $this->end_array_common();
    }

    public function end_array_common()
    {
        $obj = array_pop($this->stack);
        if (!empty($this->stack)) {
            $parentObj = array_pop($this->stack);
            if ($this->objectKeys[$this->level]) {
                $parentObj[$this->objectKeys[$this->level]] = $obj;
            } else {
                array_push($parentObj, $obj);
            }
            array_push($this->stack, $parentObj);
        }

        $this->level--;
    }

    /**
     * @param $key
     */
    public function key($key)
    {
        $this->key = $key;
    }

    /**
     * @param $value
     */
    public function value($value)
    {
        $obj = array_pop($this->stack);
        if ($this->key) {
            $obj[$this->key] = $value;
            $this->key = null;
        } else {
            array_push($obj, $value);
        }
        array_push($this->stack, $obj);
    }

    public function whitespace($whitespace)
    {
    }
}
