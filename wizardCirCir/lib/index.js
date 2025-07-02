Ext.onReady(function () {
  Ext.QuickTips.init();

  var cir_id = false;

  function refreshGrids() {

    Ext.Ajax.request({
      url: './tools/wizardCirCir/src/index.php',
      params: { action: 'get_circuit', id: cir_id },
      success: function (result) {
        const res = Ext.decode(result.responseText);
        if (res.success && res.data) {
          Ext.getCmp('circuit_field').setValue(res.data.circuit?.NAME || '');
          console.log(res.data.circuit);

          populateGridFromData('grid_used', res.data.used || []);
          populateGridFromData('grid_uses', res.data.uses || []);
          populateGridFromData('grid_link', res.data.links || []);
          populateGridFromData('grid_service', res.data.services || []);
        }
      }
    });
  }
  function populateGridFromData(gridId, records) {
    const grid = Ext.getCmp(gridId);
    if (grid) {
      const store = grid.getStore();
      store.removeAll();
      records.forEach(item => {
        store.add(new store.recordType({
          id: item.ID,
          name: item.NAME
        }));
      });
    }
  }


  function createAddWindow(title, gridPanel) {
    const comboId = 'add_combo_' + title;

    const comboStore = new Ext.data.JsonStore({
      url: './tools/wizardCirCir/src/index.php',
      baseParams: { action: 'get_' + title },
      root: 'data',
      fields: ['ID', 'NAME']
    });

    const combo = new Ext.form.ComboBox({
      id: comboId,
      fieldLabel: 'ID or NAME',
      store: comboStore,
      valueField: 'ID',
      displayField: 'NAME',
      mode: 'remote',
      triggerAction: 'all',
      minChars: 2,
      queryDelay: 300,
      typeAhead: false,
      forceSelection: true,
      allowBlank: false,
      anchor: '95%'
    });

    const formPanel = new Ext.form.FormPanel({
      labelWidth: 80,
      bodyStyle: 'padding:10px;',
      width: 320,
      height: 120,
      items: [combo],
      buttons: [
        {
          text: 'Add',
          handler: function () {
            const selectedId = combo.getValue();
            const selectedName = combo.getRawValue();

            if (!selectedId || selectedId.trim() === '') {
              Ext.Msg.alert('Error', 'Please select a value from the list.');
              return;
            }

            Ext.Ajax.request({
              url: './tools/wizardCirCir/src/index.php',
              params: {
                action: 'add_' + title,
                cir_id: cir_id,
                id: selectedId,
                name: selectedName
              },
              success: function () {
                console.log('Add ' + title + ' successful');

                const store = gridPanel.items.get(0).getStore();
                store.add(new store.recordType({ id: selectedId, name: selectedName }));

                formPanel.ownerCt.close();
              },
              failure: function () {
                Ext.Msg.alert('Error', 'Failed to add ' + title);
              }
            });
          }
        },
        {
          text: 'Close',
          handler: function () {
            formPanel.ownerCt.close();
          }
        }
      ]
    });

    const win = new Ext.Window({
      title: 'Add ' + title,
      modal: true,
      layout: 'fit',
      width: 360,
      height: 160,
      items: [formPanel]
    });

    win.show();
  }





  function createGridPanel(title) {
    return {
      xtype: 'panel',
      layout: 'hbox',
      border: false,
      bodyStyle: 'margin: 10px 0;',
      items: [
        {
          id: 'grid_' + title,
          xtype: 'grid',
          title: title.charAt(0).toUpperCase() + title.slice(1),
          width: 400,
          height: 150,
          store: new Ext.data.ArrayStore({
            fields: ['id', 'name'],
            data: []
          }),
          columns: [
            { header: 'Id', dataIndex: 'id', width: 100 },
            { header: 'Name', dataIndex: 'name', width: 280 }
          ]
        },
        {
          xtype: 'panel',
          layout: {
            type: 'vbox',
            align: 'middle',
            pack: 'center'
          },
          width: 100,
          height: 150,
          bodyStyle: 'padding: 10px 5px;',
          defaults: {
            xtype: 'button',
            width: 60,
            style: 'margin-bottom: 8px;'
          },
          items: [
            {
              text: 'Add',
              handler: function () {
                const grid = Ext.getCmp('grid_' + title.toLowerCase());
                if (grid) {
                  const store = grid.getStore();
                  createAddWindow(title, store);
                }
              }

            },
            {
              text: 'Del',
              handler: function () {
                Ext.Ajax.request({
                  url: './tools/wizardCirCir/src/index.php',
                  params: { action: 'del_' + title },
                  success: function () {
                    console.log('Delete ' + title + ' successful');
                  }
                });
              }
            }
          ]
        }
      ]
    };
  }

  window.wizard_cir_cir = Ext.extend(Ext.Window, {
    constructor: function (cfg) {
      const config = Ext.applyIf(cfg || {}, {
        title: 'Wizard Circuit',
        width: 540,
        autoHeight: true,
        layout: 'form',
        border: false,
        plain: true,
        modal: true,
        resizable: false,
        closable: true,
        bodyStyle: 'padding: 10px;',
        items: [
          {
            xtype: 'panel',
            layout: 'hbox',
            border: false,
            bodyStyle: 'padding: 10px 10px 10px 10px;',
            items: [
              { xtype: 'label', text: 'Circuit:', width: 60, style: 'margin-right:10px;margin-top:4px;' },
              { xtype: 'textfield', id: 'circuit_field', readOnly: true, width: 400 }
            ]
          },
          createGridPanel('used'),
          createGridPanel('uses'),
          createGridPanel('service'),
          createGridPanel('link')
        ],
        buttons: [
          {
            text: 'Refresh',
            handler: function () {
              refreshGrids();
            }
          }, {
            text: 'Close',
            handler: function () {
              this.ownerCt.ownerCt.close();
            }
          }
        ]
      });

      window.wizard_cir_cir.superclass.constructor.call(this, config);

      this.initWizard = function (cfg) {
        if (cfg.objectId?.key === 'circ') {
          cir_id = cfg.objectId.id;

          refreshGrids();
        }


        this.show();
      };

    }
  });
});
