<?php
/*
 * This file is part of the feed-io package.
 *
 * (c) Alexandre Debril <alex.debril@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FeedIo\Feed;

use FeedIo\Feed\Node\Category;

use PHPUnit\Framework\TestCase;

class NodeTest extends TestCase
{
    /**
     * @var \FeedIo\Feed\Node
     */
    protected $object;

    protected function setUp(): void
    {
        $this->object = new Node();
    }

    public function testTitle()
    {
        $title = 'my brilliant title';

        $this->assertInstanceOf('\FeedIo\Feed\Node', $this->object->setTitle($title));
        $this->assertEquals($title, $this->object->getTitle());
    }

    public function testPublicId()
    {
        $publicId = 'a12';
        $this->assertInstanceOf('\FeedIo\Feed\Node', $this->object->setPublicId($publicId));
        $this->assertEquals($publicId, $this->object->getPublicId());
    }

    public function testLink()
    {
        $link = 'http://localhost';
        $this->assertInstanceOf('\FeedIo\Feed\Node', $this->object->setLink($link));
        $this->assertEquals($link, $this->object->getLink());
    }

    public function testLastModified()
    {
        $lastModified = new \DateTime();
        $this->assertInstanceOf('\FeedIo\Feed\Node', $this->object->setLastModified($lastModified));
        $this->assertEquals($lastModified, $this->object->getLastModified());
    }

    public function testNewCategory()
    {
        $this->assertInstanceOf('\FeedIo\Feed\Node\CategoryInterface', $this->object->newCategory());
    }

    public function testGetCategoryAsGenerator()
    {
        $category = new Category();
        $category->setLabel('test');

        $this->object->addCategory($category);

        $categories = $this->object->getCategoriesGenerator();

        $this->assertEquals('test', $categories->current());
    }

    public function testToArray()
    {
        $category = new Category();
        $category->setLabel('test');
        $this->object->set('foo', 'bar')
            ->setLastModified(new \DateTime())
            ->setTitle('my title')
            ->addCategory($category);

        $out = $this->object->toArray();

        $this->assertEquals('my title', $out['title']);
        $this->assertEquals('bar', $out['elements']['foo']);
        $this->assertEquals('test', $out['categories'][0]);
    }

    public function testAddCategory()
    {
        $category = new \FeedIo\Feed\Node\Category();
        $category->setTerm('term');

        $this->object->addCategory($category);
        $categories = $this->object->getCategories();

        $count = 0;
        foreach ($categories as $testedCategory) {
            $count++;
            $this->assertEquals('term', $testedCategory->getTerm());
            $this->assertEquals($category, $testedCategory);
        }

        $this->assertEquals(1, $count);
    }

    public function testGetHostFromLinkReturnsNullForRelativePath()
    {
        $this->object->setLink('/relative/path');

        $this->assertNull($this->object->getHostFromLink());
    }

    public function testSetHostInContentResolvesDotDotLinks(): void
    {
        $item = new Item();
        $item->setLink('https://wiki.xxiivv.com/site/2026.html#20N');
        $item->setContent("<p><a href='../site/metadata.html'>Metadata</a></p><img src='../media/refs/hello.png'/>");

        $item->setHostInContent('https://wiki.xxiivv.com');

        $this->assertStringContainsString(
            "href='https://wiki.xxiivv.com/site/metadata.html'",
            $item->getContent()
        );
        $this->assertStringContainsString(
            "src='https://wiki.xxiivv.com/media/refs/hello.png'",
            $item->getContent()
        );
    }

    public function testSetHostInContentResolvesDotLinks(): void
    {
        $item = new Item();
        $item->setLink('https://example.com/dir/page.html');
        $item->setContent('<a href="./sibling.html">Sibling</a>');

        $item->setHostInContent('https://example.com');

        $this->assertStringContainsString(
            'href="https://example.com/dir/sibling.html"',
            $item->getContent()
        );
    }

    public function testSetHostInContentPreservesQueryStringsAndFragments(): void
    {
        $item = new Item();
        $item->setLink('https://example.com/dir/page.html');
        $item->setContent('<a href="../page.html?x=1#sec">Sibling</a>');

        $item->setHostInContent('https://example.com');

        $this->assertStringContainsString(
            'href="https://example.com/page.html?x=1#sec"',
            $item->getContent()
        );
    }

    public function testSetHostInContentKeepsAuthorityComponentsForRelativeLinks(): void
    {
        $cases = [
            [
                'link' => 'https://user:pass@example.com:8080/dir/page.html',
                'expected' => 'https://user:pass@example.com:8080/page.html',
            ],
            [
                'link' => 'https://example.com:8080/dir/page.html',
                'expected' => 'https://example.com:8080/page.html',
            ],
            [
                'link' => 'https://[2001:db8::1]/dir/page.html',
                'expected' => 'https://[2001:db8::1]/page.html',
            ],
        ];

        foreach ($cases as $case) {
            $item = new Item();
            $item->setLink($case['link']);
            $item->setContent('<a href="../page.html">Sibling</a>');

            $item->setHostInContent('https://example.com');

            $this->assertStringContainsString(
                'href="' . $case['expected'] . '"',
                $item->getContent()
            );
        }
    }

    public function testSetHostInContentDoesNotModifyLinksInsideCodeTags(): void
    {
        $item = new Item();
        $item->setLink('https://example.com/dir/page.html');
        $item->setContent('<code><a href="../page.html">Sibling</a></code>');

        $item->setHostInContent('https://example.com');

        $this->assertStringContainsString(
            '<code><a href="../page.html">Sibling</a></code>',
            $item->getContent()
        );
    }

    public function testSetHostInContentDoesNotModifyMagnetLinks(): void
    {
        $item = new Item();
        $item->setLink('https://www.website.com/some/page');
        $item->setContent('<a href="magnet:?xt=urn:btih:343434343434343434&dn=Example.File.Name%5D&tr=udp://tracker.opentrackr.org/announce">Torrent</a>');

        $item->setHostInContent('https://www.website.com');

        $this->assertStringContainsString(
            'href="magnet:?xt=urn:btih:343434343434343434&dn=Example.File.Name%5D&tr=udp://tracker.opentrackr.org/announce"',
            $item->getContent()
        );
    }

    public function testSetHostInContentDoesNotModifyAbsoluteLinksInContent(): void
    {
        $item = new Item();
        $item->setLink('https://example.com/dir/page.html');
        $item->setContent('<a href="https://other.example.com/page.html">Other</a>');

        $item->setHostInContent('https://example.com');

        $this->assertStringContainsString(
            'href="https://other.example.com/page.html"',
            $item->getContent()
        );
    }
}
