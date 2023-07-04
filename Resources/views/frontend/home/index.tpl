{extends file='parent:frontend/home/index.tpl'}

{* Canonical URL *}
{block name='frontend_index_header_canonical'}
    {if $ItswCar.rootUrl}
        <link rel="canonical" href="{$ItswCar.rootUrl}" />
    {else}
        <link rel="canonical" href="{url controller='index'}" />
    {/if}
{/block}