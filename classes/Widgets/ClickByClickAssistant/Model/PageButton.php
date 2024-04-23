<?php

namespace Xentral\Widgets\ClickByClickAssistant\Model;

class PageButton implements \JsonSerializable
{
    public function __construct(
        public string $action,
        public string $title,
        public ?string $link = null
    )
    {
    }

    public function jsonSerialize(): mixed
    {
        return (array) $this;
    }
}