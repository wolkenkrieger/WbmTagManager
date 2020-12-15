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
						name="manufacturers"
						aria-label="Testlabel"
						data-no-fancy-select="true"
						disabled
						data-itsw_getter="/widgets/carfinder/get-manufacturers"
						data-itsw_setter="/widgets/carfinder/set-manufacturer"
						data-itsw_trigger="models"
				>
					<option selected value="1">Punkt 1</option>
					<option value="2">Punkt 2</option>
				</select>
				<select id="models"
						name="models"
						aria-label="Testlabel"
						data-no-fancy-select="true"
						disabled
						data-itsw_getter="/widgets/carfinder/get-models"
						data-itsw_setter="/widgets/carfinder/set-model"
						data-itsw_manufacturer=""
						data-itsw_trigger="types"
				>
					<option selected value="1">Punkt 1</option>
					<option value="2">Punkt 2</option>
				</select>
				<select id="types"
						name="types"
						aria-label="Testlabel"
						data-no-fancy-select="true"
						disabled
						data-itsw_getter="/widgets/carfinder/get-types"
						data-itsw_setter="/wifgets/carfinder/set-type"
						data-itsw_manufacturer=""
						data-itsw_model=""
						data-itsw_trigger=""
				>
					<option selected value="1">Punkt 1</option>
					<option value="2">Punkt 2</option>
				</select>
			</div>
        {/block}
	</div>
{/block}