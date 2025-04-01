Ext.onReady(function () {
    Ext.QuickTips.init();

    var primary_key_col = 'LOCID';

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
                        var dynamicFields = res.data.map(col => ({
                            name: col.COLUMN_NAME,
                            type: col.DATA_TYPE === "DATE" ? 'date' : 'auto',
                            dateFormat: col.DATA_TYPE === "DATE" ? 'd.m.Y H:i' : undefined // Match API format
                        }));


                        var grid_store = new Ext.data.JsonStore({
                            root: 'data',
                            baseParams: { action: 'get_data' },
                            url: './tools/wizardPower/src/index.php',
                            fields: dynamicFields,
                            listeners: {
                                load: function (store, records) {
                                    Ext.getCmp('main_grid').getView().refresh();
                                    var colModel = Ext.getCmp('main_grid').getColumnModel();
                                    colModel.setHidden(colModel.findColumnIndex(primary_key_col), true);
                                    colModel.setHidden(colModel.findColumnIndex('LOCATIONID'), true);
                                }
                            }
                        });



                        var dynamicColumns = res.data.map(col => ({
                            header: col.COLUMN_NAME.replace(/_/g, ' '),
                            dataIndex: col.COLUMN_NAME,
                            width: 100,
                            editor:
                                col.COLUMN_NAME === "NAME" ? null :  // Make "NAME" column non-editable
                                    col.COLUMN_NAME === "ATOLL_SITE_NAME" ? null :
                                        col.COLUMN_NAME === "CREATED_AT" ? null :
                                            col.COLUMN_NAME === "UPDATED_AT" ? null :
                                                col.DATA_TYPE === "DATE" ? {
                                                    xtype: 'datefield',
                                                    format: 'd.m.Y H:i', // Match database format
                                                    submitFormat: 'Y-m-d H:i:s', // Format when saving
                                                    allowBlank: true
                                                } :
                                                    (enumMap[col.COLUMN_NAME] ? {
                                                        xtype: 'combo',
                                                        store: enumStores[col.COLUMN_NAME],
                                                        displayField: 'value',
                                                        valueField: 'key',
                                                        mode: 'local',
                                                        triggerAction: 'all',
                                                        editable: false
                                                    } : { xtype: 'textfield' }),
                            hidden: false,
                            renderer: function (value) {
                                if (col.DATA_TYPE === "DATE") {
                                    return Ext.isDate(value) ? Ext.util.Format.date(value, 'd.m.Y H:i') : value;
                                }
                                return enumMap[col.COLUMN_NAME] ? (enumMap[col.COLUMN_NAME][value] || value) : value;
                            }
                        }));



                        // Define `wizardPower` globally after fetching columns 
                        window.wizardPower = Ext.extend(Ext.Window, {
                            constructor: function (cnf) {
                                var main_id;

                                // Create a search field above the grid
                                var searchField = new Ext.form.TextField({
                                    id: 'search_field',
                                    fieldLabel: 'Search',
                                    width: 220,  // Adjust width to accommodate button
                                    enableKeyEvents: true,
                                    listeners: {
                                        specialkey: function (field, e) {
                                            if (e.getKey() === e.ENTER) {
                                                performSearch();
                                            }
                                        }
                                    }
                                });

                                // Create the "Search" button
                                var searchButton = new Ext.Button({
                                    text: 'Search',
                                    handler: function () {
                                        performSearch();
                                    }
                                });

                                // Function to perform the search
                                function performSearch() {
                                    var query = Ext.getCmp('search_field').getValue();
                                    grid_store.load({
                                        params: {
                                            action: 'get_data',
                                            query: query
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
                                var searchPanel = new Ext.Panel({
                                    layout: 'hbox',
                                    border: false,
                                    bodyStyle: 'padding: 8px;',
                                    items: [
                                        searchField,
                                        { xtype: 'spacer', width: 10 }, // Add space between field and button
                                        searchButton
                                    ]
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
                                            params: { action: 'get_data', locd: cfg.objectId.id },
                                            success: function (result) {
                                                var res = Ext.decode(result.responseText);
                                                if (res.success) {
                                                    main_id = cfg.objectId.id;
                                                    grid_store.load({ params: { 'locd': cfg.objectId.id } });
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
