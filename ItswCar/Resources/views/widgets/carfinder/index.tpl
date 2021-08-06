{*
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   15.12.2020
 * Zeit:    21:37
 * Datei:   index.tpl
 *}
{namespace name="itsw/carfinder"}

{block name="itsw_carfinder_widget"}
	{block name="itsw_carfinder_widget__container"}
		<div class="itsw">
            {block name="itsw_carfinder_widget__container-inner"}
				<div class="table carfinder">
                    {if $showSelect}
						<div class="table--cell hsn-tsn--container">
							{block name="itsw_carfinder_widget__hsn_tsn-container"}
								<div class="table">
									<div class="table--caption bold width--100">
										{s name="CARFINDER_WIDGET_HEAD_1"}Fahrzeugauswahl nach Schlüsselnummern{/s}
									</div>
									<div class="table--cell width--50">
										<label for="hsn">{s name="CARFINDER_WIDGET_HSN"}HSN{/s}</label>
										<input id="hsn"
											   name="hsn"
											   type="text"
											   maxlength="4"
											   minlength="4" />
									</div>
									<div class="table--cell width--50">
										<label for="tsn">{s name="CARFINDER_WIDGET_TSN"}TSN{/s}</label>
										<input id="tsn"
											   name="tsn"
											   type="text"
											   maxlength="3"
											   minlength="3" />
									</div>
									<div class="table--cell width--100">
										<button class="btn is--primary is--full is--icon-right pull-right" data-itsw-basepath="{$basePath}"><i class="icon--search"></i>{s name="CARFINDER_WIDGET_CHOOSE_CAR"}Fahrzeug auswählen{/s}</button>
									</div>
								</div>
							{/block}
						</div>
						<div class="table--cell select--container">
							{block name="itsw_carfinder_widget__select-container"}
								<div class="table">
									<div class="table--caption bold width--100">
										{s name="CARFINDER_WIDGET_HEAD_2"}Fahrzeugauswahl nach Kriterien{/s}
									</div>
									<div class="table--cell width--100">
										<label for="manufacturers">{s name="CARFINDER_WIDGET_MANUFACTURER"}Hersteller{/s}</label>
										<select id="manufacturers"
												name="manufacturer"
												data-no-fancy-select="true"
												disabled
												data-itsw-select="true"
												data-itsw-trigger="models"
												data-itsw-basepath="{$basePath}"
										>
										</select>
									</div>
									<div class="table--cell width--100">
										<label for="models">{s name="CARFINDER_WIDGET_MODEL"}Modell{/s}</label>
										<select id="models"
												name="model"
												data-no-fancy-select="true"
												disabled
												data-itsw-select="true"
												data-itsw-trigger="types"
												data-itsw-basepath="{$basePath}"
										>
										</select>
									</div>
									<div class="table--cell width--100">
										<label for="types">{s name="CARFINDER_WIDGET_TYPE"}Typ/Motor{/s}</label>
										<select id="types"
												name="type"
												data-no-fancy-select="true"
												data-itsw-select="true"
												data-itsw-basepath="{$basePath}"
												disabled
										>
										</select>
									</div>
								</div>
							{/block}
						</div>
                    {else}
						<div class="table--cell selected_car--container">
							<div class="table">
								<div class="table--caption bold width--100">
                                    {s name="CARFINDER_WIDGET_HEAD_4"}Ihr gewähltes Fahrzeug{/s}
								</div>
								<div class="table--cell width--100">
                                    {if $car}
										<div>
											<label for="itsw-car">{s name="CARFINDER_WIDGET_CAR"}Modell und Typ:{/s}</label>
											<span id="itsw-car" class="bold">{$car.manufacturer.display} {$car.model.display} {$car.type.display}</span>
										</div>
										<div>
											<label for="itsw-motor">{s name="CARFINDER_WIDGET_MOTOR"}Motorisierung:{/s}</label>
											<span id="itsw-motor" class="bold">{$car.ccm}&nbsp;cm&sup3;; {$car.ps}&nbsp;PS / {$car.kw}&nbsp;kW</span>
										</div>
										<div>
											<label for="itsw-buildfromto">{s name="CARFINDER_WIDGET_BUILDFROMTO"}Baujahr:{/s}</label>
											<span id="itsw-buildfromto" class="bold">{$car.buildFrom} - {$car.buildTo}</span>
										</div>
										<div>
											<label for="itsw-platform">{s name="CARFINDER_WIDGET_PLATFORM"}Platform:{/s}</label>
											<span id="itsw-platform" class="bold">{$car.platform.display}</span>
										</div>
										<div>
											<label for="itsw-hsntsncodes">{s name="CARFINDER_WIDGET_HSNTSNCODES"}HSN/TSN:{/s}</label>
											<span id="itsw-hsntsncodes" class="bold">{foreach name=codes item=code from=$car.codes}{$code.hsn}/{$code.tsn}{if !$smarty.foreach.codes.last} {/if}{/foreach}</span>
										</div>
                                    {/if}
								</div>
								<div class="table--cell width--100">
									<a class="btn is--primary is--icon-right" href="{url controller="carfinder" module="widgets" action="unset-car"}">Auswahl ändern<i class="icon--cross"></i></a>
								</div>
							</div>
						</div>
					{/if}
					<div class="table--cell info--container">
						{block name="itsw_carfinder_widget__info-container"}
							<div class="table">
								<div class="table--cell width--100">
									<span class="h2">{s name="CARFINDER_WIDGET_HEAD_3"}Bestell- und Servicehotline{/s}</span>
									<span class="h3 bold">{s name="CARFINDER_WIDGET_INFO_1"}+49 - (0)3586 70 70 380{/s}</span>
									<span class="h4">{s name="CARFINDER_WIDGET_INFO_2"}Mo.-Do. 09-17 Uhr / Fr. 09-15 Uhr{/s}</span>
								</div>
							</div>
						{/block}
					</div>
				</div>
            {/block}
		</div>
	{/block}
{/block}

{*block name="itsw_carfinder_widget__selected_car-container"}
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
{/block*}