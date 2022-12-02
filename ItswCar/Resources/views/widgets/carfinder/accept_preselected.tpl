{*
 * Author: Rico Wunglueck <development@itsw.dev>
 * Date: 28.11.2022
 * Time: 11:23
 * File: accept-preselected.tpl
 *}

{namespace name="itsw/carfinder"}

{block name="itsw_carfinder_widget_accept-preselcted"}
	{literal}
		<script TYPE="text/javascript">
            document.asyncReady(function () {
                console.log('ping');
                let currState = StateManager.getCurrentState();
                let content = 'das ist ein test';
                let width = 900;

                switch (currState) {
                    case 'xs':
                    case 's' :
                    case 'm' : width = 'calc(100% - 15px)'; break;
                    default: width = 900;
                }

                $.modal.open(content, {
                    title: 'Fahrzugauswahl',
                    sizing: 'content',
                    width: width
                })
            });
		</script>
	{/literal}
{/block}
