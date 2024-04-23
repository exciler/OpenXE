<?php

namespace Xentral\Widgets\ClickByClickAssistant\Model;

class Page implements \JsonSerializable
{
    public function __construct(
        public string $type,
        public string $headline,
        public array $ctaButtons,
        public array $dataRequiredForSubmit = [],
        public ?string $subHeadline = null,
        public ?string $text = null,
        public ?string $icon = null,
        public ?Media $headerMedia = null,
        public ?Link $link = null,
        public ?string $submitType = null,
        public ?string $submitUrl = null,
        public ?array $form = null,
        public ?string $errorMsg = null
    )
    {
    }

    public function jsonSerialize(): mixed
    {
        return (array)$this;
    }
}