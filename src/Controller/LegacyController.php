<?php

namespace OpenXE\Controller;

use Config;
use erpooSystem;
use Player;
use Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LegacyController extends AbstractController
{
    #[Route('/index.php')]
    public function index(
        #[Autowire(service: 'service_container')]ContainerInterface $serviceContainer,
        Config $config,
    ): Response
    {
        //error_reporting(E_ERROR);
        @session_start();

        $app = new erpooSystem($config, serviceContainer: $serviceContainer);

        $session = new Session();
        $session->Check($app);

        $player = new Player();
        return $player->Run($session);
    }
}
