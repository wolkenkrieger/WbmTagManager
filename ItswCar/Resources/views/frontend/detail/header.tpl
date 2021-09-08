{*
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   08.09.2021
 * Zeit:    11:10
 * Datei:   header.tpl
 *}

{extends file='frontend/index/header.tpl'}

{* Meta title *}
{block name="frontend_index_header_title"}{if $sArticle.seoTitle}{$sArticle.seoTitle}{else}{if $sArticle.metaTitle}{$sArticle.metaTitle|escapeHtml} | {{config name="sShopname"}|escapeHtml}{else}{$sArticle.articleName|escape} | {$smarty.block.parent}{/if}{/if}{/block}

{* Meta opengraph tags *}
{block name='frontend_index_header_meta_tags_opengraph'}
	<meta property="og:type" content="product" />
	<meta property="og:site_name" content="{{config name="sShopname"}|escapeHtml}" />
	<meta property="og:url" content="{url sArticle=$sArticle.articleID title=$sArticle.articleName controller=detail}" />
	<meta property="og:title" content="{if $sArticle.seoTitle}{$sArticle.seoTitle}{else}{$sArticle.articleName|escapeHtml}{/if}" />
	<meta property="og:description" content="{if $sArticle.seoDescription}{$sArticle.seoDescription}{else}{$sArticle.description_long|strip_tags|trim|truncate:$SeoDescriptionMaxLength:'…'|escapeHtml}{/if}" />
	<meta property="og:image" content="{$sArticle.image.source}" />

	<meta property="product:brand" content="{$sArticle.supplierName|escapeHtml}" />
	<meta property="product:price" content="{$sArticle.price}" />
	<meta property="product:product_link" content="{url sArticle=$sArticle.articleID title=$sArticle.articleName controller=detail}" />

	<meta name="twitter:card" content="product" />
	<meta name="twitter:site" content="{{config name="sShopname"}|escapeHtml}" />
	<meta name="twitter:title" content="{if $sArticle.seoTitle}{$sArticle.seoTitle}{else}{$sArticle.articleName|escapeHtml}{/if}" />
	<meta name="twitter:description" content="{if $sArticle.seoDescription}{$sArticle.seoDescription}{else}{$sArticle.description_long|strip_tags|trim|truncate:$SeoDescriptionMaxLength:'…'|escapeHtml}{/if}" />
	<meta name="twitter:image" content="{$sArticle.image.source}" />
{/block}

{* Keywords *}
{block name="frontend_index_header_meta_keywords"}{if $sArticle.keywords}{$sArticle.keywords|escapeHtml}{elseif $sArticle.sDescriptionKeywords}{$sArticle.sDescriptionKeywords|escapeHtml}{/if}{/block}

{* Description *}
{block name="frontend_index_header_meta_description"}{if $sArticle.seoDescription}{$sArticle.seoDescription}{else}{if $sArticle.description}{$sArticle.description|escapeHtml}{else}{$sArticle.description_long|strip_tags|trim|escapeHtml}{/if}{/if}{/block}

{* Canonical link *}
{block name='frontend_index_header_canonical'}
	<link rel="canonical" href="{url sArticle=$sArticle.articleID title=$sArticle.articleName controller=detail}" />
{/block}
