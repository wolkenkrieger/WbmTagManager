{*
 * Author: Rico Wunglueck <development@itsw.dev>
 * Date: 06.12.2021
 * Time: 10:46
 * File: info.tpl
 *}

{namespace name="itsw/garage"}
{* Info entry *}
{block name="itsw_frontend_index_garage_actions_info"}
	<span class="itsw">
		<li class="navigation--entry entry--garage" role="menuitem">
            {s name="GARAGE_WIDGET_INDEX_LINK" assign="snippetIndexLinkGarage"}Meine Fahrzeuge{/s}
			<a href="{url module="frontend" controller="garage"}" title="{$snippetIndexLinkGarage|escape}" aria-label="{$snippetIndexLinkGarage|escape}" class="btn">
				<i class="icon--house"></i>
                {if $sCarQuantity > 0}
					<span class="badge garage--quantity">
                    {$sCarQuantity}
                </span>
                {/if}
			</a>
		</li>
	</span>
{/block}