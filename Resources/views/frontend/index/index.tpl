{extends file="parent:frontend/index/index.tpl"}

{block name="frontend_index_javascript_async_ready"}
    {if {config name='wbmTagManagerCookieConsent'}}
        <script>
            document.asyncReady(function () {
                if ($.getCookiePreference('wbm_tag_manager')) {
                    googleTag('consent', 'default', {
                        'ad_storage': 'denied',
                        'ad_user_data': 'denied',
                        'ad_personalization': 'denied',
                        'analytics_storage': 'denied'
                    });
                    googleTag(window,document,'script','dataLayer','{"{config name='wbmTagManagerContainer'}"|escape:'javascript'}');
                    googleTag = function () { };
                }
                $.subscribe('plugin/swCookieConsentManager/onBuildCookiePreferences', function (event, plugin, preferences) {
                    if ($.getCookiePreference('wbm_tag_manager')) {
                        googleTag('consent', 'default', {
                            'ad_storage': 'denied',
                            'ad_user_data': 'denied',
                            'ad_personalization': 'denied',
                            'analytics_storage': 'denied'
                        });
                        googleTag(window,document,'script','dataLayer','{"{config name='wbmTagManagerContainer'}"|escape:'javascript'}');
                        googleTag = function () { };
                    }
                });
            });
        </script>
    {/if}
    {$smarty.block.parent}
{/block}
