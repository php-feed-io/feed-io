<?php

declare(strict_types=1);

namespace FeedIo\Rule\Atom;

use DomDocument;
use DOMElement;
use FeedIo\Feed\ItemInterface;
use FeedIo\Feed\NodeInterface;
use FeedIo\FeedInterface;
use FeedIo\RuleAbstract;
use FeedIo\RuleSet;

class LinkNode extends RuleAbstract
{
    public const NODE_NAME = 'link';

    protected RuleSet $ruleSet;

    public function __construct(?string $nodeName = null)
    {
        parent::__construct($nodeName);
        $mediaRule = new Media();
        $mediaRule->setUrlAttributeName('href');
        $this->ruleSet = new RuleSet(new Link('related'));
        $this->ruleSet->add($mediaRule, ['media', 'enclosure']);
    }

    public function setProperty(NodeInterface $node, DOMElement $element): void
    {
        if ($element->hasAttribute('rel')) {
            $this->ruleSet->get($element->getAttribute('rel'))->setProperty($node, $element);
        } else {
            $this->ruleSet->getDefault()->setProperty($node, $element);
        }
    }

    protected function hasValue(NodeInterface $node): bool
    {
        return true;
    }

    protected function addElement(DomDocument $document, DOMElement $rootElement, NodeInterface $node): void
    {
        if ($node instanceof ItemInterface && $node->hasMedia()) {
            $this->ruleSet->get('media')->apply($document, $rootElement, $node);
        }

        if ($node instanceof FeedInterface && $this->hasFeedLinks($node)) {
            $this->addFeedLinks($document, $rootElement, $node);
            return;
        }

        $this->ruleSet->getDefault()->apply($document, $rootElement, $node);
    }

    protected function hasFeedLinks(FeedInterface $feed): bool
    {
        foreach ($feed->getLinks() as $_) {
            return true;
        }

        return false;
    }

    protected function addFeedLinks(DomDocument $document, DOMElement $rootElement, FeedInterface $feed): void
    {
        foreach ($feed->getLinks() as $feedLink) {
            $element = $document->createElement(static::NODE_NAME);
            $element->setAttribute('rel', $feedLink->getRel());
            $element->setAttribute('href', $feedLink->getHref());
            if ($feedLink->getType() !== null) {
                $element->setAttribute('type', $feedLink->getType());
            }
            $rootElement->appendChild($element);
        }
    }
}
