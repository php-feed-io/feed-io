<?php

declare(strict_types=1);

namespace FeedIo\Rule;

use FeedIo\Feed\ItemInterface;
use FeedIo\Feed\NodeInterface;
use FeedIo\RuleAbstract;

class Author extends RuleAbstract
{
    public const NODE_NAME = 'author';

    /**
     * Sets the accurate $item property according to the DomElement content
     *
     * @param  NodeInterface $node
     * @param  \DOMElement   $element
     */
    public function setProperty(NodeInterface $node, \DOMElement $element): void
    {
        // Some feeds carry more than one <author>/<dc:creator> element per item.
        // This rule fires once per element, so keep the first author and ignore
        // the rest rather than let the last one silently overwrite it.
        if ($node instanceof ItemInterface && $node->getAuthor() === null) {
            $author = $node->newAuthor();
            $author->setName($element->nodeValue);
            $node->setAuthor($author);
        }
    }

    /**
     * Tells if the node contains the expected value
     *
     * @param NodeInterface $node
     * @return bool
     */
    protected function hasValue(NodeInterface $node): bool
    {
        return $node instanceof ItemInterface && !! $node->getAuthor();
    }

    /**
     * Creates and adds the element(s) to the docuement's rootElement
     *
     * @param \DomDocument $document
     * @param \DOMElement $rootElement
     * @param NodeInterface $node
     */
    protected function addElement(\DomDocument $document, \DOMElement $rootElement, NodeInterface $node): void
    {
        $rootElement->appendChild($document->createElement($this->getNodeName(), $node->getAuthor()->getName()));
    }
}
