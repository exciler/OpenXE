<?php
/*
**** COPYRIGHT & LICENSE NOTICE *** DO NOT REMOVE ****
* 
* Xentral (c) Xentral ERP Sorftware GmbH, Fuggerstrasse 11, D-86150 Augsburg, * Germany 2019
*
* This file is licensed under the Embedded Projects General Public License *Version 3.1. 
*
* You should have received a copy of this license from your vendor and/or *along with this file; If not, please visit www.wawision.de/Lizenzhinweis 
* to obtain the text of the corresponding license version.  
*
**** END OF COPYRIGHT & LICENSE NOTICE *** DO NOT REMOVE ****
*/

?>
<?php

use Symfony\Component\HttpFoundation\Response;
use Xentral\Components\Barcode\BarcodeFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Xentral\Components\Http\Session\Session;
use Xentral\Components\Http\Session\SessionHandler;
use Xentral\Components\Mailer\Data\EmailRecipient;
use Xentral\Modules\DownloadSpooler\DownloadSpoolerGateway;
use Xentral\Modules\DownloadSpooler\DownloadSpoolerService;
use Xentral\Modules\DownloadSpooler\Exception\DownloadSpoolerExceptionInterface;
use Xentral\Modules\GoogleApi\Exception\AuthorizationExpiredException;
use Xentral\Modules\GoogleApi\Exception\GoogleAccountNotFoundException;
use Xentral\Modules\GoogleApi\GoogleScope;
use Xentral\Modules\GoogleApi\Service\GoogleAccountGateway;
use Xentral\Modules\GoogleApi\Service\GoogleAccountService;
use Xentral\Modules\GoogleApi\Service\GoogleAuthorizationService;
use Xentral\Modules\GoogleApi\Service\GoogleCredentialsService;
use Xentral\Modules\GoogleCalendar\Client\GoogleCalendarClientFactory;
use Xentral\Modules\GoogleCalendar\Exception\GoogleCalendarSyncException;
use Xentral\Modules\GoogleCalendar\Service\GoogleCalendarSynchronizer;
use Xentral\Modules\SystemHealth\Service\SystemHealthService;
use Xentral\Modules\SystemMailer\SystemMailer;
use Xentral\Modules\SystemNotification\Service\NotificationService;
use Xentral\Modules\TOTPLogin\TOTPLoginService;
use Xentral\Modules\RoleSurvey\SurveyGateway;
use Xentral\Modules\RoleSurvey\SurveyService;

class Welcome
{
    var $_meineapps;

    const MODULE_NAME = 'Welcome';

    public $javascript = [
        './classes/Modules/Calendar/www/js/fullcalendar.js',
        './classes/Modules/Calendar/www/js/calendar.js',
        './classes/Modules/Calendar/www/js/calendargroups.js',
    ];

    /**
     * Welcome constructor.
     *
     * @param Application $app
     * @param bool $intern
     */
    public function __construct(private readonly Application $app, bool $intern = false)
    {
        $this->_meineapps = null;
        if ($intern) {
            return;
        }
        $this->app->ActionHandlerInit($this);

        $this->app->ActionHandler("login", "WelcomeLogin");
        $this->app->ActionHandler("main", "WelcomeMain");
        $this->app->ActionHandler("poll", "WelcomePoll");
        $this->app->ActionHandler("list", "TermineList");
        $this->app->ActionHandler("cronjob", "WelcomeCronjob");
        $this->app->ActionHandler("cronjob2", "WelcomeCronjob2");
        $this->app->ActionHandler("adapterbox", "WelcomeAdapterbox");
        $this->app->ActionHandler("help", "WelcomeHelp");
        $this->app->ActionHandler("info", "WelcomeInfo");
        $this->app->ActionHandler("icons", "WelcomeIcons");
        $this->app->ActionHandler("vorgang", "VorgangAnlegen");
        $this->app->ActionHandler("removevorgang", "VorgangEntfernen");
        $this->app->ActionHandler("editvorgang", "VorgangEdit");
        $this->app->ActionHandler("logout", "WelcomeLogout");
        $this->app->ActionHandler("start", "WelcomeStart");
        $this->app->ActionHandler("list", "WelcomeStart");
        $this->app->ActionHandler("settings", "WelcomeSettings");
        $this->app->ActionHandler("mobileapps", "WelcomeMobileApps");
        $this->app->ActionHandler("spooler", "WelcomeSpooler");
        $this->app->ActionHandler("redirect", "WelcomeRedirect");
        $this->app->ActionHandler("startseite", "WelcomeStartseite");

        $this->app->ActionHandler("addnote", "WelcomeAddNote");
        $this->app->ActionHandler("addpinwand", "WelcomeAddPinwand");
        $this->app->ActionHandler("movenote", "WelcomeMoveNote");
        $this->app->ActionHandler("oknote", "WelcomeOkNote");
        $this->app->ActionHandler("delnote", "WelcomeDelNote");
        $this->app->ActionHandler("pinwand", "WelcomePinwand");

        $this->app->ActionHandler("css", "WelcomeCss");
        $this->app->ActionHandler("logo", "WelcomeLogo");
        $this->app->ActionHandler("unlock", "WelcomeUnlock");
        $this->app->ActionHandler("direktzugriff", "WelcomeDirektzugriff");
        $this->app->ActionHandler("meineapps", "WelcomeMeineApps");
        $this->app->ActionHandler("passwortvergessen", "Welcomepasswortvergessen");
        $this->app->ActionHandler("changelog", "WelcomeChangelog");
        $this->app->ActionHandler("survey", "WelcomeSurvey");
        $this->app->NoHooks = ['poll'];

        $this->app->DefaultActionHandler("login");
        $action = $this->app->Request->query->getAlnum('action');
        if (
            !in_array(
                $action,
                [
                    'login',
                    'logout',
                    'poll',
                    'css',
                    'logo',
                    'unlock',
                    'icons',
                    'cronjob',
                    'cronjob2',
                    'addnote',
                    'addpinwand',
                    'movenote',
                    'oknote',
                    'delnote',
                    'adapterbox',
                    'spooler',
                    'removevorgang',
                    'editvorgang',
                    'vorgang',
                    'survey',
                ],
            )
        ) {
            $this->GetMeineApps();
            $this->app->erp->RegisterMenuHook('startseite', 'MenuHook', $this);
        }
        $this->app->ModuleScriptCache->IncludeJavascriptFiles(
            'welcome',
            ['./classes/Modules/TOTPLogin/www/js/totplogin.js'],
        );
        $this->app->ActionHandlerListen($app);
    }

    public function Install(): void
    {
        try {
            /** @var SurveyService $surveyService */
            $surveyService = $this->app->Container->get('SurveyService');
            $surveyService->create('xentral_role', 'welcome', 'start', true, true);
        } catch (Exception $e) {
        }
    }

    public function HandleSaveSurveyDataAjaxAction(): JsonResponse
    {
        $surveyId = $this->app->Request->request->getInt('surveyId');
        $surveyName = $this->app->Request->request->getString('surveyName');
        if (empty($surveyId)) {
            /** @var SurveyGateway $surveyGateway */
            $surveyGateway = $this->app->Container->get('SurveyGateway');
            $survey = $surveyGateway->getByName($surveyName);
            if (!empty($survey)) {
                $surveyId = (int)$survey['id'];
            }
        }
        /** @var SurveyService $surveyService */
        $surveyService = $this->app->Container->get('SurveyService');
        try {
            $surveyUserId = $surveyService->saveUserAnswer(
                $surveyId,
                $this->app->User->GetID(),
                $this->app->Request->request->all(),
            );
        } catch (Exception $e) {
            return new JsonResponse(
                ['sucess' => false, 'error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST,
            );
        }
        $response = ['success' => true, 'surveyUserId' => $surveyUserId];
        $this->app->erp->RunHook('welcome_surveysave', 3, $surveyId, $surveyUserId, $response);

        return new JsonResponse($response);
    }

    public function HandleOpenSurveyAjaxAction(): JsonResponse
    {
        $surveyId = $this->app->Request->request->getInt('surveyId');
        $surveyName = $this->app->Request->request->getString('surveyName');
        /** @var SurveyGateway $surveyGateway */
        $surveyGateway = $this->app->Container->get('SurveyGateway');
        if (empty($surveyId)) {
            $survey = $surveyGateway->getByName($surveyName);
            if (!empty($survey)) {
                $surveyId = (int)$survey['id'];
            }
        } else {
            $survey = $surveyGateway->getById($surveyId);
        }
        if (empty($survey)) {
            return new JsonResponse(
                ['sucess' => false, 'error' => 'Umfrage nicht gefunden'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $filled = $surveyGateway->getFilledSurveyByUser($surveyId, $this->app->User->GetID());
        if (!empty($filled)) {
            $filled = json_decode($filled, true);
        }

        return new JsonResponse(['success' => true, 'survey' => $survey, 'data' => $filled]);
    }

    public function WelcomeSurvey()
    {
        $cmd = $this->app->Request->query->getAlnum('cmd');
        if ($cmd === 'saveSurveyData') {
            return $this->HandleSaveSurveyDataAjaxAction();
        }
        if ($cmd === 'openSurvey') {
            return $this->HandleOpenSurveyAjaxAction();
        }
    }

    public function Welcomepasswortvergessen()
    {
        $this->app->acl->Passwortvergessen();
    }

    public function MenuHook() {}

    public function StartseiteMenu()
    {
        $module = $this->app->Request->query->getAlnum('module');

        $this->app->erp->MenuEintrag('index.php?module=welcome&action=start', 'Startseite');
        $this->app->erp->MenuEintrag('index.php?module=welcome&action=pinwand', 'Pinnwand');


        if ($module === 'aufgaben') {
            $this->app->erp->MenuEintrag('index.php?module=aufgaben&action=create', 'Neue Aufgaben');
        }

        $this->app->erp->MenuEintrag('index.php?module=aufgaben&action=list', 'Aufgaben');

        $this->app->erp->MenuEintrag('index.php?module=kalender&action=list', 'Kalender');
        $this->app->erp->MenuEintrag('index.php?module=chat&action=list', 'Chat');
        $this->app->erp->MenuEintrag('index.php?module=zeiterfassung&action=create', 'Zeiterfassung buchen');
        $this->app->erp->MenuEintrag(
            'index.php?module=zeiterfassung&action=listuser',
            'Eigene Zeiterfassung &Uuml;bersicht',
        );
        if ($this->app->Request->query->getAlnum('action') === 'changelog') {
            $this->app->erp->MenuEintrag('index.php?module=welcome&action=changelog', 'Changelog');
        }
        $this->app->erp->RunMenuHook('startseite');
    }

    /**
     * @param string $bezeichnung
     * @param string $url
     */
    public function CheckFav($bezeichnung, $url)
    {
        if ($this->app->erp->GetKonfiguration('checkFav' . md5($url))) {
            return;
        }
        $this->app->erp->SetKonfigurationValue('checkFav' . md5($url), 1);
        $user = $this->app->EntityManager
            ->getConnection()->executeQuery(
            "SELECT u.id 
        FROM `user` u 
        LEFT JOIN `userrights` ur ON u.id = ur.`user` AND ur.module = 'amazon' AND ur.action = 'list'
        WHERE NOT isnull(ur.id) OR u.type = 'admin'",
        )
            ->fetchAllAssociative();
        if (empty($user)) {
            return;
        }
        foreach ($user as $vu) {
            $u = $vu['id'];
            $eigenlinks = $this->app->EntityManager
                ->getConnection()->executeQuery(
                "SELECT uk.`value` FROM `userkonfiguration` uk WHERE `name` = 'welcome_links_eigen' AND `user` = :userid LIMIT 1",
                ['userid' => $u],
            )
                ->fetchOne();
            $index = 1;
            $check2 = null;
            $check3 = null;
            if ($eigenlinks) {
                for ($i = 1; $i <= 8; $i++) {
                    $link = $this->app->EntityManager
                        ->getConnection()->executeQuery(
                        "SELECT uk.`value`, uk.id FROM `userkonfiguration` uk WHERE `name` = :name AND `user` = :userid LIMIT 1",
                        ['name' => 'welcome_linklink' . $i, 'userid' => $u],
                    )
                        ->fetchAssociative();
                    if (empty($link)) {
                        $link = ['id' => 0, 'link' => ''];
                    }
                    if (stripos($link['value'], $url) !== false) {
                        $index = 9;
                        break;
                    }
                    if ($link['value'] != '') {
                        if ($index == $i) {
                            $index++;
                        }
                    } else {
                        $check2 = $link['id'];
                        $check3 = $this->app->EntityManager
                            ->getConnection()->executeQuery(
                            "SELECT uk.id FROM `userkonfiguration` uk WHERE `name` = :name AND `user` = :userid LIMIT 1",
                            ['name' => 'welcome_linkname' . $i, 'userid' => $u],
                        )
                            ->fetchOne();
                        break;
                    }
                }
            } else {
                $check = $this->app->EntityManager
                    ->getConnection()->executeQuery(
                    "SELECT id FROM `userkonfiguration` uk WHERE `name` = :name AND `user` = :userid LIMIT 1",
                    ['name' => 'welcome_links_eigen', 'userid' => $u],
                )
                    ->fetchOne();
                if ($check) {
                    $this->app->EntityManager->getConnection()->executeStatement(
                        "UPDATE `userkonfiguration` SET `value` = :value WHERE id = :id LIMIT 1",
                        ['value' => '1', 'id' => $check],
                    );
                } else {
                    $this->app->EntityManager->getConnection()->executeStatement(
                        "INSERT INTO `userkonfiguration` (`user`, `value`, `name`) VALUES (:userid , :value , :name)",
                        ['userid' => $u, 'value' => '1', 'name' => 'welcome_links_eigen'],
                    );
                }
                $existingId = $this->app->EntityManager->getConnection()->executeQuery(
                    "SELECT id FROM `userkonfiguration` WHERE `user` = :userid AND `name` LIKE 'welcome_linklink_%' LIMIT 1",
                    ['userid' => $u],
                )->fetchOne();

                if (!$existingId) {
                    $index = 2;
                    $this->app->EntityManager->getConnection()->executeStatement(
                        "INSERT INTO `userkonfiguration` (`user`, `value`, `name`) VALUES (:userid, :value, :name)",
                        [
                            'userid' => $u,
                            'value' => 'index.php?module=welcome&action=settings',
                            'name' => 'welcome_linklink1',
                        ],
                    );
                    $this->app->EntityManager->getConnection()->executeStatement(
                        "INSERT INTO `userkonfiguration` (`user`, `value`, `name`) VALUES (:userid, :value, :name)",
                        [
                            'userid' => $u,
                            'value' => 'Eigene Einstellungen',
                            'name' => 'welcome_linkname1',
                        ],
                    );
                }
            }
            if ($index <= 8) {
                if ($check2) {
                    $this->app->EntityManager->getConnection()->executeStatement(
                        "UPDATE `userkonfiguration` SET `value` = :value WHERE id = :id LIMIT 1",
                        ['value' => $url, 'id' => $check2],
                    );
                } else {
                    $this->app->EntityManager->getConnection()->executeStatement(
                        "INSERT INTO `userkonfiguration` (`user`, `value`, `name`) VALUES (:userid, :value, :name)",
                        ['userid' => $u, 'value' => $url, 'name' => 'welcome_linklink' . $index],
                    );
                }
                if ($check3) {
                    $this->app->EntityManager->getConnection()->executeStatement(
                        "UPDATE `userkonfiguration` SET `value` = :value WHERE id = :id LIMIT 1",
                        ['value' => $bezeichnung, 'id' => $check3],
                    );
                } else {
                    $this->app->EntityManager->getConnection()->executeStatement(
                        "INSERT INTO `userkonfiguration` (`user`, `value`, `name`) VALUES (:userid, :value, :name)",
                        ['userid' => $u, 'value' => $bezeichnung, 'name' => 'welcome_linkname' . $index],
                    );
                }
            }
        }
    }

    /**
     * @return bool|int
     */
    public function CheckRights()
    {
        if ($this->app->User->GetType() === 'admin') {
            return true;
        }
        $action = $this->app->Request->query->getAlnum('action');
        if ($action !== 'meineapps') {
            return true;
        }
        if (!$this->app->erp->RechteVorhanden('welcome', 'start')) {
            return false;
        }

        return $this->GetMeineApps();
    }

    /**
     * @return bool|int
     */
    protected function GetMeineApps()
    {
        if (is_array($this->_meineapps)) {
            return (!empty($this->_meineapps) ? count($this->_meineapps) : 0);
        }

        $anz = 0;
        /** @var Appstore $appstore */
        $appstore = $this->app->loadModule('appstore');
        $modulliste = $appstore->getAppsList();
        $modulliste = $appstore->markGetAppsWithUserRights($modulliste);

        if (!empty($modulliste['installiert'])) {
            foreach ($modulliste['installiert'] as $module) {
                if (!empty($module['my_app'])) {
                    $this->_meineapps[] = $module;
                    $anz++;
                }
            }
        }
        if ($anz) {
            return $anz;
        }

        return false;
    }

    public function WelcomeMeineApps()
    {
        $this->app->User->SetParameter('appstore_filter', 'userapps');
        $this->app->Location->execute('index.php?module=appstore&action=list');

        // Suchebegriff wurde eingegeben
        if ($this->app->Request->query->getAlnum('cmd') === 'suche') {
            return $this->GetMeineAppsSuchergebnisse();
        }

        $this->app->erp->StartseiteMenu();
        if ($this->_meineapps) {
            $cmeineapps = !empty($this->_meineapps) ? count($this->_meineapps) : 0;
            for ($i = 0; $i < $cmeineapps; $i++) {
                $modul = $this->_meineapps[$i];
                $modul['IconUrl'] = './themes/' . $this->app->Conf->WFconf['defaulttheme'] . '/images/einstellungen/' . $modul['Icon'];
                if (empty($modul['key'])) {
                    $modul['key'] = md5($modul['Bezeichnung']);
                }
                $appstore = $this->app->erp->LoadModul('appstore');
                $iconTag = $appstore->GetAppIconTagByCategory($modul['kategorie']);

                $modulHtml = sprintf(
                    '<div class="app" id="%s">' .
                    '<a href="%s"><div class="icon app-category-icon-%s" alt="%s" border="0"></div><span>%s</span></a>' .
                    '</div>',
                    'modul-' . $modul['key'],
                    $modul['Link'],
                    $iconTag,
                    $modul['Bezeichnung'],
                    $modul['Bezeichnung'],
                );
                $this->app->Tpl->Add('APPLIST', $modulHtml);
            }
        }

        $this->app->Tpl->Parse('PAGE', 'welcome_meineapps.tpl');
    }

    public function GetMeineAppsSuchergebnisse(): JsonResponse
    {
        $suchbegriff = $this->app->Request->request->getString('val');
        $modulliste = $this->_meineapps;

        $anzeigen = [];
        $ausblenden = [];
        $moduleGefunden = 0;
        /** @var Appstore $appStore */
        $appStore = $this->app->loadModule('appstore');

        foreach ($modulliste as $modul) {
            if (empty($modul['key'])) {
                $modul['key'] = md5($modul['Bezeichnung']);
            }
            $appId = 'modul-' . $modul['key'];
            if (empty($suchbegriff) || $appStore->match($modul['Bezeichnung'], $suchbegriff)) {
                $anzeigen[$appId] = true;
                $moduleGefunden++;
            } else {
                $ausblenden[$appId] = true;
            }
        }

        return new JsonResponse(['anzeigen' => $anzeigen, 'ausblenden' => $ausblenden, 'gefunden' => $moduleGefunden]);
    }


    public function WelcomePoll(): JsonResponse
    {
        if (!empty($this->app->User) && method_exists($this->app->User, 'GetID') && !$this->app->User->GetID()) {
            return new JsonResponse([['event' => 'logout']]);
        }
        $sid = $this->app->Request->query->getDigits('sid');
        $noTimeoutUserEdit = $this->app->Request->query->getBoolean('nousertimeout');

        if ($sid > 0 && !$noTimeoutUserEdit) {
            $user = $this->app->Request->query->getInt('user'); //Edit Bruno 14.12.17 reingezogen
            $smodule = $this->app->Request->query->getAlnum('smodule');
            $this->app->erp->TimeoutUseredit($smodule, $sid, $user);
        }

        $invisible = $this->app->Request->request->getBoolean('invisible');
        $cmd = $this->app->Request->query->getAlnum('cmd');
        if ($cmd === 'messages') {
            $result = $this->app->erp->UserEvent($invisible);
            if (!empty($result) && is_array($result)) {
                return new JsonResponse($result);
            }
            return new JsonResponse([]);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }


    function WelcomeDirektzugriff(): RedirectResponse
    {
        $direktzugriff = $this->app->Request->request->getAlnum('direktzugriff');


        switch ($direktzugriff) {
            case "1":
                $link = "index.php?module=adresse&action=list";
                break;
            case "11":
                $link = "index.php?module=adresse&action=list";
                break;
            case "12":
                $link = "index.php?module=artikel&action=list";
                break;
            case "13":
                $link = "index.php?module=projekt&action=list";
                break;

            case "2":
                $link = "index.php?module=angebot&action=list";
                break;
            case "21":
                $link = "index.php?module=anfrage&action=list";
                break;
            case "22":
                $link = "index.php?module=angebot&action=list";
                break;
            case "23":
                $link = "index.php?module=auftrag&action=list";
                break;

            case "3":
                $link = "index.php?module=bestellung&action=list";
                break;
            case "31":
                $link = "index.php?module=bestellung&action=list";
                break;
            case "32":
                $link = "index.php?module=lager&action=ausgehend";
                break;
            case "33":
                $link = "index.php?module=produktion&action=list";
                break;

            case "5":
                $link = "index.php?module=rechnung&action=list";
                break;

            case "8":
                $link = "index.php?module=lieferschein&action=list";
                break;
            case "81":
                $link = "index.php?module=lieferschein&action=list";
                break;
            case "82":
                $link = "index.php?module=lager&action=list";
                break;
            case "84":
                $link = "index.php?module=versanderzeugen&action=offene";
                break;
            default:
                $link = "index.php";
        }

        return new RedirectResponse($link);
    }

    function WelcomeAdapterbox()
    {
        $anzahl = null;
        $ip = $this->app->Request->query->getString('ip');
        $serial = $this->app->Request->query->getString('serial');
        $device = $this->app->Request->query->getString('device');
        if (is_numeric($ip)) {
            $ip = long2ip($ip);
        } else {
            $ip = '';
        }

        echo 'OK';
        $this->app->EntityManager->getConnection()->executeStatement(
            "DELETE FROM `adapterbox_log` WHERE `ip`=:ip OR `seriennummer`=:serial",
            ['ip' => $ip, 'serial' => $serial],
        );
        $this->app->EntityManager->getConnection()->executeStatement(
            "INSERT INTO `adapterbox_log` (`id`,`datum`,`ip`,`meldung`,`seriennummer`,`device`)
        VALUES ('', NOW(), :ip, :msg, :serial'$serial', 'device')",
            ['ip' => $ip, 'msg' => "Adapterbox connected: ($device)", $serial => $serial],
        );

        // check if there is an adapterbox

        if ($device === 'zebra'
            && ($this->app->EntityManager
                ->getConnection()->executeQuery(
                "SELECT COUNT(`id`) FROM `drucker` WHERE `art`=2 AND `anbindung`='adapterbox'",
            )
                ->fetchOne()
            ) <= 0) {
            $this->app->EntityManager->getConnection()->executeStatement(
                "INSERT INTO `drucker` (`id`,`art`,`anbindung`,`adapterboxseriennummer`,`bezeichnung`,`name`,`aktiv`,`firma`)
          VALUES ('','2','adapterbox',:serial,'Zebra','Etikettendrucker',1,1)",
                ['serial' => $serial],
            );
            $tmpid = $this->app->EntityManager->getConnection()->lastInsertId();

            $this->app->erp->FirmendatenSet("standardetikettendrucker", $tmpid);
        }

        $xml = '
      <label>
      <line x="3" y="3" size="4">Step 2 of 2</line>
      <line x="3" y="8" size="4">Connection establish</line>
      <line x="3" y="13" size="4">Server: ' . $this->app->Request->server->getString('SERVER_ADDR') . '</line>
      </label>
      ';

        if ($this->app->erp->Firmendaten('deviceenable') == '1') {
            $job = base64_encode(json_encode(['label' => base64_encode($xml), 'amount' => $anzahl]),
            );//."<amount>".$anzahl."</amount>");
            $this->app->EntityManager->getConnection()->executeStatement(
                "INSERT INTO `device_jobs` (`id`,`zeitstempel`,`deviceidsource`,`deviceiddest`,`job`,`art`) 
        VALUES ('',NOW(),'000000000',:serial,:job,'labelprinter')",
                ['serial' => $serial, 'job' => $job],
            );
        }


        // update ip
        if ($ip != '') {
            $this->app->EntityManager->getConnection()->executeStatement(
                "UPDATE `drucker` SET `adapterboxip`=:ip WHERE `adapterboxseriennummer`=:serial LIMIT 1",
                ['ip' => $ip, 'serial' => $serial],
            );
        }

        $this->app->erp->ExitWawi();
    }


    function WelcomeCronjob()
    {
        @ignore_user_abort(true);
        include dirname(dirname(__DIR__)) . '/cronjobs/starter.php';
        exit;
    }

    function WelcomeCronjob2()
    {
        @ignore_user_abort(true);
        include dirname(dirname(__DIR__)) . '/cronjobs/starter2.php';
        exit;
    }

    public function WelcomeStart()
    {
        $addtionalcspheader = ' ' . str_replace([';', '"'],
                '',
                $this->app->erp->Firmendaten('additionalcspheader')) . ' ';
        $this->app->Tpl->Add('ADDITIONALCSPHEADER', $addtionalcspheader);

        if ($this->app->erp->UserDevice() === 'smartphone') {
            $this->WelcomeStartSmartphone();
        } else {
            $this->WelcomeStartDesktop();
        }
    }

    function WelcomeStartSmartphone()
    {
        return new RedirectResponse('index.php?module=mobile&action=list');
    }

    public function WelcomeStartDesktop()
    {
        $tpl = '';
        if ($this->app->Request->request->getBoolean('addfav')) {
            $link = $this->app->Request->request->getString('link');
            if (str_ends_with($link, '&id=')) {
                $link = substr($link, 0, -4);
            }
            if (str_contains($link, 'action=delete')) {
                $link = '';
            }
            $title = $this->app->Request->request->getString('title');
            $newlink = $this->app->Request->request->getString('newlink');
            $success = 0;
            if (!empty($link)) {
                for ($i = 1; $i <= 8; $i++) {
                    $linkAct = (string)$this->app->User->GetParameter('welcome_linklink' . $i);
                    if ($linkAct === '' || $linkAct === $link) {
                        $success = 1;
                        $this->app->User->SetParameter('welcome_links_eigen', 1);
                        $this->app->User->SetParameter('welcome_linklink' . $i, $link);
                        $this->app->User->SetParameter('welcome_linkname' . $i, $title);
                        $this->app->User->SetParameter('welcome_linkintern' . $i, $newlink ? 0 : 1);
                        break;
                    }
                }
            }
            return new JsonResponse(['success' => $success]);
        }

        if ($this->app->Request->request->getBoolean('savelinks')) {
            $this->app->User->SetParameter('welcome_links_eigen', 1);
            for ($i = 1; $i <= 8; $i++) {
                $this->app->User->SetParameter(
                    'welcome_linklink' . $i,
                    $this->app->Request->request->getString('linklink' . $i),
                );
                $this->app->User->SetParameter(
                    'welcome_linkname' . $i,
                    $this->app->Request->request->getString('linkname' . $i),
                );
                $this->app->User->SetParameter(
                    'welcome_linkintern' . $i,
                    $this->app->Request->request->getString('linkintern' . $i),
                );
            }
        }
        $eigenlinks = $this->app->User->GetParameter('welcome_links_eigen');

        if ($eigenlinks) {
            for ($i = 1; $i <= 8; $i++) {
                $links[] = [
                    'name' => $this->app->User->GetParameter('welcome_linkname' . $i),
                    'link' => $this->app->User->GetParameter('welcome_linklink' . $i),
                    'intern' => $this->app->User->GetParameter('welcome_linkintern' . $i),
                ];
            }
        } else {
            $links = [
                ['name' => 'Eigene Einstellungen', 'link' => 'index.php?module=welcome&action=settings'],
                ['name' => '', 'link' => '', 'intern' => ''],
                ['name' => '', 'link' => '', 'intern' => ''],
                ['name' => '', 'link' => '', 'intern' => ''],
                ['name' => '', 'link' => '', 'intern' => ''],
                ['name' => '', 'link' => '', 'intern' => ''],
                ['name' => '', 'link' => '', 'intern' => ''],
                ['name' => '', 'link' => '', 'intern' => ''],
            ];
        }

        $this->app->erp->Headlines('Ihre Startseite');
        $this->app->Tpl->Set('KURZUEBERSCHRIFT2', '[BENUTZER]');
        $this->app->erp->StartseiteMenu();

        $this->app->Tpl->Set('TABTEXT', 'Ihre Startseite');

        $module = $this->app->Request->query->getAlnum('module');


        //fenster rechts offene vorgaenge ***
        $this->app->Tpl->Set('SUBSUBHEADING', 'Vorg&auml;nge');
        $arrVorgaenge = $this->app->EntityManager
            ->getConnection()->executeQuery(
            'SELECT * FROM offenevorgaenge WHERE adresse=:addressid ORDER by id DESC',
            ['addressid' => $this->app->User->GetAdresse()],
        )
            ->fetchAllAssociative();
        $this->app->Tpl->Set('INHALT', '');
        $carrVorgaenge = !empty($arrVorgaenge) ? count($arrVorgaenge) : 0;
        for ($i = 0; $i < $carrVorgaenge; $i++) {
            $this->app->Tpl->Add(
                'VORGAENGE',
                "<tr><td>" . substr(ucfirst($arrVorgaenge[$i]['titel']), 0, 100) . "</td><td align=\"right\"><img src=\"./themes/[THEME]/images/1x1t.gif\" width=\"7\" border=\"0\" align=\"right\">
          <a href=\"index.php?" . $arrVorgaenge[$i]['href'] . "\"><img src=\"./themes/[THEME]/images/right.png\" border=\"0\" align=\"right\" title=\"Erledigen\"></a>&nbsp;
          <a href=\"index.php?module=welcome&action=removevorgang&vorgang={$arrVorgaenge[$i]['id']}\"><img src=\"./themes/[THEME]/images/delete.svg\" border=\"0\" align=\"right\" title=\"Erledigt\"></a>&nbsp;
          <img src=\"./themes/[THEME]/images/1x1t.gif\" width=\"3\" border=\"0\" align=\"right\">
          <a href=\"javascript: var ergebnistext=prompt('Offenen Vorgang umbenennen:','" . ucfirst(
                    $arrVorgaenge[$i]['titel'],
                ) . "'); if(ergebnistext!='' && ergebnistext!=null) window.location.href='index.php?module=welcome&action=editvorgang&vorgang={$arrVorgaenge[$i]['id']}&titel='+ergebnistext;\"><img src=\"./themes/[THEME]/images/edit.svg\" alt=\"Bearbeiten\" title=\"Bearbeiten\" border=\"0\" align=\"right\"></a></td></tr>",
            );
        }

        $this->app->Tpl->Set(
            'CALENDAR_DAYNAMES',
            '["{|Sonntag|}", "{|Montag|}", "{|Dienstag|}", "{|Mittwoch|}",
        "{|Donnerstag|}", "{|Freitag|}", "{|Samstag|}"]',
        );
        $this->app->Tpl->Set(
            'CALENDAR_MONTHNAMES',
            '["{|Januar|}", "{|Februar|}", "{|März|}", "{|April|}", "{|Mai|}",
        "{|Juni|}", "{|Juli|}", "{|August|}", "{|September|}", "{|Oktober|}", "{|November|}", "{|Dezember|}"]',
        );
        $this->app->Tpl->Set('CALENDAR_TODAY', '{|Heute|}');
        $this->app->Tpl->Set('CALENDAR_MONTH', '{|Monat|}');
        $this->app->Tpl->Set('CALENDAR_WEEK', '{|Woche|}');
        $this->app->Tpl->Set('CALENDAR_DAY', '{|Tag|}');
        $this->app->erp->KalenderList('KALENDER');

        $this->app->Tpl->Parse('STARTSEITE', 'lesezeichen.tpl');

        if ($this->app->User->GetType() === 'admin') {
            $this->app->Tpl->Set(
                'UMSATZ',
                '<h1 onmouseover="document.getElementById(\'umsatzwoche\').style.display=\'block\';" onmouseout="document.getElementById(\'umsatzwoche\').style.display=\'none\';">Umsatz ab Montag</h1>
          <div style="margin:5px;display:none" id="umsatzwoche"><table width="90%">
          ' . $tpl . '
          </table>
          </div>
          <br>',
            );
        }

        $summestunden = $this->app->EntityManager
            ->getConnection()->executeQuery(
            "SELECT SUM((UNIX_TIMESTAMP(z.bis)-UNIX_TIMESTAMP(z.von))/3600.0) as stunden
      FROM zeiterfassung z WHERE z.abrechnen='1' AND z.ist_abgerechnet IS NULL OR z.ist_abgerechnet='0' AND z.adresse_abrechnung > 0",
        )
            ->fetchOne();

        if ($summestunden > 0) {
            $this->app->Tpl->Add(
                'DRINGEND',
                '<li>' . number_format(
                    $summestunden,
                    2,
                    ',',
                    '.',
                ) . ' Stunden nicht abgerechnet (<a href="index.php?module=zeiterfassung&action=abrechnenpdf">PDF</a>)</li>',
            );
        }


        $this->app->Tpl->Set('USERNAME', $this->app->User->GetName());

        $todosUser = $this->app->EntityManager->getConnection()->executeQuery(
            "SELECT t.id, t.aufgabe, t.prio, a.name
         FROM aufgabe t
         LEFT OUTER JOIN adresse a ON a.id=t.initiator AND a.id != t.adresse
         WHERE (t.adresse=:addressId OR (t.initiator=:addressId AND t.adresse<=0))
           AND t.startseite='1'
           AND (t.status='offen' or t.status='')
           AND ((t.intervall_tage > 0 AND t.abgabe_bis <=NOW()) OR t.intervall_tage <=0)
         ORDER by t.prio DESC",
            ['addressId' => $this->app->User->GetAdresse()],
        )->fetchAllAssociative();

        $todosEmployees = $this->app->EntityManager->getConnection()->executeQuery(
            "SELECT t.aufgabe, t.prio, a.name 
         FROM aufgabe t
         JOIN adresse a ON a.id=t.adresse
         WHERE t.initiator=:addressId
          AND t.adresse!=:addressId
          AND t.adresse > 0
          AND t.startseite='1'
          AND t.status='offen'
          AND ((t.intervall_tage > 0 AND t.abgabe_bis <=NOW()) OR t.intervall_tage <=0)
         ORDER by t.prio DESC",
            ['addressId' => $this->app->User->GetAdresse()],
        )->fetchAllAssociative();


        // Aufgabe-Bearbeiten-Popup
        $pinnwaende = $this->app->erp->GetPinwandSelect();
        $pinnwand = "";
        foreach ($pinnwaende as $key => $value) {
            $pinnwand .= "<option value='$key'>" . $value . "</option>";
        }
        $this->app->Tpl->Set("PINNWAND", $pinnwand);
        $this->app->YUI->CkEditor("e_notizen", "belege", ["width" => "625"]);
        $this->app->YUI->CkEditor("e_beschreibung", "belege", ["width" => "420"]);
        $this->app->YUI->DatePicker("e_datum");
        $this->app->YUI->TimePicker("e_zeit");
        $this->app->Tpl->Parse('AUFGABENPOPUP', 'aufgaben_popup.tpl');
        // ENDE:Aufgabe-Bearbeiten-Popup

        $this->app->erp->RunHook('welcome_start', 1, $this);

        // Xentral 20 database compatibility
        if ($this->app->EntityManager->getConnection()->executeQuery("SHOW COLUMNS FROM `user` LIKE 'role'")->rowCount(
            ) > 0) {
            if (empty($this->app->User->GetField('role')) || $this->app->acl->IsAdminadmin()) {
                $this->app->ModuleScriptCache->IncludeWidgetNew('ClickByClickAssistant');
                $this->app->ModuleScriptCache->IncludeJavascriptFiles(
                    'welcome',
                    [
                        'body' => [
                            './classes/Modules/Welcome/www/js/welcome_firststart.js',
                        ],
                    ],
                );
                $this->app->Tpl->Parse('AUFGABENPOPUP', 'welcome_firststart.tpl');
            }
        }

        $calendarAllowed = $this->app->erp->RechteVorhanden('kalender', 'list');

        $this->app->Tpl->RenderTwig('PAGE', 'welcome/startseite.html.twig', [
            'todosEmployees' => $todosEmployees,
            'todosUser' => $todosUser,
            'links' => $links,
            'accordion' => $this->Accordion(),
            'freeDiskSpace' => $this->checkFreeSpace(),
            'termineHeute' => $calendarAllowed ? $this->Termine(new DateTime()) : [],
            'termineMorgen' => $calendarAllowed ? $this->Termine(new DateTime('+1 day')) : [],
        ]);
    }

    protected function checkFreeSpace(): int
    {
        /** @var SystemHealthService $service */
        $systemHealthService = $this->app->Container->get('SystemHealthService');
        try {
            $freeDiskSpace = $systemHealthService->getDiskFree('');

            if ($freeDiskSpace === false) {
                return PHP_INT_MAX;
            }

            return $freeDiskSpace / (1024 * 1024);
        } catch (Exception $e) {
            $this->app->erp->LogFile('can not evaluate disk space: ' . $e->getMessage());
        }
    }


    public function WelcomeIcons(): Response
    {
        $type = $this->app->Request->query->getString('type');

        switch ($type) {
            case 'artikelgruppe.svg':
                $xml = file_get_contents('./images/icons/artikelgruppe.svg');
                break;
        }

        $farbe1 = $this->app->erp->Firmendaten('firmenfarbeganzdunkel');

        if ($farbe1 == '') {
            $farbe1 = '#26727a';//$farbe1 = "rgb(7, 134, 153)";
        }

        $farbe2 = '#e43f25'; // rot im artikel
        $farbe3 = '#a6e0be'; // hell tyrkis im artikel kreis
        $farbe4 = '#449cbe'; // dunkelblau im artikel rechteck

        $xml = str_replace('#3fb9cd', $farbe1, $xml);
        $xml = str_replace('#e43f25', $farbe2, $xml);
        $xml = str_replace('#a6e0be', $farbe3, $xml);
        $xml = str_replace('#449cbe', $farbe4, $xml);


        return new Response($xml, Response::HTTP_OK, ['Content-Type' => 'image/svg+xml']);
    }

    public function WelcomeLogo()
    {
        $firmenlogo = $this->app->erp->getSettingsFile('firmenlogo');
        return new Response($firmenlogo, Response::HTTP_OK, ['Content-Type' => 'image/png']);
    }


    public function WelcomeCss()
    {
        $file = $this->app->Request->query->getString('file');

        if ($this->app->erp->UserDevice() !== 'smartphone') {
            if ($file === 'style.css') {
                $tmp = file_get_contents('./themes/new/css/style.css');
            }


            if ($file === 'popup.css') {
                $tmp = file_get_contents('./themes/new/css/popup.css');
            }


            if ($file === 'grid.css') {
                $tmp = file_get_contents('./themes/new/css/grid.css');
            }
        }
        $tmpfirmendatenfkt = 'Firmendaten';
        if (method_exists($this->app->erp, 'TplFirmendaten')) {
            $tmpfirmendatenfkt = 'TplFirmendaten';
        }

        $firmenfarbehell = $this->app->erp->$tmpfirmendatenfkt('firmenfarbehell');
        if ($firmenfarbehell == '') {
            $firmenfarbehell = '#c2e3ea';//rgb(67, 187, 209)"; //ALT
        }

        $firmenfarbedunkel = $this->app->erp->$tmpfirmendatenfkt('firmenfarbedunkel');
        if ($firmenfarbedunkel == '') {
            $firmenfarbedunkel = '#53bed0';//rgb(2, 125, 141)"; //ALT
        }

        $firmenfarbeganzdunkel = $this->app->erp->$tmpfirmendatenfkt('firmenfarbeganzdunkel');
        if ($firmenfarbeganzdunkel == '') {
            $firmenfarbeganzdunkel = '#018fa3';
        }

        $navigationfarbe = $this->app->erp->$tmpfirmendatenfkt('navigationfarbe'); //ALT
        if ($navigationfarbe == '') {
            $navigationfarbe = '#48494b';
        }

        $navigationfarbeschrift = $this->app->erp->$tmpfirmendatenfkt('navigationfarbeschrift');
        if ($navigationfarbeschrift == '') {
            $navigationfarbeschrift = '#c9c9cb';
        }

        $navigationfarbe2 = $this->app->erp->$tmpfirmendatenfkt('navigationfarbe2'); //ALT
        if ($navigationfarbe2 == '') {
            $navigationfarbe2 = $navigationfarbeschrift;
        }

        $navigationfarbeschrift2 = $this->app->erp->$tmpfirmendatenfkt('navigationfarbeschrift2');
        if ($navigationfarbeschrift2 == '') {
            $navigationfarbeschrift2 = $navigationfarbe;
        }

        $unternavigationfarbe = $this->app->erp->$tmpfirmendatenfkt('unternavigationfarbe');
        if ($unternavigationfarbe == '') {
            $unternavigationfarbe = '#d5ecf2';
        }

        $unternavigationfarbeschrift = $this->app->erp->$tmpfirmendatenfkt('unternavigationfarbeschrift');
        if ($unternavigationfarbeschrift == '') {
            $unternavigationfarbeschrift = '#027d8d';
        }


        $firmenfarbe = $this->app->erp->$tmpfirmendatenfkt('firmenfarbe');
        if ($firmenfarbe == '') {
            $firmenfarbe = '#48494b';
        }

        $navigationfarbeschrift2 = $this->app->erp->$tmpfirmendatenfkt('navigationfarbeschrift2');
        if ($navigationfarbeschrift2 == '') {
            $navigationfarbeschrift2 = '#ffffff';
        }

        $tmp = str_replace('[TPLSYSTEMBASE]', $firmenfarbe, $tmp);

        if ($this->app->erp->Firmendaten('iconset_dunkel') == '1') {
            $tmp = str_replace('[TPLNACHRICHTBOX]', 'rgba(255,255,255,0.5)', $tmp);
        } else {
            $tmp = str_replace('[TPLNACHRICHTBOX]', 'rgba(255,255,255,0.1)', $tmp);
        }

        $tmp = str_replace('[TPLFIRMENFARBEHELL]', $firmenfarbehell, $tmp);
        $tmp = str_replace('[TPLFIRMENFARBEDUNKEL]', $firmenfarbedunkel, $tmp);
        $tmp = str_replace('[TPLFIRMENFARBEGANZDUNKEL]', $firmenfarbeganzdunkel, $tmp);
        $tmp = str_replace('[TPLNAVIGATIONFARBE]', $navigationfarbe, $tmp);
        $tmp = str_replace('[TPLNAVIGATIONFARBE2]', $navigationfarbe2, $tmp);
        $tmp = str_replace('[TPLNAVIGATIONFARBESCHRIFT]', $navigationfarbeschrift, $tmp);
        $tmp = str_replace('[TPLNAVIGATIONFARBESCHRIFT2]', $navigationfarbeschrift2, $tmp);

        $tmp = str_replace('[TPLUNTERNAVIGATIONFARBE]', $unternavigationfarbe, $tmp);
        $tmp = str_replace('[TPLUNTERNAVIGATIONFARBESCHRIFT]', $unternavigationfarbeschrift, $tmp);


        $subaction = $this->app->Request->query->getAlnum('subaction');
        $submodule = $this->app->Request->query->getAlnum('submodule');
        if ($subaction == 'pinwand' || $subaction == 'start' || $submodule == 'kalender') {
            $tmp = str_replace('[JSDMMZINDEX]', '10000', $tmp);
        } else {
            $tmp = str_replace('[JSDMMZINDEX]', '10', $tmp);
        }

        if ($this->app->erp->Firmendaten('standardaufloesung') == '1') {
            $tmp = str_replace('[CSSSMALL1]', '1000', $tmp);
            $tmp = str_replace('[CSSSMALL2]', '1000', $tmp);
            $tmp = str_replace('[CSSMARGIN]', 'margin-left: auto; margin-right: auto;', $tmp);
        } else {
            $tmp = str_replace('[CSSSMALL1]', '1200', $tmp);
            $tmp = str_replace('[CSSSMALL2]', '1200', $tmp);
            $tmp = str_replace('[CSSMARGIN]', 'margin-left: auto; margin-right: auto;', $tmp);
        }

        return new Response($tmp, Response::HTTP_OK, ['Content-Type' => 'text/css']);
    }


    public function WelcomeAddPinwand()
    {
        $user = $this->app->User->GetID();
        $users = $this->app->EntityManager
            ->getConnection()->executeQuery(
            "SELECT u.id, a.name as description FROM user u LEFT JOIN adresse a ON a.id=u.adresse WHERE u.activ='1' ORDER BY u.username",
        )
            ->fetchAllAssociative();

        $name = $this->app->Request->request->getString('name');
        if ($name != '') {
            $personen = $this->app->Request->request->all('personen');
            $this->app->EntityManager->getConnection()->executeStatement(
                "INSERT INTO pinwand (id,name,user) VALUES ('',:name, :user)",
                ['name' => $name, 'user' => $user],
            );
            $pinwand = $this->app->EntityManager->getConnection()->lastInsertId();
            $cpersonen = !empty($personen) ? count($personen) : 0;
            for ($i = 0; $i <= $cpersonen; $i++) {
                if ($personen[$i] > 0) {
                    $this->app->EntityManager->getConnection()->executeStatement(
                        "INSERT INTO pinwand_user (pinwand,user) VALUES (:pinwand, :user)",
                        ['pinwand' => $pinwand, 'user' => $personen[$i]],
                    );
                }
            }

            return new RedirectResponse('index.php?module=welcome&action=pinwand');
        } else {
            $this->app->Tpl->RenderTwig('PAGE', 'welcome/_addpinwand.html.twig', [
                'users' => $users,
                'userId' => $user,
            ]);
        }

        $this->app->BuildNavigation = false;
    }

    public function WelcomeAddNote()
    {
        $aufgabeid = $this->app->Request->query->getInt('aufgabeid');
        $beschreibung = $this->app->Request->request->getString('notebody');
        if ($beschreibung != '') {
            $color = $this->app->Request->request->getString('color');
            $aufgabe = $beschreibung;
            $pinwand = $this->app->Request->query->getInt('pinwand');

            $aufgabe = str_replace('\r\n', ' ', $aufgabe);

            $max_z = $this->app->EntityManager
                ->getConnection()->executeQuery(
                "SELECT MAX(note_z) FROM aufgabe WHERE adresse=:address",
                ['address' => $this->app->User->GetAdresse()],
            )
                ->fetchOne();
            $new = true;
            if ($aufgabeid) {
                $cuid = $this->app->EntityManager
                    ->getConnection()->executeQuery(
                    "SELECT id FROM aufgabe WHERE adresse = :address AND id = :task LIMIT 1",
                    ['address' => $this->app->User->GetAdresse(), 'task' => $aufgabeid],
                )
                    ->fetchOne();
                if ($cuid) {
                    $new = false;
                    $id = $cuid;
                }
            }

            if ($this->app->erp->is_html($aufgabe)) {
                $aufgabe = strip_tags(str_replace('<', ' <', $aufgabe));
                $aufgabe = trim(str_replace('  ', ' ', $aufgabe));

                if ($new) {
                    $id = $this->app->erp->CreateAufgabe($this->app->User->GetAdresse(), $aufgabe);
                }
            } else {
                if ($new) {
                    $id = $this->app->erp->CreateAufgabe($this->app->User->GetAdresse(), $aufgabe);
                }
            }
            $xy = $this->getCoordsForNewTask($id);
            $note_x = $xy['note_x'];
            $note_y = $xy['note_y'];
            $this->app->EntityManager->getConnection()->executeStatement(
                "UPDATE aufgabe 
          SET pinwand='1',
              pinwand_id=:pinwand, 
              note_color=:color, 
              note_z=:z,
              note_x=:x,
              note_y=:y,
              beschreibung=:description 
          WHERE id=:id LIMIT 1",
                [
                    'pinwand' => $pinwand,
                    'color' => $color,
                    'z' => $max_z,
                    'x' => $note_x,
                    'y' => $note_y,
                    'description' => $beschreibung,
                    'id' => $id,
                ],
            );

            return new RedirectResponse('index.php?module=welcome&action=pinwand&pinwand=' . $pinwand);
        } else {
            if ($aufgabeid) {
                $aufg = $this->app->EntityManager->getConnection()->executeQuery(
                    'SELECT * FROM aufgabe WHERE id = :id LIMIT 1',
                    ['id' => $aufgabeid],
                )->fetchAssociative();
            }

            $this->app->YUI->CkEditor('notebody', 'belege');

            $this->app->Tpl->RenderTwig('PAGE', 'welcome/_pinwand_addnote.html.twig', [
                'task' => $aufg,
            ]);
        }

        $this->app->BuildNavigation = false;
    }

    public function WelcomeDelNote(): RedirectResponse
    {
        $id = $this->app->Request->query->getInt('id');
        $pinwand = $this->app->Request->query->getInt('pinwand');
        if ($id > 0) {
            $this->app->EntityManager->getConnection()->executeStatement(
                "DELETE FROM aufgabe WHERE id=:id LIMIT 1",
                ['id' => $id],
            );
        }
        return new RedirectResponse('index.php?module=welcome&action=pinwand&pinwand=' . $pinwand);
    }

    public function WelcomeOkNote()
    {
        $id = $this->app->Request->query->getInt('id');
        $pinwand = $this->app->Request->query->getInt('pinwand');

        $this->app->erp->AbschlussAufgabe($id);
        return new RedirectResponse('index.php?module=welcome&action=pinwand&pinwand=' . $pinwand);
    }

    public function WelcomeMoveNote()
    {
        $id = $this->app->Request->query->getInt('id');
        if ($id > 0) {
            $x = $this->app->Request->query->getInt('x');
            $y = $this->app->Request->query->getInt('y');
            $z = $this->app->Request->query->getInt('z');
            $this->app->EntityManager->getConnection()->executeStatement(
                "UPDATE aufgabe SET note_x=:x, note_y=:y, note_z=:z WHERE id=:id LIMIT 1",
                ['x' => $x, 'y' => $y, 'z' => $z, 'id' => $id],
            );
        }
        return new Response();
    }

    public function WelcomePinwand()
    {
        $this->app->erp->StartseiteMenu();

        $cmd = $this->app->Request->query->getAlnum('cmd');
        $pinwand = $this->app->Request->query->getInt('pinwand');

        switch ($cmd) {
            case 'resize':
                $id = $this->app->Request->query->getInt('id');
                if ($id > 0) {
                    $w = $this->app->Request->query->getInt('w');
                    $h = $this->app->Request->query->getInt('h');
                    $this->app->EntityManager->getConnection()->executeStatement(
                        "UPDATE aufgabe SET note_w=:width, note_h=:height WHERE id=:id LIMIT 1",
                        ['width' => $w, 'height' => $h, 'id' => $id],
                    );
                    $result['status'] = 1;
                } else {
                    $result['status'] = 0;
                    $result['statusText'] = 'Fehlgeschlagen';
                }
                return new JsonResponse($result);
            case 'get':
                $id = $this->app->Request->request->getInt('id');
                $aufgabe = $this->app->EntityManager->getConnection()->executeQuery(
                    "SELECT beschreibung, note_color FROM aufgabe WHERE id=:id LIMIT 1",
                    ['id' => $id],
                )->fetchAssociative();
                $result['id'] = $id;
                $result['beschreibung'] = $aufgabe['beschreibung'];
                $result['note_color'] = $aufgabe['note_color'];
                $result['status'] = 1;
                $result['statusText'] = '';
                return new JsonResponse($result);
            case 'save':
                $id = $this->app->Request->request->getInt('id');
                $beschreibung = $this->app->Request->request->getString('beschreibung');
                $note_color = $this->app->Request->request->getString('note_color');
                $pinwand = $this->app->Request->request->getInt('pinwand');
                if ($pinwand <= 0) {
                    $pinwand = 0;
                }
                if ($id > 0) {
                    $this->app->EntityManager->getConnection()->executeStatement(
                        "UPDATE aufgabe SET beschreibung=:beschreibung, note_color=:noteColor WHERE id=:id LIMIT 1",
                        ['beschreibung' => $beschreibung, 'noteColor' => $note_color, 'id' => $id],
                    );
                    $result['note_color'] = $note_color;
                    $result['beschreibung'] = $beschreibung;
                    $result['status'] = 1;
                } else {
                    $aufgabe = strip_tags(str_replace('<', ' <', $beschreibung));
                    $aufgabe = trim(str_replace('  ', ' ', $aufgabe));
                    $max_z = $this->app->EntityManager->getConnection()->executeQuery(
                        "SELECT MAX(note_z) FROM aufgabe WHERE adresse=:address",
                        ['address' => $this->app->User->GetAdresse()],
                    )->fetchOne();
                    $id = $this->app->erp->CreateAufgabe($this->app->User->GetAdresse(), $aufgabe);
                    $xy = $this->getCoordsForNewTask($id);
                    $note_x = $xy['note_x'];
                    $note_y = $xy['note_y'];
                    $this->app->EntityManager->getConnection()->executeStatement(
                        "UPDATE aufgabe 
            SET note_color=:noteColor,
                beschreibung=:beschreibung, 
                note_z=:z,
                note_x=:x,
                note_y=:y,
                pinwand='1',
                pinwand_id=:pinwandId
            WHERE id=:id LIMIT 1",
                        [
                            'noteColor' => $note_color,
                            'beschreibung' => $beschreibung,
                            'z' => $max_z,
                            'x' => $note_x,
                            'y' => $note_y,
                            'pinwandId' => $pinwand,
                            'id' => $id,
                        ],
                    );

                    $result['note_color'] = $note_color;
                    $result['beschreibung'] = $beschreibung;
                    $result['status'] = 1;
                    $result['statusText'] = '';
                }
                return new JsonResponse($result);
        }

        if ($pinwand <= 0) {
            $tmp = $this->app->EntityManager->getConnection()->executeQuery(
                "SELECT * FROM aufgabe WHERE adresse=:address AND pinwand='1' AND pinwand_id='0' AND status='offen'",
                ['address' => $this->app->User->GetAdresse()],
            )->fetchAllAssociative();
        } else {
            $erlaubt = true;
            if ($this->app->User->GetType() != 'admin') {
                $check = $this->app->EntityManager->getConnection()->executeQuery(
                    'SELECT id FROM pinwand WHERE id = :pinwandId AND `user` = :userId',
                    ['pinwandId' => $pinwand, 'userId' => $this->app->User->GetID()],
                )->fetchOne();
                if (!$check && !$this->app->EntityManager->getConnection()->executeQuery(
                        "SELECT id FROM pinwand_user WHERE pinwand = :pinwandId AND `user` = :userId LIMIT 1",
                        ['pinwandId' => $pinwand, 'userId' => $this->app->User->GetID()],
                    )->fetchOne()) {
                    $erlaubt = false;
                }
            }
            if ($erlaubt) {
                $tmp = $this->app->EntityManager->getConnection()->executeQuery(
                    "SELECT * FROM aufgabe WHERE pinwand='1' AND pinwand_id=:pinwandId AND status='offen'",
                    ['pinwandId' => $pinwand],
                )->fetchAllAssociative();
            }
        }
        $notes = [];
        $ctmp = !empty($tmp) ? count($tmp) : 0;
        for ($i = 0; $i < $ctmp; $i++) {
            $left = $tmp[$i]['note_x'];
            $color = $tmp[$i]['note_color'];
            if ($color == '') {
                $color = 'yellow';
            }
            $top = $tmp[$i]['note_y'];
            $zindex = $tmp[$i]['note_z'];
            $text = nl2br($this->app->erp->ReadyForPDF($tmp[$i]['beschreibung']));
            if ($text == '') {
                $text = $tmp[$i]['aufgabe'];
            }
            $id = $tmp[$i]['id'];
            $projekt = $this->app->EntityManager->getConnection()->executeQuery(
                "SELECT abkuerzung FROM projekt WHERE id=:projectId LIMIT 1",
                ['projectId' => $tmp[$i]['projekt']],
            )->fetchOne();

            $width = $tmp[$i]['note_w'] ? $tmp[$i]['note_w'] : 130;
            $height = $tmp[$i]['note_h'] ? $tmp[$i]['note_h'] : 130;

            if ($pinwand <= 0) {
                $pinwand = 0;
            }

            $seriennummer = $this->app->EntityManager->getConnection()->executeQuery(
                "SELECT seriennummer FROM adapterbox WHERE verwendenals='bondrucker' LIMIT 1",
            )->fetchOne();
            if ($seriennummer != '') {
                $menu_bon = '<a href="#" onclick=AjaxCall("index.php?module=aufgaben&action=bondrucker&id=' . $id . '");><img src="themes/[THEME]/images/bon_druck.png" border="0"></a>&nbsp;';
            } else {
                $menu_bon = '<a href="#" onclick=InfoBox("aufgabe_bondrucker");><img src="themes/[THEME]/images/bon_druck.png" border="0"></a>&nbsp;';
            }

            switch ($color) {
                case 'yellow':
                    $color = '#f69e06';
                    break;
                case 'blue':
                    $color = '#41b3ce';
                    break;
                case 'green':
                    $color = '#a9ca45';
                    break;
                case 'coral':
                    $color = '#be3978';
                    break;
            }

            $notes[] = [
                'id' => $id,
                'color' => $color,
                'text' => $text,
                'left' => $left,
                'top' => $top,
                'zindex' => $zindex,
                'width' => $width,
                'height' => $height,
                'projekt' => $projekt,
            ];
        }

        $pinnwaende = $this->app->EntityManager->getConnection()->executeQuery(
            "SELECT DISTINCT p.id,p.name FROM pinwand p 
        LEFT JOIN pinwand_user pu ON pu.pinwand=p.id 
        WHERE (pu.user=:userId OR p.user=:userId) ORDER by p.name",
            ['userId' => $this->app->User->GetID()],
        )->fetchAllAssociative();

        // Aufgabe-Bearbeiten-Popup
        $pinnwand = "";
        foreach ($this->app->erp->GetPinwandSelect() as $key => $value) {
            $pinnwand .= "<option value='$key'>" . $value . "</option>";
        }
        $this->app->Tpl->Set("PINNWAND", $pinnwand);
        // ENDE:Aufgabe-Bearbeiten-Popup

        $this->app->YUI->CkEditor(
            'editbeschreibung',
            'minimal',
            ['height' => '150', 'width' => '250', 'ckeditor5' => true],
        );
        $this->app->YUI->ColorPicker('editnote_color');
        $this->app->YUI->CkEditor("e_notizen", "belege", ["width" => "625"]);
        $this->app->YUI->CkEditor("e_beschreibung", "belege", ["width" => "420"]);
        $this->app->YUI->DatePicker("e_datum");
        $this->app->YUI->TimePicker("e_zeit");
        $this->app->Tpl->Parse('AUFGABENPOPUP', 'aufgaben_popup.tpl');
        $this->app->Tpl->RenderTwig('PAGE', 'welcome/pinwand.html.twig', [
            'pinwand' => $pinwand,
            'pinwaende' => $pinnwaende,
            'notes' => $notes,
        ]);
    }

    private function getCoordsForNewTask($taskId)
    {
        $coords = ['note_x' => 0, 'note_y' => 0];

        $oldCoords = $this->app->EntityManager->getConnection()->executeQuery(
            'SELECT MAX(a.note_x) 
        FROM `aufgabe` AS `a` 
        WHERE a.id != :id 
        AND (a.note_x = a.note_y OR (a.note_x IS NULL AND a.note_y IS NULL))
        ORDER BY a.note_x',
            ['id' => (int)$taskId],
        )->fetchOne();

        if (!empty($oldCoords) || $oldCoords == 0) {
            $toAdd = 10;

            if ($oldCoords % $toAdd == 0) {
                $coords = ['note_x' => $oldCoords + $toAdd, 'note_y' => $oldCoords + $toAdd];
            }
        }

        return $coords;
    }

    public function Accordion()
    {
        // check if accordion is empty

        //$this->app->DB->Insert("INSERT INTO accordion (name,target,position) VALUES ('Startseite','StartseiteWiki','1')");
        $this->app->DB->DisableHTMLClearing(true);
        $check_startseite = $this->app->EntityManager
            ->getConnection()->executeQuery("SELECT `name` FROM wiki WHERE name='StartseiteWiki' LIMIT 1")
            ->fetchOne();
        if ($check_startseite == '') {
            $wikifirstpage = '
<p>Herzlich Willkommen in Ihrem Xentral,<br><br>wir freuen uns Sie als Xentral Benutzer begrüßen zu dürfen. Mit Xentral organisieren Sie Ihre Firma schnell und einfach. Sie haben alle wichtigen Zahlen und Vorgänge im Überblick.<br><br>Für Einsteiger sind die folgenden Themen wichtig:<br><br></p>
<ul>
<li> <a href="index.php?module=firmendaten&amp;action=edit" target="_blank"> Firmendaten</a> (dort richten Sie Ihr Briefpapier ein)</li>
<li> <a href="index.php?module=adresse&amp;action=list" target="_blank"> Stammdaten / Adressen</a> (Kunden und Lieferanten anlegen)</li>
<li> <a href="index.php?module=artikel&amp;action=list" target="_blank"> Artikel anlegen</a> (Ihr Artikelstamm)</li>
<li> <a href="index.php?module=angebot&amp;action=list" target="_blank"> Angebot</a> / <a href="index.php?module=auftrag&amp;action=list" target="_blank"> Auftrag</a> (Alle Dokumente für Ihr Geschäft)</li>
<li> <a href="index.php?module=rechnung&amp;action=list" target="_blank"> Rechnung</a> / <a href="index.php?module=gutschrift&amp;action=list" target="_blank"> Gutschrift</a></li>
<li> <a href="index.php?module=lieferschein&amp;action=list" target="_blank"> Lieferschein</a></li>
</ul>
<p><br><br>Kennen Sie unsere Zusatzmodule die Struktur und Organisation in das tägliche Geschäft bringen?<br><br></p>
<ul>
<li> <a href="index.php?module=kalender&amp;action=list" target="_blank"> Kalender</a></li>
<li> <a href="index.php?module=wiki&amp;action=list" target="_blank"> Wiki</a></li>
</ul>';

            $this->app->EntityManager->getConnection()->executeStatement(
                "INSERT INTO wiki (name,content) VALUES ('StartseiteWiki',:content)",
                ['content' => $wikifirstpage],
            );
        }
        $data = $this->app->EntityManager
            ->getConnection()->fetchAllAssociative("SELECT * FROM accordion ORDER BY position");


        $out = '';
        $entry = '';

        $wikipage_exists = $this->app->EntityManager
            ->getConnection()->fetchOne("SELECT '1' FROM wiki WHERE name='StartseiteWiki' LIMIT 1");
        if ($wikipage_exists != '1') {
            $this->app->EntityManager->getConnection()->executeStatement(
                "INSERT INTO wiki (name) VALUES ('StartseiteWiki')",
            );
        }
        $wikipage_content = $this->app->EntityManager->getConnection()->executeQuery(
            "SELECT content FROM wiki WHERE name='StartseiteWiki' LIMIT 1",
        )->fetchOne();
        $this->app->DB->DisableHTMLClearing(false);
        $wikipage_content = $this->app->erp->ReadyForPDF($wikipage_content);
        $wikiparser = new WikiParser();
        $content = $wikiparser->parse($wikipage_content);
        return $content;
    }

    public function Termine(DateTime $date): array
    {
        $userid = $this->app->User->GetID();

        if (!is_numeric($userid)) {
            return [];
        }

        $termine = $this->app->EntityManager->getConnection()->executeQuery(
            "SELECT DISTINCT color,von,bis,bezeichnung,allDay,ke.id FROM kalender_user AS ka
      RIGHT JOIN kalender_event AS ke ON ka.event=ke.id
      WHERE (ka.userid=:userId OR ke.public='1') AND DATE(von)=DATE(:date)
      ORDER BY von",
            ['userId' => $userid, 'date' => $date->format(DATE_ATOM)],
        )->fetchAllAssociative();
        return $termine;
    }


    function Aufgaben($parse) {}

    function WelcomeHelp() {}

    /**
     * Nur eine Fake-Action um eine Berechtigung zu erzeugen
     *
     * @return void
     */
    public function WelcomeMobileApps() {}

    public function WelcomeSettings()
    {
        $cmd = $this->app->Request->query->getAlnum('cmd');
        switch ($cmd) {
            case 'inviteteamclickbyclick':
                return $this->HandleInviteTeamClickByClick();
            case 'startclickbyclick':
                return $this->HandleStartClickByClick();
            case 'changeroleclickbyclick':
                return $this->HandleChangeRoleClickByClick();
            case 'changepasswordclickbyclick':
                return $this->HandlePasswordChangeClickByClick();
            // Passwort ändern
            case 'password-change':
                $this->HandlePasswordChange();
                break;

            // Einstellungen speichern
            case 'settings-save':
                $this->HandleProfileSettingsSave();

            // Profilbild löschen
            case 'picture-delete':
                return $this->HandleProfilePictureDeletion();

            // Profilbild hochladen
            case 'picture-upload':
                return $this->HandleProfilePictureUpload();

            case 'mobile-apps-account':
                $this->HandleMobileAppsAccount();
                break;

            case 'googlecalendar-save':
                return $this->HandleGoogleCalendarSave();

            case 'gmail-save':
                return $this->HandleGoogleMailAuth();

            case 'gmail-test':
                return $this->HandleGoogleMailTest();

            case 'totp_toggle':
                return $this->HandleTOTPToggle();

            case 'totp_regenerate':
                return $this->handleTOTPRegenerate();
        }

        // Einstellungen laden
        $settings = $this->app->EntityManager->getConnection()->executeQuery(
            "SELECT u.startseite, u.chat_popup, u.callcenter_notification, u.defaultcolor, u.sprachebevorzugen 
       FROM `user` AS u WHERE u.id = :id LIMIT 1",
            ['id' => $this->app->User->GetID()],
        )->fetchAssociative();
        $settings['sprachebevorzugen'] = $this->getCurrentDefaultLanguage($settings['sprachebevorzugen']);

        // Profilbild laden
        $adresse = $this->app->User->GetAdresse();
        $dateiversion = (int)$this->app->EntityManager->getConnection()->executeQuery(
            "SELECT dv.id FROM datei_stichwoerter ds
        INNER JOIN datei d ON ds.datei = d.id
        INNER JOIN datei_version dv ON dv.datei = d.id
        WHERE d.geloescht = 0 AND objekt like 'Adressen'
        AND parameter = :address AND subjekt like 'Profilbild' ORDER by d.id DESC, dv.id DESC LIMIT 1",
            ['address' => $adresse],
        )->fetchOne();

        // Mobile Apps Einstellungen laden
        $apiAccountActive = false;
        $apiAccountExisting = false;
        $hasMobileAppsPermission = (bool)$this->app->erp->RechteVorhanden('welcome', 'mobileapps');
        if ($hasMobileAppsPermission) {
            $apiAccountId = (int)$this->app->User->GetParameter('mobile_apps_api_account_id');
            $apiAccountData = $this->app->EntityManager->getConnection()->executeQuery(
                "SELECT a.remotedomain, a.initkey, a.aktiv FROM api_account AS a WHERE a.id = :id",
                ['id' => $apiAccountId],
            )->fetchAssociative();
            $apiAccountActive = isset($apiAccountData['aktiv']) && (int)$apiAccountData['aktiv'] === 1;
            $apiAccountExisting = isset($apiAccountData['remotedomain']) && !empty($apiAccountData['remotedomain']);

            $serverUrl = $this->app->Request->getBaseUrl(
                ) . '/'; // Url muss aufs www-Verzeichnis zeigen; App hängt 'api/v1/mobileapi/dashboard' an
            $qrCodeArray = [
                'server_url' => $serverUrl,
                'username' => $apiAccountData['remotedomain'],
                'password' => $apiAccountData['initkey'],
            ];
            $qrCodeData = json_encode($qrCodeArray);
        }

        $mobileAppStatus = 'unavailable';
        if ($hasMobileAppsPermission) {
            if ($apiAccountExisting) {
                if ($apiAccountActive) {
                    /** @var BarcodeFactory $barcodeFactory */
                    $barcodeFactory = $this->app->Container->get('BarcodeFactory');
                    $barcodeObject = $barcodeFactory->createQrCode($qrCodeData);
                    $qrCodeHtml = $barcodeObject->toHtml(3, 3);
                    $mobileAppStatus = 'active';
                    $mobileAppQrCode = $qrCodeHtml;
                }
            } else {
                $mobileAppStatus = 'available';
            }
        }

        $this->app->erp->Headlines('Mein Bereich', 'Pers&ouml;nliche Einstellungen');
        $this->app->erp->MenuEintrag('index.php?module=welcome&action=settings', '&Uuml;bersicht');

        $this->app->YUI->AutoSaveUserParameter('name_fuer_unterartikel', 'matrixprodukt_name_fuer_unterartikel');
        $this->app->YUI->ColorPicker('defaultcolor');
        $this->app->YUI->PasswordCheck('password', 'repassword', '', 'submit_password');

        $this->app->Tpl->RenderTwig('PAGE', 'welcome/settings.html.twig', [
            'userId' => $this->app->User->GetID(),
            'hasProfilePicture' => $dateiversion !== null,
            'settings' => $settings,
            'languages' => $this->getLanguages(),
            'mobileAppStatus' => $mobileAppStatus,
            'mobileAppQrCode' => $mobileAppQrCode ?? '',
            'totp' => $this->renderTOTP(),
            'googleCalendar' => $this->renderGoogleCalendarSettings(),
            'googleMail' => $this->renderGoogleMailSettings(),
        ]);
    }

    private function renderTOTP()
    {
        /** @var TOTPLoginService $totpLoginManager */
        $totpLoginManager = $this->app->Container->get('TOTPLoginService');

        $userID = $this->app->User->GetID();

        $totpEnabled = $totpLoginManager->isTOTPEnabled($userID);

        if ($totpEnabled) {
            $label = 'Xentral' . ' | ' . $this->app->erp->GetFirmaName();
            $qrCode = $totpLoginManager->generatePairingQrCode($userID, $label);
            $secret = $totpLoginManager->getTOTPSecret($userID);
            $qrHtml = $qrCode->toHtml(3, 3);
        }

        return [
            'enabled' => $totpEnabled,
            'qrCode' => $qrHtml ?? null,
            'secret' => $secret ?? null,
        ];
    }

    public function WelcomeRedirect()
    {
        $url = $this->app->Request->query->getString('url');
        if (empty($url)) {
            $this->app->Tpl->Set(
                'MESSAGE',
                '<div class="error">Es wurde keine Weiterleitungs-URL &uuml;bergeben</div>',
            );
        }

        $urlParts = parse_url($url);
        if (!is_array($urlParts)) {
            $this->app->Tpl->Set(
                'MESSAGE',
                '<div class="error">Die &uuml;bergebene Weiterleitungs-URL is ung&uuml;ltig.</div>',
            );
        }
        if (empty($urlParts['scheme']) && empty($urlParts['host'])) {
            $this->app->Tpl->Set(
                'MESSAGE',
                '<div class="error">Die &uuml;bergebene Weiterleitungs-URL ist unvollst&auml;ndig.</div>',
            );
        }
        if (!empty($urlParts['scheme']) && !in_array($urlParts['scheme'], ['http', 'https'])) {
            $this->app->Tpl->Set(
                'MESSAGE',
                '<div class="warning">Die Weiterleitungs-URL führt nicht auf eine Webseite.</div>',
            );
        }

        $this->app->Tpl->Set('REDIRECT_URL_LINK', $url);
        $this->app->Tpl->Set('REDIRECT_URL_TEXT', htmlspecialchars($url));
        $this->app->Tpl->Parse('PAGE', 'welcome_redirect.tpl');
    }

    /**
     * @param Application $app
     * @param string $name
     * @param array $erlaubtevars
     *
     * @return array
     */
    public function TableSearch(&$app, $name, $erlaubtevars)
    {
        $result = [];
        switch ($name) {
            case 'welcome_spooler':
                $id = $this->app->Request->query->getInt('id');

                $aligncenter = [1];
                $heading = ['', 'Zeit', 'Dateiname', 'Bearbeiter', 'Gedruckt', 'Men&uuml;'];
                $width = ['1%', '30%', '30%', '20%', '10%', '5%'];
                $findcols = ['d.id', 'd.zeitstempel', 'd.filename', 'a.name', 'd.gedruckt', 'd.id'];
                $searchsql = ["DATE_FORMAT(d.zeitstempel,'%d.%m.%Y %H:%i:%s')", 'd.filename', 'a.name'];

                $menu = "<table cellpadding=0 cellspacing=0><tr><td nowrap>" .
                    "<a href=\"index.php?module=welcome&action=spooler&cmd=download&file=%value%\" data-cmd=\"download\">" .
                    "<img src=\"themes/{$this->app->Conf->WFconf['defaulttheme']}/images/download.svg\" border=\"0\"></a>" .
                    "&nbsp;" .
                    "<a href=\"index.php?module=welcome&action=spooler&cmd=delete&file=%value%\" data-cmd=\"delete\">" .
                    "<img src=\"themes/{$this->app->Conf->WFconf['defaulttheme']}/images/delete.svg\" border=\"0\"></a>" .
                    "&nbsp;</td></tr></table>";

                $nichtGedruckt = (int)$app->YUI->TableSearchFilter($name, 1, 'nicht_gedruckt', '0', 0, 'checkbox');
                if ($nichtGedruckt === 1) {
                    $subwhere = ' AND d.gedruckt = 0 ';
                } else {
                    $subwhere = '';
                }

                // SQL statement
                $sql = "SELECT 
                  SQL_CALC_FOUND_ROWS d.id, 
                  CONCAT('<input type=\"checkbox\" name=\"selection[]\" value=\"', d.id, '\">'),
                  DATE_FORMAT(d.zeitstempel,'%d.%m.%Y %H:%i:%s'), 
                  IF(d.filename != '', d.filename, 'Kein Dateiname vorhanden'), 
                  a.name,
                  IF(d.gedruckt = 1, 'ja', '') as gedruckt,
                  d.id 
                FROM drucker_spooler AS d 
                LEFT JOIN `user` AS u ON u.id = d.user 
                LEFT JOIN adresse AS a ON a.id = u.adresse ";
                $where = " d.drucker = '{$id}' " . $subwhere;
                $count = "SELECT COUNT(d.id) FROM drucker_spooler AS d WHERE " . $where;

                $result = [
                    'aligncenter' => $aligncenter,
                    'heading' => $heading,
                    'width' => $width,
                    'findcols' => $findcols,
                    'searchsql' => $searchsql,
                    'menu' => $menu,
                    'where' => $where,
                    'sql' => $sql,
                    'count' => $count,
                    'maxrows' => 50,
                ];

                break;
        }

        // Nicht erlaubt Keys aus Result entfernen
        foreach ($result as $key => $value) {
            if (!in_array($key, $erlaubtevars, true)) {
                unset($result[$key]);
            }
        }

        return !empty($result) ? $result : [];
    }

    public function WelcomeSpooler()
    {
        $cmd = $this->app->Request->query->getAlnum('cmd');
        $fileId = $this->app->Request->query->getInt('file');
        $printerId = $this->app->Request->query->getInt('id');
        if ($printerId === 0) {
            $printerId = null;
        }

        // Zip erstellen
        if (!empty($this->app->Request->request->getString('makezip'))) {
            try {
                $selection = $this->app->Request->request->all('selection');
                $this->DownloadSpoolerZipCompilation($selection, $printerId);
                $this->app->erp->ExitWawi();
            } catch (DownloadSpoolerExceptionInterface $e) {
                $notification = $this->GetNotificationService();
                $notification->create(
                    $this->app->User->GetID(),
                    'error',
                    'Download-Drucker',
                    'Zip-Datei konnte nicht erstellt werden. Fehler: ' . $e->getMessage(),
                );
            }
        }

        // Sammel-PDF erstellen
        if (!empty($this->app->Request->request->getString('makepdf'))) {
            try {
                $selection = $this->app->Request->request->all('selection');
                $this->DownloadSpoolerPdfCompilation($selection, $printerId);
                $this->app->erp->ExitWawi();
            } catch (DownloadSpoolerExceptionInterface $e) {
                $notification = $this->GetNotificationService();
                $notification->create(
                    $this->app->User->GetID(),
                    'error',
                    'Download-Drucker',
                    'Sammel-PDF konnte nicht erstellt werden. Fehler: ' . $e->getMessage(),
                );
            }
        }

        // Alle "noch nicht gedruckte" Dateien herunterladen
        if ($cmd === 'download-unprinted') {
            try {
                $this->DownloadSpoolerUnprintedFiles($printerId);
                $this->app->erp->ExitWawi();
            } catch (DownloadSpoolerExceptionInterface $e) {
                $notification = $this->GetNotificationService();
                $notification->create(
                    $this->app->User->GetID(),
                    'error',
                    'Download-Drucker',
                    'Dateien konnte nicht heruntergeladen werden. Fehler: ' . $e->getMessage(),
                );
            }
        }

        // Einzelne Datei runterladen
        if ($cmd === 'download-file') {
            try {
                $this->DownloadSpoolerFile($fileId);
                $this->app->erp->ExitWawi();
            } catch (DownloadSpoolerExceptionInterface $e) {
                $notification = $this->GetNotificationService();
                $notification->create(
                    $this->app->User->GetID(),
                    'error',
                    'Download-Drucker',
                    'Datei konnte nicht heruntergeladen werden. Fehler: ' . $e->getMessage(),
                );
            }
        }

        // Einzelne Datei löschen
        if ($cmd === 'delete-file') {
            try {
                $isFileDeleted = $this->DeleteSpoolerFile($fileId, $printerId);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => $isFileDeleted]);
                $this->app->erp->ExitWawi();
            } catch (DownloadSpoolerExceptionInterface $e) {
                $notification = $this->GetNotificationService();
                $notification->create(
                    $this->app->User->GetID(),
                    'error',
                    'Download-Drucker',
                    'Datei konnte nicht gelöscht werden. Fehler: ' . $e->getMessage(),
                );
            }
        }

        // DataTable-HTML-Struktur
        if ($cmd === 'datatable-html') {
            $table = new DownloadSpoolerTable($this->app, $printerId);
            $settings = $table->GetSettings(
                sprintf('./index.php?module=welcome&action=spooler&cmd=datatable-data&id=%s', $printerId),
            );

            header('Content-Type: text/html; charset=utf-8');
            echo $table->GetContentHtml();
            echo '<script type="application/json" id="downloadspooler-table-settings">';
            echo json_encode($settings, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
            echo '</script>';
            $this->app->erp->ExitWawi();
        }

        // DataTable-Daten
        if ($cmd === 'datatable-data') {
            try {
                $data = $this->DownloadSpoolerDataTableResult($printerId);
            } catch (Exception $e) {
                header('HTTP/1.1 404 Not Found');
                $data = ['success' => false, 'error' => $e->getMessage()];
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($data);
            $this->app->erp->ExitWawi();
        }
    }

    /**
     * @param int $printerId
     *
     * @return array
     * @throws RuntimeException
     *
     */
    protected function DownloadSpoolerDataTableResult($printerId)
    {
        $columns = $this->app->Request->query->all('columns');
        $search = $this->app->Request->query->all('search');
        $order = $this->app->Request->query->all('order');
        $offset = $this->app->Request->query->getInt('start');
        $limit = $this->app->Request->query->getInt('length');
        $draw = $this->app->Request->query->getInt('draw');

        if ((int)$printerId === 0) {
            throw new RuntimeException('Printer-ID darf nicht leer sein');
        }

        foreach ($columns as $column) {
            if ($column['data'] === 'gedruckt') {
                if (!empty($column['search']['value'])) {
                    $filter['ungedruckt'] = true;
                } else {
                    $filter['ungedruckt'] = false;
                }
                if ($draw === 1) {
                    $filter['ungedruckt'] = true;
                }
            }
        }

        $table = new DownloadSpoolerTable($this->app, $printerId);
        $searchQuery = !empty($search['value']) ? $search['value'] : null;
        $orderCol = (int)$order[0]['column'];
        $orderDir = strtolower($order[0]['dir']) === 'desc' ? 'DESC' : 'ASC';

        return $table->GetData($filter, $searchQuery, $orderCol, $orderDir, $offset, $limit, $draw);
    }

    /**
     * Alle "noch nicht gedruckten" Dateien herunterladen
     *
     * @param int|null $printerId
     *
     * @return void
     */
    protected function DownloadSpoolerUnprintedFiles($printerId = null)
    {
        $gateway = $this->GetDownloadSpoolerGateway();
        $unprinted = $gateway->getUnprintedFileIdsByUser($this->app->User->GetID());
        if (empty($unprinted)) {
            return;
        }

        // Einzelne Datei herunterladen
        if ((!empty($unprinted) ? count($unprinted) : 0) === 1) {
            $this->DownloadSpoolerFile($unprinted[0]);
        } else {
            $this->DownloadSpoolerZipCompilation($unprinted, $printerId);
        }
    }

    /**
     * Einzelne Druckerspooler-Datei herunterladen
     *
     * @param int $fileId
     *
     * @return void
     * @throws DownloadSpoolerExceptionInterface
     *
     */
    protected function DownloadSpoolerFile($fileId)
    {
        $service = $this->GetDownloadSpoolerService();
        $data = $service->fetchFile($fileId, $this->app->User->GetID());

        $rawData = base64_decode($data['content']);
        if (!empty($data['filename'])) {
            $filename = urlencode($data['filedate'] . '-' . $data['filename']);
        } else {
            $filename = urlencode($data['filedate']);
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Type: application/force-download');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');
        header('Pragma: public');
        header('Content-Length: ' . strlen($rawData));
        echo $rawData;
        $this->app->erp->ExitWawi();
    }

    /**
     * @param array|int[] $spoolerIds
     * @param int|null $printerId
     *
     * @return void
     */
    protected function DownloadSpoolerZipCompilation($spoolerIds, $printerId = null)
    {
        $downloadSpooler = $this->GetDownloadSpoolerService();
        $zipPath = $downloadSpooler->createZipCompilation($spoolerIds, $this->app->User->GetID(), $printerId);
        $zipName = 'DOWNLOAD_SPOOLER_' . date('Y-m-d') . '.zip';

        // Download ZIP
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename=' . $zipName);
        header('Content-Length: ' . filesize($zipPath));
        readfile($zipPath);
        unlink($zipPath);
        $this->app->erp->ExitWawi();
    }

    /**
     * @param array|int[] $spoolerIds
     * @param int|null $printerId
     *
     * @return void
     */
    protected function DownloadSpoolerPdfCompilation($spoolerIds, $printerId = null)
    {
        $downloadSpooler = $this->GetDownloadSpoolerService();
        $pdfPath = $downloadSpooler->createPdfCompilation($spoolerIds, $this->app->User->GetID(), $printerId);
        $pdfName = 'DOWNLOAD_SPOOLER_' . date('Y-m-d') . '.pdf';

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header("Content-Type: application/force-download");
        header('Content-Disposition: attachment; filename=' . $pdfName);
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($pdfPath));
        readfile($pdfPath);
        unlink($pdfPath);
        $this->app->erp->ExitWawi();
    }

    /**
     * @param int $fileId
     * @param int $printerId
     *
     * @return bool
     */
    protected function DeleteSpoolerFile($fileId, $printerId)
    {
        $downloadSpooler = $this->GetDownloadSpoolerService();

        return $downloadSpooler->deleteFile($fileId, $printerId);
    }

    /**
     * @param string $dir
     *
     * @return bool
     */
    protected function DelFolder($dir)
    {
        $files = array_diff(scandir($dir, SCANDIR_SORT_NONE), ['.', '..']);
        if (!empty($files)) {
            foreach ($files as $file) {
                if (is_dir($dir . '/' . $file)) {
                    $this->DelFolder($dir . '/' . $file);
                } elseif (is_file($dir . '/' . $file)) {
                    @unlink($dir . '/' . $file);
                }
            }
        }
        return is_dir($dir) && @rmdir($dir);
    }

    public function WelcomeInfo()
    {
        $this->app->erp->Headlines('Informationen zur Software');

        $this->app->Tpl->RenderTwig('PAGE', 'welcome/info.html.twig');
    }


    public function WelcomeMenu()
    {
        $this->app->Tpl->Add('KURZUEBERSCHRIFT', '<h2>Startseite</h2>');
    }


    public function WelcomeMain()
    {
        $this->app->Tpl->Set('UEBERSCHRIFT', 'Herzlich Willkommen ' . $this->app->User->GetDescription() . '!');
        $this->WelcomeMenu();

        // muss jeder sehen
        $this->app->erp->LagerAusgehend('ARTIKEL');

        $this->app->Tpl->Parse('PAGE', 'welcome_main.tpl');
    }


    public function WelcomeStartseite()
    {
        $this->app->erp->Startseite();
    }

    public function WelcomeLogin()
    {
        if ($this->app->User->GetID() > 0) {
            // alle cookies SpryMedia loeschen

            // Setzen des Verfalls-Zeitpunktes auf 1 Stunde in der Vergangenheit
            $this->app->erp->ClearCookies();
            $startseite = '';
            if ($code = $this->app->Request->request->getString('code')) {
                $result = $this->app->EntityManager->getConnection()->executeQuery(
                    "SELECT url, reduziert FROM stechuhrdevice WHERE code = :code AND aktiv = 1 LIMIT 1",
                    ['code' => $code],
                )->fetchAssociative();

                $startseite = $result['url'] ?? '';
                $isReduziert = $result['reduziert'] ?? null;

                if ($isReduziert) {
                    $this->app->User->SetParameter('stechuhrdevicereduziert', true);
                }

                if ($isReduziert && empty($startseite)) {
                    $startseite = 'index.php?module=stechuhr&action=list&prodcmd=arbeitsschritt';
                }
            }
            $this->app->erp->Startseite($startseite);
        } else {
            $this->app->erp->InitialSetup();
            $this->app->Tpl->Set('UEBERSCHRIFT', 'Xentral &middot; Enterprise Warehouse Management');

            $this->app->acl->Login();
        }
    }

    public function WelcomeLogout()
    {
        $this->app->acl->Logout();
        $this->app->erp->ClearCookies();
    }

    public function WelcomeUnlock()
    {
        $gui = $this->app->Request->query->getAlnum('gui');
        $id = $this->app->Request->query->getInt('id');
        $backlink = $this->app->Request->query->getString('backlink');

        // Prüfen ob Backlink mit index.php? beginnt; ansonsten ist Open Redirect möglich
        if (!empty($backlink) && strpos($backlink, 'index.php?') !== 0) {
            unset($backlink);
        }

        // sperre entfernen bzw umschreiben
        if ($gui === 'angebot' || $gui === 'auftrag' || $gui === 'rechnung' || $gui === 'bestellung' || $gui === 'gutschrift' || $gui === 'lieferschein' || $gui === 'retoure' || $gui === 'adresse' || $gui === 'artikel' || $gui === 'produktion' || $gui === 'reisekosten' || $gui === 'preisanfrage') {
            $this->app->EntityManager->getConnection()->executeStatement(
                "UPDATE `$gui` SET usereditid = :userId WHERE id = :id LIMIT 1",
                ['userId' => $this->app->User->GetID(), 'id' => $id],
            );
            if (!empty($backlink)) {
                header('Location: ' . $backlink);
            } else {
                header("Location: index.php?module=$gui&action=edit&id=$id");
            }
            exit;
        }
    }


    public function VorgangAnlegen()
    {
        //print_r($_SERVER['HTTP_REFERER']);
        $titel = $this->app->Request->query->getString('titel');

        $url = parse_url($_SERVER['HTTP_REFERER']);
        //$url = parse_url("http://dev.eproo.de/~sauterbe/eprooSystem-2009-11-21/webroot/index.php?module=ticket&action=edit&id=1");

        //module=ticket&action=edit&id=1
        //$url['query']
        $params = explode('&', $url['query']);
        foreach ($params as $value) {
            $attribut = explode('=', $value);
            $arrPara[$attribut[0]] = (!empty($attribut) ? count($attribut) : 0) > 1 ? $attribut[1] : '';
        }

        $adresse = $this->app->User->GetAdresse();
        if ($titel == '') {
            $titel = ucfirst($arrPara['module']) . ' ' . $arrPara['id'];
        }
        $href = $url['query'];
        $this->app->erp->AddOffenenVorgang($adresse, $titel, $href);

        header('Location: ' . $_SERVER['HTTP_REFERER']);
    }

    /**
     * @param int $cacheTime
     *
     * @return string
     */
    public function loadChangeLogCacheFile(int $cacheTime = 4): string
    {
        $version = $this->app->erp->Version();
        $revision = $this->app->erp->Revision();

        $tmp = explode('.', $revision);
        $branch = strtolower($version) . '_' . $tmp[0] . '.' . $tmp[1];

        $blogUrl = "https://{$this->app->Conf->updateHost}/wawision_2016.php?all=1&branch=" . $branch;
        $cacheFile = $this->app->erp->GetTMP() . md5($blogUrl);
        $cacheFile2 = $this->app->erp->GetTMP() . md5($blogUrl) . '2';
        if (!file_exists($cacheFile2)) {
            if (file_exists($cacheFile)) {
                @unlink($cacheFile);
            }
        } else {
            if (trim(file_get_contents($cacheFile2)) != $version . $revision) {
                @unlink($cacheFile);
            }
        }

        if (!file_exists($cacheFile) || ((time() - filemtime($cacheFile)) > 3600 * $cacheTime)) {
            if ($feed_contents = @file_get_contents($blogUrl)) {
                $fp = fopen($cacheFile, 'w');
                fwrite($fp, $feed_contents);
                fclose($fp);
                @file_put_contents($cacheFile2, $version . $revision);
            }
        }

        return $cacheFile;
    }

    /**
     * @param int $hours
     */
    public function loadChangeLogByTime(int $hours = 4): void
    {
        $lastTime = (int)$this->app->erp->GetKonfiguration('welcome_changelog_last_save');
        if ($lastTime > 0 && time() - $lastTime < $hours * 3600) {
            return;
        }

        $this->loadArrFromChangeLogFile();
    }

    /**
     * @return array
     */
    public function loadArrFromChangeLogFile(): array
    {
        $file = $this->loadChangeLogCacheFile();
        $this->app->erp->SetKonfigurationValue('welcome_changelog_last_save', time());
        if (!is_file($file)) {
            $this->app->erp->SetKonfigurationValue('welcome_changelog_count', 0);
            return ['changelog' => [], 'act' => [], 'found' => false, 'types' => [], 'count_new' => 0,];
        }
        $content = file_get_contents($file);
        if (empty($content)) {
            $this->app->erp->SetKonfigurationValue('welcome_changelog_count', 0);
            return ['changelog' => [], 'act' => [], 'found' => false, 'types' => [], 'count_new' => 0,];
        }
        $xml = simplexml_load_string($content);
        if (empty($xml)) {
            $this->app->erp->SetKonfigurationValue('welcome_changelog_count', 0);
            return ['changelog' => [], 'act' => [], 'found' => false, 'types' => [], 'count_new' => 0,];
        }
        $json = json_encode($xml);

        $array = json_decode($json, true);
        if (empty($array)) {
            $this->app->erp->SetKonfigurationValue('welcome_changelog_count', 0);
            return ['changelog' => [], 'act' => [], 'found' => false, 'types' => [], 'count_new' => 0,];
        }
        $found = false;
        $akt = [];
        $version_revision = null;
        include dirname(dirname(__DIR__)) . '/version.php';
        if (isset($version_revision) && $version_revision != '') {
            $ra = explode('.', $version_revision);
            if (isset($ra[2]) && $ra[2] != '') {
                $citems = isset($array['channel']['item']) ? count($array['channel']['item']) : 0;
                for ($i = 0; $i < $citems; $i++) {
                    if ($found !== false) {
                        if (!empty($array['channel']['item'][$i]['title'])) {
                            $akt['channel']['item'][$i] = $array['channel']['item'][$i];
                        }
                        unset($array['channel']['item'][$i]);
                    } else {
                        $rev = isset($array['channel']['item'][$i]['guid']) ? $array['channel']['item'][$i]['guid'] : '';
                        if ($rev == '') {
                            $rev = trim(trim($array['channel']['item'][$i]['title']), ')');
                            $rev = trim(substr($rev, strrpos($rev, '(') + 4));
                        }
                        if ($rev == $ra[2]) {
                            $found = $i;
                            $akt['channel']['item'][$i] = $array['channel']['item'][$i];
                            unset($array['channel']['item'][$i]);
                        }
                    }
                }
            }
        }
        $citems = isset($array['channel']['item']) ? count($array['channel']['item']) : 0;

        $types = [];
        for ($i = 0; $i < $citems; $i++) {
            $messageType = explode(' ', ltrim($array['channel']['item'][$i]['description']));
            $messageType = strtolower($messageType[0]);
            if (!array_key_exists($messageType, $types)) {
                $types[$messageType] = [];
            }
            $types[$messageType][] = $array['channel']['item'][$i];
        }

        $this->app->erp->SetKonfigurationValue('welcome_changelog_count', $citems);

        return ['changelog' => $array, 'act' => $akt, 'found' => $found, 'types' => $types, 'count_new' => $citems,];
    }

    public function WelcomeChangelog()
    {
        $this->StartseiteMenu();

        $version_revision = null;
        $revision = $this->app->erp->Revision();

        $changeLogArray = $this->loadArrFromChangeLogFile();
        $found = $changeLogArray['found'];
        $array = $changeLogArray['changelog'];
        $akt = $changeLogArray['act'];
        $types = $changeLogArray['types'];

        $this->app->Tpl->Add(
            'TAB1',
            '<div class="row">
                                  <div class="row-height">
                                  <div class="col-md-12 col-md-height">
                                  <div class="inside inside-full-height">
                                  <fieldset><legend>Neue verf&uuml;gbare Updates</legend>',
        );
        if (!empty($array['channel']) && !empty($array['channel']['item']) && is_array($array['channel']['item'])
            && count($array['channel']['item']) > 0) {
            $listingOrder = ['new', 'add', 'change', 'fix', 'merge'];
            foreach ($listingOrder as $informations) {
                if (isset($types[$informations])) {
                    $this->app->Tpl->Add('TAB1', '<fieldset><legend>' . ucfirst($informations) . '</legend><ul>');
                    foreach ($types[$informations] as $information) {
                        $messageDate = DateTime::createFromFormat('Y-m-d', $information['pubDate']);
                        $messageDate = $messageDate->format('d.m.Y');
                        $this->app->Tpl->Add(
                            'TAB1',
                            '<li>' . $information['description'] . ' (' . $messageDate . ' rev ' . $information['guid'] . ')</li>',
                        );
                    }
                    $this->app->Tpl->Add('TAB1', '</ul></fieldset>');
                    unset($types[$informations]);
                }
            }

            //dynamic remaining messagetypes for output
            foreach ($types as $type => $informations) {
                $this->app->Tpl->Add('TAB1', '<fieldset><legend>' . ucfirst($type) . '</legend><ul>');
                foreach ($informations as $value) {
                    $messageDate = DateTime::createFromFormat('Y-m-d', $value['pubDate']);
                    $messageDate = $messageDate->format('d.m.Y');
                    $this->app->Tpl->Add(
                        'TAB1',
                        '<li>' . $value['description'] . ' (' . $messageDate . ' rev ' . $value['guid'] . ')</li>',
                    );
                }
                $this->app->Tpl->Add('TAB1', '</ul></fieldset>');
                unset($types[$type]);
            }
        } elseif ($found !== false) {
            $this->app->Tpl->Add(
                'TAB1',
                '<div class="info">Ihre Version ist auf dem neuesten Stand.</div>',
            );//<fieldset><legend>Ihre Version ist auf dem neuesten Stand.</legend></fieldset>');
        }
        $this->app->Tpl->Add('TAB1', '</fieldset></div></div></div></div>');


        if (!empty($akt)) {
            $this->app->Tpl->Add(
                'TAB1',
                '<div class="row">
                                    <div class="row-height">
                                    <div class="col-md-12 col-md-height">
                                    <div class="inside inside-full-height">
                                    <fieldset><legend>Letzte durchgef&uuml;hrte Updates</legend><ul>',
            );
            $citems = isset($akt['channel']['item']) ? count($akt['channel']['item']) : 0;
            for ($i = 0; $i < $citems; $i++) {
                if (empty($akt['channel']['item'][$i]['title'])) {
                    continue;
                }
                $messageDate = DateTime::createFromFormat('Y-m-d', $akt['channel']['item'][$i]['pubDate']);
                $messageDate = $messageDate->format('d.m.Y');
                $this->app->Tpl->Add(
                    'TAB1',
                    '<li>' . $akt['channel']['item'][$i]['description'] . ' (' . $messageDate . ' rev ' . $akt['channel']['item'][$i]['guid'] . ')</li>',
                );
            }
            $this->app->Tpl->Add('TAB1', '</ul></fieldset></div></div></div></div>');
        }

        $versionNumber = explode('.', $revision);
        $versionNumber = $versionNumber[0] . '.' . $versionNumber[1];

        $allChangesInVersionUrl = "https://{$this->app->Conf->updateHost}/xentral_2020.php?branch=" . $versionNumber;
        $allChangesInVersionCacheFile = $this->app->erp->GetTMP() . md5($allChangesInVersionUrl);
        $allChangesInVersionCacheFile2 = $this->app->erp->GetTMP() . md5($allChangesInVersionUrl) . '2';

        if (!file_exists($allChangesInVersionCacheFile2)) {
            if (file_exists($allChangesInVersionCacheFile)) {
                @unlink($allChangesInVersionCacheFile);
            }
        } else {
            if (trim(file_get_contents($allChangesInVersionCacheFile2)) != $versionNumber) {
                @unlink($allChangesInVersionCacheFile);
            }
        }
        $allChangesInVersionCacheTime = 4; # hours
        if (!file_exists($allChangesInVersionCacheFile) || ((time() - filemtime(
                        $allChangesInVersionCacheFile,
                    )) > 3600 * $allChangesInVersionCacheTime)) {
            if ($feed_contents = @file_get_contents($allChangesInVersionUrl)) {
                $fp = fopen($allChangesInVersionCacheFile, 'w');
                fwrite($fp, $feed_contents);
                fclose($fp);
                @file_put_contents($allChangesInVersionCacheFile2, $versionNumber);
            }
        }
        $feed_contents = file_get_contents($allChangesInVersionCacheFile);

        $allChangesInVersion = json_decode($feed_contents, true);

        $this->app->Tpl->Add(
            'TAB2',
            '<div class="row">
                                  <div class="row-height">
                                  <div class="col-md-12 col-md-height">
                                  <div class="inside inside-full-height">
                                  <fieldset><legend>Alle &Auml;nderungen in Version ' . $versionNumber . '</legend>',
        );

        $countChanges = isset($allChangesInVersion['new']) ? count($allChangesInVersion['new']) : 0;
        $types = [];
        for ($i = 0; $i < $countChanges; $i++) {
            $messageType = explode(' ', ltrim($allChangesInVersion['new'][$i]['msg']));
            $messageType = strtolower($messageType[0]);
            if (!array_key_exists($messageType, $types)) {
                $types[$messageType] = [];
            }
            $types[$messageType][] = $allChangesInVersion['new'][$i];
        }

        //fixed order of messagetypes for output
        $listingOrder = ['new', 'add', 'change', 'fix', 'merge'];
        foreach ($listingOrder as $informations) {
            if (isset($types[$informations])) {
                $this->app->Tpl->Add('TAB2', '<fieldset><legend>' . ucfirst($informations) . '</legend><ul>');
                foreach ($types[$informations] as $information) {
                    $messageDate = DateTime::createFromFormat('Y-m-d\TH:i:sP', $information['date']);
                    $messageDate = $messageDate->format('d.m.Y');
                    $this->app->Tpl->Add(
                        'TAB2',
                        '<li>' . $information['msg'] . ' (' . $messageDate . ' rev ' . $information['rev'] . ')</li>',
                    );
                }
                $this->app->Tpl->Add('TAB2', '</ul></fieldset>');
                unset($types[$informations]);
            }
        }

        //dynamic remaining messagetypes for output
        foreach ($types as $type => $informations) {
            $this->app->Tpl->Add('TAB2', '<fieldset><legend>' . ucfirst($type) . '</legend><ul>');
            foreach ($informations as $value) {
                $messageDate = DateTime::createFromFormat('Y-m-d\TH:i:sP', $value['date']);
                $messageDate = $messageDate->format('d.m.Y');
                $this->app->Tpl->Add(
                    'TAB2',
                    '<li>' . $value['msg'] . ' (' . $messageDate . ' rev ' . $value['rev'] . ')</li>',
                );
            }
            $this->app->Tpl->Add('TAB2', '</ul></fieldset>');
            unset($types[$type]);
        }
        $this->app->Tpl->Add('TAB2', '</fieldset></div></div></div></div>');


        $this->app->Tpl->Set('VERSION', $versionNumber);
        $this->app->Tpl->Parse('PAGE', 'welcome_changelog.tpl');
    }


    public function VorgangEdit()
    {
        $vorgang = $this->app->Request->query->getInt('vorgang');
        $titel = $this->app->Request->query->getString('titel');
        $this->app->erp->RenameOffenenVorgangID($vorgang, $titel);
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    public function VorgangEntfernen()
    {
        $vorgang = $this->app->Request->query->getInt('vorgang');
        $this->app->erp->RemoveOffenenVorgangID($vorgang);
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    private function getCurrentDefaultLanguage($fromPost)
    {
        if (empty($fromPost)) {
            $fromPost = $this->app->erp->Firmendaten('preferredLanguage');

            if (empty($fromPost)) {
                $fromPost = 'deutsch';
            }
        }
        return $fromPost;
    }

    /**
     * Liefert einen String aus HTML-Optionen zurück
     * @param string $fromPost
     * @return string
     */
    private function languageSelectOptions($fromPost = '')
    {
        $select = $this->getCurrentDefaultLanguage($fromPost);

        $out = "";
        $sprachen = $this->getLanguages();

        foreach ($sprachen as $sprache) {
            $selected = (($select == $sprache) ? 'selected' : '');
            $out .= "<option value=\"$sprache\" $selected>$sprache</option>";
        }
        return $out;
    }

    /**
     * Liefert einen Array aus Strings zurück. Immer mindestens 'deutsch' enthalten
     * @return array
     */
    private function getLanguages()
    {
        $sprachen[] = 'deutsch';
        $folder = __DIR__ . '/../../languages';
        if (is_dir($folder)) {
            $handle = opendir($folder);
            if ($handle) {
                while ($file = readdir($handle)) {
                    if ($file[0] !== '.') {
                        if (is_dir($folder . '/' . $file) && (file_exists(
                                    $folder . '/' . $file . '/variablen.php',
                                ) || file_exists($folder . '/' . $file . '/variablen_custom.php'))) {
                            if ($file == 'german') {
                                $file = 'deutsch';
                            }
                            if (!in_array($file, $sprachen)) {
                                $sprachen[] = $file;
                            }
                        }
                    }
                }
                closedir($handle);
            }
        }
        return $sprachen;
    }

    /**
     * @param array $arr
     *
     * @return array
     */
    protected function formatOptionArrForVue($arr)
    {
        $ret = [];
        foreach ($arr as $key => $value) {
            $ret[] = [
                'text' => $value,
                'value' => $key,
            ];
        }
        return $ret;
    }

    /**
     * @return JsonResponse
     */
    protected function HandleStartClickByClick()
    {
        $isAdminAdmin = $this->app->acl->IsAdminadmin();
        $hasRole = !empty($this->app->User->GetField('role'));
        /** @var Benutzer $userModule */
        $userModule = $this->app->loadModule('benutzer');
        $firstPage = [
            'type' => 'form',
            'submitType' => 'submit',
            'submitUrl' => $isAdminAdmin
                ? 'index.php?module=welcome&action=settings&cmd=changepasswordclickbyclick'
                : 'index.php?module=welcome&action=settings&cmd=changeroleclickbyclick',
            'icon' => $isAdminAdmin ? 'password-icon' : 'add-person-icon',
            'headline' => $isAdminAdmin ? 'Passwort ändern' : 'Ihre Rolle',
            'subHeadline' => $isAdminAdmin ? 'Bitte geben Sie ein Passwort ein und bestätige es mit einer zweiten Eingabe'
                : 'Bitte geben Sie Ihre Rolle im Unternehmen ein',
            'form' => [],
            'ctaButtons' => [
                [
                    'title' => 'Weiter',
                    'type' => 'submit',
                    'action' => 'submit',
                ],
            ],
        ];

        if ($isAdminAdmin) {
            $firstPage['form'] = [
                [
                    'id' => 0,
                    'name' => 'set-password-row',
                    'inputs' => [
                        [
                            'type' => 'password',
                            'name' => 'setPassword',
                            'label' => 'Passwort',
                            'validation' => true,
                        ],
                    ],
                ],
                [
                    'id' => 1,
                    'name' => 'repeat-password-row',
                    'inputs' => [
                        [
                            'type' => 'password',
                            'name' => 'repeatPassword',
                            'label' => 'Passwort wiederholen',
                            'connectedTo' => 'setPassword',
                            'validation' => true,
                            'customErrorMsg' => 'Passwörter sind nicht identisch',
                        ],
                    ],
                ],
            ];
        }

        if (!$hasRole) {
            $firstPage['form'][] = [
                'id' => 2,
                'name' => 'role-row',
                'inputs' => [
                    [
                        'type' => 'select',
                        'name' => 'teamMemberRole',
                        'label' => 'Rolle',
                        'validation' => false,
                        'options' => $this->formatOptionArrForVue($userModule->getRoleOptions()),
                    ],
                ],
            ];
            $firstPage['form'][] = [
                'id' => 3,
                'name' => 'otherrole-row',
                'inputs' => [
                    [
                        'type' => 'text',
                        'name' => 'teamMemberOtherRole',
                        'label' => 'Sonstiges',
                        'validation' => false,
                    ],
                ],
            ];
        }

        $pages[] = $firstPage;


        /*/ This is pointless because the mailaccount is not configured yet
    if($isAdminAdmin) {
      $isFirstAdmin = $this->app->User->GetType() === 'admin' && $this->app->User->GetName('admin');
      if($isFirstAdmin) {
        $pages[] = [
          'type' => 'form',
          'submitType' => 'submit',
          'submitUrl' => 'index.php?module=welcome&action=settings&cmd=inviteteamclickbyclick',
          'icon' => 'add-person-icon',
          'headline' => 'Lade Dein Team ein',
          'subHeadline' => 'Du kannst bis zu 5 weitere Mitglieder hinzufügen',
          'form' => [
            [
              'id' => 0,
              'name' => 'add-person-row',
              'removable' => true,
              'add' => [
                'allow'=> true,
                'maximum'=> 5,
                'text'=> 'Weitere Mitglieder hinzufügen'
              ],
              'inputs' => [
                [
                  'type' => 'text',
                  'name' => 'teamMemberName',
                  'label' => 'Name',
                  'validation' => false,
                  'customErrorMsg' => 'too short',
                ],
                [
                  'type' => 'email',
                  'name' => 'teamMemberEmail',
                  'label' => 'E-Mail',
                  'validation' => false,
                ],

                [
                  'type' => 'select',
                  'name' => 'teamMemberRole',
                  'label' => 'Rolle',
                  'validation' => false,
                  'options' => $this->formatOptionArrForVue($userModule->getRoleOptions())
                ]
              ]
            ]
          ],
          'ctaButtons' => [
            [
              'title' => 'Weiter',
              'type' => 'submit',
              'action' => 'submit'
            ]]
        ];
      }
    }
*/

        if ($isAdminAdmin) {
            $subHeadline = 'Bitte nach der Installation das <a href="index.php?module=upgrade" target="_blank">Datenbank-Upgrade</a> durchführen.';
        } else {
            $subHeadline = 'Es kann nun losgehen.';
        }

        $lastPage = [
            'type' => 'defaultPage',
            'icon' => 'add-person-icon',
            'headline' => $isAdminAdmin ? 'Das Passwort wurde geändert' : 'Ihre Rolle wurde gespeichert',
            'subHeadline' => $subHeadline,
            'ctaButtons' => [
                [
                    'title' => 'Ok',
                    'action' => 'close',
                ],
            ],
        ];
        if ($showExampleImport) {
            $lastPage['subHeadline'] .= '<br /><a href="index.php?module=systemtemplates&action=list">Beispieldaten einspielen</a>';
        }

        $pages[] = $lastPage;

        return new JsonResponse(['success' => true, 'pages' => $pages]);
    }

    /**
     * @return JsonResponse
     */
    protected function HandleInviteTeamClickByClick()
    {
        $userNames = [];
        $members = [];
        for ($i = 0; $i < 5; $i++) {
            $userName = $this->app->Request->request->getString('teamMemberName' . ($i > 0 ? (string)$i : ''));
            if (empty($userName)) {
                continue;
            }
            if (in_array($userName, $userNames)) {
                return new JsonResponse(['error' => 'Usernamen sind identisch'], Response::HTTP_BAD_REQUEST);
            }
            $userNames[] = $userName;
            $userEmail = $this->app->Request->request->getString('teamMemberEmail' . ($i > 0 ? (string)$i : ''));
            if (empty($userEmail)) {
                return new JsonResponse(['error' => 'Bitte füllen Sie die Email-Adresse aus'],
                    Response::HTTP_BAD_REQUEST);
            }
            $userRole = $this->app->Request->request->getString('teamMemberRole' . ($i > 0 ? (string)$i : ''));

            $conn = $this->app->EntityManager->getConnection();
            $userCount = (int)$conn->fetchOne(
                "SELECT COUNT(`id`) FROM `user` WHERE `username` = :username",
                ['username' => $userName],
            );
            $addressCount = (int)$conn->fetchOne(
                "SELECT COUNT(`id`) FROM `adresse` WHERE `name` = :name",
                ['name' => $userName],
            );

            if ($userCount > 0 || $addressCount > 0) {
                return new JsonResponse(
                    ['error' => sprintf('Usernamen %s existiert bereits', $userName)],
                    Response::HTTP_BAD_REQUEST,
                );
            }
            $members[] = ['username' => $userName, 'email' => $userEmail, 'role' => $userRole];
        }
        if (empty($members)) {
            return new JsonResponse(['success' => true]);
        }

        $conn = $this->app->EntityManager->getConnection();
        $projectId = (int)$conn->fetchOne(
            'SELECT `id` FROM `projekt` WHERE `geloescht` = 0 ORDER BY `oeffentlich` DESC LIMIT 1',
        );

        foreach ($members as $member) {
            $password = $member['username'];

            $conn->executeStatement(
                "INSERT INTO `adresse` (`name`, `email`, `projekt`) VALUES (:name, :email, :projekt)",
                ['name' => $member['username'], 'email' => $member['email'], 'projekt' => $projectId],
            );
            $addressId = (int)$conn->lastInsertId();

            $this->app->erp->AddRolleZuAdresse($addressId, 'Mitarbeiter', 'von', 'Projekt', $projectId);

            $vorlage = (string)$conn->fetchOne(
                "SELECT `bezeichnung` FROM `uservorlage` WHERE `bezeichnung` = :role LIMIT 1",
                ['role' => $member['role']],
            );

            $conn->executeStatement(
                "INSERT INTO `user`
              (`username`, `passwordmd5`, `hwtoken`, `type`, `repassword`, `externlogin`,`firma`,`fehllogins`, 
               `adresse`,`standarddrucker`,`settings`, `activ`,`vorlage`,`role`) 
             VALUES (:username, :passwordmd5, 0, 'benutzer', 0, 1, 1, 0,
                     :adresse, 0, '', 1, :vorlage, :role)",
                [
                    'username' => $member['username'],
                    'passwordmd5' => md5($password),
                    'adresse' => $addressId,
                    'vorlage' => $vorlage,
                    'role' => $member['role'],
                ],
            );
            $newUserId = (int)$conn->lastInsertId();

            $this->app->erp->insertDefaultUserRights($newUserId);
            if ($vorlage !== '') {
                $this->app->erp->AbgleichBenutzerVorlagen($newUserId);
            }
            $link = $this->app->Location->getServer();
            $this->app->erp->MailSend(
                $this->app->erp->Firmendaten('email'),
                $this->app->erp->Firmendaten('absendername'),
                $member['email'],
                $member['username'],
                'Einladung',
                sprintf(
                    'Hallo %s,<br />
              <br />
              Willkommen auf Xentral.<br />
              <br />
              Du kannst dich mit den folgenden Zugangsdaten einloggen<br /><br />
              Username: %s<br />
              Passwort: %s<br />
              <a href="%s" style="margin-top:24px;display:inline-block;padding:10px 23px;color:#fff;background:#2DCA73;border-radius:4px;font-size:15px;font-weight:600;text-decoration:none;cursor:pointer">Hier gehts los</a>',
                    $member['username'],
                    $member['username'],
                    $password,
                    $link,
                ),
                '',
                0,
                true,
                '',
                '',
                true,
            );
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * @return JsonResponse
     */
    protected function HandleChangeRoleClickByClick()
    {
        $role = $this->app->Request->request->getString('teamMemberRole');
        if (empty($role)) {
            return new JsonResponse(
                ['error' => 'Bitte eine Rolle angeben!'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $this->app->EntityManager->getConnection()->executeStatement(
            "UPDATE `user` SET `role` = :role WHERE `id` = :id",
            ['role' => $role, 'id' => $this->app->User->GetID()],
        );

        return new JsonResponse(['success' => true]);
    }

    /**
     * @return JsonResponse
     */
    protected function HandlePasswordChangeClickByClick()
    {
        $password = $this->app->Request->request->getString('setPassword');
        $repassword = $this->app->Request->request->getString('repeatPassword');
        $role = $this->app->Request->request->getString('teamMemberRole');
        $otherRole = $this->app->Request->request->getString('teamMemberOtherRole');
        $hasUserRole = !empty($this->app->User->GetField('role'));
        if ($otherRole !== '' && ($role === '' || $role === 'Sonstiges')) {
            $role = $otherRole;
        }

        if (
            !$hasUserRole
            && $role === ''
            && (string)$this->app->EntityManager->getConnection()->fetchOne(
                'SELECT `role` FROM `user` WHERE `id` = :id',
                ['id' => $this->app->User->GetID()],
            )
        ) {
            return new JsonResponse(
                ['error' => 'Bitte gebe eine Rolle ein!'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $passwordunescaped = $this->app->Request->request->getString('setPassword');
        if (empty($password)) {
            return new JsonResponse(
                ['error' => 'Passworteingabe falsch! Bitte geben Sie ein Passwort ein!'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        if (strlen($password) < 8) {
            return new JsonResponse(
                ['error' => 'Passworteingabe falsch! Das Passwort muss mindestens 8 Zeichen enthalten!'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        if ($password !== $repassword) {
            return new JsonResponse(
                ['error' => 'Passworteingabe falsch! Bitte zwei mal das gleiche Passwort eingeben!'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        if ($password === $this->app->User->GetUsername()) {
            return new JsonResponse(
                ['error' => 'Das Passwort darf nicht dem Username entsprechen!'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $this->changeUserPassword($password, $passwordunescaped);

        if (!empty($role)) {
            $this->app->EntityManager->getConnection()->executeStatement(
                "UPDATE `user` SET `role` = :role WHERE `id` = :id",
                ['role' => $role, 'id' => $this->app->User->GetID()],
            );
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * @param string $password
     * @param string $passwordunescaped
     *
     * @return string|null
     */
    protected function changeUserPassword($password, $passwordunescaped)
    {
        if (!empty($password) && $password !== $this->app->User->GetUsername()) {
            $conn = $this->app->EntityManager->getConnection();
            $userId = (int)$this->app->User->GetID();

            $hasPasswordHashColumn = false;
            try {
                $conn->fetchOne(
                    "SELECT u.passwordhash FROM `user` AS u WHERE u.id = :id LIMIT 1",
                    ['id' => $userId],
                );
                $hasPasswordHashColumn = true;
            } catch (\Throwable $e) {
                $hasPasswordHashColumn = false;
            }

            if ($hasPasswordHashColumn) {
                $options = [
                    'cost' => 12,
                ];
                $passwordhash = @password_hash($passwordunescaped, PASSWORD_BCRYPT, $options);
                if (!empty($passwordhash)) {
                    $conn->executeStatement(
                        "UPDATE `user` SET
                                `passwordhash` = :passwordhash,
                                `password` = '',
                                `passwordmd5` = '',
                                `passwordsha512` = '',
                                `salt` = ''
                             WHERE `id` = :id LIMIT 1",
                        ['passwordhash' => $passwordhash, 'id' => $userId],
                    );

                    return '<div class="warning">{|Passwort wurde erfolgreich ge&auml;ndert!|}</div>';
                }
            } else {
                $salt = null;
                try {
                    $salt = $conn->fetchOne(
                        "SELECT u.salt FROM `user` AS u WHERE u.id = :id LIMIT 1",
                        ['id' => $userId],
                    );
                } catch (\Throwable $e) {
                    $salt = null;
                }

                if (!empty($salt) || $salt === '0') {
                    // keep value
                } else {
                    $salt = hash('sha512', microtime(true));
                }

                $passwordsha512 = hash('sha512', $password . $salt);

                if (!empty($salt) && !empty($passwordsha512)) {
                    $conn->executeStatement(
                        "UPDATE `user` SET
                                `password` = '',
                                `passwordmd5` = '',
                                `salt` = :salt,
                                `passwordsha512` = :passwordsha512
                             WHERE `id` = :id LIMIT 1",
                        ['salt' => $salt, 'passwordsha512' => $passwordsha512, 'id' => $userId],
                    );
                } else {
                    $conn->executeStatement(
                        "UPDATE `user` SET
                                `password` = '',
                                `passwordmd5` = MD5(:password),
                             WHERE `id` = :id LIMIT 1",
                        [
                            'password' => $password,
                            'id' => $userId,
                        ],
                    );
                }
            }
        }

        return null;
    }

    /**
     * Passwort-Änderung
     *
     * @return void
     */
    protected function HandlePasswordChange()
    {
        $password = $this->app->Request->request->getString('password');
        $repassword = $this->app->Request->request->getString('passwordre');
        $passwordunescaped = $this->app->Request->request->getString('password');


        if (!empty($password) && $password !== $repassword) {
            $this->app->Tpl->Set(
                'MESSAGE',
                '<div class="error">{|Passworteingabe falsch! Bitte zwei mal das gleiche Passwort eingeben!|}</div>',
            );
            return;
        }

        if ($password === $this->app->User->GetUsername()) {
            $this->app->Tpl->Set(
                'MESSAGE',
                '<div class="error">{|Das Passwort darf nicht dem Username entsprechen!|}</div>',
            );
            return;
        }

        $message = $this->changeUserPassword($password, $passwordunescaped);
        if ($message !== null) {
            $this->app->Tpl->Set('MESSAGE', $message);
        }
    }

    /**
     * Profileinstellungen speichern
     *
     * @return void
     */
    protected function HandleProfileSettingsSave()
    {
        $submit_startseite = $this->app->Request->request->getString('submit_startseite');
        $startseite = $this->app->Request->request->getString('startseite');
        $chat_popup = $this->app->Request->request->getInt('chat_popup');
        $callcenter_notification = $this->app->Request->request->getInt('callcenter_notification');
        $defaultcolor = $this->app->Request->request->getString('defaultcolor');
        if ($defaultcolor === 'transparent') {
            $defaultcolor = '';
        }
        $sprachebevorzugen = $this->app->Request->request->getString('sprachebevorzugen');

        $conn = $this->app->EntityManager->getConnection();
        $userId = (int)$this->app->User->GetID();

        // umzug in tabelle user
        if ($this->app->User->GetParameter('welcome_defaultcolor_fuer_kalender') != '') {
            $defaultcolor = $this->app->Request->request->getString('defaultcolor');
            $conn->executeStatement(
                "UPDATE `user` SET `defaultcolor` = :defaultcolor WHERE `id` = :id LIMIT 1",
                ['defaultcolor' => $defaultcolor, 'id' => $userId],
            );
            $this->app->User->SetParameter('welcome_defaultcolor_fuer_kalender', '');
        }

        if ($sprachebevorzugen != '') {
            $conn->executeStatement(
                "UPDATE `user` SET `sprachebevorzugen` = :sprache WHERE `id` = :id LIMIT 1",
                ['sprache' => $sprachebevorzugen, 'id' => $userId],
            );
        }

        if ($submit_startseite != '') {
            $conn->executeStatement(
                "UPDATE `user` SET
                        `startseite` = :startseite,
                        `chat_popup` = :chat_popup,
                        `callcenter_notification` = :callcenter_notification,
                        `defaultcolor` = :defaultcolor
                     WHERE `id` = :id LIMIT 1",
                [
                    'startseite' => $startseite,
                    'chat_popup' => $chat_popup,
                    'callcenter_notification' => $callcenter_notification,
                    'defaultcolor' => $defaultcolor,
                    'id' => $userId,
                ],
            );
        }
    }

    /**
     * Vorhandenes Profilbild löschen
     *
     * @return void
     */
    protected function HandleProfilePictureDeletion()
    {
        $adresse = $this->app->User->GetAdresse();
        $dateien = $this->app->EntityManager->getConnection()->executeQuery(
            "SELECT d.id 
             FROM datei AS d 
             INNER JOIN datei_stichwoerter AS ds ON d.id = ds.datei
             WHERE d.geloescht = 0 AND ds.objekt LIKE 'Adressen' AND ds.parameter = :address AND ds.subjekt LIKE 'Profilbild' 
             ORDER BY d.id DESC",
            ['address' => $adresse],
        )->fetchFirstColumn();
        if (!empty($dateien)) {
            foreach ($dateien as $datei) {
                $this->app->erp->DeleteDatei($datei);
            }
        }
        return new RedirectResponse('index.php?module=welcome&action=settings');
    }

    /**
     * Neues Profilbild hochladen
     *
     * @return void
     */
    protected function HandleProfilePictureUpload(): RedirectResponse
    {
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
        $file = $this->app->Request->files->get('upload');

        $fileName = (string)$file->getClientOriginalName();
        $fileName = basename($fileName);
        $fileName = preg_replace('/[^\w.\- ]/u', '_', $fileName);

        $fileTmp = (string)$file->getFilename();

        if (!empty($fileTmp)) {
            $addressId = $this->app->User->GetAdresse();
            $fileid = $this->app->erp->CreateDatei(
                $fileName,
                'Profilbild',
                '',
                '',
                $fileTmp,
                $this->app->User->GetName(),
            );
            $this->app->erp->AddDateiStichwort($fileid, 'Profilbild', 'Adressen', $addressId);
        }
        return new RedirectResponse('index.php?module=welcome&action=settings');
    }

    /**
     * API-Account für mobile Apps de-/aktivieren
     *
     * @return void
     */
    protected function HandleMobileAppsAccount()
    {
        $hasPermission = (bool)$this->app->erp->RechteVorhanden('welcome', 'mobileapps');
        if (!$hasPermission) {
            $this->app->Tpl->Set(
                'MESSAGE',
                '<div class="error">Sie haben nicht die erforderlichen Rechte für einen API-Account!</div>',
            );
            return;
        }

        // API-Account anlegen und aktivieren
        if (!empty($this->app->Request->request->getString('mobile_app_api_create'))) {
            $title = $this->app->User->GetName() . ' / Mobile-Dashboard';
            $username = $this->app->User->GetUsername() . '_dashboard';
            $password = md5(uniqid('', true));

            $conn = $this->app->EntityManager->getConnection();
            $conn->executeStatement(
                "INSERT INTO `api_account`
                      (`id`, `bezeichnung`, `initkey`, `importwarteschlange_name`, `event_url`, `remotedomain`, `aktiv`,
                       `importwarteschlange`, `cleanutf8`, `uebertragung_account`, `permissions`)
                     VALUES (NULL, :title, :initkey, '', '', :remotedomain, 1, 0, 1, 0, :permissions)",
                [
                    'title' => $title,
                    'initkey' => $password,
                    'remotedomain' => $username,
                    'permissions' => json_encode(['mobile_app_communication']),
                ],
            );

            $apiAccountId = (int)$conn->fetchOne(
                "SELECT a.id FROM `api_account` AS `a` WHERE a.remotedomain = :remotedomain LIMIT 1",
                ['remotedomain' => $username],
            );
            $this->app->User->SetParameter('mobile_apps_api_account_id', $apiAccountId);

            if ($apiAccountId === 0) {
                $this->app->Tpl->Set('MESSAGE', '<div class="error">API-Account konnte nicht angelegt werden!</div>');
            }
        }

        // API-Account aktivieren
        if (!empty($this->app->Request->request->getString('mobile_app_api_activate'))) {
            $apiAccountId = (int)$this->app->User->GetParameter('mobile_apps_api_account_id');
            $this->app->EntityManager->getConnection()->executeStatement(
                "UPDATE api_account SET aktiv = 1 WHERE id = :id LIMIT 1",
                ['id' => $apiAccountId],
            );
        }

        // API-Account deaktivieren
        if (!empty($this->app->Request->request->getString('mobile_app_api_deactivate'))) {
            $apiAccountId = (int)$this->app->User->GetParameter('mobile_apps_api_account_id');
            $this->app->EntityManager->getConnection()->executeStatement(
                "UPDATE api_account SET aktiv = 0 WHERE id = :id LIMIT 1",
                ['id' => $apiAccountId],
            );
        }    }

    private function handleTOTPRegenerate(): Response
    {
        /** @var TOTPLoginService $totpLoginManager */
        $totpLoginManager = $this->app->Container->get('TOTPLoginService');

        $userID = $this->app->User->GetID();

        $totpLoginManager->regenerateUserSecret($userID);

        return new RedirectResponse('index.php?module=welcome&action=settings');
    }

    private function HandleTOTPToggle(): Response
    {
        /** @var TOTPLoginService $totpLoginManager */
        $totpLoginManager = $this->app->Container->get('TOTPLoginService');

        $userId = $this->app->User->GetID();

        if ($totpLoginManager->isTOTPEnabled($userId)) {
            $totpLoginManager->disableTotp($userId);
        } else {
            $totpLoginManager->enableTotp($userId);
        }

        return new RedirectResponse('index.php?module=welcome&action=settings');
    }

    protected function HandleGoogleMailAuth(): Response
    {
        $request = $this->app->Request;
        $email = $request->request->getString('gmail_address');
        $doAuthorize = $request->request->getBoolean('submit_authorize_gmail');
        $doTest = $request->request->getBoolean('submit_testmail_gmail');
        $redirect = new RedirectResponse('?module=welcome&action=settings');

        if (empty($email)) {
            $msg = '<div class="error">Fehler: Google E-Mail ist ein Pflichtfeld.</div>';
            $redirect->setTargetUrl(sprintf('%s&msg=%s', $redirect->getTargetUrl(), base64_encode($msg)));
            return $redirect;
        }
        /** @var GoogleAccountGateway $gateway */
        $gateway = $this->app->Container->get('GoogleAccountGateway');
        /** @var GoogleAccountService $service */
        $service = $this->app->Container->get('GoogleAccountService');
        try {
            $account = $gateway->getAccountByUser((int)$this->app->User->GetID());
        } catch (Exception $e) {
            $account = $service->createAccount((int)$this->app->User->GetID(), null, null);
        }
        $props = $gateway->getAccountProperties($account->getId());
        $props = $props->set('gmail_address', $email);
        $service->saveAccountProperties($account->getId(), $props);
        if ($doAuthorize && !$gateway->hasAccountScope($account->getId(), GoogleScope::MAIL)) {
            /** @var Session $session */
            $session = $this->app->Container->get('Session');
            /** @var GoogleAuthorizationService $authorizer */
            $authorizer = $this->app->Container->get('GoogleAuthorizationService');
            $redirect = $authorizer->requestScopeAuthorization(
                $session,
                [GoogleScope::MAIL],
                'index.php?module=welcome&action=settings',
            );
            SessionHandler::commitSession($session);
            return $redirect;
        }
        if (!$doAuthorize && $doTest) {
            return $this->HandleGoogleMailTest();
        }

        return $redirect;
    }

    protected function HandleGoogleMailTest(): Response
    {
        $request = $this->app->Request;
        if (!$request->request->getBoolean('submit_testmail_gmail')) {
            return new RedirectResponse('index.php?module=welcome&action=settings');
        }

        /** @var GoogleAccountGateway $gateway */
        $gateway = $this->app->Container->get('GoogleAccountGateway');
        $success = false;
        $error = false;
        $userId = (int)$this->app->User->GetID();
        $msg = '';
        try {
            $account = $gateway->getAccountByUser($userId);
            $email = $gateway->getAccountProperties($account->getId())->get('gmail_address');
            if (empty($email)) {
                throw new RuntimeException('Google Account has no email address');
            }
        } catch (Exception $e) {
            $error = true;
            $msg = '<div class="error">Google Account nicht gefunden.</div>';
        }

        if (!$error) {
            try {
                /** @var SystemMailer $mailer */
                $mailer = $this->app->Container->get('SystemMailer');
                $success = $mailer->composeAndSendEmail(
                    $email,
                    $this->app->User->GetName(),
                    [new EmailRecipient($email, $this->app->User->GetName())],
                    'Test Email Von Xentral',
                    '<p>Wenn Sie Diese Test-Email erhalten haben, hat die Verwendung von Google Mail funktioniert.</p>',
                );
            } catch (Exception $e) {
                $success = false;
            }
            if (!$success) {
                $msg = '<div class="error">Das Versenden der Test-Email ist fehlgeschlagen.
                Bitte wiederholen Sie den Authorisierungsvorgang.</div>';
            }
        }
        if (!$error && $success === true) {
            $msg = sprintf(
                '<div class="error2">Eine Test-Email wurde an "%s" verschickt. Bitte überprüfen Sie den Posteingang.</div>',
                $email,
            );
        }

        $url = 'index.php?module=welcome&action=settings';
        if (!empty($msg)) {
            $url .= sprintf('%s&msg=%s', $url, base64_encode($msg));
        }
        return new RedirectResponse($url);
    }

    /**
     * @return void
     */
    protected function renderGoogleMailSettings(): array
    {
        $result = [
            'active' => false,
            'auth' => false,
            'mailAddress' => null,
        ];
        if (!$this->app->Container->has('GoogleCredentialsService')) {
            return $result;
        }
        /** @var GoogleCredentialsService $credService */
        $credService = $this->app->Container->get('GoogleCredentialsService');
        if ($credService->existCredentials()) {
            $result['active'] = true;
        }
        /** @var GoogleAccountGateway $gateway */
        $gateway = $this->app->Container->get('GoogleAccountGateway');
        try {
            $account = $gateway->getAccountByUser((int)$this->app->User->GetID());
        } catch (GoogleAccountNotFoundException $e) {
            return $result;
        }
        if ($gateway->hasAccountScope($account->getId(), GoogleScope::MAIL)) {
            $result['auth'] = true;
        }
        $props = $gateway->getAccountProperties($account->getId());
        if ($props->has('gmail_address')) {
            $result['mailAddress'] = $props->get('gmail_address');
        }
        return $result;
    }

    protected function HandleGoogleCalendarSave(): Response
    {
        $request = $this->app->Request;
        $doAuthorize = $request->request->getBoolean('authorize_google_calendar');
        $doImport = $request->request->getBoolean('import_google_calendar');
        /** @var GoogleAccountGateway $gateway */
        $gateway = $this->app->Container->get('GoogleAccountGateway');
        /** @var GoogleAccountService $service */
        $service = $this->app->Container->get('GoogleAccountService');
        try {
            $account = $gateway->getAccountByUser((int)$this->app->User->GetID());
        } catch (Exception $e) {
            $account = $service->createAccount((int)$this->app->User->GetID(), null, null);
        }
        if ($doAuthorize && !$gateway->hasAccountScope($account->getId(), GoogleScope::CALENDAR)) {
            /** @var Session $session */
            $session = $this->app->Container->get('Session');
            /** @var GoogleAuthorizationService $authorizer */
            $authorizer = $this->app->Container->get('GoogleAuthorizationService');
            $redirect = $authorizer->requestScopeAuthorization(
                $session,
                [GoogleScope::CALENDAR],
                'index.php?module=welcome&action=settings&selectcalendar=1',
            );
            SessionHandler::commitSession($session);
            return $redirect;
        }
        if (!$doAuthorize && $doImport) {
            return $this->HandleGoogleCalendarImport();
        }

        return new RedirectResponse('?module=welcome&action=settings');
    }

    protected function HandleGoogleCalendarImport(): Response
    {
        $request = $this->app->Request;
        if (!$request->request->getBoolean('import_google_calendar')) {
            return new RedirectResponse('index.php?module=welcome&action=settings');
        }

        $msg = '';
        $userId = (int)$this->app->User->GetID();
        try {
            /** @var GoogleCalendarClientFactory $factory */
            $factory = $this->app->Container->get('GoogleCalendarClientFactory');
            /** @var GoogleCalendarSynchronizer $synchronizer */
            $synchronizer = $this->app->Container->get('GoogleCalendarSynchronizer');
            $client = $factory->createClient($userId);
            $synchronizer->importAbsoluteEvents($client);
            $msg = '<div class="error2">Termine erfolgreich Importiert.</div>';
        } catch (GoogleAccountNotFoundException $e) {
            $msg = '<div class="error">Fehler: Keine Verbindung zur Google-API</div>';
        } catch (GoogleCalendarSyncException $e) {
            $msg = '<div class="error">Fehler beim Terminimport</div>';
        } catch (Exception $e) {
            $msg = sprintf('<div class="error">Fehler: %s</div>', $e->getMessage());
        }

        $url = 'index.php?module=welcome&action=settings';
        if (!empty($msg)) {
            $url .= sprintf('&msg=%s', base64_encode($msg));
        }
        return new RedirectResponse($url);
    }

    protected function renderGoogleCalendarSettings(): array
    {
        $result = [
            'active' => false,
            'auth' => false,
            'calendar' => null,
        ];
        if (!$this->app->Container->has('GoogleCredentialsService')) {
            return $result;
        }
        /** @var GoogleCredentialsService $credService */
        $credService = $this->app->Container->get('GoogleCredentialsService');
        if ($credService->existCredentials()) {
            $result['active'] = true;
        }
        /** @var GoogleAccountGateway $gateway */
        $gateway = $this->app->Container->get('GoogleAccountGateway');
        try {
            $userId = (int)$this->app->User->GetID();
            $account = $gateway->getAccountByUser($userId);
        } catch (GoogleAccountNotFoundException $e) {
            return $result;
        }
        if ($gateway->hasAccountScope($account->getId(), GoogleScope::CALENDAR)) {
            $result['auth'] = true;
        }
        $props = $gateway->getAccountProperties($account->getId());
        $result['calendar'] = $props->get('selected_calendar');

        return $result;
    }

    /**
     * @return NotificationService
     */
    protected function GetNotificationService()
    {
        return $this->app->Container->get('NotificationService');
    }

    /**
     * @return DownloadSpoolerService
     */
    protected function GetDownloadSpoolerService()
    {
        return $this->app->Container->get('DownloadSpoolerService');
    }

    /**
     * @return DownloadSpoolerGateway
     */
    protected function GetDownloadSpoolerGateway()
    {
        return $this->app->Container->get('DownloadSpoolerGateway');
    }
}
