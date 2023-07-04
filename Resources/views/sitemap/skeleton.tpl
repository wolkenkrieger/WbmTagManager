<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

<url>
	<loc>{$baseUrl}/{$manufacturerPath}/{$models[0].manufacturerUrl}</loc>
{if lastmod}
	<lastmod>{$lastmod}</lastmod>
{/if}
	<changefreq>monthly</changefreq>
	<priority>{$priority}</priority>
</url>
{foreach $models as $model}
{include file="entry.tpl" manufacturerUrl = $model.manufacturerUrl modelUrl = $model.modelUrl lastmod = $lastmod priority = $priority baseurl = $baseUrl manufacturerPath = $manufacturerPath modelPath = $modelPath}
{/foreach}
</urlset>