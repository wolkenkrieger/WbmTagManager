{extends file="parent:frontend/account/sidebar.tpl"}

{block name="frontend_account_menu_link_notes"}
    {$smarty.block.parent}
    {* Link to the user garage *}
    {block name="itsw_frontent_account_menu_link_garage"}
        <li class="navigation--entry">
            <a href="{url module='frontend' controller='garage'}" title="{s namespace="itsw/garage" name="GARAGE_WIDGET_INDEX_LINK"}{/s}" class="navigation--link{if {controllerName} == 'garage'} is--active{/if}" rel="nofollow">
                {s namespace="itsw/garage" name="GARAGE_WIDGET_INDEX_LINK"}{/s}
            </a>
        </li>
    {/block}
{/block}