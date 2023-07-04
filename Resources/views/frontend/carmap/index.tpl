{*
 * Author: Rico Wunglueck <development@itsw.dev>
 * Date: 14.02.2022
 * Time: 09:08
 * File: index.tpl
 *}

{extends file='parent:frontend/index/index.tpl'}

{namespace name="itsw/carmap"}

* Breadcrumb *}
{block name='frontend_index_start'}
    {s name="CARMAP_FRONTEND_INDEX_LINK" assign="snippetCarmapTitle"}Fahrzeugliste und -schnellwahl{/s}
    {$sBreadcrumb = [['name' => $snippetCarmapTitle, 'link' => {url}]]}
    {$smarty.block.parent}
{/block}

{* Account Sidebar *}
{block name="frontend_index_left_categories"}{/block}

{* Main content *}
{block name="frontend_index_content"}
	<div class="content">
        {* Infotext *}
        {block name="itsw_frontend_carmap_index_welcome"}
			<div class="account--welcome panel">
                {block name="itsw_frontend_carmap_index_welcome_headline"}
					<h1 class="panel--title">{s name="CARMAP_FRONTEND_INDEX_HEADLINE"}Autoteile Wiesel - Fahrzeuge in der Datenbank{/s}</h1>
                {/block}

                {block name="itsw_frontend_carmap_index_welcome_content"}
					<div class="panel--body is--wide">
						<p>{s name="CARMAP_FRONTEND_TEXT_1"}{/s}</p>
						<p>{s name="CARMAP_FRONTEND_TEXT_2"}{/s}</p>
					</div>
                {/block}
			</div>
        {/block}

		{block name="itsw_frontend_carmap_index_topbrands"}
			<nav class="itsw">
				<div class="carmap manufacturer panel is--rounded is--border">
					<span class="panel--title is--underline" role="heading">{s namespace="itsw/carfinder" name="ManufacturerSelectTopBrands"}{/s}</span>
					<div class="panel--body is--wide" role="menu">
                        {foreach $topBrandsViewData as $manufacturerDisplay => $data}
							<a href="{url controller=carmap action=models manufacturer=$data.manufacturerId}" title="{$manufacturerDisplay}" itemprop="item" role="menuitem">
								<link itemprop="url" href="{url controller=carmap action=models manufacturer=$data.manufacturerId}">
								<span itemprop="name"><span class="link--name">{$manufacturerDisplay}</span> {foreach $data.models as $modelDisplay}{$modelDisplay}, {/foreach}...</span>
							</a>
                        {/foreach}
					</div>
				</div>
			</nav>
        {/block}


		{block name="itsw_frontend_carmap_index_manufacturers"}
			<p></p>
			<nav class="itsw">
				<div class="carmap manufacturer panel is--rounded is--border">
					{foreach $viewData as $firstChar => $manufacturers}
						<span class="panel--title is--underline" role="heading">{$firstChar}</span>
						<div class="panel--body is--wide" role="menu">
                            {foreach $manufacturers as $manufacturerDisplay => $data}
								<a href="{url controller=carmap action=models manufacturer=$data.manufacturerId}" title="{$manufacturerDisplay}" itemprop="item" role="menuitem">
									<link itemprop="url" href="{url controller=carmap action=models manufacturer=$data.manufacturerId}">
									<span itemprop="name"><span class="link--name">{$manufacturerDisplay}</span> {foreach $data.models as $modelDisplay}{$modelDisplay}, {/foreach}...</span>
								</a>
                            {/foreach}
						</div>
					{/foreach}
				</div>
			</nav>
        {/block}

	</div>
{/block}
