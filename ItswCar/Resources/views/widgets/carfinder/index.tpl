{*
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   15.12.2020
 * Zeit:    21:37
 * Datei:   index.tpl
 *}
{block name="itsw_carfinder_widget"}
	<div class="itsw">
        {block name="itsw_carfinder_widget__container"}
			<div class="carfinder-widget--container">
				<select id="manufacturers"
						name="manufacturer"
						aria-label="Testlabel"
						data-no-fancy-select="true"
						disabled
						data-itsw-trigger="models"
				>
				</select>
				<select id="models"
						name="model"
						aria-label="Testlabel"
						data-no-fancy-select="true"
						disabled
						data-itsw-trigger="types"
				>
				</select>
				<select id="types"
						name="type"
						aria-label="Testlabel"
						data-no-fancy-select="true"
						disabled
				>
				</select>
			</div>
        {/block}
	</div>
{/block}