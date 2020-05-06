<?php

declare(strict_types=1);

namespace JsonCollectionParser;

class ChunkListener extends Listener
{
    /**
     * The size of the chunk.
     *
     * @var int
     */
    protected $size;

    /**
     * The callback processing the chunk.
     *
     * @var callable
     */
    protected $callback;

    /**
     * The chunk of objects.
     *
     * @var array
     */
    protected $chunk;

    /**
     * @param int               $size size of the chunk to collect before processing
     * @param callback|callable $callback callback for parsed collection item
     * @param bool              $assoc    When true, returned objects will be converted into associative arrays
     */
    public function __construct(int $size, callable $callback, bool $assoc = true)
    {
        parent::__construct($callback, $assoc);

        $this->size  = $size;
        $this->chunk = [];
    }

    public function endObject(): void
    {
        $this->endCommon();

        $this->objectLevel--;
        if ($this->objectLevel === 0) {
            $obj = array_pop($this->stack);

            $this->chunk[] = reset($obj);
        }

        if (count($this->chunk) === $this->size) {
            $this->processChunk();
        }
    }

    public function endDocument(): void
    {
        $this->processChunk();

        parent::endDocument();
    }

    protected function processChunk(): void
    {
        if (empty($this->chunk)) {
            return;
        }

        call_user_func($this->callback, $this->chunk);

        $this->chunk = [];
    }
}
