{*
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   15.12.2020
 * Zeit:    11:17
 * Datei:   index.tpl
 *}
{extends file="parent:frontend/index/index.tpl"}

{block name='frontend_index_header_navigation'}
	<div class="container header--navigation">

        {* Logo container *}
        {block name='frontend_index_logo_container'}
            {include file="frontend/index/logo-container.tpl"}
        {/block}

        {* Shop navigation *}
        {block name='frontend_index_shop_navigation'}
            {include file="frontend/index/shop-navigation.tpl"}
        {/block}

        {block name='frontend_index_container_ajax_cart'}
			<div class="container--ajax-cart" data-collapse-cart="true"{if $theme.offcanvasCart} data-displayMode="offcanvas"{/if}></div>
        {/block}

        {* ITSW CarFinder Widget *}
        {block name="frontend_index_itsw_carfinder_widget"}
            {* include file="widgets/carfinder/carfinder.tpl" *}
			{action module=widgets controller=carfinder}
        {/block}
	</div>
{/block}