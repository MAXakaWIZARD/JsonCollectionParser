<?php
namespace JsonCollectionParser;

class Parser
{
    /**
     * @var array
     */
    protected $options = array(
        'line_ending' => "\n",
        'emit_whitespace' => false
    );

    /**
     * @param $filePath
     * @param $itemCallback
     *
     * @throws \Exception
     */
    public function parse($filePath, $itemCallback)
    {
        if (!is_callable($itemCallback)) {
            throw new \Exception("Callback should be callable");
        }

        if (!is_file($filePath)) {
            throw new \Exception('File does not exist: ' . $filePath);
        }

        $stream = @fopen($filePath, 'r');
        if (false === $stream) {
            throw new \Exception('Unable to open file for read: ' . $filePath);
        }

        try {
            $listener = new Listener($itemCallback);
            $parser = new \JsonStreamingParser_Parser(
                $stream,
                $listener,
                $this->getOption('line_ending'),
                $this->getOption('emit_whitespace')
            );
            $parser->parse();
        } catch (\Exception $e) {
            fclose($stream);
            throw $e;
        }
        fclose($stream);
    }

    /**
     * @param $name
     * @param $value
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }

    /**
     * @param $name
     *
     * @return null
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
