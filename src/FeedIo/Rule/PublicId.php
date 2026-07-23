<?php

declare(strict_types=1);

namespace FeedIo\Rule;

use FeedIo\Feed\NodeInterface;
use FeedIo\RuleAbstract;

class PublicId extends RuleAbstract
{
    public const NODE_NAME = 'guid';

    /**
     * @param  NodeInterface $node
     * @param  \DOMElement   $element
     */
    public function setProperty(NodeInterface $node, \DOMElement $element): void
    {
        $isPermaLink = $element->getAttribute('isPermaLink') !== 'false';
        $node->setPublicId($element->nodeValue, $isPermaLink);
        if ($element->nodeName === 'guid'
        && $isPermaLink
        && $node->getLink() === null) {
            $node->setLink($element->nodeValue);
        }
    }

    /**
     * @inheritDoc
     */
    protected function hasValue(NodeInterface $node): bool
    {
        return !! $node->getPublicId();
    }

    /**
     * @inheritDoc
     */
    protected function addElement(\DomDocument $document, \DOMElement $rootElement, NodeInterface $node): void
    {
        $element = $document->createElement($this->getNodeName(), $node->getPublicId());
        if (!$node->getPublicIdIsPermaLink()) {
            $element->setAttribute('isPermaLink', 'false');
        }
        $rootElement->appendChild($element);
    }
}
