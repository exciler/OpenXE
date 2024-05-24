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

use Xentral\Components\Database\Database;
use Xentral\Components\Http\JsonResponse;
use Xentral\Components\Http\Request;
use Xentral\Modules\Calendar\CalendarService;
use Xentral\Modules\GoogleApi\Service\GoogleCredentialsService;
use Xentral\Modules\GoogleCalendar\Client\GoogleCalendarClientFactory;
use Xentral\Modules\GoogleCalendar\Service\GoogleCalendarSynchronizer;

class Kalender
{
    protected Application $app;

    protected Request $request;
    protected CalendarService $service;
    protected Database $db;

    const MODULE_NAME = 'Calendar';

    /**
     * @param Application $app
     * @param string $name
     * @param array $erlaubtevars
     *
     * @return array
     */
    public static function TableSearch($app, $name, $erlaubtevars)
    {
        // in dieses switch alle lokalen Tabellen (diese Live Tabellen mit Suche etc.) für dieses Modul
        switch ($name) {
            case "kalender_gruppenlist":
                $allowed['kalender'] = array('gruppenlist');

                $heading = array('Bezeichnung', 'Farbe', 'Aktiv', 'Men&uuml;');
                $width = array('50%', '30%', '10%', '5%');

                $findcols = array('kg.bezeichnung', 'kg.farbe', "if(kg.ausblenden,'ja','nein')", 'kg.id');
                $searchsql = array('kg.bezeichnung', 'kg.farbe', 'kg.ausblenden');

                $defaultorder = 1;
                $defaultorderdesc = 0;

                $menu = '<table>';
                $menu .= '<tr>';
                $menu .= '<td nowrap>';
                $menu .= '<a href="#" class="vue-action" data-action="edit" data-id="%value%">';
                $menu .= "<img src=\"themes/{$app->Conf->WFconf['defaulttheme']}/images/edit.svg\" border=\"0\">";
                $menu .= '</a>&nbsp;';
                $menu .= '<a href="#" class="vue-action" data-action="delete" data-id="%value%">';
                $menu .= "<img src=\"themes/{$app->Conf->WFconf['defaulttheme']}/images/delete.svg\" border=\"0\">";
                $menu .= '</a>';
                $menu .= "</td>";
                $menu .= "</tr>";
                $menu .= "</table>";

                $where = " kg.id > 0 ";

                $sql = "SELECT SQL_CALC_FOUND_ROWS kg.id, kg.bezeichnung, CONCAT('<span style=\"background-color:',kg.farbe,';\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>'), if(kg.ausblenden,'nein','ja'), kg.id FROM kalender_gruppen kg";

                $count = "SELECT count(kg.id) FROM kalender_gruppen kg WHERE $where";
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
     * Kalender constructor.
     *
     * @param Application $app
     * @param bool $intern
     */
    public function __construct($app, $intern = false)
    {
        $this->app = $app;

        $this->request = $this->app->Container->get('Request');
        $this->service = $this->app->Container->get('CalendarService');
        $this->db = $this->app->Container->get('Database');

        if ($intern) {
            return;
        }

        $this->app->ActionHandlerInit($this);

        $this->app->ActionHandler("list", "KalenderList");
        $this->app->ActionHandler("data", "KalenderData");
        $this->app->ActionHandler("ics", "KalenderICS");
        $this->app->ActionHandler("eventedit", "KalenderEventEdit");
        $this->app->ActionHandler("eventsave", "KalenderEventSave");
        $this->app->ActionHandler("eventdelete", "KalenderEventDelete");
        $this->app->ActionHandler("taskstatus", "KalenderTaskStatus");
        $this->app->ActionHandler("gruppenlist", "KalenderGruppenList");
        $this->app->ActionHandler("gruppenedit", "KalenderGruppenEdit");
        $this->app->ActionHandler("gruppensave", "KalenderGruppenSave");
        $this->app->ActionHandler("gruppendelete", "KalenderGruppenDelete");
        $this->app->ActionHandler("invitation", "KalenderInvitation");
        $this->app->ActionHandler('viewoptions', "KalenderViewOptions");

        $this->publicColor = "#3fa848";
        $this->taskColor = "#ae161e";
        $this->urlaubColor = ($this->app->erp->GetKonfiguration("mitarbeiterzeiterfassung_calendarcolor") ? $this->app->erp->GetKonfiguration("mitarbeiterzeiterfassung_calendarcolor") : "#00ccff");

        $this->app->ActionHandlerListen($app);
    }

    public function KalenderEventEdit() : JsonResponse
    {
        $id = (int)$this->app->Secure->GetGET('id');

        $sql = "SELECT u.id, a.name 
                FROM user u 
                LEFT JOIN adresse a ON a.id=u.adresse
                WHERE u.activ='1' 
                AND u.kalender_ausblenden!=1
                ORDER BY u.username";
        $response['users'] = $this->db->fetchAll($sql);

        $sql = "SELECT kg.*
                FROM kalender_gruppen kg
                LEFT JOIN kalender_gruppen_mitglieder kgm ON kg.id = kgm.kalendergruppe
                LEFT JOIN user u ON u.adresse = kgm.adresse
                WHERE ausblenden != 1
                AND u.id = :userid
                ORDER BY bezeichnung";
        $response['groups'] = $this->db->fetchAll($sql, ['userid' => $this->app->User->GetID()]);

        if ($id > 0) {
            $sql = "SELECT * FROM kalender_event WHERE id = :id";
            $event = $this->db->fetchRow($sql, ['id' => $id]);
            $sql = "SELECT userid FROM kalender_user WHERE event = :id AND userid > 0";
            $users = $this->db->fetchCol($sql, ['id' => $id]);
            $sql = "SELECT gruppe FROM kalender_user WHERE event = :id AND gruppe > 0";
            $groups = $this->db->fetchCol($sql, ['id' => $id]);
            $response['event'] = [
                'id' => $id,
                'title' => $event['bezeichnung'],
                'description' => $event['beschreibung'],
                'location' => $event['ort'],
                'beginDate' => (new DateTimeImmutable($event['von']))->format(DATE_ATOM),
                'endDate' => (new DateTimeImmutable($event['bis']))->format(DATE_ATOM),
                'allDay' => (bool)$event['allDay'],
                'color' => $event['color'],
                'isPublic' => (bool)$event['public'],
                'reminder' => (bool)$event['erinnerung'],
                'userIds' => $users,
                'groupIds' => $groups
            ];
            if ($event['adresse'])
                $response['event']['address'] =
                    $this->db->fetchRow("SELECT id, name FROM adresse WHERE id = :id",
                        ['id' => $event['adresse']]);
            if ($event['ansprechpartner_id'])
                $response['event']['contactPerson'] =
                    $this->db->fetchRow("SELECT id, name FROM ansprechpartner WHERE id = :id",
                        ['id' => $event['ansprechpartner_id']]);
            if ($event['adresseintern'])
                $response['event']['internalAddress'] =
                    $this->db->fetchRow("SELECT id, name FROM adresse WHERE id = :id",
                        ['id' => $event['adresseintern']]);
            if ($event['projekt'])
                $response['event']['project'] =
                    $this->db->fetchRow("SELECT id, name FROM projekt WHERE id = :id",
                        ['id' => $event['projekt']]);
        }
        return new JsonResponse($response);
    }

    public function KalenderEventSave() : JsonResponse
    {
        $json = $this->request->getJson();
        $id = (int)$json->id;

        if ($id > 0) {
            $sql = "UPDATE kalender_event SET
                    bezeichnung = :title, beschreibung = :description,
                    ort = :location,
                    von = :beginDate, bis = :endDate, allDay = :allDay,
                    adresse = :address, ansprechpartner_id = :contactPerson,
                    adresseintern = :internalAddress,
                    projekt = :project,
                    color = :color, erinnerung = :reminder, public = :isPublic
                    WHERE id = :id";
        } else {
            $sql = "INSERT INTO kalender_event
                    (ort, bezeichnung, beschreibung, von, bis, allDay, color, public, adresse, ansprechpartner_id,
                     adresseintern, angelegtvon, erinnerung, projekt)
                    VALUES (:location, :title, :description, :beginDate, :endDate, :allDay, :color, :isPublic, :address,
                            :contactPerson, :internalAddress, :creator, :reminder, :project)";
        }

        $this->db->perform($sql, [
            'id' => $id,
            'title' => $json->title,
            'description' => $json->description,
            'location' => $json->location,
            'beginDate' => (new DateTimeImmutable($json->beginDate))->setTimezone(new DateTimeZone('Europe/Berlin'))->format('Y-m-d H:i:s'),
            'endDate' => (new DateTimeImmutable($json->endDate))->setTimezone(new DateTimeZone('Europe/Berlin'))->format('Y-m-d H:i:s'),
            'allDay' => $json->allDay ?? false,
            'color' => $json->color,
            'isPublic' => $json->isPublic ?? false,
            'address' => $json->address->id ?? 0,
            'contactPerson' => $json->contactPerson->id ?? 0,
            'internalAddress' => $json->internalAddress->id ?? 0,
            'creator' => $this->app->User->GetID(),
            'reminder' => $json->reminder ?? false,
            'project' => $json->project->id ?? 0,
        ]);
        if ($id === 0)
            $id = $this->db->lastInsertId();

        if (empty($json->userIds))
            $json->userIds = [$this->app->User->GetID()];
        $json->groupIds ??= [];
        $sql = "SELECT userid FROM kalender_user WHERE event = :id AND userid > 0";
        $users = $this->db->fetchCol($sql, ['id' => $id]);
        $sql = "SELECT gruppe FROM kalender_user WHERE event = :id AND gruppe > 0";
        $groups = $this->db->fetchCol($sql, ['id' => $id]);

        $toDelete = array_diff($users, $json->userIds);
        if (!empty($toDelete)) {
            $sql = "DELETE FROM kalender_user WHERE event = :id AND userid IN (:userIds)";
            $this->db->perform($sql, ['id' => $id, 'userIds' => $toDelete]);
        }
        foreach (array_diff($json->userIds, $users) as $toAdd) {
            $sql = "INSERT INTO kalender_user (event, userid) VALUES (:id, :userId)";
            $this->db->perform($sql, ['id' => $id, 'userId' => $toAdd]);
        }

        $toDelete = array_diff($groups, $json->groupIds);
        if (!empty($toDelete)) {
            $sql = "DELETE FROM kalender_user WHERE event = :id AND gruppe IN (:groupIds)";
            $this->db->perform($sql, ['id' => $id, 'groupIds' => $toDelete]);
        }
        foreach (array_diff($json->groupIds, $groups) as $toAdd) {
            $sql = "INSERT INTO kalender_user (event, userid, gruppe) VALUES (:id, 0, :groupId)";
            $this->db->perform($sql, ['id' => $id, 'groupId' => $toAdd]);
        }

        return new JsonResponse(['id' => $id]);
    }

    public function KalenderEventDelete() :JsonResponse {
        $json = $this->request->getJson();
        $params = ['id' => (int)$json->id];
        $sql = "DELETE FROM kalender_user WHERE event = :id";
        $this->db->perform($sql, $params);
        $sql = "DELETE FROM kalender_event WHERE id = :id";
        $this->db->perform($sql, $params);
        return JsonResponse::NoContent();
    }

    public function KalenderInvitation() : JsonResponse {
        if ($this->request->getMethod() === 'POST')
            return $this->KalenderInvitationSend();

        $id = (int)$this->app->Secure->GetGET('id');

        $recipients = $this->KalenderGetParticipants($id, false);

        $buildMailAdr = function ($item) {
            $result = $item['email'];
            if ($item['name'])
                $result = "{$item['name']} <{$result}>";
            return $result;
        };
        $recipients = array_map($buildMailAdr, $recipients);

        $sql = "SELECT k.adresse, k.ansprechpartner_id, k.adresseintern, IFNULL(a.sprache,''), k.projekt, 
                k.bezeichnung, k.beschreibung, k.ort,
                DATE_FORMAT(k.von,'%d.%m.%Y %H:%i') as von,
                DATE_FORMAT(k.bis,'%d.%m.%Y %H:%i') as bis
                FROM kalender_event k
                LEFT JOIN adresse a ON a.id=k.adresse
                WHERE k.id = :id";
        $data = $this->db->fetchRow($sql, ['id' => $id]);

        $vorlage = $this->app->erp->Geschaeftsbriefvorlage('', "EinladungKalender");

        foreach ($data as $key => $value) {
            $vorlage['betreff'] = str_replace('{' . strtoupper($key) . '}', $value, $vorlage['betreff']);
            $vorlage['text'] = str_replace('{' . strtoupper($key) . '}', $value, $vorlage['text']);
        }

        return new JsonResponse([
            'id' => $id,
            'subject' => $vorlage['betreff'],
            'body' => $vorlage['text'],
            'recipientOptions' => $recipients
        ]);
    }

    public function KalenderInvitationSend() : JsonResponse {
        $json = $this->request->getJson();
        $id = (int)$json->id;
        $result = $this->KalenderMailNew($id, $json->subject, $json->body, $json->recipients);

        if (!$result)
            return JsonResponse::BadRequest('error sending invitation');
        return JsonResponse::NoContent();
    }

    public function KalenderList()
    {
        $this->trySynchronizeGoogleChanges();

        $this->app->erp->Headlines('Kalender');
        $this->app->Tpl->Set('TABTEXT', "Kalender");
        $this->app->Tpl->Parse('TAB1', "kalender.tpl");
        $this->app->Tpl->Parse('PAGE', "tabview.tpl");
        $this->app->erp->StartseiteMenu();
    }

    public function KalenderICS()
    {
        $findlogin = $this->app->DB->Select("SELECT id FROM user WHERE username='" . $this->app->DB->real_escape_string($_SERVER['PHP_AUTH_USER']) . "' AND username!=''
        AND kalender_aktiv='1' AND kalender_passwort='" . $this->app->DB->real_escape_string($_SERVER['PHP_AUTH_PW']) . "' AND kalender_passwort!='' AND `activ`='1' LIMIT 1");

        $this->app->erp->Protokoll("Benutzer: " . $this->app->DB->real_escape_string($_SERVER['PHP_AUTH_USER']));

        //$findlogin='1000';
        //if ($_SERVER['PHP_AUTH_USER']=="sauterbe" && $_SERVER['PHP_AUTH_PW']=="ZakledhLs")
        if ($findlogin > 0) {
            $event = new ICS("wawision");

            $data = $this->app->DB->SelectArr("SELECT DISTINCT ke.id, ort,beschreibung, bezeichnung AS title, DATE_FORMAT(von,'%Y-%m-%d %H:%i') AS start, 
          DATE_FORMAT(bis,'%Y-%m-%d %H:%i') AS end, allDay, color, public,erinnerung
          FROM kalender_user AS ku
          LEFT JOIN kalender_event AS ke ON ke.id=ku.event
          WHERE (ku.userid='$findlogin' OR ke.public='1')  AND ke.von!='0000-00-00 00:00:00' AND ke.bis!='0000-00-00 00:00:00' ORDER by von");
            $cdata = !empty($data) ? count($data) : 0;
            for ($i = 0; $i < $cdata; $i++) {
                //	$data[$i]['color'] = (($data[$i]['public']=='1')?$this->publicColor:$data[$i]['color']);
                $data[$i]['allDay'] = (($data[$i]['allDay'] == '1') ? true : false);
                $data[$i]['public'] = (($data[$i]['public'] == '1') ? true : false);
                $data[$i]['erinnerung'] = (($data[$i]['erinnerung'] == '1') ? true : false);
                $data[$i]['title'] = $this->app->erp->ReadyForPDF($data[$i]['title']);
                $data[$i]['ort'] = $this->app->erp->ReadyForPDF($data[$i]['ort']);
                $data[$i]['beschreibung'] = str_ireplace("\x0D", "", $data[$i]['beschreibung']);
                $data[$i]['beschreibung'] = str_replace("\n", "\\n", $data[$i]['beschreibung']);
                $data[$i]['beschreibung'] = str_replace("\r\n", "\\n", $data[$i]['beschreibung']);
                $data[$i]['beschreibung'] = $this->app->erp->ReadyForPDF($data[$i]['beschreibung']);


                $event->AddEvent($data[$i]['id'], $data[$i]['start'], $data[$i]['end'], $data[$i]['title'], $data[$i]['beschreibung'], $data[$i]['ort']);
            }
            //$event->AddEvent(1,"2014-05-18 11:00","2014-05-18 21:00","Test 444 Event","This is an event made by Benedikt","Augsburg");
            //$event->AddEvent(2,"2014-05-18 09:00","2014-05-18 09:30","Test 3 Event","This is an event made by Benedikt","Augsburg");
            $event->show();
            $this->app->ExitXentral();
        }

        header('WWW-Authenticate: Basic realm="WaWision Kalender"');
        header('HTTP/1.0 401 Unauthorized');
        $this->app->ExitXentral();
    }

    function KalenderTaskStatus()
    {
        $user = $this->app->User->GetID();
        $data = $this->app->DB->SelectArr("SELECT kalender_aufgaben , a.id FROM adresse AS a
        LEFT JOIN user as u ON u.adresse=a.id
        WHERE u.id='$user' LIMIT 1");
        $new_status = '';
        if ($data[0]['kalender_aufgaben'] == '1') {
            $new_status = '0';
        } else {
            $new_status = '1';
        }
        $this->app->DB->Update("UPDATE adresse SET kalender_aufgaben='$new_status' WHERE id='{$data[0]['id']}' LIMIT 1");
        $this->app->ExitXentral();
    }

    function KalenderData() : JsonResponse
    {
        $user = $this->app->User->GetID();
        $useradresse = $this->app->User->GetAdresse();
        $start = (new DateTimeImmutable($this->app->Secure->GetGET('start')))->format('Y-m-d H:i:s');
        $end = (new DateTimeImmutable($this->app->Secure->GetGET('end')))->format('Y-m-d H:i:s');

        $start_datum = (new DateTimeImmutable($this->app->Secure->GetGET('start')))->format('Y-m-d');
        $end_datum = (new DateTimeImmutable($this->app->Secure->GetGET('end')))->format('Y-m-d');

        $gruppenkalender = $this->app->DB->SelectArr("SELECT * FROM kalender_gruppen");
        $hideserviceauftrag = false;
        $subwhere = "";
        $cgruppenkalender = !empty($gruppenkalender) ? count($gruppenkalender) : 0;
        for ($i = 0; $i < $cgruppenkalender; $i++) {
            if ($this->app->User->GetParameter("kalender_gruppe_" . $gruppenkalender[$i]['id']) == "1") {
                if ($subwhere != "") $subwhere .= " OR ";
                $subwhere .= " (ku.gruppe='" . $gruppenkalender[$i]['id'] . "' AND kg.id IN(SELECT kgm.kalendergruppe FROM kalender_gruppen_mitglieder kgm LEFT JOIN user u ON u.adresse = kgm.adresse WHERE u.id='$user')) ";
            }
        }
        if ($subwhere == "") {
            $subwhere = " AND (ku.userid='$user' OR ke.public=1 OR (ku.gruppe > 0 AND kg.id IN (SELECT kgm.kalendergruppe FROM kalender_gruppen_mitglieder kgm LEFT JOIN user u ON u.adresse = kgm.adresse WHERE u.id='$user')))";
        } else {
            //$hideserviceauftrag = true; 2018-10-05 BS entfernt
            $subwhere = " AND (" . $subwhere . ") ";
        }


        $nurmeine = $this->app->User->GetParameter("adresse_kalender_termine");

        if ($nurmeine > 0) {
            $data = $this->app->DB->SelectArr("SELECT DISTINCT ke.id, 'kalender_event' AS typ, ort, beschreibung, ke.bezeichnung AS title, von AS start, bis AS end, allDay, color, public,erinnerung,adresse,ansprechpartner_id,adresseintern,projekt
        FROM kalender_event AS ke
        LEFT JOIN kalender_user AS ku ON ke.id=ku.event
        LEFT JOIN kalender_gruppen AS kg ON kg.id = ku.gruppe
        WHERE (ku.userid='$user' OR (ku.gruppe > 0 AND kg.id IN (SELECT kgm.kalendergruppe FROM kalender_gruppen_mitglieder kgm LEFT JOIN user u ON u.adresse = kgm.adresse WHERE u.id='$user'))) AND (ke.von < '$end' AND (ke.bis >= '$start' OR ke.bis='0000-00-00 00:00:00') ) GROUP by ke.id ORDER by start");
        } else {

            $data = $this->app->DB->SelectArr("SELECT DISTINCT ke.id, 'kalender_event' AS typ, ort, beschreibung, ke.bezeichnung AS title, von AS start, bis AS end, allDay, color, public,erinnerung,adresse,ansprechpartner_id,adresseintern,projekt,kg.farbe
        FROM kalender_event AS ke
        LEFT JOIN kalender_user ku ON ke.id=ku.event
        LEFT JOIN kalender_gruppen kg ON kg.id=ku.gruppe
        WHERE (ke.von < '$end' AND (ke.bis >= '$start' OR (ke.bis='0000-00-00 00:00:00' AND ke.von!='0000-00-00 00:00:00') AND NOT (ke.von < '$start' AND ke.bis='0000-00-00 00:00:00'))  ) $subwhere GROUP by ke.id ORDER by start");
        }
        $cdata = !empty($data) ? count($data) : 0;
        for ($i = 0; $i < $cdata; $i++) {
            $data[$i]['allDay'] = (($data[$i]['allDay'] == '1') ? true : false);
            $data[$i]['public'] = (($data[$i]['public'] == '1') ? true : false);
            $data[$i]['erinnerung'] = (($data[$i]['erinnerung'] == '1') ? true : false);
            $data[$i]['title'] = $this->app->erp->ReadyForPDF($data[$i]['title']);
            $data[$i]['ort'] = $this->app->erp->ReadyForPDF($data[$i]['ort']);
            $data[$i]['adresse'] = $this->app->erp->ReadyForPDF($data[$i]['adresse']);
            $data[$i]['ansprechpartner'] = $this->app->erp->ReadyForPDF($data[$i]['ansprechpartner_id']);
            $data[$i]['adresseintern'] = $this->app->erp->ReadyForPDF($data[$i]['adresseintern']);
            $data[$i]['projekt'] = $this->app->erp->ReadyForPDF($data[$i]['adresseintern']);
            $data[$i]['beschreibung'] = $this->app->erp->ReadyForPDF($data[$i]['projekt']);
            if ($data[$i]['farbe'] != "")
                $data[$i]['color'] = $data[$i]['farbe'];
        }

        $aufgaben_visible = $this->app->DB->Select("SELECT kalender_aufgaben FROM adresse AS a
        LEFT JOIN user as u ON u.adresse=a.id
        WHERE u.id='$user' LIMIT 1");

        // Mindesthaltbarkeitsdatum einblenden
        if ($this->app->erp->RechteVorhanden("mhdwarning", "list")) {
            $sql = "SELECT a.id as id,a.name_de, a.nummer, SUM(lm.menge) as menge, lm.mhddatum
        FROM lager_mindesthaltbarkeitsdatum lm
        LEFT JOIN artikel a ON a.id=lm.artikel LEFT JOIN lager_platz l ON l.id=lm.lager_platz WHERE DATE_FORMAT(lm.datum,'%Y-%m') <= DATE_FORMAT('$end_datum','%Y-%m') OR DATE_FORMAT(lm.datum,'%Y-%m') >= DATE_FORMAT('$start_datum','%Y-%m') GROUP By lm.mhddatum, a.id";

            $tmpartikel = $this->app->DB->SelectArr($sql);
            $ctmpartikel = !empty($tmpartikel) ? count($tmpartikel) : 0;
            for ($ij = 0; $ij < $ctmpartikel; $ij++) {
                $data[] = array('id' => -3,
                    'title' => round($tmpartikel[$ij]['menge'], 0) . " x " . $this->app->erp->ReadyForPDF($tmpartikel[$ij]['name_de']),
                    'start' => $tmpartikel[$ij]['mhddatum'],
                    'end' => $tmpartikel[$ij]['mhddatum'],
                    'allDay' => true,
                    'color' => '#FA5858',
                    'public' => '1',
                    'url' => 'index.php?module=artikel&action=mindesthaltbarkeitsdatum&id=' . $tmpartikel[$ij]['id'],
                    );
            }
        }


        //Geburtstage einblenden


        $tmp = explode('-', $start);
        $startyear = $tmp[0];

        $tmp = explode('-', $end);
        $endyear = $tmp[0];

        $types = array('adresse', 'ansprechpartner');

        foreach ($types as $key) {

            if ($key == "adresse") $p_key = "a.id";
            else if ($key == "ansprechpartner") $p_key = "a.adresse";

            if ($endyear > $startyear) {

                //0111   1230 // neues jahr
                $sql = "SELECT $p_key as id,a.name,DATE_FORMAT(a.geburtstag,'%m-%d') as datum,
          YEAR('$end') - YEAR(a.geburtstag) - IF(DAYOFYEAR('$end') < DAYOFYEAR(CONCAT(YEAR('$end'),DATE_FORMAT(a.geburtstag, '-%m-%d'))),1,0) as alterjahre
          FROM " . $key . " a WHERE DATE_FORMAT(a.geburtstag,'%m%d') <= date_format('$end','%m%d') AND a.geloescht!='1' AND a.geburtstag!='0000-00-00' AND a.geburtstagkalender=1";

                $tmpartikel = $this->app->DB->SelectArr($sql);
                $ctmpartikel = !empty($tmpartikel) ? count($tmpartikel) : 0;
                for ($ij = 0; $ij < $ctmpartikel; $ij++) {
                    $data[] = array('id' => -4,
                        //'title'=>"Geburtstag: ".$this->app->erp->ReadyForPDF($tmpartikel[$ij]['name'])." (".$tmpartikel[$ij]['alterjahre'].")",
                        'title' => "Geburtstag: " . $this->app->erp->ReadyForPDF($tmpartikel[$ij]['name']),
                        'start' => $endyear . "-" . $tmpartikel[$ij]['datum'],
                        'end' => $endyear . "-" . $tmpartikel[$ij]['datum'],
                        'allDay' => true,
                        'color' => '#FA5858',
                        'public' => '1',
                        'url' => 'index.php?module=adresse&action=brief&id=' . $tmpartikel[$ij]['id']);
                }


                //0111   1230 // altes jahr
                $sql = "SELECT $p_key as id,a.name,DATE_FORMAT(a.geburtstag,'%m-%d') as datum,
          YEAR('$end') - YEAR(a.geburtstag) - IF(DAYOFYEAR('$end') < DAYOFYEAR(CONCAT(YEAR('$end'),DATE_FORMAT(a.geburtstag, '-%m-%d'))),1,0) as alterjahre
          FROM " . $key . " a WHERE DATE_FORMAT(a.geburtstag,'%m%d') <= 1231 AND a.geloescht!='1' AND a.geburtstag!='0000-00-00' AND a.geburtstagkalender=1";

                $tmpartikel = $this->app->DB->SelectArr($sql);
                $ctmpartikel = !empty($tmpartikel) ? count($tmpartikel) : 0;
                for ($ij = 0; $ij < $ctmpartikel; $ij++) {
                    $data[] = array('id' => -4,
                        'title' => "Geburtstag: " . $this->app->erp->ReadyForPDF($tmpartikel[$ij]['name']) . " (" . $tmpartikel[$ij]['alterjahre'] . ")",
                        'start' => $startyear . "-" . $tmpartikel[$ij]['datum'],
                        'end' => $startyear . "-" . $tmpartikel[$ij]['datum'],
                        'allDay' => true,
                        'color' => '#FA5858',
                        'public' => '1',
                        'url' => 'index.php?module=adresse&action=brief&id=' . $tmpartikel[$ij]['id']);
                }


            } else {
                $sql = "SELECT $p_key as id,a.name,DATE_FORMAT(a.geburtstag,'%m-%d') as datum,
          YEAR('$end') - YEAR(a.geburtstag) - IF(DAYOFYEAR('$end') < DAYOFYEAR(CONCAT(YEAR('$end'),DATE_FORMAT(a.geburtstag, '-%m-%d'))),1,0) as alterjahre
          FROM " . $key . " a WHERE DATE_FORMAT(a.geburtstag,'%m%d') <= date_format('$end','%m%d') AND DATE_FORMAT(a.geburtstag,'%m%d') >= date_format('$start','%m%d') AND a.geloescht!='1' AND a.geburtstag!='0000-00-00' AND a.geburtstagkalender=1";

                $tmpartikel = $this->app->DB->SelectArr($sql);
                $ctmpartikel = !empty($tmpartikel) ? count($tmpartikel) : 0;
                for ($ij = 0; $ij < $ctmpartikel; $ij++) {
                    $data[] = array('id' => -4,
                        'title' => "Geburtstag: " . $this->app->erp->ReadyForPDF($tmpartikel[$ij]['name']) . " (" . $tmpartikel[$ij]['alterjahre'] . ")",
                        'start' => $startyear . "-" . $tmpartikel[$ij]['datum'],
                        'end' => $startyear . "-" . $tmpartikel[$ij]['datum'],
                        'allDay' => true,
                        'color' => '#FA5858',
                        'public' => '1',
                        'url' => 'index.php?module=adresse&action=brief&id=' . $tmpartikel[$ij]['id']);
                }
            }
        }


        //arbeitsfreie tage einblenden
        $sql = "SELECT a.id as id,a.bezeichnung, a.datum,a.typ FROM arbeitsfreietage a WHERE a.datum <='$end_datum' AND a.datum >='$start_datum'";

        $tmpartikel = $this->app->DB->SelectArr($sql);
        $ctmpartikel = !empty($tmpartikel) ? count($tmpartikel) : 0;
        for ($ij = 0; $ij < $ctmpartikel; $ij++) {
            $data[] = array('id' => -7,
                'title' => ucfirst($tmpartikel[$ij]['typ']) . ": " . $this->app->erp->ReadyForPDF($tmpartikel[$ij]['bezeichnung']),
                'start' => $tmpartikel[$ij]['datum'],
                'end' => $tmpartikel[$ij]['datum'],
                'allDay' => true,
                'color' => '#FA5858',
                'public' => '1',
                'task' => $tmpartikel[$ij]['id']);
        }

        // Serviceauftrag
        $serviceauftrag_visible = $this->app->User->GetParameter("adresse_kalender_serviceauftrag");
        if ($this->app->erp->ModulVorhanden("serviceauftrag")) {
            if (!$hideserviceauftrag && $serviceauftrag_visible) {
                $tmpserviceauftragsql = "SELECT sa.id, sa.datum, sa.abschluss_bis, a.name,sa.bearbeiter FROM serviceauftrag sa LEFT JOIN adresse a ON sa.adresse = a.id WHERE sa.datum >= '$start_datum' AND (sa.abschluss_bis = '0000-00-00' OR sa.abschluss_bis < '$end_datum')";
                if ($nurmeine > 0) {
                    $tmpserviceauftragsql .= " AND sa.bearbeiter = '$useradresse'";
                }
                $tmpserviceauftrag = $this->app->DB->SelectArr($tmpserviceauftragsql);
                $ctmpserviceauftrag = !empty($tmpserviceauftrag) ? count($tmpserviceauftrag) : 0;
                for ($ij = 0; $ij < $ctmpserviceauftrag; $ij++) {

                    $defaultCalendarColor = $this->app->DB->Select("SELECT defaultcolor FROM user WHERE adresse='" . $tmpserviceauftrag[$ij]['bearbeiter'] . "' AND defaultcolor!='' LIMIT 1");
                    if ($defaultCalendarColor == "") $defaultCalendarColor = "#DD00DD";

                    $data[] = array('id' => -6,
                        'title' => "Serviceauftrag: " . $tmpserviceauftrag[$ij]['name'],
                        'start' => $tmpserviceauftrag[$ij]['datum'],
                        'end' => $tmpserviceauftrag[$ij]['abschluss_bis'],
                        'allDay' => true,
                        'color' => $defaultCalendarColor,
                        'public' => '1',
                        'task' => $tmpserviceauftrag[$ij]['id']);
                }
            }
        }


        $aufgaben_visible = $this->app->User->GetParameter("adresse_kalender_aufgaben");
        // Aufgabene einblenden
        if ($aufgaben_visible == '1') {
            // Aufgaben hinzufügen
            $tasks = $this->app->DB->SelectArr("SELECT DISTINCT a.id, a.aufgabe, a.abgabe_bis, a.ganztags, ma.name as mitarbeiter, ku.name as kunde FROM aufgabe AS a 
          LEFT JOIN user AS u ON u.adresse=a.adresse
          LEFT JOIN adresse AS ku ON ku.id=a.kunde
          LEFT JOIN adresse AS ma ON ma.id=a.adresse
          WHERE (u.id='$user' OR oeffentlich='1') AND a.status='offen' AND a.abgabe_bis>='$start' 
          AND a.abgabe_bis<='$end'");
            $ctasks = !empty($tasks) ? count($tasks) : 0;
            for ($i = 0; $i < $ctasks; $i++) {
                $allday = (($tasks[$i]['ganztags'] == '1') ? true : false);
                $data[] = array('id' => -2,
                    'title' => $tasks[$i]['mitarbeiter'] . ": " . $this->app->erp->ReadyForPDF($tasks[$i]['aufgabe']) . ($tasks[$i]['kunde'] != "" ? " (" . $tasks[$i]['kunde'] . ")" : ""),
                    'start' => $tasks[$i]['abgabe_bis'],
                    'end' => $tasks[$i]['abgabe_bis'],
                    'allDay' => $allday,
                    'color' => $this->taskColor,
                    'public' => '',
                    'url' => 'index.php?module=aufgaben&action=edit&id=' . $tasks[$i]['id'] . '&back=kalender#tabs-3');
            }

        }

        $urlaub_visible = $this->app->User->GetParameter("adresse_kalender_urlaub");
        // Aufgabene einblenden
        if ($urlaub_visible == '1') {
            // Aufgaben hinzufügen
            $tasks = $this->app->DB->SelectArr("SELECT DISTINCT ms.id, a2.name, ms.datum,ms.kuerzel FROM mitarbeiterzeiterfassung_sollstunden ms 
          LEFT JOIN user AS u ON u.adresse=ms.adresse
          LEFT JOIN adresse a2 ON a2.id=ms.adresse
          WHERE ms.datum>='$start' 
          AND ms.datum<='$end' AND (ms.kuerzel='U' OR ms.kuerzel='K' OR ms.kuerzel='N')");
            $ctasks = !empty($tasks) ? count($tasks) : 0;
            for ($i = 0; $i < $ctasks; $i++) {
                switch ($tasks[$i]['kuerzel']) {
                    case "U":
                        $kuerzel = "Abwesend";
                        break;
                    case "N":
                        $kuerzel = "Abwesend";
                        break;
                    case "K":
                        $kuerzel = "Abwesend";
                        break;
                }
                $data[] = array('id' => -7,
                    'title' => $kuerzel . ": " . $this->app->erp->ReadyForPDF($tasks[$i]['name']),
                    'start' => $tasks[$i]['datum'],
                    'end' => $tasks[$i]['datum'],
                    'allDay' => 1,
                    'color' => $this->urlaubColor,
                    'public' => '1',
                    'task' => $tasks[$i]['id']);
            }

        }


        $projekte_visible = $this->app->User->GetParameter("adresse_kalender_projekte");
        // Aufgabene einblenden
        if ($projekte_visible == '1') {
            // Aufgaben hinzufügen

            $nureigene = $this->app->User->GetParameter("adresse_kalender_termine");
            if ($nureigene == "1") {
                $tasks = $this->app->DB->SelectArr("SELECT DISTINCT a.id, a.aufgabe, a.startdatum,a.farbe,a.projekt FROM arbeitspaket AS a 
          LEFT JOIN user AS u ON u.adresse=a.adresse
          WHERE (u.id='$user') AND a.startdatum>='$start' 
          AND a.startdatum <='$end'");
            } else {
                $tasks = $this->app->DB->SelectArr("SELECT DISTINCT a.id, a.aufgabe, a.startdatum,a.farbe,a.projekt FROM arbeitspaket AS a 
          LEFT JOIN user AS u ON u.adresse=a.adresse
          LEFT JOIN projekt p ON a.projekt=p.id
          WHERE a.startdatum>='$start'  AND p.oeffentlich=1
          AND a.startdatum <='$end'");
            }
            $ctasks = !empty($tasks) ? count($tasks) : 0;
            for ($i = 0; $i < $ctasks; $i++) {
                $data[] = array('id' => -5,
                    'title' => "Teilprojekt Start: " . $this->app->erp->ReadyForPDF($tasks[$i]['aufgabe']),
                    'start' => $tasks[$i]['startdatum'],
                    'end' => $tasks[$i]['startdatum'],
                    'allDay' => true,
                    'color' => $tasks[$i]['farbe'],
                    'public' => '',
                    'url' => 'index.php?module=projekt&action=dashboard&id=' . $tasks[$i]['projekt']);
            }
            $tasks = $this->app->DB->SelectArr("SELECT DISTINCT a.id, a.aufgabe, a.abgabedatum,a.farbe,a.projekt FROM arbeitspaket AS a 
          LEFT JOIN user AS u ON u.adresse=a.adresse
          WHERE (u.id='$user') AND a.abgabedatum>='$start' 
          AND a.abgabedatum <='$end'");
            $ctasks = !empty($tasks) ? count($tasks) : 0;
            for ($i = 0; $i < $ctasks; $i++) {
                $data[] = array('id' => -5,
                    'title' => "Teilprojekt Abgabe: " . $this->app->erp->ReadyForPDF($tasks[$i]['aufgabe']),
                    'start' => $tasks[$i]['abgabedatum'],
                    'end' => $tasks[$i]['abgabedatum'],
                    'allDay' => true,
                    'color' => $tasks[$i]['farbe'],
                    'public' => '',
                    'url' => 'index.php?module=projekt&action=dashboard&id=' . $tasks[$i]['projekt']);
            }


        }
        return new JsonResponse($data ?? []);
    }

    /*
    * Retrieve the participants and the organizer of an event
    * Result structure 2-dimensional array of name,email
    * The first entry is the organizer and can reoccurr as participant
    */
    function KalenderGetParticipants(int $eventId, bool $includeOrganizer = true)
    {
        /*
        *
        * Organizer:
        * If there is an adresseintern, use this, otherwise use the user's address
        *
        * Recipients:
        * if there is an address and a person, use the person
        * if there is an address and no person, use the address
        * add the addresses of the selected users
        * each address only once
        *
        */

        $ret = array();

        $sql = "SELECT adresse, ansprechpartner_id, adresseintern FROM kalender_event WHERE id = :id";
        $data = $this->db->fetchRow($sql, ['id' => $eventId]);

        if ($includeOrganizer) {
            // Add Organizer
            $adresseintern = $data['adresseintern'];

            if ($adresseintern) {
                $sql = "SELECT email, name FROM adresse WHERE id = :id AND geloescht != 1";
                $organizer = $this->db->fetchRow($sql, ['id' => $adresseintern]);
            }
            if (!$organizer || !$organizer['email']) {
                $organizer = [
                    'email' => $this->app->User->GetEmail(),
                    'name' => $this->app->User->GetName()
                ];
            }

            if ($organizer && $organizer['email'])
                $ret[] = $organizer;
        }

        $address = $data['adresse'];
        // Add primary recipient
        if ($address) {
            // Check for ansprechpartner person
            $ansprechpartner_id = $data['ansprechpartner_id'];
            if ($ansprechpartner_id) {
                $sql = "SELECT name, email FROM ansprechpartner WHERE id = :id AND geloescht!=1";
                $recipient_result = $this->db->fetchRow($sql, ['id' => $ansprechpartner_id]);
            }
            if (empty($recipient_result)) {
                $sql = "SELECT name, email FROM adresse WHERE id = :id AND geloescht!=1";
                $recipient_result = $this->db->fetchRow($sql, ['id' => $address]);
            }

            if ($recipient_result && $recipient_result['email'])
                $ret[] = $recipient_result;
        }

        // jetzt holen wir alle Email-Adressen
        $sql = "SELECT a.name, a.email
                FROM kalender_user ku 
                LEFT JOIN user u ON u.id = ku.userid
                LEFT JOIN adresse a ON a.id=u.adresse
                WHERE ku.event = :id";
        $userData = $this->db->fetchAll($sql, ['id' => $eventId]);

        return array_merge($ret, $userData);
    }

    function Install()
    {
        $this->app->erp->CheckTable("kalender_gruppen");
        $this->app->erp->CheckColumn("id", "int(11)", "kalender_gruppen", "NOT NULL AUTO_INCREMENT");
        $this->app->erp->CheckColumn("bezeichnung", "varchar(255)", "kalender_gruppen", "NOT NULL");
        $this->app->erp->CheckColumn("farbe", "varchar(255)", "kalender_gruppen", "NOT NULL");
        $this->app->erp->CheckColumn("ausblenden", "tinyint(1)", "kalender_gruppen", "NOT NULL DEFAULT 0");

        $this->app->erp->CheckTable("kalender_gruppen_mitglieder");
        $this->app->erp->CheckColumn("id", "int(11)", "kalender_gruppen_mitglieder", "NOT NULL AUTO_INCREMENT");
        $this->app->erp->CheckColumn("kalendergruppe", "int(11)", "kalender_gruppen_mitglieder", "NOT NULL");
        $this->app->erp->CheckColumn("benutzergruppe", "int(11)", "kalender_gruppen_mitglieder", "NOT NULL");
        $this->app->erp->CheckColumn("adresse", "int(11)", "kalender_gruppen_mitglieder", "NOT NULL");
        $this->app->erp->CheckIndex('kalender_gruppen_mitglieder', 'kalendergruppe');
        $this->app->erp->CheckIndex('kalender_gruppen_mitglieder', 'adresse');
    }

    function KalenderViewOptions() : JsonResponse
    {
        $options = [
            'adresse_kalender_aufgaben',
            'adresse_kalender_termine',
            'adresse_kalender_urlaub',
            'adresse_kalender_projekte'
        ];
        $sql = "SELECT kg.id, kg.bezeichnung
                FROM kalender_gruppen kg
                LEFT JOIN kalender_gruppen_mitglieder kgm ON kg.id = kgm.kalendergruppe
                LEFT JOIN user u ON u.adresse = kgm.adresse
                WHERE kg.ausblenden != 1
                AND u.id = :userid
                ORDER BY kg.bezeichnung";
        $groups = $this->db->fetchPairs($sql, ['userid' => $this->app->User->GetID()]);
        $options = array_merge($options, array_map(fn($id) => sprintf('kalender_gruppe_%d', $id), array_keys($groups)));
        if ($this->request->getMethod() === 'POST') {
            $json = $this->request->getJson();
            foreach ($json->options as $key => $value) {
                if (!in_array($key, $options))
                    continue;
                $this->app->User->SetParameter($key, $value);
            }
            return JsonResponse::NoContent();
        }
        $params = $this->app->User->GetParameter($options);
        $ret = [];
        foreach ($params as $param) {
            $ret[$param['name']] = boolval($param['value']);
        }
        return new JsonResponse([
            'options' => $ret,
            'groups' => $groups
        ]);
    }

    function KalenderGruppenList() : void
    {
        $this->app->ModuleScriptCache->IncludeJavascriptModules(['classes/Modules/Calendar/www/js/group.entry.ts']);
        $this->app->erp->Headlines('Kalender Gruppen');
        $this->app->erp->MenuEintrag("index.php?module=kalender&action=gruppenlist", "&Uuml;bersicht");

        $this->app->YUI->TableSearch('TAB1', 'kalender_gruppenlist', "show", "", "", basename(__FILE__), __CLASS__);
        $this->app->Tpl->Parse("PAGE", "kalender_gruppenlist.tpl");
    }

    function KalenderGruppenEdit() : JsonResponse
    {
        $id = (int)$this->app->Secure->GetGET('id');

        $response = [];
        $sql = "SELECT a.id, a.mitarbeiternummer, a.name FROM adresse a 
                INNER JOIN (SELECT adresse FROM adresse_rolle WHERE subjekt='Mitarbeiter' AND (ifnull(bis,'0000-00-00') = '0000-00-00' OR bis >= CURDATE()) GROUP BY adresse) ar on ar.adresse=a.id
                WHERE a.mitarbeiternummer!='' AND a.geloescht!=1";
        $response['memberOptions'] = $this->db->fetchAll($sql);

        if ($id > 0) {
            $sql = 'SELECT id, bezeichnung, farbe, ausblenden FROM kalender_gruppen WHERE id = :id ';
            $data = $this->db->fetchRow($sql, ['id' => $id]);
            $sql = 'SELECT adresse FROM kalender_gruppen_mitglieder WHERE kalendergruppe = :id';
            $response['members'] = $this->db->fetchCol($sql, ['id' => $id]);

            $response['name'] = $data['bezeichnung'];
            $response['color'] = $data['farbe'];
            $response['active'] = $data['ausblenden'] != 1;
        }
        return new JsonResponse($response);
    }

    function KalenderGruppenSave(): JsonResponse
    {
        $json = $this->request->getJson();
        $id = (int)$json->id;

        $error = "";
        if (trim($json->name) == "") {
            $error = "Bitte Bezeichnung ausfüllen";
        }

        if (!empty($error))
            return JsonResponse::BadRequest($error);

        if ($id > 0) {
            $sql = 'UPDATE kalender_gruppen SET bezeichnung = :name, farbe = :color, ausblenden = :hide WHERE id = :id';
        } else {
            $sql = 'INSERT INTO kalender_gruppen (bezeichnung, farbe, ausblenden) VALUES (:name, :color, :hide)';
        }
        $this->db->perform($sql, [
            'name' => $json->name,
            'color' => $json->color,
            'hide' => !$json->active,
            'id' => $json->id
        ]);

        $sql = 'SELECT adresse FROM kalender_gruppen_mitglieder WHERE kalendergruppe = :id';
        $members = $this->db->fetchCol($sql, ['id' => $id]);

        $toDelete = array_diff($members, $json->members);
        $toAdd = array_diff($json->members, $members);

        if (!empty($toDelete)) {
            $sql = 'DELETE FROM kalender_gruppen_mitglieder WHERE kalendergruppe = :id AND adresse IN (:adressList)';
            $this->db->perform($sql, ['id' => $id, 'adressList' => $toDelete]);
        }
        $sql = 'INSERT INTO kalender_gruppen_mitglieder (kalendergruppe, adresse) VALUES (:id, :address)';
        foreach ($toAdd as $address) {
            $this->db->perform($sql, ['id' => $id, 'address' => $address]);
        }

        return JsonResponse::NoContent();
    }

    function KalenderGruppenDelete(): JsonResponse
    {
        $json = $this->request->getJson();
        $id = (int)$json->id;
        if ($id > 0) {
            $this->db->perform('DELETE FROM kalender_gruppen WHERE id = :id', ['id' => $id]);
            $this->db->perform('DELETE FROM kalender_gruppen_mitglieder WHERE kalendergruppe = :id', ['id' => $id]);
            return JsonResponse::NoContent();
        }
        return JsonResponse::BadRequest();
    }

    /*
    * Create invitation mail
    * All (!) E-mail recipients are in $recipients, participants for ICS are in the event
    * Use new sendmail with multiple recipients
    */
    public function KalenderMailNew(int $eventId, string $betreff = '', string $text = '', array $recipients) : bool
    {
        $sql = "SELECT * FROM kalender_event WHERE id = :id";
        $eventData = $this->db->fetchRow($sql, ['id' => $eventId]);

        $startDT = new DateTimeImmutable($eventData['von']);
        $endDT = new DateTimeImmutable($eventData['bis']);
        $beschreibung = $eventData["bezeichnung"];
        $venue = $eventData["ort"];
        $start = $startDT->format("Ymd");
        $start_time = $startDT->format("His");
        $end = $endDT->format("Ymd");
        $end_time = $endDT->format("His");

        $status = 'TENTATIVE';
        $sequence = 0;

        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//OpenXE//Termin//DE\r\n";
        $ical .= "METHOD:REQUEST\r\n";
        $ical .= "BEGIN:VEVENT\r\n";

        // Add participants
        $participants = $this->KalenderGetParticipants($eventId);

        // The first one is the organizer
        $organizer_name = $participants[0]['name'];
        $organizer_email = $participants[0]['email'];
        $ical .= "ORGANIZER;CN=$organizer_name:mailto:$organizer_email\r\n";

        if (count($participants) > 1) {
            unset($participants[0]);
            foreach ($participants as $participant) {
                $participant_name = $participant['name'];
                $participant_email = $participant['email'];
                $ical .= "ATTENDEE;CN=$participant_name;ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED;RSVP=TRUE:mailto:$participant_email\r\n";
            }
        }

        $ical .= "UID:" . strtoupper(md5($eventId)) . "-openxe\r\n";
        $ical .= "SEQUENCE:" . $sequence . "\r\n";
        $ical .= "STATUS:" . $status . "\r\n";
        $ical .= "DTSTAMPTZID=Europe/Berlin:" . date('Ymd') . 'T' . date('His') . "\r\n";
        $ical .= "DTSTART:" . $start . "T" . $start_time . "\r\n";
        $ical .= "DTEND:" . $end . "T" . $end_time . "\r\n";
        $ical .= "LOCATION:" . $venue . "\r\n";
        $ical .= "SUMMARY:" . $beschreibung . "\r\n";
        $ical .= "BEGIN:VALARM\r\n";
        $ical .= "TRIGGER:-PT15M\r\n";
        $ical .= "ACTION:DISPLAY\r\n";
        $ical .= "DESCRIPTION:Reminder\r\n";
        $ical .= "END:VALARM\r\n";
        $ical .= "END:VEVENT\r\n";
        $ical .= "END:VCALENDAR\r\n";

        $datei = $this->app->erp->GetTMP() . 'Einladung_' . $beschreibung . '_' . $startDT->format("YmdHis") . ".ics";
        file_put_contents($datei, $ical);
        if ($start != '00000000') {
            $dateien = array($datei);
        } else {
            $dateien = '';
        }

        $bcc = array();

        $to = '';
        foreach ($recipients as $rcpt) {
            $from = strstr($rcpt, '<', true); // Ab PHP 5.3.0
            $email = strstr($rcpt, '<'); // Ab PHP 5.3.0
            if ($from != "") {
                $email = str_replace(['<', '>'], '', $email);
            } else {
                $email = $rcpt;
            }

            if ($to == "") {
                $to = $email;
                $to_name = $from;
            } else {
                if ($email != $to) {
                    if ($from == "") $bcc[] = $email;
                    else $bcc[] = $email;//$from." <".$email.">";
                }
            }
        }

        $result = $this->app->erp->MailSend($this->app->erp->GetFirmaMail(), $this->app->erp->GetFirmaAbsender(),
            $to, $to_name, $betreff, $text, $dateien, "", false, $bcc);

        unlink($datei);
        return boolval($result);
    }


    /*
    * Original version
    */

    public
    function KalenderMail($event, $betreff = '', $text = '', $emailcc = '')
    {
        $datum = '';
        $arraufgabe = $this->app->DB->SelectArr("SELECT *,DATE_FORMAT(von,'%d.%m.%Y') as datum,
        DATE_FORMAT(bis,'%d.%m.%Y') as datumbis,
        DATE_FORMAT(von,'%Y%m%d') as icaldatumvon,
        DATE_FORMAT(bis,'%Y%m%d') as icaldatumbis, DATE_FORMAT(von,'%H%i00') as icaluhrzeitvon, DATE_FORMAT(bis,'%H%i00') as icaluhrzeitbis,
        DATE_FORMAT(von,'%H:%i') as zeit,
        DATE_FORMAT(bis,'%H:%i') as zeitbis
        FROM kalender_event WHERE id='$event' LIMIT 1");

        $adresse = $arraufgabe[0]["adresse"];
        $adresseintern = $arraufgabe[0]["adresseintern"];

        $to = $this->app->DB->Select("SELECT email FROM adresse WHERE id='$adresse' AND geloescht!=1 LIMIT 1");
        $to_name = $this->app->DB->Select("SELECT name FROM adresse WHERE id='$adresse' AND geloescht!=1 LIMIT 1");

        if ($adresseintern > 0) {
            $initiator_to = $this->app->DB->Select("SELECT email FROM adresse WHERE id='$adresseintern' AND geloescht!=1 LIMIT 1");
            $initiator_to_name = $this->app->DB->Select("SELECT name FROM adresse WHERE id='$adresseintern' AND geloescht!=1 LIMIT 1");
        } else {
            $initiator_to = $this->app->User->GetEmail();
            $initiator_to_name = $this->app->User->GetName();
        }

        //$this->LogFile("Sende Aufgabe $aufgabe an Email ".$to." und Initiator ".$initiator_to);

        if ($betreff == "")
            $betreff = "Einladung für Termin " . $arraufgabe[0]["bezeichnung"];

        if ($text == "") {
            if ($arraufgabe[0]["datumbis"] != "00.00.0000" && $arraufgabe[0]["datum"] != $arraufgabe[0]["datumbis"]) {
                $text = "Datum: " . $arraufgabe[0]["datum"] . " bis " . $arraufgabe[0]["datumbis"];//Hallo hier die mail";
            } else {
                if ($arraufgabe[0]["zeit"] != "00:00" && $arraufgabe[0]["zeitbis"] != "00:00")
                    $text = "Datum: " . $arraufgabe[0]["datum"] . " von " . $arraufgabe[0]["zeit"] . " bis " . $arraufgabe[0]["zeitbis"];//Hallo hier die mail";
                else if ($arraufgabe[0]["zeit"] != "00:00")
                    $text = "Datum: " . $arraufgabe[0]["datum"] . " um " . $arraufgabe[0]["zeit"];
                else
                    $text = "Datum: " . $arraufgabe[0]["datum"];
            }
        } else {
            $text .= "\r\n";
        }
        $text .= "\r\n";

        $beschreibung = $arraufgabe[0]["bezeichnung"];

        $venue = $arraufgabe[0]["ort"];
        $start = $arraufgabe[0]["icaldatumvon"];
        $start_time = $arraufgabe[0]["icaluhrzeitvon"];
        $end = $arraufgabe[0]["icaldatumbis"];
        $end_time = $arraufgabe[0]["icaluhrzeitbis"];

        $status = 'TENTATIVE';
        $sequence = 0;

        $event_id = $event;

        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//OpenXE//Termin//DE\r\n";
        $ical .= "METHOD:REQUEST\r\n";
        $ical .= "BEGIN:VEVENT\r\n";
        $ical .= "ORGANIZER;SENT-BY=\"MAILTO:$initiator_to\"\r\n";
        $ical .= "ATTENDEE;CN=$to;ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED;RSVP=TRUE:mailto:$initiator_to\r\n";
        $ical .= "UID:" . strtoupper(md5($event_id)) . "-openxe\r\n";
        $ical .= "SEQUENCE:" . $sequence . "\r\n";
        $ical .= "STATUS:" . $status . "\r\n";
        $ical .= "DTSTAMPTZID=Europe/Berlin:" . date('Ymd') . 'T' . date('His') . "\r\n";
        $ical .= "DTSTART:" . $start . "T" . $start_time . "\r\n";
        $ical .= "DTEND:" . $end . "T" . $end_time . "\r\n";
        $ical .= "LOCATION:" . $venue . "\r\n";
        $ical .= "SUMMARY:" . $beschreibung . "\r\n";
        $ical .= "BEGIN:VALARM\r\n";
        $ical .= "TRIGGER:-PT15M\r\n";
        $ical .= "ACTION:DISPLAY\r\n";
        $ical .= "DESCRIPTION:Reminder\r\n";
        $ical .= "END:VALARM\r\n";
        $ical .= "END:VEVENT\r\n";
        $ical .= "END:VCALENDAR\r\n";

        $datei = $this->app->erp->GetTMP() . 'Einladung_' . $beschreibung . '_' . $datum . ".ics";
        file_put_contents($datei, $ical);
        if ($start != '00000000') {
            $dateien = array($datei);
        } else {
            $dateien = '';
        }

        $bcc = array();

        if ($emailcc != '') {
            //$to="";
            //$to_name="";

            $parts = explode(',', $emailcc);
            $cparts = (!empty($parts) ? count($parts) : 0);
            for ($i = 0; $i < $cparts; $i++) {
                $from = strstr($parts[$i], '<', true); // Ab PHP 5.3.0
                $email = strstr($parts[$i], '<'); // Ab PHP 5.3.0
                if ($from != "") {
                    $email = str_replace(['<', '>'], '', $email);
                } else {
                    $email = $parts[$i];
                }

                if ($i == 0) {
                    if ($to == "") {
                        $to = $email;
                        $to_name = $from;
                    }
                } else {
                    if ($email != $to) {
                        if ($from == "") $bcc[] = $email;
                        else $bcc[] = $email;//$from." <".$email.">";
                    }
                }
            }
        }

        $result = $this->app->erp->MailSend($this->app->erp->GetFirmaMail(), $this->app->erp->GetFirmaAbsender(), $to, $to_name, $betreff, $text, $dateien, "", false, $bcc);

        unlink($datei);
        return $result;
    }

    /**
     * @return void
     */
    protected
    function trySynchronizeGoogleChanges()
    {
        if (!$this->isGoogleSynchronizationActive()) {
            return;
        }
        /** @var GoogleCalendarSynchronizer $sync */
        $sync = $this->app->Container->get('GoogleCalendarSynchronizer');
        $userId = (int)$this->app->User->GetID();
        try {
            /** @var GoogleCalendarClientFactory $clientFactory */
            $clientFactory = $this->app->Container->get('GoogleCalendarClientFactory');
            $client = $clientFactory->createClient($userId);
            $sync->importChangedEvents($client);
        } catch (Throwable $e) {
            return;
        }
    }

    /**
     * @return bool
     */
    protected
    function isGoogleSynchronizationActive()
    {
        if (!$this->app->Container->has('GoogleCredentialsService')) {
            return false;
        }
        /** @var GoogleCredentialsService $credentialService */
        $credentialService = $this->app->Container->get('GoogleCredentialsService');

        return $this->app->Container->has('GoogleCalendarSynchronizer') && $credentialService->existCredentials();
    }
}
