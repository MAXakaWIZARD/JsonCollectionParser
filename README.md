# JsonCollectionParser
[![Build Status](https://api.travis-ci.org/MAXakaWIZARD/JsonCollectionParser.png?branch=master)](https://travis-ci.org/MAXakaWIZARD/JsonCollectionParser) 
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/MAXakaWIZARD/JsonCollectionParser/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/MAXakaWIZARD/JsonCollectionParser/?branch=master)
[![Code Climate](https://codeclimate.com/github/MAXakaWIZARD/JsonCollectionParser/badges/gpa.svg)](https://codeclimate.com/github/MAXakaWIZARD/JsonCollectionParser)
[![Coverage Status](https://coveralls.io/repos/MAXakaWIZARD/JsonCollectionParser/badge.svg?branch=master)](https://coveralls.io/r/MAXakaWIZARD/JsonCollectionParser?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/801d5faf-e753-4b5c-8a14-5795a1a4d239/mini.png)](https://insight.sensiolabs.com/projects/801d5faf-e753-4b5c-8a14-5795a1a4d239)

[![GitHub tag](https://img.shields.io/github/tag/MAXakaWIZARD/JsonCollectionParser.svg?label=latest)](https://packagist.org/packages/maxakawizard/json-collection-parser) 
[![Packagist](https://img.shields.io/packagist/dt/maxakawizard/json-collection-parser.svg)](https://packagist.org/packages/maxakawizard/json-collection-parser)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%207.1-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/packagist/l/maxakawizard/json-collection-parser.svg)](https://packagist.org/packages/maxakawizard/json-collection-parser)

Event-based parser for large JSON collections (consumes small amount of memory).
Built on top of [JSON Streaming Parser](https://github.com/salsify/jsonstreamingparser)

This package is compliant with [PSR-4](https://www.php-fig.org/psr/psr-4/) and [PSR-12](https://www.php-fig.org/psr/psr-12/) code styles
and supports parsing of [PSR-7](https://www.php-fig.org/psr/psr-7/) message interfaces.
If you notice compliance oversights, please send a patch via pull request.

## Installation
You will need [Composer](https://getcomposer.org/) to install the package
```bash
composer require maxakawizard/json-collection-parser:~1.0
```

## Input data format
Data must be in one of following formats:

### Array of objects (valid JSON)
```javascript
[
    {
        "id": 78,
        "title": "Title",
        "dealType": "sale",
        "propertyType": "townhouse",
        "properties": {
            "bedroomsCount": 6,
            "parking": "yes"
        },
        "photos": [
            "1.jpg",
            "2.jpg"
        ],
        "agents": [
            {
                "name": "Joe",
                "email": "joe@realestate.email"
            },
            {
                "name": "Sally",
                "email": "sally@realestate.email"
            }
         ]
    },
    {
        "id": 729,
        "dealType": "rent_long",
        "propertyType": "villa"
    },
    {
        "id": 5165,
        "dealType": "rent_short",
        "propertyType": "villa"
    }
]
```

### Sequence of object literals:
```text
{
    "id": 78,
    "dealType": "sale",
    "propertyType": "townhouse"
}
{
    "id": 729,
    "dealType": "rent_long",
    "propertyType": "villa"
}
{
    "id": 5165,
    "dealType": "rent_short",
    "propertyType": "villa"
}
```

### Sequence of object and array literals:
```text
[[{
    "id": 78,
    "dealType": "sale",
    "propertyType": "townhouse"
}]]
{
    "id": 729,
    "dealType": "rent_long",
    "propertyType": "villa"
}
[{
    "id": 5165,
    "dealType": "rent_short",
    "propertyType": "villa"
}]
```

### Sequence of object and array literals (some of objects in subarrays, comma-separated):
```text
[
{
    "id": 78,
    "dealType": "sale",
    "propertyType": "townhouse"
},
{
    "id": 729,
    "dealType": "rent_long",
    "propertyType": "villa"
}
]
{
    "id": 5165,
    "dealType": "rent_short",
    "propertyType": "villa"
}
```

## Usage

### Function as callback:
```php
function processItem(array $item)
{
    is_array($item); //true
    print_r($item);
}

$parser = new \JsonCollectionParser\Parser();
$parser->parse('/path/to/file.json', 'processItem');
```

### Closure as callback:
```php
$items = [];

$parser = new \JsonCollectionParser\Parser();
$parser->parse('/path/to/file.json', function (array $item) use (&$items) {
    $items[] = $item;
});
```

### Static method as callback:
```php
class ItemProcessor {
    public static function process(array $item)
    {
        is_array($item); //true
        print_r($item);
    }
}

$parser = new \JsonCollectionParser\Parser();
$parser->parse('/path/to/file.json', ['ItemProcessor', 'process']);
```

### Instance method as callback:
```php
class ItemProcessor {
    public function process(array $item)
    {
        is_array($item); //true
        print_r($item);
    }
}

$parser = new \JsonCollectionParser\Parser();
$processor = new \ItemProcessor();
$parser->parse('/path/to/file.json', [$processor, 'process']);
```

### Receive items as objects:
```php
function processItem(\stdClass $item)
{
    is_array($item); //false
    is_object($item); //true
    print_r($item);
}

$parser = new \JsonCollectionParser\Parser();
$parser->parseAsObjects('/path/to/file.json', 'processItem');
```

### Receive chunks of items as arrays:
```php
function processChunk(array $chunk)
{
    is_array($chunk);    //true
    count($chunk) === 5; //true

    foreach ($chunk as $item) {
        is_array($item);  //true
        is_object($item); //false
        print_r($item);
    }
}

$parser = new \JsonCollectionParser\Parser();
$parser->chunk('/path/to/file.json', 'processChunk', 5);
```

### Receive chunks of items as objects:
```php
function processChunk(array $chunk)
{
    is_array($chunk);    //true
    count($chunk) === 5; //true

    foreach ($chunk as $item) {
        is_array($item);  //false
        is_object($item); //true
        print_r($item);
    }
}

$parser = new \JsonCollectionParser\Parser();
$parser->chunkAsObjects(5, '/path/to/file.json', 'processChunk');
```

### Pass stream as parser input:
```php
$stream = fopen('/path/to/file.json', 'r');

$parser = new \JsonCollectionParser\Parser();
$parser->parseAsObjects($stream, 'processItem');
```

### Pass [PSR-7](https://www.php-fig.org/psr/psr-7/) MessageInterface as parser input:
```php
$resource = $httpClient->get('https://httpbin.org/get');

$parser = new \JsonCollectionParser\Parser();
$parser->parseAsObjects($resource, 'processItem');
```

### Pass [PSR-7](https://www.php-fig.org/psr/psr-7/) StreamInterface as parser input:
```php
$resource = $httpClient->get('https://httpbin.org/get');

$parser = new \JsonCollectionParser\Parser();
$parser->parseAsObjects($resource->getBody(), 'processItem');
```

## Supported formats

* [PSR-7](https://www.php-fig.org/psr/psr-7/) - HTTP message interface
* `.json` - raw JSON file
* `.gz` - GZIP-compressed file (you will need `zlib` PHP extension installed)

## Running tests
```bash
composer test
```

## License
This library is released under [MIT](http://www.tldrlegal.com/license/mit-license) license.
