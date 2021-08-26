{extends file="parent:frontend/checkout/finish.tpl"}

{block name="frontend_index_header_javascript_jquery"}
    {$smarty.block.parent}
    {literal}
        <script src="https://apis.google.com/js/platform.js?onload=renderOptIn" async defer></script>
        <script>
            window.renderOptIn = function() {
                window.gapi.load('surveyoptin', function() {
                    window.gapi.surveyoptin.render({
                        "merchant_id": "{/literal}{$ITSW.google.merchantId}{literal}",
                        "order_id": "{/literal}{$sOrderNumber}{literal}",
                        "email": "{/literal}{$sUserData.additional.user.email}{literal}",
                        "delivery_country": "{/literal}{$sCountry.countryiso}{literal}",
                        "estimated_delivery_date": "{/literal}{$ITSW.basketdata.shippingdate}{literal}",
                        "opt_in_style": "{/literal}{$ITSW.google.surveyoptinstyle}{literal}"
                        {/literal}
                        {if $ITSW.basketData.gtins|@count gt 0}
                            {literal},"products": [{/literal}
                            {foreach $ITSW.basketData.gtins as $gtin}
                                {literal}{"gtin":"{/literal}{$gtin}"{literal}}{/literal}
                                {if not $gtin@last},{/if}
                            {/foreach}
                            {literal}]{/literal}
                        {/if}{literal}
                    });
                });
            }
        </script>
    {/literal}
{/block}
