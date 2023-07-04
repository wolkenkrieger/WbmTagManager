{extends file="parent:frontend/detail/tabs.tpl"}

{block name="frontend_detail_tabs_navigation_inner"}
    {* ItswCar Fits for cars tab *}
    {block name="itsw_frontend_detail_tabs_fitsforcars"}
        <a href="#" class="tab--link" title="{s name='DetailTabsFitsForCars' namespace="itsw/details"}Kompatibel mit folgenden PKW{/s}" data-tabName="fitsforcars">{s name='DetailTabsFitsForCars' namespace="itsw/details"}Artikel passend für{/s}</a>
    {/block}

    {* Rating tab *}
    {block name="frontend_detail_tabs_rating"}
        {if !{config name=VoteDisable}}
            <a href="#" class="tab--link" title="{s name='DetailTabsRating'}{/s}" data-tabName="rating">
                {s name='DetailTabsRating'}{/s}
                {block name="frontend_detail_tabs_navigation_rating_count"}
                    <span class="product--rating-count">{$sArticle.sVoteAverage.count}</span>
                {/block}
            </a>
        {/if}
    {/block}
{/block}

{block name="frontend_detail_tabs_content_inner"}

    {* ItswCar Fits for cars container *}
    {block name="itsw_frontend_detail_tabs_content_fitsforcars"}
        <div class="tab--container">
            {block name="itsw_frontend_detail_tabs_content_fitsforcars_inner"}
                {* Title *}
                {block name="itsw_frontend_detail_tabs_content_firtsforcars_title"}
                    <div class="tab--header">
                        {block name="itsw_frontend_detail_tabs_content_fitsforcars_title_inner"}
                            <a href="#" class="tab--title" title="{s name='DetailTabsFitsForCars' namespace="itsw/details"}Kompatibel mit folgenden PKW{/s}">{s name='DetailTabsFitsForCars' namespace="itsw/details"}Artikel passend für{/s}</a>
                        {/block}
                    </div>
                {/block}
                {* Preview *}
                {block name="itsw_frontend_detail_tabs_fitsforcars_preview"}
                    <div class="tab--preview">
                        {block name="itsw_frontend_detail_tabs_content_fitsforcars_preview_inner"}{/block}
                    </div>
                {/block}
                {* Content *}
                {block name="itsw_frontend_detail_tabs_content_fitsforcars_description"}
                    <div class="tab--content">
                        {block name="itsw_frontend_detail_tabs_content_fitsforcars_description_inner"}
                            {include file="frontend/detail/tabs/fitsforcars.tpl"}
                        {/block}
                    </div>
                {/block}
            {/block}
        </div>
    {/block}

    {* Rating container *}
    {block name="frontend_detail_tabs_content_rating"}
        {if !{config name=VoteDisable}}
            <div class="tab--container">
                {block name="frontend_detail_tabs_content_rating_inner"}

                    {* Rating title *}
                    {block name="frontend_detail_tabs_rating_title"}
                        <div class="tab--header">
                            {block name="frontend_detail_tabs_rating_title_inner"}
                                <a href="#" class="tab--title" title="{s name='DetailTabsRating'}{/s}">{s name='DetailTabsRating'}{/s}</a>
                                {block name="frontend_detail_tabs_rating_title_count"}
                                    <span class="product--rating-count">{$sArticle.sVoteAverage.count}</span>
                                {/block}
                            {/block}
                        </div>
                    {/block}

                    {* Rating preview *}
                    {block name="frontend_detail_tabs_rating_preview"}
                        <div class="tab--preview">
                            {block name="frontend_detail_tabs_rating_preview_inner"}
                                {s name="RatingPreviewText"}{/s}<a href="#" class="tab--link" title="{s name="PreviewTextMore"}{/s}">{s name="PreviewTextMore"}{/s}</a>
                            {/block}
                        </div>
                    {/block}

                    {* Rating content *}
                    {block name="frontend_detail_tabs_rating_content"}
                        <div id="tab--product-comment" class="tab--content">
                            {block name="frontend_detail_tabs_rating_content_inner"}
                                {include file="frontend/detail/tabs/comment.tpl"}
                            {/block}
                        </div>
                    {/block}

                {/block}
            </div>
        {/if}
    {/block}
{/block}