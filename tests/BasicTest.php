<?php
namespace JsonCollectionParser\Tests;

use JsonCollectionParser\Parser;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class BasicTest extends TestCase
{
    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var string
     */
    protected $basicJsonFilePath;

    public function setUp()
    {
        $this->basicJsonFilePath = TEST_DATA_PATH . '/basic.json';

        $this->parser = new Parser();
        $this->parser->setOption('emit_whitespace', true);
    }

    public function tearDown()
    {
        $filePath = TEST_DATA_PATH . '/non_readable.json';
        if (file_exists($filePath)) {
            @chmod($filePath, 0664);
            @unlink($filePath);
        }
    }

    public function testGeneral()
    {
        $this->items = [];

        $this->parser->parse(
            $this->basicJsonFilePath,
            [$this, 'processArrayItem']
        );

        $correctData = json_decode(file_get_contents($this->basicJsonFilePath), true);
        $this->assertSame($correctData, $this->items);
    }

    public function testReceiveAsObjects()
    {
        $this->items = [];

        $this->parser->parse(
            $this->basicJsonFilePath,
            [$this, 'processObjectItem'],
            false
        );

        $correctData = json_decode(file_get_contents($this->basicJsonFilePath));
        $this->assertEquals($correctData, $this->items);
    }

    /**
     * @param array $item
     */
    public function processArrayItem($item)
    {
        $this->assertTrue(is_array($item), 'Item is expected as associative array');
        $this->items[] = $item;
    }

    /**
     * @param array $item
     */
    public function processArrayItemBypass($item)
    {
        $this->assertTrue(is_array($item), 'Item is expected as associative array');
    }

    /**
     * @param object $item
     */
    public function processObjectItem($item)
    {
        $this->assertTrue(is_object($item), 'Item is expected as object');
        $this->items[] = $item;
    }

    public function testWithStop()
    {
        $this->items = [];

        $this->parser->parse(
            $this->basicJsonFilePath,
            [$this, 'processFirstItem'],
            true
        );

        $correctData = json_decode(file_get_contents($this->basicJsonFilePath), true);
        $this->assertSame([$correctData[0]], $this->items);
    }

    /**
     * @param array $item
     */
    public function processFirstItem($item)
    {
        $this->items[] = $item;
        $this->parser->stop();
    }

    public function testInvalidCallback()
    {
        $this->expectExceptionMessage('Callback should be callable');

        $this->parser->parse(
            $this->basicJsonFilePath,
            'nonExistentFunction'
        );
    }

    public function testNonExistentFile()
    {
        $filePath = TEST_DATA_PATH . '/not_exists.json';

        $this->expectExceptionMessage('File does not exist: ' . $filePath);

        $this->parser->parse(
            $filePath,
            [$this, 'processArrayItem']
        );
    }

    public function testNonReadableFile()
    {
        $filePath = TEST_DATA_PATH . '/non_readable.json';
        file_put_contents($filePath, '');
        $this->assertFileExists($filePath);
        chmod($filePath, 0000);

        $this->expectExceptionMessage('Unable to open file for read: ' . $filePath);

        $this->parser->parse(
            $filePath,
            [$this, 'processArrayItem']
        );
    }

    public function testParseError()
    {
        $this->expectExceptionMessage(
            'Parsing error in [3:5]. Start of string expected for object key. Instead got: i'
        );

        $this->parser->parse(
            TEST_DATA_PATH . '/parse_error.json',
            [$this, 'processArrayItem']
        );
    }

    public function testNonExistentOption()
    {
        $this->assertNull($this->parser->getOption('non_existent_option'));
    }

    public function testGzip()
    {
        if (!extension_loaded('zlib')) {
            $this->addWarning('zlib extension not loaded, test skipped');
            $this->markAsRisky();
        }

        $this->items = [];

        $this->parser->parse(
            $this->basicJsonFilePath . '.gz',
            [$this, 'processArrayItem']
        );

        $correctData = json_decode(file_get_contents($this->basicJsonFilePath), true);

        $this->assertSame($correctData, $this->items);
    }

    public function testFileStream()
    {
        $this->items = [];

        $this->parser->parse(
            fopen($this->basicJsonFilePath, 'r'),
            [$this, 'processArrayItem']
        );

        $correctData = json_decode(file_get_contents($this->basicJsonFilePath), true);
        $this->assertSame($correctData, $this->items);
    }

    public function testMemoryStream()
    {
        $this->items = [];

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, file_get_contents($this->basicJsonFilePath));
        rewind($stream);

        $this->parser->parse(
            $stream,
            [$this, 'processArrayItem']
        );

        $correctData = json_decode(file_get_contents($this->basicJsonFilePath), true);
        $this->assertSame($correctData, $this->items);
    }
}
