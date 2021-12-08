{extends file='parent:frontend/index/index.tpl'}
{namespace name="itsw/garage"}

{* Breadcrumb *}
{block name='frontend_index_start'}
    {s name="GARAGE_WIDGET_INDEX_LINK" assign="snippetGarageTitle"}{/s}
    {$sBreadcrumb = [['name' => $snippetGarageTitle, 'link' => {url}]]}
    {$smarty.block.parent}
{/block}

{* Account Sidebar *}
{block name="frontend_index_left_categories"}
    {block name="frontend_account_sidebar"}
        {include file="frontend/account/sidebar.tpl"}
    {/block}
    {$smarty.block.parent}
{/block}

{* Main content *}
{block name="frontend_index_content"}
    <div class="content{if $sUserLoggedIn && !$sOneTimeAccount} account--content{else} note--content{/if}">

        {* Infotext *}
        {block name="itsw_frontend_garage_index_welcome"}
            <div class="account--welcome panel">
                {block name="itsw_frontend_garage_index_welcome_headline"}
                    <h1 class="panel--title">{s name="GARAGE_FRONTEND_INDEX_HEADLINE"}Meine Fahrzeuge{/s}</h1>
                {/block}

                {block name="itsw_frontend_garage_index_welcome_content"}
                    <div class="panel--body is--wide">
                        <p>{s name="GARAGE_FRONTEND_TEXT_1"}{/s}</p>
                        <p>{s name="GARAGE_FRONTEND_TEXT_2"}{/s}</p>
                    </div>
                {/block}
            </div>
        {/block}

        {if $sUserLoggedIn && !$sOneTimeAccount}
            {block name="itsw_frontend_garage_index_overview"}
                <div class="itsw">
                    <div class="garage--overview">
                        {if $cars}
                            {block name="itsw_frontend_garage_index_table"}
                                <div class="panel--table is--rounded">
                                    {block name="itsw_frontend_garage_index_table_items"}
                                        {foreach $cars as $car}
                                            {include file="frontend/garage/item.tpl"}
                                        {/foreach}
                                    {/block}
                                </div>
                            {/block}
                        {/if}
                    </div>
                </div>
            {/block}
        {else}
            {block name="itsw_frontend_garage_index_notloggedin_content"}
                <div class="panel--body is--wide">
                    <div class="alert is--info is--rounded">
                        <div class="alert--icon">
                            <i class="icon--element icon--info"></i>
                        </div>
                        <div class="alert--content">{s name="GARAGE_FRONTEND_NOT_LOGGED_IN"}Um die Garage nutzen zu können, melden Sie sich bitte in ihrem Nutzerkonto an{/s}</div>
                    </div>
                </div>
            {/block}
        {/if}
    </div>
{/block}
