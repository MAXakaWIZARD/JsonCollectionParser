<?php

namespace JsonCollectionParser;

use JsonStreamingParser\Listener\ListenerInterface;

class Listener implements ListenerInterface
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
     * @var bool
     */
    protected $assoc;

    /**
     * @param callback|callable $callback callback for parsed collection item
     * @param bool $assoc When true, returned objects will be converted into associative arrays
     */
    public function __construct($callback, $assoc = true)
    {
        $this->callback = $callback;
        $this->assoc = $assoc;
    }

    public function startDocument(): void
    {
        $this->stack = [];
        $this->key = null;
        $this->objectLevel = 0;
        $this->level = 0;
        $this->objectKeys = [];
    }

    public function endDocument(): void
    {
        $this->stack = [];
    }

    public function startObject(): void
    {
        $this->objectLevel++;

        $this->startCommon();
    }

    public function endObject(): void
    {
        $this->endCommon();

        $this->objectLevel--;
        if ($this->objectLevel === 0) {
            $obj = array_pop($this->stack);
            $obj = reset($obj);

            call_user_func($this->callback, $obj);
        }
    }

    public function startArray(): void
    {
        $this->startCommon();
    }

    public function startCommon(): void
    {
        $this->level++;
        $this->objectKeys[$this->level] = ($this->key) ? $this->key : null;
        $this->key = null;

        array_push($this->stack, []);
    }

    public function endArray(): void
    {
        $this->endCommon(false);
    }

    public function endCommon($isObject = true): void
    {
        $obj = array_pop($this->stack);

        if ($isObject && !$this->assoc) {
            $obj = (object)$obj;
        }

        if (!empty($this->stack)) {
            $parentObj = array_pop($this->stack);

            if ($this->objectKeys[$this->level]) {
                $objectKey = $this->objectKeys[$this->level];
                $parentObj[$objectKey] = $obj;
                unset($this->objectKeys[$this->level]);
            } else {
                $parentObj[0] = $obj;
            }
        } else {
            $parentObj[0] = $obj;
        }

        array_push($this->stack, $parentObj);

        $this->level--;
    }

    public function key(string $key): void
    {
        $this->key = $key;
    }

    /**
     * @param mixed $value
     */
    public function value($value): void
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

    public function whitespace(string $whitespace): void
    {
    }
}
