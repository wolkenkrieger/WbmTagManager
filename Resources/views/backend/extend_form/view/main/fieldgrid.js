/**
 * Projekt: ITSW Car
 * Autor:   Rico Wunglück <development@itsw.dev>
 * Datum:   13.07.2021
 * Zeit:    10:11
 * Datei:   fieldgrid.js
 */

//{block name="backend/form/view/main/fieldgrid"}
//{$smarty.block.parent}
Ext.define('Shopware.apps.ExtendForm.view.main.Fieldgrid', {
    override: 'Shopware.apps.Form.view.main.Fieldgrid',
    
    /**
     * Creates store object used for the class column
     *
     * @return [Ext.data.SimpleStore]
     */
    getClassComboStore: function() {
        var me = this;
        var classesStore = me.callParent(arguments);
        
        classesStore.on('load', function () {
            classesStore.add(
                {
                    id: 'forms_hsn',
                    label: 'forms_hsn'
                },
                {
                    id: 'forms_tsn',
                    label: 'forms_tsn'
                },
                {
                    id: 'forms_vin',
                    label: 'forms_vin'
                },
                {
                    id: 'forms_month',
                    label: 'forms_month'
                },
                {
                    id: 'forms_year',
                    label: 'forms_year'
                },
                {
                    id: 'forms_by',
                    label: 'forms_by'
                }
            );
        });
        
        return classesStore;
    }
});
//{/block}