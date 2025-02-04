<?php

namespace Xentral\Modules\Onlineshop\Data;

class ArticleExportResult {
    public int $articleId;
    public bool $success = false;
    public ?string $message;
    public ?string $extArticleId;
}