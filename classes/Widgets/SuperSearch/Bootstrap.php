<?php

namespace Xentral\Widgets\SuperSearch;

final class Bootstrap
{
    /**
     * @return array
     */
    public static function registerStylesheets()
    {
        return [
            'supersearch' => [
                './classes/Widgets/SuperSearch/www/css/supersearch.css',
            ],
        ];
    }
}
