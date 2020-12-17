{*
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   15.12.2020
 * Zeit:    21:37
 * Datei:   index.tpl
 *}
{block name="itsw_carfinder_widget"}
	{block name="itsw_carfinder_widget__conatiner"}
		<div class="itsw">
            {block name="itsw_carfinder_widget__container-inner"}
				{if $showSelect}
					{block name="itsw_carfinder_widget__select-container"}
						<div class="widget--select-container">
							<select id="manufacturers"
									name="manufacturer"
									aria-label="Testlabel"
									data-no-fancy-select="true"
									disabled
									data-itsw-select="true"
									data-itsw-trigger="models"
							>
							</select>
							<select id="models"
									name="model"
									aria-label="Testlabel"
									data-no-fancy-select="true"
									disabled
									data-itsw-select="true"
									data-itsw-trigger="types"
							>
							</select>
							<select id="types"
									name="type"
									aria-label="Testlabel"
									data-no-fancy-select="true"
									data-itsw-select="true"
									disabled
							>
							</select>
						</div>
					{/block}
				{else}
                    {block name="itsw_carfinder_widget__selected_car-container"}
						<div class="widget--selected-car-container">
							<div class="table">
								<div class="table--head">
									<div class="table--cell"><a class="btn is--primary is--icon-right" href="{url controller="carfinder" module="widgets" action="unset-car"}">Auswahl ändern<i class="icon--cross"></i></a></div>
								</div>
							</div>
						</div>
                    {/block}
				{/if}
            {/block}
		</div>
	{/block}
	{debug}
{/block}