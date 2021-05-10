{*
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   27.04.2021
 * Zeit:    15:09
 * Datei:   index.tpl
 *}
{block name="itsw_widget_categories"}
	<div class="itsw">
		{block name="itsw_widget_categories_headline"}
			<div class="headline h2">
				{s name="itsw_widget_categories_headline"}Autoteile Wiesel Baugruppen und Autoteilekatalog{/s}
			</div>
		{/block}
		{block name="itsw_widget_categories_content"}
			<div class="widget categories">
				<ul>
                    {foreach $sMainCategories as $mainCategory}
                        {if $mainCategory.mediaId}
							<li>
								<div class="image">
									<span>
										<a href="{$mainCategory.link}">
										<img src="{media path=$mainCategory.media.path}" alt="{$mainCategory.name}" class="categories--image"/>
									</a>
									</span>
									<span class="headline h3">
										<a href="{$mainCategory.link}">{$mainCategory.name}</a>
									</span>
								</div>
							</li>
                        {/if}
                    {/foreach}
				</ul>
			</div>
		{/block}
	</div>
{/block}
