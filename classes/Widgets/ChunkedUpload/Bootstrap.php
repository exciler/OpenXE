<?php

namespace Xentral\Widgets\ChunkedUpload;

final class Bootstrap
{
    /**
     * @return array
     */
    public static function registerServices()
    {
        return [
            'ChunkedUploadRequestHandler' => 'onInitChunkedUploadRequestHandler',
        ];
    }

    /**
     * @return ChunkedUploadRequestHandler
     */
    public static function onInitChunkedUploadRequestHandler()
    {
        return new ChunkedUploadRequestHandler();
    }
}
