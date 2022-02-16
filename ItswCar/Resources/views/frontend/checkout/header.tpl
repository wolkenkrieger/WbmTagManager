{extends file="parent:frontend/checkout/header.tpl"}

{block name='frontend_index_logo'}
    <div class="logo--shop block itsw">
        {s name="IndexLinkDefault" namespace="frontend/index/index" assign="snippetIndexLinkDefault"}{/s}
        <a class="logo--link" href="{$ItswCar.rootUrl}" title="{"{config name=shopName}"|escapeHtml} - {$snippetIndexLinkDefault|escape}">
            <picture>
                <source srcset="{link file='frontend/_public/src/img/atw-logo.jpg'}" media="(min-width: 78.75em)">
                <source srcset="{link file='frontend/_public/src/img/atw-logo.jpg'}" media="(min-width: 64em)">
                <source srcset="{link file='frontend/_public/src/img/atw-logo.jpg'}" media="(min-width: 48em)">
                <img width="116" height="50" srcset="{link file='frontend/_public/src/img/atw-logo.jpg'}" alt="{"{config name=shopName}"|escapeHtml} - {$snippetIndexLinkDefault|escape}" />
            </picture>
        </a>
    </div>
{/block}