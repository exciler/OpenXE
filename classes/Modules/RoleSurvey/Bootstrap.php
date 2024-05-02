<?php
declare(strict_types=1);

namespace Xentral\Modules\RoleSurvey;


use Xentral\Core\DependencyInjection\ContainerInterface;

final class Bootstrap
{
    /**
     * @return array
     */
    public static function registerServices(): array
    {
        return [
            'SurveyGateway'         => 'onInitSurveyGateway',
            'SurveyService'         => 'onInitSurveyService',
        ];
    }

    /**
     * @param ContainerInterface $container
     *
     * @return SurveyService
     */
    public static function onInitSurveyService(ContainerInterface $container): SurveyService
    {
        return new SurveyService($container->get('Database'), $container->get('SurveyGateway'));
    }

    /**
     * @param ContainerInterface $container
     *
     * @return SurveyGateway
     */
    public static function onInitSurveyGateway(ContainerInterface $container): SurveyGateway
    {
        return new SurveyGateway($container->get('Database'));
    }
}
