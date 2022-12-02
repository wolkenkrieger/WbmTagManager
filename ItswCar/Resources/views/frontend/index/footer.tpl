{*
 * Author: Rico Wunglueck <development@itsw.dev>
 * Date: 01.12.2022
 * Time: 09:21
 * File: footer.tpl
 *}
{extends file="parent:frontend/index/footer.tpl"}

{block name="frontend_index_shopware_footer_logo"}
	{$smarty.block.parent}
	{if $ItswCar.google.cookieallowed && $ItswCar.google.showbadge}
		{literal}
			<script src="https://apis.google.com/js/platform.js?onload=renderBadge" async defer></script>
			<script>
                window.renderBadge = function() {
                    var ratingBadgeContainer = document.createElement("div");
                    document.body.appendChild(ratingBadgeContainer);
                    window.gapi.load('ratingbadge', function() {
                        window.gapi.ratingbadge.render(ratingBadgeContainer, {
                            "merchant_id": {/literal}{$ItswCar.google.merchantId}{literal},
							"position": "{/literal}{$ItswCar.google.badgeposition}{literal}"
                        });
                    });
                }
			</script>
		{/literal}
	{/if}
{/block}