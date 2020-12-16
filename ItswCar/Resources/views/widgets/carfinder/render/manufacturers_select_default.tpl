{*
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   15.12.2020
 * Zeit:    23:06
 * Datei:   manufacturers_select_default.tpl
 *}
<option></option>
{foreach $manufacturers as $manufacturer}
	<option
			data-id="{$manufacturer.id}"
			value="{$manufacturer.id}"
            {if $manufacturer.id === $session.manufacturer}selected="selected"{/if}
	>
        {$manufacturer.display}
	</option>
{/foreach}