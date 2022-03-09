{*
 * Author: Rico Wunglueck <development@itsw.dev>
 * Date: 14.02.2022
 * Time: 12:32
 * File: types.tpl
 *}
{extends file='parent:frontend/index/index.tpl'}

{namespace name="itsw/carmap"}

* Breadcrumb *}
{block name='frontend_index_start'}
    {s name="CARMAP_FRONTEND_TYPES_LINK" assign="snippetCarmapTitle"}Fahrzeugliste und -schnellwahl{/s}
    {$sBreadcrumb = [['name' => $snippetCarmapTitle, 'link' => {url}]]}
    {$smarty.block.parent}
{/block}

{* Account Sidebar *}
{block name="frontend_index_left_categories"}{/block}

{* Main content *}
{block name="frontend_index_content"}
	<div class="content">
        {* Infotext *}
        {block name="itsw_frontend_carmap_types_welcome"}
			<div class="account--welcome panel">
                {block name="itsw_frontend_carmap_types_welcome_headline"}
                    {assign var=firstCar value=$cars|@reset}
					<h1 class="panel--title">{$firstCar.manufacturer.display} {$firstCar.model.display} {s name="CARMAP_FRONTEND_TYPES_HEADLINE"}- Typen und Motorisierungen{/s}</h1>
                {/block}

                {block name="itsw_frontend_carmap_types_welcome_content"}
					<div class="panel--body is--wide">
						<p>{s name="CARMAP_FRONTEND_TYPES_TEXT_1"}{/s}</p>
						<p>{s name="CARMAP_FRONTEND_TYPES_TEXT_2"}{/s}</p>
					</div>
                {/block}
			</div>
        {/block}

        {block name="itsw_carmap_types"}
			<div class="itsw" role="menu">
				<div class="table flex carmap">
                    {foreach $cars as $car}
						<div class="table--cell panel--td is--rounded is--border padding-none" role="menuitem">
							<div class="table">
								<div class="table--cell">
									<a class="is--text-color" href="{$car.seoUrl}">
										<div>{s name="CARFINDER_WIDGET_CAR" namespace="itsw/carfinder"}{/s} <span class="bold">{$car.manufacturer.display}&nbsp;{$car.model.display}&nbsp;{$car.type.display}</span></div>
										<div>{s name="CARFINDER_WIDGET_MOTOR" namespace="itsw/carfinder"}{/s} <span class="bold">{$car.ccm}&nbsp;cm&sup3;, {$car.ps}&nbsp;PS, {$car.kw}&nbsp;kW</span></div>
										<div>{s name="CARFINDER_WIDGET_BUILDFROMTO" namespace="itsw/carfinder"}{/s} <span class="bold">{$car.buildFrom} - {$car.buildTo}</span></div>
										<div>{s name="CARFINDER_WIDGET_PLATFORM" namespace="itsw/carfinder"}{/s} <span class="bold">{$car.platform.display}</span></div>
										<div>{s name="CARFINDER_WIDGET_HSNTSNCODES" namespace="itsw/carfinder"}{/s} <span class="bold">{foreach name=codes item=code from=$car.codes}{$code.hsn}/{$code.tsn}{if !$smarty.foreach.codes.last} {/if}{/foreach}</span></div>
									</a>
								</div>
								<div class="table--cell is--right">
									<a class="btn is--small is--primary" href="{$car.seoUrl}" title="{$car.manufacturer.display} {$car.model.display} {$car.type.display}" itemprop="item">
										<i class="icon--arrow-right"></i>
										<link href="{$car.seoUrl}" itemprop="url">
										<span class="is--hidden" itemprop="name">{$car.manufacturer.display}&nbsp;{$car.model.display}&nbsp;{$car.type.display}</span>
									</a>
								</div>
							</div>
						</div>
                    {/foreach}
				</div>
			</div>

        {/block}
	</div>
{/block}