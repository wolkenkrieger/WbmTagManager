{*
 * Author: Rico Wunglueck <development@itsw.dev>
 * Date: 08.12.2021
 * Time: 08:38
 * File: item.tpl
 *}
{block name="itsw_frontend_garage_item"}
	<div class="garage--item table">
		<div class="table--cell">
			<div class="bold h2">{$car.manufacturer.display}&nbsp;{$car.model.display}&nbsp;{$car.type.display}</div>
			<div>{$car.ccm}&nbsp;cm&sup3;, {$car.ps}&nbsp;PS, {$car.kw}&nbsp;kW, {$car.buildFrom} - {$car.buildTo}</div>
			<div>{if $car.platform.display neq '--'}{$car.platform.display}{if !empty($car.codes)}, {/if}{/if}{foreach name=codes item=code from=$car.codes}{$code.hsn}/{$code.tsn}{if !$smarty.foreach.codes.last} {/if}{/foreach}</div>
		</div>
		<div class="table--cell is--right">
			<a class="btn is--small is--basic" href="{url controller="garage" module="frontend" action="delete" car=$car.tecdocId}"><i class="icon--cross"></i></a>
			<a class="btn is--small is--primary" href="{url controller="carfinder" module="widgets" action="set-car" manufacturer=$car.manufacturer.id model=$car.model.id type=$car.type.id car=$car.tecdocId}"><i class="icon--arrow-right"></i></a>

		</div>
	</div>
{/block}