<?php
namespace JsonCollectionParser;

use JsonStreamingParser\Parser as BaseParser;

class Parser
{
    /**
     * @var array
     */
    protected $options = [
        'line_ending' => "\n",
        'emit_whitespace' => false
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
     * @param string|resource $input File path or resource
     * @param callback|callable $itemCallback Callback
     * @param bool $assoc Parse as associative arrays
     *
     * @throws \Exception
     */
    public function parse($input, $itemCallback, $assoc = true)
    {
        $this->checkCallback($itemCallback);

        $stream = $this->openStream($input);

        try {
            $listener = new Listener($itemCallback, $assoc);
            $this->parser = new BaseParser(
                $stream,
                $listener,
                $this->getOption('line_ending'),
                $this->getOption('emit_whitespace')
            );
            $this->parser->parse();
        } catch (\Exception $e) {
            $this->closeStream($stream);
            throw $e;
        }

        $this->closeStream($stream);
    }

    /**
     * @param string|resource $input File path or resource
     * @param callback|callable $itemCallback Callback
     *
     * @throws \Exception
     */
    public function parseAsObjects($input, $itemCallback)
    {
        $this->parse($input, $itemCallback, false);
    }

    /**
     *
     */
    public function stop()
    {
        $this->parser->stop();
    }

    /**
     * @param callback|callable $callback
     *
     * @throws \Exception
     */
    protected function checkCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new \Exception("Callback should be callable");
        }
    }

    /**
     * @param string|resource $input File path or resource
     *
     * @return resource
     * @throws \Exception
     */
    protected function openStream($input)
    {
        if (is_resource($input)) {
            return $input;
        }

        if (!is_file($input)) {
            throw new \Exception('File does not exist: ' . $input);
        }

        $stream = $this->gzipSupported ? @gzopen($input, 'r') : @fopen($input, 'r');
        if (false === $stream) {
            throw new \Exception('Unable to open file for read: ' . $input);
        }

        return $stream;
    }

    /**
     * @param resource $stream
     */
    protected function closeStream($stream)
    {
        if (!is_resource($stream)) {
            return;
        }

        $this->gzipSupported ? gzclose($stream) : fclose($stream);
    }


    /**
     * @param string $name
     * @param mixed $value
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getOption($name)
    {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        } else {
            return null;
        }
    }
}
