<?php

declare(strict_types=1);

namespace JsonCollectionParser\Stream;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Class StreamWrapper
 *
 * An instance of this class is initialized as soon as a stream function
 * tries to access the protocol it is associated with.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class StreamWrapper
{
    /** @var string */
    public const NAME = 'json-collection-parser';

    /**
     * The current context, or NULL if no context
     * was passed to the caller function.
     *
     * @var resource|null
     */
    public $context;

    /** @var StreamInterface */
    protected $stream;

    /**
     * Opens file or URL
     *
     * This method is called immediately after the wrapper is initialized
     * (f.e. by fopen() and file_get_contents()).
     *
     * @param string $path
     * @param string $mode
     * @param int    $options
     * @param string $opened_path
     *
     * @return bool
     */
    public function stream_open(string $path, string $mode, int $options, &$opened_path): bool
    {
        $options = stream_context_get_options($this->context);
        $stream  = $options[static::NAME]['stream'] ?? null;

        if (! $stream instanceof StreamInterface || ! $stream->isReadable()) {
            return false;
        }

        $this->stream = $stream;

        return true;
    }

    /**
     * Tests for end-of-file on a file pointer
     *
     * This method is called in response to feof().
     *
     * @return bool
     */
    public function stream_eof(): bool
    {
        return $this->stream->eof();
    }

    /**
     * Read from stream
     *
     * This method is called in response to fread() and fgets().
     *
     * @param int $count
     *
     * @throws RuntimeException if an error occurs.
     *
     * @return string
     */
    public function stream_read(int $count): string
    {
        return $this->stream->read($count);
    }
}
