<?php
/**
 * Created by PhpStorm.
 * User: windischmannbr
 * Date: 07.01.19
 * Time: 12:15
 */

namespace Xentral\Modules\Voucher;

use Xentral\Core\DependencyInjection\ContainerInterface;
use Xentral\Modules\Voucher\Gateway\VoucherGateway;
use Xentral\Modules\Voucher\Service\VoucherService;
use Xentral\Modules\Voucher\Service\VoucherServiceInterface;

final class Bootstrap
{
    /**
     * @return array
     */
    public static function registerServices()
    {
        return [
            'VoucherGateway' => 'onInitVoucherGateway',
        ];
    }

    /**
     * @param ContainerInterface $container
     *
     * @return VoucherGateway
     */
    public static function onInitVoucherGateway(ContainerInterface $container)
    {
        return new VoucherGateway($container->get('Database'), $container->get('LegacyApplication'));
    }

}