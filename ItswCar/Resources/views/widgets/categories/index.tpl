{*
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   27.04.2021
 * Zeit:    15:09
 * Datei:   index.tpl
 *}
{block name="itsw_widget_categories"}
	<div class="itsw">
		{block name="itsw_widget_categories_content"}
			<div class="table flex">
                {foreach $sMainCategories as $mainCategory}
					{if $mainCategory.mediaId}
						<div class="table--cell"><a href="{$mainCategory.link}"><img src="{media path=$mainCategory.media.path}" alt="{$mainCategory.name}" class="categories--image"/> </a></div>
					{/if}
                {/foreach}
			</div>
		{/block}
	</div>
{/block}
