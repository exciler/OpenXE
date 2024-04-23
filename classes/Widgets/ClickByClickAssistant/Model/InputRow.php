<?php

namespace Xentral\Widgets\ClickByClickAssistant\Model;

class InputRow implements \JsonSerializable
{
    public function __construct(
        public int $id,
        public ?array $inputs = null,
        public ?array $surveyButtons = null,
        public ?Link $link = null
    )
    {
    }

    public function jsonSerialize(): mixed
    {
        return (array) $this;
    }
}