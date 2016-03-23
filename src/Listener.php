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
     * @var int
     */
    protected $level;

    /**
     * @var int
     */
    protected $objectLevel;

    /**
     * @var array
     */
    protected $objectKeys;

    /**
     * @var callback|callable
     */
    protected $callback;

    /**
     * @param callback|callable $callback callback for parsed collection item
     */
    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    public function startDocument()
    {
        $this->stack = [];
        $this->key = null;
        $this->keys = [];
        $this->objectLevel = 0;
        $this->level = 0;
        $this->objectKeys = [];
    }

    public function endDocument()
    {
        $this->stack = [];
        $this->keys = [];
    }

    public function startObject()
    {
        $this->objectLevel++;

        $this->startArrayCommon();
    }

    public function endObject()
    {
        $this->endArrayCommon();

        $this->objectLevel--;
        if ($this->objectLevel === 0) {
            $obj = $this->stack[0][0];
            array_shift($this->stack[0]);

            call_user_func($this->callback, $obj);
        }
    }

    public function startArray()
    {
        $this->startArrayCommon();
    }

    public function startArrayCommon()
    {
        $this->level++;
        $this->objectKeys[$this->level] = ($this->key) ? $this->key : null;
        $this->key = null;

        array_push($this->stack, []);
    }

    public function endArray()
    {
        $this->endArrayCommon();
    }

    public function endArrayCommon()
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
     * @param string $key
     */
    public function key($key)
    {
        $this->key = $key;
    }

    /**
     * @param mixed $value
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

    /**
     * @param string $whitespace
     */
    public function whitespace($whitespace)
    {
    }
}
