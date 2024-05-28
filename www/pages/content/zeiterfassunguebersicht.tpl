<div id="tabs">
    <ul>
        <li><a href="#tabs-2">{|Zeitkonto Kunden|}</a></li>
<!--        <li><a href="#tabs-3">Zeitkonto Projekte</a></li>-->
        <li><a href="#tabs-1">{|Zeitkonto Mitarbeiter|}</a></li>
        <li><a href="#tabs-3" data-toggle="tab" >{|Zeitkonto Kalenderansicht|}</a></li>
    </ul>
<div id="tabs-1">

  <div class="filter-box filter-usersave">
    <div class="filter-block filter-inline">
      <div class="filter-title">{|Filter|}</div>
      <ul class="filter-list">
        <li class="filter-item">
          <label for="offen" class="switch">
            <input type="checkbox" id="offen">
            <span class="slider round"></span>
          </label>
          <label for="offen">{|offen|}</label>
        </li>
        <li class="filter-item">
          <label for="projekt">{|Projekt|}:</label>
          <input type="text" id="projekt" size="30"/>
        </li>
        <li class="filter-item">
          <label for="mitarbeiter">{|Mitarbeiter|}:</label>
          <input type="text" id="mitarbeiter" size="30" />
        </li>
        <li class="filter-item">
          <label for="von">{|von|}:</label>
          <input type="text" id="von" size="12"/>
        </li>
        <li class="filter-item">
          <label for="bis">{|bis|}:</label>
          <input type="text" id="bis" size="12"/>
        </li>
      </ul>
    </div>
  </div>

[MESSAGE]
[TAB1]
</div>


<div id="tabs-2">

  <div class="filter-box filter-usersave">
    <div class="filter-block filter-inline">
      <div class="filter-title">{|Filter|}</div>
      <ul class="filter-list">
        <li class="filter-item">
          <label for="kunden" class="switch">
            <input type="checkbox" id="kunden" title="auf Kundenkonto gebucht">
            <span class="slider round"></span>
          </label>
          <label for="kunden">{|Nur auf Kundenkonto gebuchte Zeiten|}</label>
        </li>
      </ul>
    </div>
  </div>

[MESSAGE]
[TAB2]
</div>


<div id="tabs-3">
<div class="row">
<div class="row-height">
<div class="col-xs-12 col-md-10 col-md-height">
<div class="inside_white inside-full-height">
<fieldset class="white"><legend>&nbsp;</legend>
<div id="vueapp_calendar"></div>
</fieldset>
</div>
</div>
<div class="col-xs-12 col-md-2 col-md-height">
<div class="inside inside-full-height">
<fieldset><legend>{|Übersicht|}</legend>
<form action="#tabs-3" method="post">
{|Auswahl Mitarbeiter|}:
<br><input type="text" size="25" name="mitarbeiterkalenderansicht" id="mitarbeiterkalenderansicht" value="[MITARBEITERKALENDERANSICHT]"><input type="submit" value="{|übernehmen|}">
</form>
</fieldset>


</div>
</div>
</div>
</div>


</div>




</div>

