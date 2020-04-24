<?php
namespace JsonCollectionParser\Tests;

use JsonCollectionParser\Parser;
use JsonCollectionParser\Stream\DataStream;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

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

    /**
     * @var string
     */
    protected $chunkJsonFilePath;

    /**
     * @var int
     */
    protected $chunkSize = 5;

    public function setUp()
    {
        $this->basicJsonFilePath = TEST_DATA_PATH . '/basic.json';
        $this->chunkJsonFilePath = TEST_DATA_PATH . '/chunk.json';

        $this->parser = new Parser();
        $this->parser->setOption('emit_whitespace', true);
    }

    public function tearDown()
    {
        Mockery::close();

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

    public function testGeneralChunk()
    {
        $this->items = [];

        $this->parser->chunk(
            $this->chunkSize,
            $this->chunkJsonFilePath,
            [$this, 'processArrayChunk']
        );

        $correctData = json_decode(file_get_contents($this->chunkJsonFilePath), true);
        $this->assertSame($correctData, $this->items);
    }

    public function testReceiveAsObjects()
    {
        $this->items = [];

        $this->parser->parseAsObjects(
            $this->basicJsonFilePath,
            [$this, 'processObjectItem']
        );

        $correctData = json_decode(file_get_contents($this->basicJsonFilePath));
        $this->assertEquals($correctData, $this->items);
    }

    public function testReceiveAsObjectChunks()
    {
        $this->items = [];

        $this->parser->chunkAsObjects(
            $this->chunkSize,
            $this->chunkJsonFilePath,
            [$this, 'processObjectChunk']
        );

        $correctData = json_decode(file_get_contents($this->chunkJsonFilePath));
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
     * @param array $chunk
     */
    public function processArrayChunk($chunk)
    {
        $this->assertTrue(is_array($chunk), 'Chunk is expected as array of items');
        $this->assertTrue(
            count($chunk) === $this->chunkSize,
            'Chunk is expected to contain ' . $this->chunkSize . ' items.'
        );

        foreach ($chunk as $item) {
            $this->assertTrue(is_array($item), 'Item is expected as associative array');
        }

        $this->items = array_merge($this->items, $chunk);
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

    /**
     * @param array $chunk
     */
    public function processObjectChunk($chunk)
    {
        $this->assertTrue(is_array($chunk), 'Chunk is expected as array of items');
        $this->assertTrue(
            count($chunk) === $this->chunkSize,
            'Chunk is expected to contain ' . $this->chunkSize . ' items.'
        );

        foreach ($chunk as $item) {
            $this->assertTrue(is_object($item), 'Item is expected as object');
        }

        $this->items = array_merge($this->items, $chunk);
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

        $this->expectExceptionMessage('File does not exist: `' . $filePath . '`.');

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

        $this->expectExceptionMessage('Unable to open file for reading: `' . $filePath . '`.');

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

    public function testDocumentsFlow()
    {
        $sourcePath = TEST_DATA_PATH . '/basic_docs_flow_source.json';
        $resultPath = TEST_DATA_PATH . '/basic_docs_flow_result.json';

        $this->items = [];

        $this->parser->parse(
            $sourcePath,
            [$this, 'processArrayItem']
        );

        $correctData = json_decode(file_get_contents($resultPath), true);
        $this->assertSame($correctData, $this->items);
    }

    /**
     * @dataProvider providerDocumentsFlowFromString
     */
    public function testDocumentsFlowFromString(string $data, array $result)
    {
        $this->items = [];

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $data);
        rewind($stream);

        $this->parser->parse(
            $stream,
            [$this, 'processArrayItem']
        );

        $this->assertSame($result, $this->items);
    }

    public function providerDocumentsFlowFromString(): array
    {
        return [
            [
                '[{"a": 1}]',
                [["a" => 1]],
            ],
            [
                '{"a": 1}',
                [["a" => 1]],
            ],
            [
                '{"a": 1}{"b": 2}',
                [["a" => 1], ["b" => 2]],
            ],
            [
                '[{"a": 1}]{"b": 2}',
                [["a" => 1], ["b" => 2]],
            ],
            [
                '[[{"a": 1}]]{"b": 2}',
                [["a" => 1], ["b" => 2]],
            ],
            [
                '[{"a": 1}][{"b": 2}]',
                [["a" => 1], ["b" => 2]],
            ],
            [
                '[[{"a": 1}, {"b": 2}, {"c": 3}]]',
                [["a" => 1], ["b" => 2], ["c" => 3]],
            ],
            [
                '[{"a": 1}, {"b": 2}]{"c": 3}',
                [["a" => 1], ["b" => 2], ["c" => 3]],
            ],
        ];
    }
}
