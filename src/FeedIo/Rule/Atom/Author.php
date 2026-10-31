<?php

declare(strict_types=1);

namespace FeedIo\Rule\Atom;

use DomDocument;
use DOMElement;
use FeedIo\Feed\NodeInterface;
use FeedIo\RuleAbstract;

class Author extends RuleAbstract
{
    public const NODE_NAME = 'author';

    public function setProperty(NodeInterface $node, DOMElement $element): void
    {
        $author = $node->newAuthor();
        $author->setName($this->getChildValue($element, 'name'));
        $author->setUri($this->getChildValue($element, 'uri'));
        $author->setEmail($this->getChildValue($element, 'email'));
        $node->addAuthor($author);
    }

    protected function hasValue(NodeInterface $node): bool
    {
        foreach ($node->getAuthors() as $author) {
            return true;
        }

        return false;
    }

    protected function addElement(DomDocument $document, DOMElement $rootElement, NodeInterface $node): void
    {
        foreach ($node->getAuthors() as $author) {
            $element = $document->createElement(static::NODE_NAME);
            $this->appendNonEmptyChild($document, $element, 'name', $author->getName());
            $this->appendNonEmptyChild($document, $element, 'uri', $author->getUri());
            $this->appendNonEmptyChild($document, $element, 'email', $author->getEmail());

            $rootElement->appendChild($element);
        }
    }
}
