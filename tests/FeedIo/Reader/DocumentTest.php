<?php
/*
 * This file is part of the feed-io package.
 *
 * (c) Alexandre Debril <alex.debril@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FeedIo\Reader;

use FeedIo\Feed;

use PHPUnit\Framework\TestCase;

class DocumentTest extends TestCase
{
    public function testIsJson()
    {
        $document = new Document('{"json": "value"}');

        $this->assertTrue($document->isJson());
    }

    public function testIsXml()
    {
        $document = new Document('<feed xmlns="http://www.w3.org/2005/Atom"></feed>');

        $this->assertTrue($document->isXml());
    }

    public function testGetJsonAsArray()
    {
        $document = new Document('{"foo": "bar"}');
        $this->assertIsArray($document->getJsonAsArray());
    }

    public function testLoadWrongDoucment()
    {
        $document = new Document('something wrong');
        $this->expectException('\LogicException');
        $document->getDOMDocument();
    }

    public function testLoadWrongJsonDoucment()
    {
        $document = new Document('something wrong');
        $this->expectException('\LogicException');
        $document->getJsonAsArray();
    }

    public function testLoadMalformedJsonDocument()
    {
        // Content starts with '{' but isn't valid JSON, so isJson() returns false
        $document = new Document('{"data":null,"status":"error","error":{"code":1000,"message":"not found"');
        $this->assertFalse($document->isJson());
        $this->expectException('\LogicException');
        $document->getJsonAsArray();
    }

    public function testLoadJsonDocumentWithNullValue()
    {
        // Valid JSON that contains null values should work if the root is an object
        $document = new Document('{"data": null}');
        $result = $document->getJsonAsArray();
        $this->assertIsArray($result);
        $this->assertNull($result['data']);
    }

    public function testLoadJsonDocumentWithEmptyObject()
    {
        $document = new Document('{}');
        $result = $document->getJsonAsArray();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testLoadJsonDocumentWithEmptyArray()
    {
        // This won't pass isJson() check since it starts with '[', not '{'
        // but we test the case where JSON array is wrapped
        $document = new Document('{"items": []}');
        $result = $document->getJsonAsArray();
        $this->assertIsArray($result);
        $this->assertEmpty($result['items']);
    }
}
