{*
 * Author: Rico Wunglueck <development@itsw.dev>
 * Date: 15.03.2022
 * Time: 12:49
 * File: models.tpl
 *}

{extends file='parent:frontend/index/index.tpl'}

{namespace name="itsw/carmap"}

* Breadcrumb *}
{block name='frontend_index_start'}
    {s name="CARMAP_FRONTEND_MODELS_LINK" assign="snippetCarmapTitle"}Fahrzeugliste und -schnellwahl{/s}
    {$sBreadcrumb = [['name' => $snippetCarmapTitle, 'link' => {url}]]}
    {$smarty.block.parent}
{/block}

{* Account Sidebar *}
{block name="frontend_index_left_categories"}{/block}

{* Main content *}
{block name="frontend_index_content"}
    <div class="content">
        {* Infotext *}
        {block name="itsw_frontend_carmap_models_welcome"}
            <div class="account--welcome panel">
                {block name="itsw_frontend_carmap_models_welcome_headline"}
                    {assign var=firstModel value=$models|@reset}
                    <h1 class="panel--title">{$firstModel.manufacturerDisplay} {s name="CARMAP_FRONTEND_MODELS_HEADLINE"}- Modelle, Typen und Motorisierungen{/s}</h1>
                {/block}

                {block name="itsw_frontend_carmap_models_welcome_content"}
                    <div class="panel--body is--wide">
                        <p>{s name="CARMAP_MODELS_TEXT_1"}{/s}</p>
                        <p>{s name="CARMAP_MODELS_TEXT_2"}{/s}</p>
                    </div>
                {/block}
            </div>
        {/block}

        {block name="itsw_frontend_carmap_models"}
            <nav class="itsw">
                <div class="carmap model panel is--rounded is--border">
                    {*<span class="panel--title is--underline" role="heading">{$firstModel.manufacturerDisplay}</span>*}
                    <div class="panel--body is--wide" role="menu">
                        {foreach $models as $data}
                            <a href="{url controller=carmap action=types manufacturer=$data.manufacturerId model=$data.modelId}" title="{$data.manufacturerDisplay} {$data.modelDisplay}" itemprop="item" role="menuitem">
                                <link itemprop="url" href="{url controller=carmap action=types manufacturer=$data.manufacturerId  model=$data.modelId}">
                                <span itemprop="name"><span class="link--name">{$data.manufacturerDisplay} {$data.modelDisplay}</span> {if $data.MIN_PS neq $data.MAX_PS}{$data.MIN_PS} - {$data.MAX_PS}{else}{$data.MIN_PS}{/if} PS / {if $data.MIN_KW neq $data.MAX_KW}{$data.MIN_KW} - {$data.MAX_KW}{else}{$data.MIN_KW}{/if} kW / {if $data.MIN_CCM neq $data.MAX_CCM}{$data.MIN_CCM} - {$data.MAX_CCM}{else}{$data.MIN_CCM}{/if} cm&sup3; / {$data.MIN_BUILD|date_format:"%m/%Y"} - {$data.MAX_BUILD|date_format:"%m/%Y"}</span>
                            </a>
                        {/foreach}
                    </div>
                </div>
            </nav>
        {/block}
    </div>
{/block}