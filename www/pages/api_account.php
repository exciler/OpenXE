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

use Xentral\Components\Http\JsonResponse;
use Xentral\Components\Http\Request;
use Xentral\Modules\ApiAccount\Data\ApiAccountData;
use Xentral\Modules\ApiAccount\Exception\ApiAccountNotFoundException;
use Xentral\Modules\ApiAccount\Service\ApiAccountService;

class Api_account
{
    /** @var Application $app */
    protected $app;

    protected Request $request;
    protected ApiAccountService $apiAccountService;


    const MODULE_NAME = 'ApiAccount';

    /**
     * @param Application $app
     * @param string $name
     * @param array $erlaubtevars
     *
     * @return array
     */
    public static function TableSearch($app, $name, $erlaubtevars)
    {
        switch ($name) {
            case 'api_account_list':
                $allowed['api_account'] = array('list');
                $heading = array('API Account ID', 'Bezeichnung', 'Aktiv', 'Men&uuml;');
                $width = array('10%', '79%', '10%', '1%');
                $findcols = array('aa.id', 'bezeichnung', "if(aktiv = 1, 'ja','nein')", 'id');
                $searchsql = array('bezeichnung');
                $defaultorder = 1; //Optional wenn andere Reihenfolge gewuenscht
                $defaultorderdesc = 1;
                $menucol = 3;
                $menu = "<table cellpadding=0 cellspacing=0><tr><td nowrap><a data-id=\"%value%\" class=\"get\" href=\"#\"><img src=\"themes/{$app->Conf->WFconf['defaulttheme']}/images/edit.svg\" border=\"0\"></a></td></tr></table>";

                $sql = "SELECT aa.id, aa.id, aa.bezeichnung, 
                           if(aa.aktiv = 1, 'ja','nein') as aktiv, 
                           aa.id
        FROM `api_account` AS `aa`
        ";
                $fastcount = "SELECT COUNT(`aa`.`id`) FROM `api_account` AS `aa`";

                break;

        }

        $erg = [];
        foreach ($erlaubtevars as $k => $v) {
            if (isset($$v)) {
                $erg[$v] = $$v;
            }
        }
        return $erg;
    }

    /**
     * Api_account constructor.
     *
     * @param ApplicationCore $app
     * @param bool $intern
     */
    public function __construct(ApplicationCore $app, bool $intern = false)
    {
        $this->app = $app;
        if ($intern || !$app instanceof Application) {
            return;
        }

        $this->request = $this->app->Container->get('Request');
        $this->apiAccountService = $this->app->Container->get('ApiAccountService');

        $this->app->ActionHandlerInit($this);

        $this->app->ActionHandler("create", "");
        $this->app->ActionHandler("edit", "");
        $this->app->ActionHandler("list", "Api_AccountList");
        $this->app->ActionHandler("delete", "Api_AccountDelete");

        $this->app->DefaultActionHandler('list');
        $this->app->ActionHandlerListen($app);
    }

    public function Api_AccountDelete()
    {
        $id = $this->app->Secure->GetGET('id');
        $this->app->DB->Delete(sprintf('DELETE FROM `api_account` WHERE `id` = %d', $id));
        $this->app->Location->execute('index.php?module=api_account&action=list');
    }

    /**
     * @return JsonResponse
     */
    public function HandleGetAjaxAction()
    {
        $json = $this->request->getJson();
        if ($json->id === 0) {
            $data = [
                'aktiv' => 0,
                'id' => '',
                'bezeichnung' => '',
                'projekt' => '',
                'remotedomain' => '',
                'initkey' => '',
                'importwarteschlange' => 0,
                'importwarteschlange_name' => '',
                'event_url' => '',
                'cleanutf8' => 0,
                'apitempkey' => '',
                'ishtmltransformation' => 0,
            ];

            return new JsonResponse($data);
        }
        if ($json->id > 0) {
            try {
                $account = $this->apiAccountService->getApiAccountById($json->id);
                $account->project = ['id' => $account->getProjectId(), 'name' => 'test'];
                return new JsonResponse($account);
            } catch (ApiAccountNotFoundException) {
                return JsonResponse::BadRequest(['error' => 'Account nicht gefunden']);
            }
        }

        return JsonResponse::BadRequest(['error' => 'Account nicht gefunden']);
    }

    /**
     * @return JsonResponse
     */
    public function HandleSaveAjaxAction()
    {
        $json = $this->request->getJson();

        if ($json->id < 0)
            return JsonResponse::BadRequest(['error' => 'Bad ID']);
        if (($json->id > 0 && !$this->app->erp->RechteVorhanden('api_account', 'edit'))
            || ($json->id == 0 && !$this->app->erp->RechteVorhanden('api_account', 'create'))) {
            return JsonResponse::BadRequest(['error' => 'Fehlende Rechte']);
        };
        if (empty($json->name)) {
            return JsonResponse::BadRequest(['error' => 'Bitte füllen Sie die Bezeichnung aus']);
        }

        $account = new ApiAccountData($json->id, $json->name, $json->initKey, $json->importQueueName, $json->eventUrl,
            $json->remoteDomain, $json->active, $json->importQueueActive, $json->cleanUtf8Active,
            $json->transferAccountId, $json->projectId, $json->apiPermissions, $json->isLagacy ?? false,
            $json->isHtmlTransformation);

        try {
            if ($account->getId() > 0)
                $this->apiAccountService->updateApiAccount($account);
            else {
                $id = $this->apiAccountService->createApiAccount($account);
            }
        } catch (Exception $e) {
            return JsonResponse::BadRequest(['error' => $e->getMessage()]);
        }

        return JsonResponse::NoContent();
    }

    public function Api_AccountList()
    {
        $cmd = $this->app->Secure->GetGET('cmd');
        switch ($cmd) {
            case 'get': return $this->HandleGetAjaxAction();
            case 'save': return $this->HandleSaveAjaxAction();
        }

        $apiPermissions = $this->app->DB->SelectArr("SELECT * FROM `api_permission`");

        if (empty($apiPermissions)) {
            $api = $this->app->loadModule('api');
            $api->fillApiPermissions();
            $apiPermissions = $this->app->DB->SelectArr("SELECT * FROM `api_permission`");
        }

        $groupedApiPermissions = [];
        foreach ($apiPermissions as $apiPermission) {
            $groupedApiPermissions[$apiPermission['group']][] = $apiPermission;
        }

        $this->app->YUI->TableSearch('TAB1', 'api_account_list', 'show', '', '', basename(__FILE__), __CLASS__);
        $this->app->erp->MenuEintrag('', 'Neu');
        $this->app->erp->MenuEintrag('index.php?module=api_account&action=list', '&Uuml;bersicht');
        $this->app->erp->Headlines('API Account');
        $this->app->Tpl->Set('PERMISSIONJSON', json_encode($groupedApiPermissions));
        $this->app->Tpl->Parse('PAGE', 'api_account_list.tpl');
    }
}
