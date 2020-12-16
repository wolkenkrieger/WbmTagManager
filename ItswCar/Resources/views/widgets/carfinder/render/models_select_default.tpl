{*
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   16.12.2020
 * Zeit:    11:07
 * Datei:   models_select_default.tpl
 *}
<option></option>
{foreach $models as $model}
	<option
			data-id="{$model.id}"
			value="{$model.id}"
            {if $model.id === $session.model}selected="selected"{/if}
	>
        {$model.display}
	</option>
{/foreach}