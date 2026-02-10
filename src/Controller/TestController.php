<?php

namespace OpenXE\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestController {

    #[Route('/luft')]
    public function index() : Response {
        return new Response('Luft');
    }
}