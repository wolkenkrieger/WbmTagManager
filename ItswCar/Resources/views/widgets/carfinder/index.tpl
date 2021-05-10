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
				<div class="table carfinder header--navigation">
					<div class="table--row">
						<div class="table--cell">
							<div class="bold">
								Fahrzeugauswahl nach Schlüsselnummern
							</div>
							<div class="table hsn-tsn--container">
								<div class="table--row">
									<div class="table--cell">
										<input id=hsn"
											   name="hsn"
											   type="text"
											   maxlength="4"
											   minlength="4"/>
									</div>
									<div class="table--cell">
										<input id=tsn"
											   name="tsn"
											   type="text"
											   maxlength="3"
											   minlength="3"/>
									</div>
								</div>
							</div>
						</div>
						<div class="table--cell">
                            {if $showSelect}
                                {block name="itsw_carfinder_widget__select-container"}
									<div class="widget--select-container">
										<div class="bold">
											Fahrzeugauswahl nach Kriterien
										</div>
										<div class="table">
											<div class="table--row">
												<div class="table--cell">
													<label for="manufacturers">Hersteller</label>
												</div>
												<div class="table--cell">
													<select id="manufacturers"
															name="manufacturer"
															aria-label="Testlabel"
															data-no-fancy-select="true"
															disabled
															data-itsw-select="true"
															data-itsw-trigger="models"
													>
													</select>
												</div>
											</div>
											<div class="table--row">
												<div class="table--cell">
													<label for="models">Modell</label>
												</div>
												<div class="table--cell">
													<select id="models"
															name="model"
															aria-label="Testlabel"
															data-no-fancy-select="true"
															disabled
															data-itsw-select="true"
															data-itsw-trigger="types"
													>
													</select>
												</div>
											</div>
											<div class="table--row">
												<div class="table--cell">
													<label for="types">Typ</label>
												</div>
												<div class="table--cell">
													<select id="types"
															name="type"
															aria-label="Testlabel"
															data-no-fancy-select="true"
															data-itsw-select="true"
															disabled
													>
													</select>
												</div>
											</div>
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
						<div class="table--cell">
							rechts
						</div>
					</div>
				</div>
            {/block}
		</div>
	{/block}
{/block}