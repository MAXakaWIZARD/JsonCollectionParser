<?php

namespace JsonCollectionParser\Tests;

use JsonCollectionParser\Stream\DataStream;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

class DataStreamTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
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

    public function testCanCloseStream()
    {
        $stream = Mockery::mock(
            StreamInterface::class,
            ['isReadable' => true]
        );

        $resource = DataStream::get($stream);

        $this->assertTrue(is_resource($resource));

        DataStream::close($resource);

        $this->assertFalse(is_resource($resource));
    }
}
