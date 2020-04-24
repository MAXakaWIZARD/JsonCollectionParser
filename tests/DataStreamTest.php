<?php

namespace JsonCollectionParser\Tests;

use JsonCollectionParser\Stream\DataStream;
use JsonCollectionParser\Stream\StreamException;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use stdClass;

class DataStreamTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @throws ReflectionException
     */
    public function testFailsToParseUnsupportedObjectType()
    {
        $objectMethod = self::getProtectedMethod('object');
        $dataStream = new DataStream();

        $this->expectException(StreamException::class);
        $this->expectExceptionMessage(
            'Unable to create stream from `stdClass`, must be one of `MessageInterface` or `StreamInterface`.'
        );

        $objectMethod->invokeArgs($dataStream, [new stdClass()]);
    }

    /**
     * @throws ReflectionException
     */
    public function testFailsToParseObjectTypeWithInvalidStreamInterface()
    {
        $objectMethod = self::getProtectedMethod('object');
        $dataStream = new DataStream();

        $message = Mockery::mock(
            MessageInterface::class,
            [
                'getBody' => new stdClass(),
            ]
        );

        $this->expectException(StreamException::class);
        $this->expectExceptionMessage(
            'Unable to create a stream from `stdClass`, must be `StreamInterface`.'
        );

        $objectMethod->invokeArgs($dataStream, [$message]);
    }

    /**
     * @throws ReflectionException
     */
    public function testFailsToParseUnreadableStreamType()
    {

        $objectMethod = self::getProtectedMethod('streamWrapper');
        $dataStream = new DataStream();

        $message = Mockery::mock(
            StreamInterface::class,
            ['isReadable' => false]
        );

        $this->expectException(StreamException::class);
        $this->expectExceptionMessage('Failed to open stream from `' . get_class($message) . '`');

        $objectMethod->invokeArgs($dataStream, [$message]);
    }

    public function testCanParseMessageInterface()
    {
        $message = Mockery::mock(
            MessageInterface::class,
            [
                'getBody' => Mockery::mock(
                    StreamInterface::class,
                    ['isReadable' => true]
                ),
            ]
        );

        $resource = DataStream::get($message);

        $this->assertTrue(is_resource($resource));
    }

    public function testCanParseStreamInterface()
    {
        $stream = Mockery::mock(
            StreamInterface::class,
            ['isReadable' => true]
        );

        $resource = DataStream::get($stream);

        $this->assertTrue(is_resource($resource));
    }

    public function testCantParseInvalidStream()
    {
        $this->expectException(StreamException::class);

        $resource = DataStream::get(new stdClass());
    }

    public function testCanCloseStream()
    {
        $stream = Mockery::mock(
            StreamInterface::class,
            ['isReadable' => true]
        );

        $resource = DataStream::get($stream);

        $this->assertTrue(is_resource($resource));

        $closed = DataStream::close($resource);

        $this->assertTrue($closed);
        $this->assertFalse(is_resource($resource));
    }

    public function testCantCloseInvalidStream()
    {
        $closed = DataStream::close(new stdClass());

        $this->assertNull($closed);
    }

    /**
     * @param string $name
     *
     * @return ReflectionMethod
     * @throws ReflectionException
     */
    protected static function getProtectedMethod(string $name)
    {
        $class  = new ReflectionClass(DataStream::class);
        $method = $class->getMethod($name);

        $method->setAccessible(true);

        return $method;
    }
}
