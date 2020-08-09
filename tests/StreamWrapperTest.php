<?php

namespace JsonCollectionParser\Tests;

use JsonCollectionParser\Stream\StreamWrapper;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use stdClass;

class StreamWrapperTest extends TestCase
{
    public function setUp(): void
    {
        if (in_array(StreamWrapper::NAME, stream_get_wrappers())) {
            return;
        }

        stream_wrapper_register(StreamWrapper::NAME, StreamWrapper::class);
    }

    public function tearDown(): void
    {
        if (in_array(StreamWrapper::NAME, stream_get_wrappers())) {
            stream_wrapper_unregister(StreamWrapper::NAME);
        }

        Mockery::close();
    }

    public function testCanOpenStream()
    {
        $this->assertTrue(
            is_resource(
                $this->openStream(
                    Mockery::mock(
                        StreamInterface::class,
                        [
                            'isReadable' => true,
                        ]
                    )
                )
            )
        );
    }

    public function testCanReadStream()
    {
        $txt = 'testing';

        $resource = $this->openStream(
            Mockery::mock(
                       StreamInterface::class,
                       [
                           'isReadable' => true,
                           'eof' => true,
                       ]
                   )
                   ->shouldReceive('read')
                   ->andReturn($txt)
                   ->getMock()
        );

        $this->assertTrue(is_resource($resource));
        $this->assertSame($txt, fread($resource, strlen($txt)));
    }

    public function testCanReadStreamEOF()
    {
        $resource = $this->openStream(
            Mockery::mock(
                StreamInterface::class,
                [
                    'isReadable' => true,
                    'eof' => true,
                ]
            )
        );

        $this->assertTrue(feof($resource));
        $this->assertTrue(is_resource($resource));
    }

    public function testFailOpeningInvalidStream()
    {
        $this->assertFalse($this->openStream(new stdClass()));
    }

    public function testFailOpeningUnreadableStream()
    {
        $this->assertFalse(
            $this->openStream(
                Mockery::mock(
                    StreamInterface::class,
                    [
                        'isReadable' => false,
                    ]
                )
            )
        );
    }

    /**
     * @param mixed $stream
     *
     * @return bool|resource
     */
    protected function openStream($stream)
    {
        return @fopen(
            StreamWrapper::NAME . '://stream',
            'rb',
            false,
            stream_context_create([StreamWrapper::NAME => compact('stream')])
        );
    }
}
