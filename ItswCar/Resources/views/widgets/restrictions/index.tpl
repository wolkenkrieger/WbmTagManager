{namespace name="itsw/restrictions"}
{block name="itsw_widget_restriction"}
	<div class="itsw">
		<div class="widget restrictions">
            {block name="itsw_widget_restriction_headline"}
				{*<h2>{s name="FitsToCars"}Passend für folgende Fahrzeuge{/s}</h2>*}
            {/block}
            {block name="itsw_widget_restriction_content"}
				{if $restrictionData|@count}
					<div class="table toggle">
						<div class="table--header">
							<div class="table--cell">{s name="Car"}Fahrzeug{/s}</div>
							<div class="table--cell">{s name="kW-PS"}kW / PS{/s}</div>
							<div class="table--cell">{s name="ccm"}ccm&sup3;{/s}</div>
							<div class="table--cell">{s name="buildFrom-buildTo"}Bauzeitraum{/s}</div>
							<div class="table--cell">{s name="restriction"}Einschränkung{/s}</div>
						</div>
                        {foreach $restrictionData as $data}
							<div class="table--row">
								<div class="table--cell">{$data.manufacturer}&nbsp;{$data.model}&nbsp;{$data.type}</div>
								<div class="table--cell">{$data.kw} / {$data.ps}</div>
								<div class="table--cell">{$data.ccm}</div>
								<div class="table--cell">{$data.buildFrom} - {$data.buildTo}</div>
								<div class="table--cell">{$data.restriction}</div>
							</div>
                        {/foreach}
					</div>
				{/if}
            {/block}
		</div>
	</div>
{/block}