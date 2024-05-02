<?php

namespace Xentral\Modules\SystemNotification;

use Xentral\Core\DependencyInjection\ContainerInterface;
use Xentral\Modules\SystemNotification\Gateway\NotificationGateway;
use Xentral\Modules\SystemNotification\Service\NotificationService;
use Xentral\Modules\SystemNotification\Service\NotificationServiceInterface;

final class Bootstrap
{
    /**
     * @return array
     */
    public static function registerServices()
    {
        return [
            'NotificationService' => 'onInitNotificationService',
            'NotificationGateway' => 'onInitNotificationGateway',
        ];
    }

    /**
     * @param ContainerInterface $container
     *
     * @return NotificationServiceInterface
     */
    public static function onInitNotificationService(ContainerInterface $container)
    {
        return new NotificationService($container->get('Database'));
    }

    /**
     * @param ContainerInterface $container
     *
     * @return NotificationGateway
     */
    public static function onInitNotificationGateway(ContainerInterface $container)
    {
        return new NotificationGateway($container->get('Database'));
    }
}
