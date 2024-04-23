<?php

namespace Xentral\Widgets\ClickByClickAssistant\Model;

class Media implements \JsonSerializable
{
    public function __construct(
        public string $type,
        public string $link
    )
    {
    }
    public function jsonSerialize(): mixed
    {
        return (array) $this;
    }

}