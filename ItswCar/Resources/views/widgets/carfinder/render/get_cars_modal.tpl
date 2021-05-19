{*
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   16.12.2020
 * Zeit:    19:02
 * Datei:   get_cars_modal.tpl
 *}

{if !empty($cars)}
	<div class="itsw modal--container">
		<div class="table toggle">
			<div class="table--header">
				<div class="table--cell">Bezeichnung</div>
				<div class="table--cell">Motor</div>
				<div class="table--cell">Baujahr</div>
				<div class="table--cell">Platform</div>
				<div class="table--cell">HSN/TSN</div>
				<div class="table--cell"></div>
			</div>
            {foreach $cars as $car}
				<div class="table--row">
					<div class="table--cell min-width--80">{$car.manufacturer.display}&nbsp;{$car.model.display}&nbsp;{$car.type.display}</div>
					<div class="table--cell min-width--80">{$car.ccm}&nbsp;cm&sup3;, {$car.ps}&nbsp;PS, {$car.kw}&nbsp;kW</div>
					<div class="table--cell min-width--80">{$car.buildFrom} - {$car.buildTo}</div>
					<div class="table--cell min-width--80">{$car.platform.display}</div>
					<div class="table--cell min-width--80">{foreach name=codes item=code from=$car.codes}{$code.hsn}/{$code.tsn}{if !$smarty.foreach.codes.last} {/if}{/foreach}</div>
					<div class="table--cell is--right"><a class="btn is--icon-right is--small is--primary" href="{url controller="carfinder" module="widgets" action="set-car" manufacturer=$car.manufacturer.id model=$car.model.id type=$car.type.id car=$car.tecdocId}">Wählen<i class="icon--check"></i></a></div>
				</div>
            {/foreach}
		</div>
	</div>
{else}
	<div class="itsw modal--container">
		keine Fahrzeuge gefunden!
	</div>
{/if}