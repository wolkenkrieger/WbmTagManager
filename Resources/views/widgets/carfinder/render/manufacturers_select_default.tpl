{*
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   15.12.2020
 * Zeit:    23:06
 * Datei:   manufacturers_select_default.tpl
 *}
{namespace name="itsw/carfinder"}
<option></option>
<optgroup label="{s name="ManufacturerSelectTopBrands"}Beliebte Marken{/s}">
    {foreach $topBrands as $manufacturer}
		{if $manufacturer.topBrand}
			<option
					value="{$manufacturer.id}"
                    {if $manufacturer.id === $session.manufacturer}selected="selected"{/if}
			>
                {$manufacturer.display}
			</option>
		{/if}
	{/foreach}
</optgroup>
<optgroup label="{s name="ManufacturerSelectAllBrands"}Alle Marken{/s}">
    {foreach $allBrands as $manufacturer}
		<option
				value="{$manufacturer.id}"
                {if $manufacturer.id === $session.manufacturer}selected="selected"{/if}
		>
            {$manufacturer.display}
		</option>
    {/foreach}
</optgroup>