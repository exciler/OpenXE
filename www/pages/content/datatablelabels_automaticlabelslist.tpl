<div id="tabs">
	<ul>
		<li><a href="#tabs-1">[TABTEXT]</a></li>
	</ul>

	<div id="tabs-1">
		<form method="post">
			[MESSAGE]
		</form>
		<div class="row">
			<div class="row-height">
				<div class="col-xs-12 col-md-10 col-md-height">
					<div class="inside-white inside-full-height">
						[TAB1]
					</div>
				</div>
				<div class="col-xs-12 col-md-2 col-md-height">
					<div class="inside inside-full-height">
						<fieldset>
							<legend>{|Aktionen|}</legend>
							<input type="button" class="btnGreenNew" name="datatablelabel_automaticlabelnew" value="&#10010; Neuer Eintrag" onclick="DataTableLabelsAutomaticLabelsUi.createItem();">
						</fieldset>
					</div>
				</div>
			</div>
		</div>

		[TAB1NEXT]
	</div>
</div>

<script id="vueapp_props" type="application/json">[VUEPROPS]</script>
<div id="vueapp"></div>
