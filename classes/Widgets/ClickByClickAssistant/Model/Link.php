<?php

namespace Xentral\Widgets\ClickByClickAssistant\Model;

class Link implements \JsonSerializable
{
    public function __construct(
        public string $link,
        public string $title
    )
    {
    }

    public function jsonSerialize(): mixed
    {
        return (array) $this;
    }
}