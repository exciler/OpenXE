<?php

namespace OpenXE;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use TemplateParser;

readonly class TranslationListener implements EventSubscriberInterface {

    public function __construct(
        private TemplateParser $tpl,
    ) {
    }
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest())
            return;

        $content = $event->getResponse()->getContent();
        $content = $this->tpl->ParseVariables($content);
        $content = $this->tpl->ParseTranslation($content);
        $event->getResponse()->setContent($content);
    }

    public static function getSubscribedEvents() : array
    {
        return [
            ResponseEvent::class => ['onKernelResponse', 128],
        ];
    }
}