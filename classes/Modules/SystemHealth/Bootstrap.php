<?php
namespace Xentral\Modules\SystemHealth;

use Xentral\Core\DependencyInjection\ContainerInterface;
use Xentral\Modules\SystemHealth\Gateway\SystemHealthGateway;
use Xentral\Modules\SystemHealth\Service\SystemHealthService;
use Xentral\Modules\SystemHealth\Service\SystemHealthServiceInterface;

final class Bootstrap
{
    /**
     * @return array
     */
    public static function registerServices()
    {
        return [
            'SystemHealthService' => 'onInitSystemHealthService',
            'SystemHealthGateway' => 'onInitSystemHealthGateway',
        ];
    }

    /**
     * @param ContainerInterface $container
     *
     * @return SystemHealthGateway
     */
    public static function onInitSystemHealthGateway(ContainerInterface $container)
    {
        return new SystemHealthGateway($container->get('Database'));
    }

    /**
     * @param ContainerInterface $container
     *
     * @return SystemHealthService
     */
    public static function onInitSystemHealthService(ContainerInterface $container)
    {
        return new SystemHealthService(
            $container->get('Database'),
            $container->get('SystemHealthGateway'),
            $container->get('NotificationService')
        );
    }

}