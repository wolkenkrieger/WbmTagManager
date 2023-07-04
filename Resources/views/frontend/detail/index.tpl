{extends file='parent:frontend/detail/index.tpl'}

{* Modify the breadcrumb *}{block name="frontend_detail_breadcrumb_overview"}
    {if !{config name="disableArticleNavigation"}}
        {$breadCrumbBackLink = $sBreadcrumb[$sBreadcrumb|count - 1]['link']}
        <a class="itsw-car-breadcrumb--button breadcrumb--link" href="{if $breadCrumbBackLink}{$breadCrumbBackLink}{else}#{/if}" title="{s name="DetailNavIndex" namespace="frontend/detail/navigation"}{/s}">
            <i class="icon--arrow-left"></i>
            <span class="breadcrumb--title">{s name='DetailNavIndex' namespace="frontend/detail/navigation"}{/s}</span>
        </a>
    {/if}
{/block}