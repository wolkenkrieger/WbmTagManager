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
{namespace name="itsw/carfinder"}
<option></option>
{foreach $types as $type}
	<option
			data-carid="{$type.tecdocId}"
			value="{$type.typeId}"
			data-renderdata="({$type.ccm}&nbsp;cm&sup3;&nbsp;&nbsp;{$type.kw}&nbsp;kW&nbsp;/&nbsp;{$type.ps}&nbsp;PS)&nbsp;&nbsp;({$type.platform})"
            {if $type.tecdocId === $session.car}selected="selected"{/if}
	>
        {$type.display}
	</option>
{/foreach}
