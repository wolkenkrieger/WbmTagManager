{*
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   15.12.2020
 * Zeit:    11:17
 * Datei:   index.tpl
 *}
{extends file="parent:frontend/index/index.tpl"}

{* Shop navigation *}
{block name='frontend_index_shop_navigation'}
	{$smarty.block.parent}

	{* ITSW CarFinder Widget *}
    {block name="frontend_index_itsw_carfinder_widget"}
        {if {controllerAction|lower} !== 'service' && {controllerName|lower} !== 'checkout' && {controllerName|lower} !== 'register'}
            {action module=widgets controller=carfinder}
        {/if}
    {/block}
{/block}