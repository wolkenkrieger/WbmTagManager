{*
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   15.12.2020
 * Zeit:    21:37
 * Datei:   index.tpl
 *}
{block name="itsw_carfinder_widget"}
	{block name="itsw_carfinder_widget__container"}
		<div class="itsw">
            {block name="itsw_carfinder_widget__container-inner"}
				<div class="table carfinder header--navigation">
					<div class="table--cell hsn-tsn--container">
						<div class="table">
							<div class="table--caption bold width--100">
								Fahrzeugauswahl nach Schlüsselnummern
							</div>
							<div class="table--cell width--50">
								<label for="hsn">HSN</label>
								<input id="hsn"
									   name="hsn"
									   type="text"
									   maxlength="4"
									   minlength="4"/>
							</div>
							<div class="table--cell width--50">
								<label for="tsn">TSN</label>
								<input id="tsn"
									   name="tsn"
									   type="text"
									   maxlength="3"
									   minlength="3"/>
							</div>
							<div class="table--cell width--100">
								<button class="btn is--primary is--full is--icon-right pull-right"><i class="icon--search"></i>Fahrzeug auswählen</button>
							</div>
						</div>
					</div>
					<div class="table--cell select--container">
                        {if $showSelect}
                            {block name="itsw_carfinder_widget__select-container"}
								<div class="table">
									<div class="table--caption bold width--100">
										Fahrzeugauswahl nach Kriterien
									</div>
									<div class="table--cell width--100">
										<label for="manufacturers">Hersteller</label>
										<select id="manufacturers"
												name="manufacturer"
												data-no-fancy-select="true"
												disabled
												data-itsw-select="true"
												data-itsw-trigger="models"
										>
										</select>
									</div>
									<div class="table--cell width--100">
										<label for="models">Modell</label>
										<select id="models"
												name="model"
												data-no-fancy-select="true"
												disabled
												data-itsw-select="true"
												data-itsw-trigger="types"
										>
										</select>
									</div>
									<div class="table--cell width--100">
										<label for="types">Typ</label>
										<select id="types"
												name="type"
												data-no-fancy-select="true"
												data-itsw-select="true"
												disabled
										>
										</select>
									</div>
								</div>
                            {/block}
                        {else}
                            {block name="itsw_carfinder_widget__selected_car-container"}
								<div class="widget--selected-car-container">
									<div class="table">
										<div class="table--row">
											<div class="table--header">
												Ihr gewähltes Fahrzeug
											</div>
										</div>
										<div class="table--row">
											<div class="table-cell">
                                                {if $car}
                                                    {$car.manufacturer.display} {$car.model.display} {$car.type.display}
                                                {/if}
											</div>
										</div>
									</div>
									<div class="table--cell">
										<a class="btn is--primary is--icon-right" href="{url controller="carfinder" module="widgets" action="unset-car"}">Auswahl ändern<i class="icon--cross"></i></a>
									</div>
								</div>
                            {/block}
                        {/if}
					</div>
					<div class="table--cell info--container">rechts</div>
				</div>
            {/block}
		</div>
	{/block}
{/block}

{*













<div class="table--cell  hsn-tsn--container">
	<div class="table">

		<div class="table--row">

		</div>
		<div class="table">
			<div class="table--row">

			</div>
		</div>
	</div>
</div>
<div class="table--cell select--container">

</div>
<div class="table--cell">
	rechts
</div>

*}