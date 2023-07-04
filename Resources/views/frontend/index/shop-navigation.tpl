{extends file="parent:frontend/index/shop-navigation.tpl"}

{* Cart entry *}
{block name='frontend_index_checkout_actions'}
    {* Include of the cart *}
    {block name='frontend_index_checkout_actions_include'}
        {action module=widgets controller=garage action=info}
        {action module=widgets controller=checkout action=info}
    {/block}
{/block}
