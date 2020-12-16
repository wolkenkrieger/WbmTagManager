{*
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   16.12.2020
 * Zeit:    19:02
 * Datei:   get_cars_modal.tpl
 *}

{if $cars}
	<div class="itsw modal--container">
		<div class="table">
			<div class="table--head">
				<div class="table--cell">Hersteller</div>
				<div class="table--cell">Modell</div>
				<div class="table--cell">Typ</div>
				<div class="table--cell">Platform</div>
				<div class="table--cell">kW</div>
				<div class="table--cell">PS</div>
				<div class="table--cell">ccm&sup3;</div>
				<div class="table--cell">Bauzeitraum</div>
				<div class="table--cell">HSN/TSN</div>
			</div>
            {foreach $cars as $car}
				<div class="table--row">
					<div class="table--cell">{$car.manufacturer.display}</div>
					<div class="table--cell">{$car.model.display}</div>
					<div class="table--cell">{$car.type.display}</div>
					<div class="table--cell">{$car.platform.display}</div>
					<div class="table--cell">{$car.kw}</div>
					<div class="table--cell">{$car.ps}</div>
					<div class="table--cell">{$car.ccm}</div>
					<div class="table--cell">{$car.buildFrom} - {$car.buildTo}</div>
					<div class="table--cell">{foreach name=codes item=code from=$car.codes}{$code.hsn}/{$code.tsn}{if !$smarty.foreach.codes.last}&nbsp;{/if}{/foreach}</div>
				</div>
            {/foreach}
		</div>
	</div>
{/if}