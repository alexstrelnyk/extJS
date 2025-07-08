Ext.onReady(function () {
  Ext.QuickTips.init();

  var cir_id = false;

  window.wizard_ser_spl = Ext.extend(Ext.Window, {
    constructor: function (cfg) {
      const config = Ext.applyIf(cfg || {}, {
        title: 'Wizard Service Splitter',
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


                ]
              }
            ]
          }
        ],
        buttons: [
          {
            text: 'Save',
            handler: function () {
            }
          }, {
            text: 'Close',
            handler: function () {
              this.ownerCt.ownerCt.close();
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

              }
            }
          });
        }


        this.show();
      };

    }
  });
});

