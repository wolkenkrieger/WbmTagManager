{*
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   15.12.2020
 * Zeit:    11:17
 * Datei:   index.tpl
 *}
{extends file="parent:frontend/index/index.tpl"}

{* Shop navigation *}
{block name='frontend_index_shop_navigation'}
	{$smarty.block.parent}

	{* ITSW CarFinder Widget *}
    {block name="frontend_index_itsw_carfinder_widget"}
        {if {controllerAction|lower} !== 'service' && {controllerName|lower} !== 'checkout' && {controllerName|lower} !== 'register'}
            {action module=widgets controller=carfinder}
        {/if}
    {/block}
{/block}

{block name="frontend_index_javascript_async_ready"}
    {if $ItswCar.google.cookieallowed && $ItswCar.google.showbadge}
        <script src="https://apis.google.com/js/platform.js?onload=renderBadge" async defer></script>
        {literal}
            <script>
                document.asyncReady(function () {
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
                });
            </script>
        {/literal}
    {/if}
    {$smarty.block.parent}
{/block}