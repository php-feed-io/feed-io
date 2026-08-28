<?php

declare(strict_types=1);

namespace FeedIo\Feed;

use ArrayIterator;
use DateTime;
use Generator;
use FeedIo\Feed\Item\Author;
use FeedIo\Feed\Item\AuthorInterface;
use FeedIo\Feed\Node\Category;
use FeedIo\Feed\Node\CategoryInterface;

class Node implements NodeInterface, ElementsAwareInterface, ArrayableInterface
{
    use ElementsAwareTrait;

    protected ArrayIterator $categories;

    protected ?AuthorInterface $author = null;

    protected ?DateTime $lastModified = null;

    protected ?string $title = null;

    protected ?string $publicId = null;

    protected bool $publicIdIsPermaLink = true;

    protected ?string $link = null;

    protected ?string $host = null;

    protected ?string $linkForAnalysis = null;

    public function __construct()
    {
        $this->initElements();
        $this->categories = new ArrayIterator();
    }

    public function set(string $name, ?string $value = null): NodeInterface
    {
        $element = $this->newElement();

        $element->setName($name);
        $element->setValue($value);

        $this->addElement($element);

        return $this;
    }

    public function getAuthor(): ?AuthorInterface
    {
        return $this->author;
    }

    public function setAuthor(?AuthorInterface $author = null): NodeInterface
    {
        $this->author = $author;

        return $this;
    }

    public function newAuthor(): AuthorInterface
    {
        return new Author();
    }

    public function getCategories(): iterable
    {
        return $this->categories;
    }

    public function getCategoriesGenerator(): Generator
    {
        foreach ($this->categories as $category) {
            yield $category->getlabel();
        }
    }

    public function addCategory(CategoryInterface $category): NodeInterface
    {
        $this->categories->append($category);

        return $this;
    }

    public function newCategory(): CategoryInterface
    {
        return new Category();
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title = null): NodeInterface
    {
        $this->title = $title;

        return $this;
    }

    public function getPublicId(): ?string
    {
        return $this->publicId;
    }

    public function setPublicId(?string $publicId = null, bool $isPermaLink = true): NodeInterface
    {
        $this->publicId = $publicId;
        $this->publicIdIsPermaLink = $isPermaLink;

        return $this;
    }

    public function getPublicIdIsPermaLink(): bool
    {
        return $this->publicIdIsPermaLink;
    }

    public function getLastModified(): ?DateTime
    {
        return $this->lastModified;
    }

    public function setLastModified(?DateTime $lastModified = null): NodeInterface
    {
        $this->lastModified = $lastModified;

        return $this;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function getLinkForAnalysis(): ?string
    {
        return $this->linkForAnalysis;
    }

    public function setLink(?string $link = null): NodeInterface
    {
        $this->link = $link;
        $this->setHost($link);
        $this->setLinkForAnalysis($link);

        return $this;
    }

    public function setLinkForAnalysis(?string $link = null): NodeInterface
    {
        $this->linkForAnalysis = $link;

        return $this;
    }

    protected function setHost(?string $link = null): void
    {
        if (!is_null($link)) {
            $this->host = '//' . parse_url($link, PHP_URL_HOST);
        }
    }

    public function setHostInContent(?string $host = null): NodeInterface
    {
        if (is_null($host)) {
            return $this;
        }
        // Replaced links like href="/aaa/bbb.xxx"
        $pattern = '(<\s*[^>]*)(href=|src=)(.?)(\/[^\/])(?!(.(?!<code))*<\/code>)';
        $this->pregReplaceInProperty('content', $pattern, '\1\2\3'.$host.'\4');
        $this->pregReplaceInProperty('description', $pattern, '\1\2\3'.$host.'\4');

        $itemFullLink = $this->getLinkForAnalysis();
        $itemLink = implode("/", array_slice(explode("/", $itemFullLink ?? ''), 0, -1))."/";

        // Replaced links like href="#aaa/bbb.xxx"
        $pattern = '(<\s*[^>]*)(href=|src=)(.?)(#)(?!(.(?!<code))*<\/code>)';
        $this->pregReplaceInProperty('content', $pattern, '\1\2\3'.$itemFullLink.'\4');
        $this->pregReplaceInProperty('description', $pattern, '\1\2\3'.$itemFullLink.'\4');

        // Replaced links like href="../aaa/bbb.xxx" or href="./aaa/bbb.xxx"
        if ($itemFullLink !== null) {
            $this->resolveRelativePathLinksInContent($itemFullLink);
        }

        // Replaced links like href="aaa/bbb.xxx"
        $pattern = '(<\s*[^>]*)(href=|src=)(.?)(\w+\b)(?![:])(?!(.(?!<code))*<\/code>)';
        $this->pregReplaceInProperty('content', $pattern, '\1\2\3'.$itemLink.'\4');
        $this->pregReplaceInProperty('description', $pattern, '\1\2\3'.$itemLink.'\4');

        return $this;
    }

    private function resolveRelativePathLinksInContent(string $itemFullLink): void
    {
        $parsed = parse_url($itemFullLink);
        if (!is_array($parsed) || !isset($parsed['scheme'], $parsed['host'])) {
            return;
        }

        $scheme = $parsed['scheme'];
        $host = $parsed['host'];
        if (str_contains($host, ':') && !str_starts_with($host, '[')) {
            // IPv6 hosts need brackets when we build the authority part of the URL.
            $host = '[' . $host . ']';
        }
        $authority = $host;
        if (isset($parsed['port'])) {
            // Preserve an explicit port in the resolved absolute URL.
            $authority .= ':' . $parsed['port'];
        }
        if (isset($parsed['user'])) {
            // Preserve user info, including an optional password, in the authority.
            $userinfo = $parsed['user'];
            if (isset($parsed['pass'])) {
                $userinfo .= ':' . $parsed['pass'];
            }
            $authority = $userinfo . '@' . $authority;
        }
        $basePath = $parsed['path'] ?? '/';
        // Determine the directory of the item's link so relative paths resolve against it.
        $baseDir = substr($basePath, 0, strrpos($basePath, '/') + 1) ?: '/';

        $resolver = function (array $matches) use ($scheme, $authority, $baseDir): string {
            $href = $matches[3];

            // Keep root-relative, hash-only, query-only, protocol-relative, and absolute URLs unchanged.
            if ($href === '' || str_starts_with($href, '/') || str_starts_with($href, '#') || str_starts_with($href, '?') || str_starts_with($href, '//')) {
                return $matches[0];
            }

            // Leave scheme-based URIs like magnet: or mailto: untouched.
            if (preg_match('~^[a-z][a-z0-9+.-]*:~i', $href) === 1) {
                return $matches[0];
            }

            // Resolve the relative path against the item's base directory.
            $merged = $baseDir . $href;
            $segments = [];
            foreach (explode('/', $merged) as $segment) {
                if ($segment === '..') {
                    // Move up one directory when possible.
                    if (!empty($segments)) {
                        array_pop($segments);
                    }
                } elseif ($segment !== '.') {
                    // Drop current-directory markers and keep the remaining path segments.
                    $segments[] = $segment;
                }
            }
            $path = implode('/', $segments) ?: '/';
            if (!str_starts_with($path, '/')) {
                $path = '/' . $path;
            }

            // Rebuild the link as an absolute URL using the original scheme and authority.
            return $matches[1] . $matches[2] . $scheme . '://' . $authority . $path . $matches[2];
        };

        foreach (['content', 'description'] as $property) {
            $this->replaceRelativeLinksInProperty($property, $resolver);
        }
    }

    private function replaceRelativeLinksInProperty(string $property, callable $resolver): void
    {
        if (!property_exists($this, $property) || is_null($this->{$property})) {
            return;
        }

        $pattern = '~((?:href|src)=)(["\'])([^"\'<>\s]+)\2~i';
        $segments = preg_split('/(<\/?code>)/i', $this->{$property}, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($segments === false) {
            return;
        }

        $result = '';
        $insideCode = false;
        foreach ($segments as $segment) {
            if (preg_match('/^<\/?code>$/i', $segment)) {
                $insideCode = !$insideCode;
                $result .= $segment;
                continue;
            }

            if ($insideCode) {
                $result .= $segment;
                continue;
            }

            $result .= preg_replace_callback($pattern, $resolver, $segment) ?? $segment;
        }

        $this->{$property} = $result;
    }

    public function pregReplaceInProperty(string $property, string $pattern, string $replacement): void
    {
        if (property_exists($this, $property) && !is_null($this->{$property})) {
            $this->{$property} = preg_replace('~'.$pattern.'~', $replacement, $this->{$property}) ?? $this->{$property};
        }
    }

    public function getHostFromLink(): ?string
    {
        if (is_null($this->getLinkForAnalysis())) {
            return null;
        }
        $partsUrl = parse_url($this->getLinkForAnalysis());
        if (!is_array($partsUrl) || !isset($partsUrl['scheme'], $partsUrl['host'])) {
            return null;
        }

        return $partsUrl['scheme']."://".$partsUrl['host'];
    }

    public function getValue(string $name): ?string
    {
        foreach ($this->getElementIterator($name) as $element) {
            return $element->getValue();
        }

        return null;
    }

    public function toArray(): array
    {
        $properties = get_object_vars($this);
        $properties['elements'] = iterator_to_array($this->getElementsGenerator());
        $properties['categories'] = iterator_to_array($this->getCategoriesGenerator());

        foreach ($properties as $name => $property) {
            if ($property instanceof \DateTime) {
                $properties[$name] = $property->format(\DateTime::ATOM);
            } elseif ($property instanceof \ArrayIterator) {
                $properties[$name] = [];
                foreach ($property as $entry) {
                    if ($entry instanceof ArrayableInterface) {
                        $entry = $entry->toArray();
                    }
                    $properties[$name] []= $entry;
                }
            } elseif ($property instanceof ArrayableInterface) {
                $properties[$name] = $property->toArray();
            }
        }

        return $properties;
    }
}
