<?php
/**
 * Created by PhpStorm.
 * User: alex
 */

namespace FeedIo\Rule;

use FeedIo\Feed\Item;

use PHPUnit\Framework\TestCase;

class AuthorTest extends TestCase
{
    /**
     * @var \FeedIo\Rule\Author
     */
    protected $object;

    public const AUTHOR = 'John Doe';

    protected function setUp(): void
    {
        $this->object = new Author();
    }

    public function testGetNodeName()
    {
        $this->assertEquals('author', $this->object->getNodeName());
    }

    public function testSet()
    {
        $item = new Item();

        $this->object->setProperty($item, new \DOMElement('author', self::AUTHOR));
        $this->assertEquals(self::AUTHOR, $item->getAuthor()->getName());
    }

    public function testSetKeepsEveryAuthorAndGetAuthorReturnsTheLast()
    {
        $item = new Item();

        $this->object->setProperty($item, new \DOMElement('author', self::AUTHOR));
        $this->object->setProperty($item, new \DOMElement('author', 'Jane Roe'));

        $this->assertEquals('Jane Roe', $item->getAuthor()->getName());
        $names = array_map(fn ($author) => $author->getName(), [...$item->getAuthors()]);
        $this->assertEquals([self::AUTHOR, 'Jane Roe'], $names);
    }

    public function testCreateElement()
    {
        $item = new Item();
        $author = new \FeedIo\Feed\Item\Author();
        $author->setName(self::AUTHOR);
        $item->setAuthor($author);

        $document = new \DOMDocument();
        $rootElement = $document->createElement('feed');
        $this->object->apply($document, $rootElement, $item);

        $addedElement = $rootElement->firstChild;

        $this->assertEquals(self::AUTHOR, $addedElement ->nodeValue);
        $this->assertEquals('author', $addedElement ->nodeName);

        $document->appendChild($rootElement);

        $this->assertXmlStringEqualsXmlString('<feed><author>'.self::AUTHOR.'</author></feed>', $document->saveXML());
    }

    public function testCreateSingleElementForMultipleAuthors()
    {
        $item = new Item();
        $item->setAuthors([
            (new \FeedIo\Feed\Item\Author())->setName('John Doe'),
            (new \FeedIo\Feed\Item\Author())->setName('Jane Roe'),
        ]);

        $document = new \DOMDocument();
        $rootElement = $document->createElement('item');
        $this->object->apply($document, $rootElement, $item);

        $this->assertSame(['Jane Roe'], array_map(
            static fn (\DOMElement $element): string => $element->nodeValue,
            iterator_to_array($rootElement->childNodes)
        ));
    }
}
