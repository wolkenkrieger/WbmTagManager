{*
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   16.12.2020
 * Zeit:    11:12
 * Datei:   types_select_default.tpl
 *}
<option></option>
{foreach $types as $type}
	<option
			data-id="{$type.id}"
			value="{$type.id}"
            {if $type.id === $session.type}selected="selected"{/if}
	>
        {$type.display}
	</option>
{/foreach}