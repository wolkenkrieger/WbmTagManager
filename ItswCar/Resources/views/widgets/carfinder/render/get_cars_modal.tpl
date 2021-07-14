{*
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   16.12.2020
 * Zeit:    19:02
 * Datei:   get_cars_modal.tpl
 *}
{namespace name="itsw/carfinder"}
{if !empty($cars)}
	<div class="itsw modal--container">
		<div class="table flex table--header">
			<div>{s name="MODAL_CAR"}Bezeichnung{/s}</div>
			<div>{s name="MODAL_MOTOR"}Motor{/s}</div>
			<div>{s name="MODAL_BUILD_YEAR"}Baujahr{/s}</div>
			<div>{s name="MODAL_PLATFORN"}Platform{/s}</div>
			<div>{s name="MODAL_HSN_TSN"}HSN/TSN{/s}</div>
			<div></div>
		</div>
        {foreach $cars as $car}
			<div class="table flex table--row">
				<div>{$car.manufacturer.display}&nbsp;{$car.model.display}&nbsp;{$car.type.display}</div>
				<div>{$car.ccm}&nbsp;cm&sup3;, {$car.ps}&nbsp;PS, {$car.kw}&nbsp;kW</div>
				<div>{$car.buildFrom} - {$car.buildTo}</div>
				<div>{$car.platform.display}</div>
				<div>{foreach name=codes item=code from=$car.codes}{$code.hsn}/{$code.tsn}{if !$smarty.foreach.codes.last} {/if}{/foreach}</div>
				<div class="is--right"><a class="btn is--icon-right is--small is--primary" href="{url controller="carfinder" module="widgets" action="set-car" manufacturer=$car.manufacturer.id model=$car.model.id type=$car.type.id car=$car.tecdocId}">{s name="MODAL_CHOOSE_BUTTON"}Wählen{/s}<i class="icon--check"></i></a></div>
			</div>
        {/foreach}
	</div>
{else}
	<div class="itsw modal--container">
		keine Fahrzeuge gefunden!
	</div>
{/if}