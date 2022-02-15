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
				<div class="table flex carmap">
                    {foreach $topBrandsViewData as $manufacturerDisplay => $modelDisplays}
						<div class="table--cell panel--td is--rounded is--border" role="navigation">
							<span class="headline">{$manufacturerDisplay}</span>
                            {foreach $modelDisplays as $modelDisplay => $data}
								<a style="display: block;" href="{url controller=carmap action=types manufacturer=$data.manufacturerId model=$data.modelId}">{$modelDisplay}</a>
                            {/foreach}
						</div>
                    {/foreach}
				</div>
			</nav>
        {/block}


		{block name="itsw_frontend_carmap_index_manufacturers"}
			<nav class="itsw">
				<div class="table flex carmap">
                    {foreach $viewData as $manufacturerDisplay => $modelDisplays}
						<div class="table--cell panel--td is--rounded is--border" role="navigation">
							<span class="headline">{$manufacturerDisplay}</span>
                            {foreach $modelDisplays as $modelDisplay => $data}
								<a style="display: block;" href="{url controller=carmap action=types manufacturer=$data.manufacturerId model=$data.modelId}">{$modelDisplay}</a>
                            {/foreach}
						</div>
                    {/foreach}
				</div>
			</nav>
        {/block}

	</div>
{/block}
