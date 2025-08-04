Ext.onReady(function () {
  Ext.QuickTips.init();

  function openEditWindow() {
    let isChanged = false;


    const saveButton = new Ext.Button({
      text: 'Save',
      disabled: true,
      handler: function () {
        if (form.getForm().isValid()) {
          const values = form.getForm().getValues();

        }
      }
    });

    let nodeCombo, portCombo;


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
          fieldLabel: 'Name'
        }
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
      title: 'Add Node',
      modal: true,
      layout: 'fit',
      width: 420,
      height: 300,
      items: [form]
    });

    win.show();
  }





  window.wizard_create_node = Ext.extend(Ext.Window, {
    constructor: function (cfg) {
      const config = Ext.applyIf(cfg || {}, {
        title: 'Create Node Wizard',
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
            bodyStyle: 'margin: 10px 0;',
            items: [
              {
                id: 'grid_cr_node',
                xtype: 'grid',
                height: 150,
                store: new Ext.data.ArrayStore({
                  fields: ['NNAME', 'NODETYPE', 'NODEDEF', 'SUBTYPE', 'LNAME', 'COMMENTS'],
                  data: []
                }),
                columns: [
                  { header: 'Name', dataIndex: 'NNAME', width: 200 },
                  { header: 'Node Type', dataIndex: 'NODETYPE', width: 100 },
                  { header: 'Node Def', dataIndex: 'NODEDEF', width: 140 },
                  { header: 'Subtype', dataIndex: 'SUBTYPE', width: 140 },
                  { header: 'Location', dataIndex: 'LNAME', width: 80 },
                  { header: 'Comments', dataIndex: 'COMMENTS', width: 80 }
                ]
              }
            ]
          }

        ],
        buttons: [
          {
            text: 'Add',
            handler: function () {
              openEditWindow();
            }
          }, {
            text: 'Cancel',
            handler: function () {
              this.ownerCt.ownerCt.close();
            }
          }
        ]
      });

      window.wizard_create_node.superclass.constructor.call(this, config);

      this.initWizard = function (cfg) {

        Ext.Ajax.request({
          url: './tools/wizardCreateNode/src/index.php',
          params: { action: 'get_data' },
          success: function (result) {
            const res = Ext.decode(result.responseText);
            if (res.success && res.data) {

              const grid = Ext.getCmp('grid_cr_node');
              if (grid) {
                const store = grid.getStore();
                store.removeAll();
                (res.data.values || []).forEach(item => {
                  store.add(new store.recordType({
                    NNAME: item.NNAME,
                    NODETYPE: item.NODETYPE,
                    NODEDEF: item.NODEDEF,
                    SUBTYPE: item.SUBTYPE,
                    LNAME: item.LNAME,
                    COMMENTS: item.COMMENTS
                  }));
                  ;
                });
              }
            }
          }
        });

        this.show();
      };


    }
  });
});
