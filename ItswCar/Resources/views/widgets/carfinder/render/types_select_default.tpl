{*
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   16.12.2020
 * Zeit:    11:12
 * Datei:   types_select_default.tpl
 *}
{*
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
*}

<option></option>
{foreach $types as $type}
	<option
			data-carid="{$type.tecdocId}"
			value="{$type.typeId}"
            {if $type.tecdocId === $session.car}selected="selected"{/if}
	>
        {$type.display} ({$type.ccm}&nbsp;cm&sup3; {$type.kw}&nbsp;kW / {$type.ps}&nbsp;PS {$type.platform})
	</option>
{/foreach}
