<!-- gehort zu tabview -->
<!-- ende gehort zu tabview -->

<!-- erstes tab -->
<!-- gehort zu tabview -->
<div id="tabs">
<ul>
[ZEITERFASSUNGTABS]
</ul>
<!-- ende gehort zu tabview -->

<!-- erstes tab -->
<div id="tabs-1">
[MESSAGE]
[TAB1]
[TAB1NEXT]
</div>

<div id="tabs-2">

<div class="row">
<div class="row-height">
<div class="col-xs-12 col-md-10 col-md-height">
<div class="inside_white inside-full-height">
<fieldset class="white"><legend>&nbsp;</legend>
<div id='vueapp_calendar'></div>
</fieldset>
</div>
</div>
<div class="col-xs-12 col-md-2 col-md-height">
<div class="inside inside-full-height">
<fieldset><legend>{|Übersicht|}</legend>
<input type="checkbox" name="zeiterfassung_buchen_termine" id="zeiterfassung_buchen_termine" value="1" [CHECKEDZEITERFASSUNGBUCHENTERMINE]>&nbsp;{|Termine aus Kalender|}
<br><input type="checkbox" name="zeiterfassung_buchen_stechuhr" id="zeiterfassung_buchen_stechuhr" value="1" [CHECKEDZEITERFASSUNGBUCHENSTECHUHR]>&nbsp;{|Stechuhr Zeiten einblenden|}
</fieldset>

<fieldset><legend>{|Aktionen|}</legend>
<input type="button" value="{|Neue Zeit buchen|}" style="width:100%" class="btnGreenNew vueAction" data-action="create"><br>
[FORMULARANSICHT]
</fieldset>

</div>
</div>
</div>
</div>

<script type='text/javascript'>
var projektname = 'hh';

var ResetMode = function() {
  $('#editZeiterfassung').find('#editid').val('');
  $('#editZeiterfassung').find('#internerkommentar').val('');
};

// Methode zum addieren/subtrahieren einer Menge an Minuten auf eine Uhrzeit
// time = Uhrzeit im Format HH:MM
// offset = Zeit in Minuten
function addMinutes(time, offset){
  // Uhrzeit wird in Stunden und Minuten geteilt
  var elements = time.split(":");
  var hours = elements[0];	
  var minutes = elements[1];
  // Aufrunden des Offsets fuer den Fall, dass eine Fliesskommazahl uebergeben wird
  var roundOffset = Math.ceil(offset);
	
  // Umrechnen der Uhrzeit in Minuten seit Tagesbeginn
  var timeSince24 = (hours * 60) + parseInt(minutes);
  // Addieren des uebergebenen Offsets
  timeSince24 = timeSince24 + parseInt(roundOffset);

  // Ueberlaufbehandlung
  if(timeSince24 < 0)
    timeSince24 = timeSince24 + 1440;
  else if(timeSince24 > 1440)
    timeSince24 = timeSince24 - 1440;
	
  // Errechnen von Stunden und Minuten aus dem Gesamtzeit seit Tagesbeginn
  var resMinutes = timeSince24 % 60;
  var resHours = (timeSince24 - resMinutes)/60;
	
  // Sicherstellen, dass der Wert fuer Minuten immer zweistellig ist
  if(resMinutes < 10)
    resMinutes = "0" + resMinutes;
	
       
  if(resHours>23)
  {
    resHours=23;
    resMinutes=59;
  }       

  if(resMinutes>59)
  {
    resMinutes=59;
  }   
  // Ausgabe des formatierten Ergebnisses
  return resHours + ":" + resMinutes;
}



function BerechneEndzeit(minuten)
{
  var vonzeit = $('#editZeiterfassung').find('#vonZeit').val();
  $('#editZeiterfassung').find('#bisZeit').val(addMinutes(vonzeit,minuten));
}


</script>
<div id="editZeiterfassung" title="Zeiterfassung">
<div id="submitError"></div>
<input type="hidden" name="eventid" id="eventid" value="">
<table>
 <tr><td>Am:</td>
	<td colspan="3">
 <input type="text" name="datum" id="datum" size="10" value="[DATUM]" class="pflicht" maxlength="">&nbsp;{|von|}&nbsp;
<input type="text" name="vonZeit" id="vonZeit" size="4" value="[VONZEIT]" class="pflicht">&nbsp;{|Uhr|} &nbsp;{|Bis|}:&nbsp;
 <input type="text" name="bisZeit" id="bisZeit" size="4"  value="[BISZEIT]" class="pflicht">&nbsp;{|Uhr|} (HH:MM)
</td></tr>
 <tr><td></td>
	<td colspan="3">
    <input type="button" value="15 Min" onclick="BerechneEndzeit(15);">&nbsp;
	<input type="button" value="30 Min" onclick="BerechneEndzeit(30);">&nbsp;
	<input type="button" value="45 Min" onclick="BerechneEndzeit(45);">&nbsp;
<input type="button" value="1 Std" onclick="BerechneEndzeit(60);">&nbsp;
<input type="button" value="2 Std" onclick="BerechneEndzeit(120);">
<input type="button" value="Dauer" onclick="var dauer = prompt('Dauer eingeben z.B. 3,5 für 3,5 Stunden:',''); dauer = dauer.replace(',','.'); if(dauer > 0) BerechneEndzeit(dauer*60);">
</td></tr>

 <tr><td></td><td colspan="3"><i>{|Bitte die Pausen gesondert als Pausen (nicht Arbeit) buchen.|}</i></td></tr>

 <tr><td>{|Art/Tätigkeit|}:</td><td colspan="3" nowrap><select name="art" id="art2">[ART]</select>&nbsp;<input type="text" name="aufgabe2" id="aufgabe2" size="40" value="[AUFGABE]" class="pflicht"></td></tr>

 <tr><td>{|Details|}:</td><td colspan="2" nowrap><textarea type="text" name="beschreibung2" cols="62" rows="5" id="beschreibung2"></textarea></td><td></td></tr>
[STARTKOMMENTAR]
 <tr><td>{|Interner Kommentar|}:</td><td colspan="2" nowrap><textarea type="text" name="internerkommentar" id="internerkommentar" cols="62" rows="3"></textarea></td><td></td></tr>
[ENDEKOMMENTAR]
[STARTORT]
 <tr><td>{|Ort (wenn extern)|}:</td><td colspan="3"><input type="text" id="ort" name="ort" size="62" value="[ORT]"></td></tr>
 <tr><td></td><td><input type="hidden" id="gps" name="gps"  value="[GPS]">&nbsp;[GPSBUTTON]<div id="message">[GPSIMAGE]</div></td></tr>
[ENDEORT]
<tr><td>{|Projekt|}:</td><td>[PROJEKT_MANUELLAUTOSTART]<input type="text" id="projekt_manuell2" size="50" name="projekt_manuell2" value="[PROJEKT_MANUELL]">[PROJEKT_MANUELLAUTOEND]</td></tr>
<tr id="teilprojektrow" style="display:"><td>{|Teilprojekt|}:</td><td><input type="text" name="arbeitspaket" id="arbeitspaket2" value="[PAKETAUSWAHL]" size="50"></td></tr>
[STARTERWEITERT]
<tr><td></td><td colspan="3"><br></td></tr>

<tr><td>{|Kunde|}:</td><td>[ADRESSE_ABRECHNUNGAUTOSTART]<input type="text" id="adresse_abrechnung2" size="50" name="adresse_abrechnung" value="[ADRESSE_ABRECHNUNG]">[ADRESSE_ABRECHNUNGAUTOEND]</td></tr>
<tr><td>{|Auftrag|}:</td><td><input type="text" id="auftrag" size="50" name="auftrag" value="[AUFTRAG]"></td></tr>
<tr><td>{|Auftragsposition|}:</td><td><input type="text" id="auftragpositionid" size="50" name="auftragpositionid" value="[AUFTRAGPOSITIONID]"></td></tr>
<tr><td>{|Produktion|}:</td><td><input type="text" id="produktion" size="50" name="produktion" value="[PRODUKTION]"></td></tr>
[VORSERVICEAUFTRAG]<tr><td>{|Serviceauftrag|}:</td><td><input type="text" id="serviceauftrag" size="50" name="serviceauftrag" value="[SERVICEAUFTRAG]"></td></tr>[NACHSERVICEAUFTRAG]
<tr><td>{|Abrechnen|}:</td><td><input type="checkbox" name="abrechnen" id="abrechnen2" value="1" [ABRECHNEN]>&nbsp;<i>{|Bitte ausw&auml;hlen, wenn Zeit abgerechnet werden soll.|}</i></td></tr>

[ENDEERWEITERT]



<!--<tr><td>Verrechnungsart:</td><td><input type="text" id="verrechnungsart" size="50" name="verrechnungsart" value="[VERRECHNUNGSART]"></td></tr>-->


</table>



</div>

</div>
<!-- tab view schließen -->
</div>



