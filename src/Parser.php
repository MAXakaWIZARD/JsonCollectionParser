<?php
namespace JsonCollectionParser;

class Parser
{
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
            $parser = new \JsonStreamingParser_Parser($stream, $listener, "\n", true);
            $parser->parse();
        } catch (\Exception $e) {
            fclose($stream);
            throw $e;
        }
        fclose($stream);
    }
}
