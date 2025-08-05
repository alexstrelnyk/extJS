Ext.onReady(function () {
  Ext.QuickTips.init();

  function refreshGrid() {

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
  }
  function openEditWindow() {
    let isChanged = false;

    const saveButton = new Ext.Button({
      text: 'Save',
      handler: function () {
        if (!form.getForm().isValid()) {
          Ext.Msg.alert('Validation', 'Please fill all required fields.');
          return;
        }

        const values = form.getForm().getValues();

        Ext.Ajax.request({
          url: './tools/wizardCreateNode/src/index.php',
          method: 'POST',
          params: {
            action: 'save_node',
            ...values
          },
          success: function (response) {
            const res = Ext.decode(response.responseText);
            if (res.success) {
              Ext.Msg.alert('Success', 'Node successfully created');
              form.ownerCt.close();
              refreshGrid();
            } else {
              Ext.Msg.alert('Error', res.message || 'Failed to save node');
            }
          },
          failure: function () {
            Ext.Msg.alert('Error', 'Server error while saving node');
          }
        });
      }
    });



    const nodeTypeCombo = new Ext.form.ComboBox({
      fieldLabel: 'Node Type',
      name: 'NODETYPE',
      hiddenName: 'NODETYPE',
      store: new Ext.data.JsonStore({
        url: './tools/wizardCreateNode/src/index.php',
        baseParams: { action: 'get_node_types' },
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
      anchor: '95%'
    });

    const nodeDefCombo = new Ext.form.ComboBox({
      fieldLabel: 'Node Def',
      name: 'NODEDEF',
      hiddenName: 'NODEDEF',
      store: new Ext.data.JsonStore({
        url: './tools/wizardCreateNode/src/index.php',
        baseParams: { action: 'get_node_defs' },
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
      anchor: '95%'
    });

    const subTypeCombo = new Ext.form.ComboBox({
      fieldLabel: 'Subtype',
      name: 'SUBTYPE',
      hiddenName: 'SUBTYPE',
      store: new Ext.data.JsonStore({
        url: './tools/wizardCreateNode/src/index.php',
        baseParams: { action: 'get_subtypes' },
        root: 'data',
        fields: ['NAME']
      }),
      valueField: 'NAME',
      displayField: 'NAME',
      mode: 'remote',
      triggerAction: 'all',
      minChars: 2,
      queryDelay: 300,
      forceSelection: true,
      typeAhead: false,
      allowBlank: false,
      anchor: '95%'
    });

    const locationCombo = new Ext.form.ComboBox({
      fieldLabel: 'Location',
      name: 'LNAME',
      hiddenName: 'LNAME',
      store: new Ext.data.JsonStore({
        url: './tools/wizardCreateNode/src/index.php',
        baseParams: { action: 'get_location' },
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
      anchor: '95%'
    });

    const form = new Ext.form.FormPanel({
      labelWidth: 70,
      bodyStyle: 'padding:10px;',
      width: 400,
      height: 840,
      defaults: { anchor: '95%', allowBlank: false },
      items: [
        {
          xtype: 'textfield',
          name: 'NAME',
          fieldLabel: 'Name'
        },
        nodeTypeCombo,
        nodeDefCombo,
        subTypeCombo,
        locationCombo,
        {
          xtype: 'textarea',
          name: 'COMMENTS',
          allowBlank: true,
          fieldLabel: 'Comments'
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
        refreshGrid();

        this.show();
      };


    }
  });
});
