<?php
namespace JsonCollectionParser\Tests;

use JsonCollectionParser\Parser;

/**
 *
 */
class BasicTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var array
     */
    protected $correctData = array(
        array(
            'id' => 78,
            'title' => 'Title',
            'dealType' => 'sale',
            'propertyType' => 'townhouse',
            'properties' => array(
                'bedroomsCount' => 6,
                'parking' => 'yes'
            ),
            'photos' => array(
                '1.jpg',
                '2.jpg'
            )
        ),
        array(
            'id' => 729,
            'dealType' => 'rent_long',
            'propertyType' => 'villa'
        ),
        array(
            'id' => 5165,
            'dealType' => 'rent_short',
            'propertyType' => 'villa'
        )
    );

    /**
     * @var int
     */
    protected $itemIndex = 0;

    /**
     *
     */
    public function setUp()
    {
        $this->parser = new Parser();
    }

    /**
     *
     */
    public function tearDown()
    {
        $filePath = TEST_DATA_PATH . '/non_readable.json';
        if (file_exists($filePath)) {
            @chmod($filePath, 0664);
            @unlink($filePath);
        }
    }

    /**
     *
     */
    public function testGeneral()
    {
        $this->itemIndex = 0;
        $this->parser->parse(
            TEST_DATA_PATH . '/basic.json',
            array($this, 'processItem')
        );
    }

    /**
     * @param $item
     */
    public function processItem($item)
    {
        $this->assertSame($item, $this->correctData[$this->itemIndex]);
        $this->itemIndex++;
    }

    /**
     *
     */
    public function testInvalidCallback()
    {
        $this->setExpectedException('\Exception', 'Callback should be callable');
        $this->parser->parse(
            TEST_DATA_PATH . '/basic.json',
            'nonExistentFunction'
        );
    }

    /**
     *
     */
    public function testNonExistentFile()
    {
        $filePath = TEST_DATA_PATH . '/not_exists.json';
        $this->setExpectedException('\Exception', 'File does not exist: ' . $filePath);
        $this->parser->parse(
            $filePath,
            array($this, 'processItem')
        );
    }

    /**
     *
     */
    public function testNonReadableFile()
    {
        $filePath = TEST_DATA_PATH . '/non_readable.json';
        file_put_contents($filePath, '');
        $this->assertFileExists($filePath);
        chmod($filePath, 0000);

        $this->setExpectedException('\Exception', 'Unable to open file for read: ' . $filePath);
        $this->parser->parse(
            $filePath,
            array($this, 'processItem')
        );
    }

    /**
     *
     */
    public function testParseError()
    {
        $this->setExpectedException(
            '\Exception',
            'Parsing error in [3:5]. Start of string expected for object key. Instead got: i'
        );
        $this->parser->parse(
            TEST_DATA_PATH . '/parse_error.json',
            array($this, 'processItem')
        );
    }
}
