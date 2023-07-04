{extends file="parent:frontend/checkout/items/product.tpl"}
{namespace name="frontend/checkout/cart_item"}

{* Product information *}
{block name='frontend_checkout_cart_item_details'}
    <div class="panel--td table--content">
        {* Product name *}
        {block name='frontend_checkout_cart_item_details_title'}
            <a class="content--title" href="{$detailLink}" title="{$sBasketItem.articlename|strip_tags|escape}"
            {if {config name="detailmodal"} && {controllerAction|lower} === 'confirm'}
               data-modalbox="true"
               data-content="{url controller="detail" action="productQuickView" ordernumber="{$sBasketItem.ordernumber}" fullPath}"
               data-mode="ajax"
               data-width="750"
               data-sizing="content"
               data-title="{$sBasketItem.articlename|strip_tags|escape}"
               data-updateImages="true"
            {/if}>
               {$sBasketItem.articlename|strip_tags|truncate:60}
            </a>
        {/block}

        {* Product SKU number *}
        {block name='frontend_checkout_cart_item_details_sku'}
            <p class="content--sku content">
                {s name="CartItemInfoId"}{/s} {$sBasketItem.ordernumber}
            </p>
        {/block}

        {* Selected Car *}
        {block name='fornted_checkout_cart_item_details_selected_car'}
            {if $sBasketItem.__s_order_basket_attributes_car_display}
            <p class="content--sku content">
                {s name="CART_ITEM_SELECTED_CAR" namespace="itsw/checkout"}Gewähltes Fahrzeug:{/s} {$sBasketItem.__s_order_basket_attributes_car_display}
            </p>
            {/if}
        {/block}

        {* Product delivery information *}
        {block name='frontend_checkout_cart_item_delivery_informations'}
            {if {config name="BasketShippingInfo"} && $sBasketItem.shippinginfo}
                {include file="frontend/plugins/index/delivery_informations.tpl" sArticle=$sBasketItem}
            {/if}
        {/block}

        {* Additional product information *}
        {block name='frontend_checkout_cart_item_details_inline'}
            {block name='frontend_checkout_cart_item_details_essential_features'}
                {if {config name="alwaysShowMainFeatures"}}
                <div class="product--essential-features">
                    {include file="string:{config name="mainfeatures"}"}
                                    </div>
                                {/if}
            {/block}
        {/block}
    </div>
{/block}
