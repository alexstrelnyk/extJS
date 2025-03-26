Ext.onReady(function () {
    Ext.QuickTips.init();

    var primary_key_col = 'LOCATIONID';
    var is_update = 1;

    // Fetch enumeration data first
    Ext.Ajax.request({
        url: './tools/wizardPower/src/index.php',
        params: { action: 'get_enumerations' },
        success: function (enumResponse) {
            var enumData = Ext.decode(enumResponse.responseText).data;

            // Convert enumeration data into a lookup map
            var enumMap = {};  // { FIELDNAME: { key: value, ... } }
            var enumStores = {}; // { FIELDNAME: Ext.data.SimpleStore }

            enumData.forEach(item => {
                if (!enumMap[item.FIELDNAME]) {
                    enumMap[item.FIELDNAME] = {};
                    enumStores[item.FIELDNAME] = new Ext.data.SimpleStore({
                        fields: ['key', 'value'],
                        data: []
                    });
                }
                enumMap[item.FIELDNAME][item.SEQUENCE] = item.VALUE;
                enumStores[item.FIELDNAME].add(new Ext.data.Record({ key: item.SEQUENCE, value: item.VALUE }));
            });

            // Fetch dynamic columns
            Ext.Ajax.request({
                url: './tools/wizardPower/src/index.php',
                params: { action: 'get_columns' },
                success: function (response) {
                    var res = Ext.decode(response.responseText);

                    if (res.success) {
                        var dynamicFields = res.data.map(col => col.COLUMN_NAME);
                        var grid_store = new Ext.data.JsonStore({
                            root: 'data',
                            baseParams: { action: 'get_data' },
                            url: './tools/wizardPower/src/index.php',
                            fields: dynamicFields,
                            listeners: {
                                load: function (store, records) {
                                    Ext.getCmp('main_grid').getView().refresh();

                                    // If no records, add an empty row and show all columns
                                    var colModel = Ext.getCmp('main_grid').getColumnModel();
                                    if (Ext.getCmp('search_field').getValue() == '') {
                                        if (records.length === 0) {
                                            store.add(new Ext.data.Record({}));
                                            colModel.setHidden(colModel.findColumnIndex(primary_key_col), false);
                                            is_update = 0;
                                        } else {
                                            colModel.setHidden(colModel.findColumnIndex(primary_key_col), true);
                                            is_update = 1;
                                        }
                                    }
                                }
                            }
                        });

                        // Generate dynamic columns
                        var dynamicColumns = res.data.map(col => ({
                            header: col.COLUMN_NAME.replace(/_/g, ' '), // Format headers
                            dataIndex: col.COLUMN_NAME,
                            width: 120,
                            editor: col.COLUMN_NAME === "NAME" ? null :  // Make "NAME" column non-editable
                                col.DATA_TYPE === "DATE" ? null :
                                    (enumMap[col.COLUMN_NAME] ? { // If field has enumeration, use combo box
                                        xtype: 'combo',
                                        store: enumStores[col.COLUMN_NAME],
                                        displayField: 'value',
                                        valueField: 'key',
                                        mode: 'local',
                                        triggerAction: 'all',
                                        editable: false
                                    } : { xtype: 'textfield' }), // Default to text field
                            hidden: false, // Default: Show all columns
                            renderer: enumMap[col.COLUMN_NAME] ?
                                function (value) { return enumMap[col.COLUMN_NAME][value] || value; } : null // Show readable value
                        }));

                        // Define `wizardPower` globally after fetching columns 
                        window.wizardPower = Ext.extend(Ext.Window, {
                            constructor: function (cnf) {
                                var main_id;

                                // Create a search field above the grid
                                var searchField = new Ext.form.TextField({
                                    id: 'search_field',
                                    fieldLabel: 'Search',
                                    width: 290,
                                    enableKeyEvents: true,
                                    listeners: {
                                        specialkey: function (field, e) {
                                            if (e.getKey() === e.ENTER) {
                                                grid_store.load({
                                                    params: {
                                                        action: 'get_data',
                                                        query: field.getValue()
                                                    },
                                                    url: './tools/wizardPower/src/index.php',
                                                    callback: function (records, options, success) {
                                                        if (success) {
                                                            Ext.getCmp('main_grid').getView().refresh();
                                                        } else {
                                                            Ext.Msg.alert('Error', 'Failed to fetch search results.');
                                                        }
                                                    }
                                                });
                                            }
                                        }
                                    }
                                });

                                // Add the search field before the grid
                                var searchPanel = new Ext.Panel({
                                    layout: 'form',
                                    border: false,
                                    bodyStyle: 'padding: 8px;',
                                    items: [searchField]
                                });

                                wizardPower.superclass.constructor.call(this, {
                                    id: 'main_wizard',
                                    title: 'Wizard',
                                    width: 1050,
                                    height: 360,
                                    autoScroll: true,
                                    layout: 'form',
                                    border: false,
                                    plain: true,
                                    bodyStyle: 'padding:5px;',
                                    buttonAlign: 'center',
                                    minimizable: true,
                                    listeners: {
                                        'minimize': function (w) {
                                            var m = Ext.getCmp('m_adv_win');
                                            m.add({
                                                text: w.title,
                                                iconCls: w.iconCls,
                                                pwin: w,
                                                id: 'adv_win_btn_' + w.id,
                                                handler: function (item) {
                                                    item.pwin.show();
                                                    item.destroy();
                                                }
                                            });
                                            w.hide();
                                        }
                                    },
                                    items: [
                                        searchPanel,
                                        {
                                            xtype: 'editorgrid',
                                            id: 'main_grid',
                                            clicksToEdit: 1,
                                            height: 220,
                                            loadMask: true,
                                            autoScroll: true,
                                            cls: 'grid-border',
                                            store: grid_store,
                                            sm: new Ext.grid.RowSelectionModel({ singleSelect: true }),
                                            cm: new Ext.grid.ColumnModel(dynamicColumns)
                                        }
                                    ],
                                    buttons: [
                                        {
                                            text: 'Save',
                                            id: 'save_button',
                                            disabled: true,
                                            handler: function () {
                                                Ext.Ajax.request({
                                                    url: './tools/wizardPower/src/index.php',
                                                    params: {
                                                        'action': 'save_data',
                                                        'is_update': is_update,
                                                        data: Ext.encode(Ext.pluck(grid_store.getModifiedRecords(), 'data'))
                                                    },
                                                    success: function (result) {
                                                        var res = Ext.decode(result.responseText);
                                                        if (res.success) {
                                                            grid_store.reload();
                                                        } else {
                                                            Ext.MessageBox.show({
                                                                title: 'Error',
                                                                msg: res.message,
                                                                buttons: Ext.MessageBox.OK
                                                            });
                                                        }
                                                    }
                                                });
                                            }
                                        },
                                        { text: 'Cancel', handler: function () { Ext.getCmp('main_wizard').close(); } }
                                    ]
                                });

                                this.initWizard = function (cfg) {
                                    if (cfg.objectId && cfg.objectId.key === 'locd') {
                                        Ext.Ajax.request({
                                            url: './tools/wizardPower/src/index.php',
                                            params: { action: 'get_data' },
                                            success: function (result) {
                                                var res = Ext.decode(result.responseText);
                                                if (res.success) {
                                                    main_id = cfg.objectId.id;
                                                    grid_store.load({ params: { LOCATIONID: cfg.objectId.id } });
                                                    Ext.getCmp('save_button').setDisabled(res.data[0]?.ROLE === 0);
                                                }
                                            }
                                        });
                                        this.show();
                                    }
                                };
                            }
                        });

                        // Ensure `show_tools()` runs only after wizardPower is defined
                        if (typeof show_tools === 'function') {
                            show_tools();
                        }
                    }
                }
            });
        }
    });
});
