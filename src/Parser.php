<?php

namespace JsonCollectionParser;

use Exception;
use JsonCollectionParser\Stream\DataStream;
use JsonStreamingParser\Listener\ListenerInterface;
use JsonStreamingParser\Parser as BaseParser;

class Parser
{
    /**
     * @var array
     */
    protected $options = [
        'line_ending' => "\n",
        'emit_whitespace' => false,
    ];

    /**
     * @var BaseParser
     */
    protected $parser;

    /**
     * @var bool
     */
    protected $gzipSupported;

    /**
     * @var resource
     */
    protected $stream;

    public function __construct()
    {
        $this->gzipSupported = extension_loaded('zlib');
    }

    /**
     * @param string|resource   $input        File path or resource
     * @param callback|callable $itemCallback Callback
     * @param bool              $assoc        Parse as associative arrays
     *
     * @throws Exception
     */
    public function parse($input, $itemCallback, bool $assoc = true): void
    {
        $this->checkCallback($itemCallback);

        $this->parseStream($input, new Listener($itemCallback, $assoc));
    }

    /**
     * @param int               $size         Size of the chunk to collect before processing
     * @param string|resource   $input        File path or resource
     * @param callback|callable $itemCallback Callback
     * @param bool              $assoc        Parse as associative arrays
     *
     * @throws Exception
     */
    public function chunk(int $size, $input, $itemCallback, bool $assoc = true): void
    {
        $this->checkCallback($itemCallback);

        $this->parseStream($input, new ChunkListener($size, $itemCallback, $assoc));
    }

    /**
     * @param string|resource   $input        File path or resource
     * @param callback|callable $itemCallback Callback
     *
     * @throws Exception
     */
    public function parseAsObjects($input, $itemCallback): void
    {
        $this->parse($input, $itemCallback, false);
    }

    /**
     * @param int               $size         Size of the chunk to collect before processing
     * @param string|resource   $input        File path or resource
     * @param callback|callable $itemCallback Callback
     *
     * @throws Exception
     */
    public function chunkAsObjects(int $size, $input, $itemCallback): void
    {
        $this->chunk($size, $input, $itemCallback, false);
    }

    public function stop(): void
    {
        $this->parser->stop();
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setOption(string $name, $value): void
    {
        $this->options[$name] = $value;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getOption(string $name)
    {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        } else {
            return null;
        }
    }

    /**
     * @param string|resource   $input    File path or resource
     * @param ListenerInterface $listener
     *
     * @throws Exception
     */
    protected function parseStream($input, ListenerInterface $listener)
    {
        $stream = DataStream::get($input);

        try {
            $this->parser = new BaseParser(
                $stream,
                $listener,
                $this->getOption('line_ending'),
                $this->getOption('emit_whitespace')
            );
            $this->parser->parse();
        } catch (Exception $e) {
            throw $e;
        } finally {
            DataStream::close($stream);
        }
    }

    /**
     * @param callback|callable $callback
     *
     * @throws Exception
     */
    protected function checkCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new Exception("Callback should be callable");
        }
    }
}
