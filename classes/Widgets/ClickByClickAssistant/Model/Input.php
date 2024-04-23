<?php

namespace Xentral\Widgets\ClickByClickAssistant\Model;

class Input implements \JsonSerializable
{
    public function __construct(
        public string $type,
        public string $name,
        public string $label,
        public ?string $value = null,
        public ?bool $validation = true,
        public ?string $customErrorMsg = null,
        public ?string $connectedTo = null,
        public ?array $options = null,
    )
    {
    }

    public function jsonSerialize(): mixed
    {
        return (array) $this;
    }
}