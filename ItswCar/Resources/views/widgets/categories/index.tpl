{*
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   27.04.2021
 * Zeit:    15:09
 * Datei:   index.tpl
 *}
<table>
	<tr>
        {foreach $sMainCategories as $mainCategory}
			<td><a href="{$mainCategory.link}">{$mainCategory.name}</a></td>
        {/foreach}
	</tr>
</table>
