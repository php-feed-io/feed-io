<?php
/*
 * This file is part of the feed-io package.
 *
 * (c) Alexandre Debril <alex.debril@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FeedIo;

use PHPUnit\Framework\TestCase;

class FeedTest extends TestCase
{
    /**
     * @var \FeedIo\Feed
     */
    protected $object;

    protected function setUp(): void
    {
        $this->object = new Feed();
    }

    public function testNext()
    {
        $item1 = new Feed\Item();
        $item2 = clone $item1;
        $item2->setTitle('item2');
        $this->object->add($item1);
        $this->object->add($item2);
        $this->object->rewind();
        $this->assertEquals($item1, $this->object->current());
        $this->object->next();
        $this->assertEquals($item2, $this->object->current());
    }

    public function testIsValid()
    {
        $item = new Feed\Item();
        $this->object->add($item);
        $this->object->rewind();

        $this->assertTrue($this->object->valid());
        $this->object->next();
        $this->assertFalse($this->object->valid());
    }

    public function testRewind()
    {
        $item = new Feed\Item();
        $this->object->add($item);

        $this->object->next();
        $this->assertFalse($this->object->valid());
        $this->object->rewind();
        $this->assertEquals($item, $this->object->current());
    }

    public function testKey()
    {
        $this->assertNull($this->object->key());
        $this->object->add(new Feed\Item());
        $this->object->add(new Feed\Item());
        $this->assertEquals(0, $this->object->key());
        $this->object->next();
        $this->assertEquals(1, $this->object->key());
    }

    public function testAdd()
    {
        $item = new Feed\Item();
        $this->object->add($item);

        $this->assertEquals($this->object->current(), $item);
    }

    public function testUrl()
    {
        $url = 'http://localhost';

        $feed = new Feed();
        $feed->setUrl($url);

        $this->assertEquals($url, $feed->getUrl());
    }


    public function testDescription()
    {
        $description = 'lorem ipsum';
        $this->assertInstanceOf('\FeedIo\Feed', $this->object->setDescription($description));
        $this->assertEquals($description, $this->object->getDescription());
    }

    public function testToArray()
    {
        $item = new Feed\Item();
        $item->setTitle('foo-bar');
        $this->object->add($item);

        $out = $this->object->toArray();

        $this->assertEquals('foo-bar', $out['items'][0]['title']);
    }

    public function testJsonSerialize()
    {
        $item = new Feed\Item();
        $item->setTitle('foo-bar');
        $this->object->add($item);
        $this->object->setTitle('hello');
        $this->object->setLastModified(new \DateTime());

        $json = json_encode($this->object);

        $this->assertIsString($json);
        $this->assertInstanceOf('stdClass', json_decode($json));
    }

    public function testCount()
    {
        $this->assertCount(0, $this->object);

        $this->object->add(new Feed\Item());
        $this->object->add(new Feed\Item());

        $this->assertCount(2, $this->object);
    }

    public function testAddLink()
    {
        $feed = new Feed();
        $feed->addLink('alternate', 'https://example.com/', 'text/html');
        $feed->addLink('self', 'https://example.com/feed.atom', 'application/atom+xml');

        $links = iterator_to_array($feed->getLinks());
        $this->assertCount(2, $links);
        $this->assertEquals('alternate', $links[0]->getRel());
        $this->assertEquals('https://example.com/', $links[0]->getHref());
        $this->assertEquals('text/html', $links[0]->getType());
        $this->assertEquals('self', $links[1]->getRel());
        $this->assertEquals('https://example.com/feed.atom', $links[1]->getHref());
        $this->assertEquals('application/atom+xml', $links[1]->getType());
    }

    public function testAddLinkWithoutType()
    {
        $feed = new Feed();
        $feed->addLink('self', 'https://example.com/feed.atom');

        $links = iterator_to_array($feed->getLinks());
        $this->assertCount(1, $links);
        $this->assertNull($links[0]->getType());
    }

    public function testGetLinksIsEmptyByDefault()
    {
        $feed = new Feed();
        $links = iterator_to_array($feed->getLinks());
        $this->assertCount(0, $links);
    }

    public function testSetHomePageUrl()
    {
        $feed = new Feed();
        $feed->setHomePageUrl('https://example.com/');

        $this->assertEquals('https://example.com/', $feed->getHomePageUrl());
        $this->assertEquals('https://example.com/', $feed->getLink());

        $links = iterator_to_array($feed->getLinks());
        $this->assertCount(1, $links);
        $this->assertEquals('alternate', $links[0]->getRel());
        $this->assertEquals('https://example.com/', $links[0]->getHref());
        $this->assertEquals('text/html', $links[0]->getType());
    }

    public function testSetFeedUrl()
    {
        $feed = new Feed();
        $feed->setFeedUrl('https://example.com/feed.atom');

        $this->assertEquals('https://example.com/feed.atom', $feed->getFeedUrl());
        $this->assertEquals('https://example.com/feed.atom', $feed->getUrl());

        $links = iterator_to_array($feed->getLinks());
        $this->assertCount(1, $links);
        $this->assertEquals('self', $links[0]->getRel());
        $this->assertEquals('https://example.com/feed.atom', $links[0]->getHref());
        $this->assertEquals('application/atom+xml', $links[0]->getType());
    }

    public function testSetHomePageUrlAndSetFeedUrl()
    {
        $feed = new Feed();
        $feed->setHomePageUrl('https://example.com/');
        $feed->setFeedUrl('https://example.com/feed.atom');

        $links = iterator_to_array($feed->getLinks());
        $this->assertCount(2, $links);
        $this->assertEquals('alternate', $links[0]->getRel());
        $this->assertEquals('self', $links[1]->getRel());
    }
}
