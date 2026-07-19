<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 22/11/14
 * Time: 11:28
 */

namespace FeedIo\Rule;

use FeedIo\Feed\Item;

use PHPUnit\Framework\TestCase;

class PublicIdTest extends TestCase
{
    /**
     * @var PublicId
     */
    protected $object;

    public const PUBLIC_ID = 'a12';

    protected function setUp(): void
    {
        $this->object = new PublicId();
    }

    public function testGetNodeName()
    {
        $this->assertEquals('guid', $this->object->getNodeName());
    }

    public function testSet()
    {
        $item = new Item();

        $this->object->setProperty($item, new \DOMElement('guid', 'foo'));
        $this->assertEquals('foo', $item->getPublicId());
        $this->assertTrue($item->getPublicIdIsPermaLink());
    }

    public function testSetWithIsPermaLinkFalse()
    {
        $item = new Item();

        $document = new \DOMDocument();
        $element = $document->createElement('guid', 'urn:uuid:1225c695-cfb8-4ebb-aaaa-80da344efa6a');
        $element->setAttribute('isPermaLink', 'false');

        $this->object->setProperty($item, $element);
        $this->assertEquals('urn:uuid:1225c695-cfb8-4ebb-aaaa-80da344efa6a', $item->getPublicId());
        $this->assertFalse($item->getPublicIdIsPermaLink());
        $this->assertNull($item->getLink());
    }

    public function testCreateElement()
    {
        $item = new Item();
        $item->setPublicId(self::PUBLIC_ID);

        $document = new \DOMDocument();
        $rootElement = $document->createElement('feed');

        $this->object->apply($document, $rootElement, $item);

        $element = $rootElement->firstChild;

        $this->assertInstanceOf('\DomElement', $element);
        $this->assertEquals(self::PUBLIC_ID, $element->nodeValue);
        $this->assertEquals('guid', $element->nodeName);
        $document->appendChild($rootElement);

        $this->assertXmlStringEqualsXmlString('<feed><guid>a12</guid></feed>', $document->saveXML());
    }

    public function testCreateElementWithIsPermaLinkFalse()
    {
        $item = new Item();
        $item->setPublicId('urn:uuid:1225c695-cfb8-4ebb-aaaa-80da344efa6a', false);

        $document = new \DOMDocument();
        $rootElement = $document->createElement('feed');

        $this->object->apply($document, $rootElement, $item);

        $element = $rootElement->firstChild;

        $this->assertInstanceOf('\DomElement', $element);
        $this->assertEquals('urn:uuid:1225c695-cfb8-4ebb-aaaa-80da344efa6a', $element->nodeValue);
        $this->assertEquals('false', $element->getAttribute('isPermaLink'));
        $document->appendChild($rootElement);

        $this->assertXmlStringEqualsXmlString(
            '<feed><guid isPermaLink="false">urn:uuid:1225c695-cfb8-4ebb-aaaa-80da344efa6a</guid></feed>',
            $document->saveXML()
        );
    }
}
