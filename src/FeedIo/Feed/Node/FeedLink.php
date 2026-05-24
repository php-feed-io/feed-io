<?php

declare(strict_types=1);

namespace FeedIo\Feed\Node;

class FeedLink
{
    public function __construct(
        protected string $rel,
        protected string $href,
        protected ?string $type = null,
    ) {
    }

    public function getRel(): string
    {
        return $this->rel;
    }

    public function getHref(): string
    {
        return $this->href;
    }

    public function getType(): ?string
    {
        return $this->type;
    }
}
