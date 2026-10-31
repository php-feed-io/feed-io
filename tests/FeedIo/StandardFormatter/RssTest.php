<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 13/12/14
 * Time: 18:49
 */

namespace FeedIo\StandardFormatter;

use FeedIo\Feed;
use FeedIo\Feed\Item;
use FeedIo\Formatter\XmlFormatter;
use FeedIo\Rule\DateTimeBuilder;
use FeedIo\Standard\Rss;

class RssTest extends FormatterTestAbstract
{
    public const SAMPLE_FILE = 'rss/expected-rss.xml';

    /**
    * @return \FeedIo\StandardAbstract
     */
    protected function newStandard()
    {
        return new Rss(
            new DateTimeBuilder()
        );
    }

    public function testMultiAuthorItemProducesOnlyOneCoreAuthorElement()
    {
        $item = new Item();
        $item->setAuthors([
            (new Item\Author())->setName('John Doe'),
            (new Item\Author())->setName('Jane Roe'),
        ]);
        $feed = new Feed();
        $feed->add($item);

        $document = (new XmlFormatter($this->standard))->toDom($feed);
        $authors = $document->getElementsByTagName('author');

        $this->assertSame(1, $authors->length);
        $this->assertSame('Jane Roe', $authors->item(0)->nodeValue);
    }
}
