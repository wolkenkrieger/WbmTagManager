{extends file="parent:frontend/index/logo-container.tpl"}

<div class="logo-main block-group" role="banner">
    {* Main shop logo *}
    {block name='frontend_index_logo'}
        <div class="logo--shop block itsw">
            {s name="IndexLinkDefault" namespace="frontend/index/index" assign="snippetIndexLinkDefault"}{/s}
            <a class="logo--link" href="{if $ItswCar.rootUrl}{$ItswCar.rootUrl}{else}{url controller='index'}{/if}" title="{"{config name=shopName}"|escapeHtml} - {$snippetIndexLinkDefault|escape}">
                <picture>
                    <source srcset="{link file='frontend/_public/src/img/atw-logo.webp'}" type="image/webp">
                    <img width="116"
                         height="50"
                         loading="lazy"
                         srcset="{link file='frontend/_public/src/img/atw-logo.jpg'}"
                         alt="{"{config name=shopName}"|escapeHtml} - {$snippetIndexLinkDefault|escape}" />
                </picture>
            </a>
        </div>
    {/block}

    {* Support Info *}
    {block name='frontend_index_logo_supportinfo'}
        {if $theme.checkoutHeader && {controllerName|lower} === 'checkout' && {controllerAction|lower} !== 'cart'}
            <div class="logo--supportinfo block">
                {s name='RegisterSupportInfo' namespace='frontend/register/index'}{/s}
            </div>
        {/if}
    {/block}

    {* Trusted Shops *}
    {block name='frontend_index_logo_trusted_shops'}{/block}
</div>
