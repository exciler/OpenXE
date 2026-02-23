<?php

namespace OpenXE\Controller;

use Config;
use erpooSystem;
use Player;
use Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LegacyController extends AbstractController
{
    public function __construct(
        private readonly erpooSystem $erpooSystem,
        private readonly Session $session,
        private readonly Player $player
    ) {}


    #[Route('/index.php')]
    public function index(Request $request, \TemplateParser $tpl): Response
    {
        @session_start();

        $this->erpooSystem->Init();
        $this->session->Check();

        $playerResult = $this->player->Run();

        if ($playerResult instanceof Response) {
            return $playerResult;
        } elseif (is_array($playerResult)) {
            return $this->render(
                $playerResult['_template'] ?? 'legacy/page.html.twig',
                $playerResult);
        }

        return $this->render('legacy/index.html.twig', [
            'page' => $playerResult
        ]);
    }
}
