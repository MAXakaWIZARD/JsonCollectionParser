<?php

declare(strict_types=1);

namespace JsonCollectionParser\Stream;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

class DataStream
{
    /**
     * @param mixed $input
     *
     * @throws StreamException
     *
     * @return resource
     */
    public static function get($input)
    {
        $type = gettype($input);

        if (method_exists(static::class, $type)) {
            return static::{$type}($input);
        }

        throw new StreamException(
            'Unable to create a stream from given input, must be one of [`object`, `resource`, `string`].'
        );
    }

    /**
     * @param resource $stream
     *
     * @return bool|null
     */
    public static function close($stream): ?bool
    {
        if (!is_resource($stream)) {
            return null;
        }

        return extension_loaded('zlib') ? gzclose($stream) : fclose($stream);
    }

    /**
     * Handler for resource input type
     *
     * @param mixed $input
     *
     * @throws StreamException
     *
     * @return resource
     */
    protected static function resource($input)
    {
        if (!is_resource($input)) {
            throw new StreamException('Invalid resource: unable to create stream.');
        }

        return $input;
    }

    /**
     * Handler for string input type
     *
     * @param string $input
     *
     * @throws StreamException
     *
     * @return resource
     */
    protected static function string(string $input)
    {
        if (!is_file($input)) {
            throw new StreamException('File does not exist: `' . $input . '`.');
        }

        $stream = extension_loaded('zlib') ? @gzopen($input, 'rb') : @fopen($input, 'rb');

        if ($stream === false) {
            throw new StreamException('Unable to open file for reading: `' . $input . '`.');
        }

        return $stream;
    }

    /**
     * Handler for object input type
     *
     * @param MessageInterface|StreamInterface $object
     *
     * @throws StreamException
     *
     * @return resource
     */
    protected static function object($object)
    {
        if (!($object instanceof MessageInterface || $object instanceof StreamInterface)) {
            throw new StreamException(
                'Unable to create stream from `'
                . get_class($object)
                . '`, must be one of `MessageInterface` or `StreamInterface`.'
            );
        }

        $object = $object instanceof MessageInterface ? $object->getBody() : $object;

        if (!$object instanceof StreamInterface) {
            throw new StreamException(
                'Unable to create a stream from `'
                . get_class($object)
                . '`, must be `StreamInterface`.'
            );
        }

        return static::streamWrapper($object);
    }

    /**
     * Stream content from the given stream wrapper
     *
     * @param StreamInterface $stream
     *
     * @throws StreamException
     *
     * @return resource
     */
    protected static function streamWrapper(StreamInterface $stream)
    {
        if (!in_array(StreamWrapper::NAME, stream_get_wrappers())) {
            stream_wrapper_register(StreamWrapper::NAME, StreamWrapper::class);
        }

        $resource = @fopen(
            StreamWrapper::NAME . '://stream',
            'rb',
            false,
            stream_context_create([StreamWrapper::NAME => compact('stream')])
        );

        if ($resource === false) {
            throw new StreamException('Failed to open stream from `' . get_class($stream) . '`');
        }

        return $resource;
    }
}
