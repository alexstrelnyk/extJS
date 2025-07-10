Ext.onReady(function () {
  Ext.QuickTips.init();

  var cir_id = false;


  function openEditWindow(record) {
    let isChanged = false;

    let locationId = record.get('LOCATIONID');
    let nodeId = record.get('NODEID');
    let portId = record.get('PORTID');

    const saveButton = new Ext.Button({
      text: 'Save',
      disabled: true,
      handler: function () {
        if (form.getForm().isValid()) {
          const values = form.getForm().getValues();

          Ext.Ajax.request({
            url: './tools/wizardServiceSplitter/src/index.php',
            params: {
              action: 'save_ser_spl',
              circuit_id: record.get('CIRCUITID'),
              ...values
            },
            success: function () {
              record.set('LOCATION', locationCombo.getRawValue());
              record.set('NODE', nodeCombo.getRawValue());
              record.set('PORT', portCombo.getRawValue());
              record.commit();
              form.ownerCt.close();
            },

            failure: function () {
              Ext.Msg.alert('Error', 'Failed to save');
            }
          });
        }
      }
    });

    let nodeCombo, portCombo;

    function createRemoteCombo(name, displayValue, idValue) {
      const storeParams = { action: 'get_' + name.toLowerCase() };

      if (name === 'NODE' && locationId) storeParams.LOCATIONID = locationId;
      if (name === 'PORT' && nodeId) storeParams.NODEID = nodeId;

      const combo = new Ext.form.ComboBox({
        fieldLabel: name.charAt(0) + name.slice(1).toLowerCase(),
        name: name + 'ID',
        hiddenName: name + 'ID',
        store: new Ext.data.JsonStore({
          url: './tools/wizardServiceSplitter/src/index.php',
          baseParams: storeParams,
          root: 'data',
          fields: ['ID', 'NAME']
        }),
        valueField: 'ID',
        displayField: 'NAME',
        mode: 'remote',
        triggerAction: 'all',
        minChars: 2,
        queryDelay: 300,
        forceSelection: true,
        typeAhead: false,
        allowBlank: false,
        anchor: '95%',
        listeners: {
          select: function (combo, rec) {
            if (!isChanged) {
              isChanged = true;
              saveButton.setDisabled(false);
            }

            if (name === 'LOCATION') {
              locationId = rec.get('ID');
              if (nodeCombo) nodeCombo.clearValue();
              if (portCombo) portCombo.clearValue();
            }

            if (name === 'NODE') {
              nodeId = rec.get('ID');
              if (portCombo) portCombo.clearValue();
            }

            if (name === 'PORT') {
              portId = rec.get('ID');
            }
          }
        }
      });

      combo.on('render', function () {
        //    combo.setValue(idValue);           
        combo.setRawValue(displayValue);
      });

      if (name === 'LOCATION') locationCombo = combo;
      if (name === 'NODE') nodeCombo = combo;
      if (name === 'PORT') portCombo = combo;


      return combo;
    }



    const form = new Ext.form.FormPanel({
      labelWidth: 70,
      bodyStyle: 'padding:10px;',
      width: 400,
      height: 240,
      defaults: { anchor: '95%', allowBlank: false },
      items: [
        {
          xtype: 'textfield',
          name: 'NAME',
          fieldLabel: 'Name',
          value: record.get('NAME'),
          readOnly: true
        },
        {
          xtype: 'textfield',
          name: 'SERVICE',
          fieldLabel: 'Service',
          value: record.get('SERVICE'),
          readOnly: true
        },
        createRemoteCombo('LOCATION', record.get('LOCATION'), record.get('LOCATIONID')),
        createRemoteCombo('NODE', record.get('NODE'), record.get('NODEID')),
        createRemoteCombo('PORT', record.get('PORT'), record.get('PORTID'))
      ],
      buttons: [
        saveButton,
        {
          text: 'Cancel',
          handler: function () {
            form.ownerCt.close();
          }
        }
      ]
    });

    const win = new Ext.Window({
      title: 'Edit Entry',
      modal: true,
      layout: 'fit',
      width: 420,
      height: 300,
      items: [form]
    });

    win.show();
  }





  window.wizard_ser_spl = Ext.extend(Ext.Window, {
    constructor: function (cfg) {
      const config = Ext.applyIf(cfg || {}, {
        title: 'Wizard Service Splitter',
        width: 740,
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
              { xtype: 'label', text: 'OLT Name:', width: 60, style: 'margin-right:10px;margin-top:4px;' },
              { xtype: 'textfield', id: 'circuit_field', readOnly: true, width: 400 }
            ]
          },
          {
            xtype: 'panel',
            layout: 'hbox',
            border: false,
            bodyStyle: 'margin: 10px 0;',
            items: [
              {
                id: 'grid_ser_spl',
                xtype: 'grid',
                height: 150,
                store: new Ext.data.ArrayStore({
                  fields: ['NAME', 'SERVICE', 'LOCATION', 'NODE', 'PORT'],
                  data: []
                }),
                listeners: {
                  rowdblclick: function (grid, rowIndex) {
                    const record = grid.getStore().getAt(rowIndex);
                    openEditWindow(record);
                  }
                },
                columns: [
                  //  { header: 'Circuit ID', dataIndex: 'CIRCUITID', width: 80 },
                  { header: 'Name', dataIndex: 'NAME', width: 200 },
                  { header: 'Service', dataIndex: 'SERVICE', width: 100 },
                  { header: 'Location', dataIndex: 'LOCATION', width: 140 },
                  { header: 'Node', dataIndex: 'NODE', width: 140 },
                  { header: 'Port', dataIndex: 'PORT', width: 80 }
                ]
              }
            ]
          }

        ],
        buttons: [
          {
            text: 'Cancel',
            handler: function () {
              this.ownerCt.ownerCt.close();
            }
          }, {
            text: 'Save',
            handler: function () {
            }
          }
        ]
      });

      window.wizard_ser_spl.superclass.constructor.call(this, config);

      this.initWizard = function (cfg) {
        if (cfg.objectId?.key === 'circ') {
          cir_id = cfg.objectId.id;

          Ext.Ajax.request({
            url: './tools/wizardServiceSplitter/src/index.php',
            params: { action: 'get_circuit', id: cir_id },
            success: function (result) {
              const res = Ext.decode(result.responseText);
              if (res.success && res.data) {
                Ext.getCmp('circuit_field').setValue(res.data.circuit?.NAME || '');

                const grid = Ext.getCmp('grid_ser_spl');
                if (grid) {
                  const store = grid.getStore();
                  store.removeAll();
                  (res.data.values || []).forEach(item => {
                    store.add(new store.recordType({
                      CIRCUITID: item.CIRCUITID,
                      NAME: item.NAME,
                      SERVICE: item.SERVICE,
                      LOCATION: item.LOCATION,
                      LOCATIONID: item.LOCATIONID,
                      NODE: item.NODE,
                      NODEID: item.NODEID,
                      PORT: item.PORT,
                      PORTID: item.PORTID
                    }));
                    ;
                  });
                }
              }
            }
          });
        }

        this.show();
      };


    }
  });
});
