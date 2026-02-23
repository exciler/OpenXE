<?php

namespace OpenXE\Controller;

use Doctrine\ORM\EntityManagerInterface;
use erpooSystem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Xentral\Modules\User\Service\UserConfigService;

final class NavigationController extends AbstractController
{
    public function __construct(
        private readonly erpooSystem $app,
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $em,
    ) {}

    public function sidebar(\Page $page, \User $user, \TemplateParser $tpl): Response
    {
        include dirname(__FILE__).'/../../version.php';

        $activeModule = $this->requestStack->getMainRequest()->query->getAlnum('module');
        $activeAction = $this->requestStack->getMainRequest()->query->getAlnum('action');
        $navigation = $page->CreateNavigation($this->app->erp->Navigation(), true, $activeModule, $activeAction);

        $appointmentCount = $this->em->getConnection()->fetchOne(
            "SELECT COUNT(ke.id)
               FROM kalender_event AS ke
               LEFT JOIN kalender_user AS ku ON ku.event=ke.id
               WHERE DATE_FORMAT(ke.von,'%Y-%m-%d')=DATE_FORMAT(NOW(),'%Y-%m-%d')
                 AND (ke.adresse=:addressid OR ke.adresseintern=:addressid OR ku.userid=:userid)",
            ['addressid' => $user->GetAdresse(), 'userid' => $user->GetID()]) ?? 0;

        $offene_tickets = $this->app->erp->AnzahlOffeneTickets(false);
        $offene_tickets_user = $this->app->erp->AnzahlOffeneTickets(true);

        $possibleUserItems = [
            'Tickets' => [
                'link' => 'index.php?module=ticket&action=list',
                'counter' => ($offene_tickets+$offene_tickets_user > 0)?$offene_tickets_user."/".$offene_tickets:""
            ],
            'Aufgaben' => [
                'link' => 'index.php?module=aufgaben&action=list',
                'counter' => $this->app->erp->AnzahlOffeneAufgaben()
            ],
            'Kalender' => [
                'link' => 'index.php?module=kalender&action=list',
                'counter' => $appointmentCount
            ]
        ];

        $userItems = [];
        foreach($possibleUserItems as $title => $data){
            $item = [];
            $item['link'] = $data['link'] .'&top=' .base64_encode($title);
            $item['title'] = $tpl->pruefeuebersetzung($title);
            $item['counter'] = $data['counter'] ?? 0;
            $item['active'] = strtolower($title) === strtolower($activeModule);
            $userItems[] = $item;
        }

        // Creates main navigation steps
        $navItems = [];
        foreach($navigation as $key => $listitem){
            $item = [];
            if(!empty($listitem)){
                $item['active'] = $listitem['active'];
                $item['title'] = $listitem['title'];
                $item['children'] = $listitem['sec'];
            }
            $navItems[] = $item;
        }

        $possibleFixedItems = [];
        $fixedItems = [];
        foreach($possibleFixedItems as $title => $link){
            $item = [];
            $item['active'] = strtolower($title) === strtolower($activeModule);
            $item['title'] = $tpl->pruefeuebersetzung($title);

            if(strpos($link, 'index.php?') !== false){
                $item['isAnchor'] = true;
                $item['href'] = $link .'&top=' .base64_encode($title);
            } elseif(strpos($link, 'id="') !== false) {
                $item['isAnchor'] = false;
                $item['id'] = $link;
            } else {
                continue;
            }
            $fixedItems[] = $item;
        }

        if($userId = $user->GetID()) {
            /** @var UserConfigService $userConfig */
            $userConfig = $this->app->Container->get('UserConfigService');
            $sidebarCollapsed = $userConfig->tryGet('sidebar_collapsed', $userId);
        }

        return $this->render('navigation/_sidebar.html.twig', [
            'version' => $version_revision ?? null,
            'sidebar_collapsed' => $sidebarCollapsed ?? false,
            'fixedItems' => $fixedItems,
            'navItems' => $navigation,
            'userItems' => $userItems,
        ]);
    }
}
