var tableName = false;
var colsLength = 0;

Ext.onReady(function () {
  Ext.QuickTips.init();
  csvupload = Ext.extend(Ext.Window, {

    constructor: function (cnf) {
      var tablesCombo = new Ext.form.ComboBox({
        id: 'tablesCombo',
        blankText: 'select type',
        fieldLabel: 'Table',
        labelWidth: 50,
        triggerAction: 'all',
        width: 180,
        store: new Ext.data.JsonStore({
          root: 'data',
          fields: ['ID', 'NAME'],
          url: './tools/CsvUpload/src/csvupload.php?action=get_tables'
        }),
        valueField: 'ID',
        displayField: 'NAME',
        listeners: {
          select: function (c, r, i) {
            tableName = r.get('ID');

            Ext.Ajax.request({
              timeout: 1800000,
              url: './tools/CsvUpload/src/csvupload.php',
              params: {
                action: 'get_table_columns',
                table_name: tableName
              },
              failure: function () {
                Ext.MessageBox.show({
                  title: 'Error',
                  msg: 'Internal error',
                  buttons: Ext.MessageBox.OK
                });
              },
              success: function (result, action) {
                var res = Ext.decode(result.responseText);
                if (cols = res.data) {
                  colsLength = cols.length;

                  var existingColumns = Ext.getCmp('columnsContainer');
                  if (existingColumns) {
                    csvupload_form.remove(existingColumns, true);
                  }

                  var columnsStore = new Ext.data.ArrayStore({
                    fields: ['COLUMN_NAME'],
                    data: [['']].concat(cols.map(col => [col.COLUMN_NAME]))
                  });

                  var columnsContainer = new Ext.Container({
                    id: 'columnsContainer',
                    layout: 'hbox',
                    items: []
                  });

                  for (var i in cols) {
                    if (!isNaN(i) && i.trim() !== "") {
                      var combo = new Ext.form.ComboBox({
                        id: 'col_' + i,
                        fieldLabel: `Column ${parseInt(i) + 1}`,
                        labelWidth: 70,
                        width: csvupload_form.getWidth() / cols.length,
                        triggerAction: 'all',
                        mode: 'local',
                        store: columnsStore,
                        valueField: 'COLUMN_NAME',
                        displayField: 'COLUMN_NAME',
                        editable: false
                      });

                      columnsContainer.add(combo);
                    }
                  }

                  csvupload_form.add(columnsContainer);
                  csvupload_form.doLayout();
                }
              }
            });
          }
        }
      });

      var csvFileUpload = new Ext.form.FileUploadField({
        id: 'csvFileUpload',
        fieldLabel: 'Upload CSV File',
        name: 'csvFile',
        emptyText: 'Select CSV File',
        allowBlank: false,
        width: 300,
        labelWidth: 120,
        listeners: {
          fileselected: function (fb, v) {
            csvupload_form.getForm().submit({
              url: './tools/CsvUpload/src/upload.php',
              waitMsg: 'Uploading file...',
              success: function (form, action) {
                var res = Ext.decode(action.response.responseText);

                // Remove all rowContainers 
                //removeCpn('rowContainer');

                if (res.data.length) {
                  var jsonData = res.data.reverse();

                  jsonData.forEach(function (row) {
                    var rowContainer = new Ext.Container({
                      id: 'rowContainer',
                      layout: 'hbox',
                      items: []
                    });

                    for (var r in row) {
                      if (typeof (row[r]) != 'function') {
                        var label = new Ext.form.Label({
                          text: row[r],
                          style: 'margin: 0px;padding: 0 0 5px 0; width: 120px'
                        });

                        rowContainer.add(label);

                        csvupload_form.add(rowContainer);
                      }
                    }
                    csvupload_form.doLayout();
                  });
                }


                //        Ext.Msg.show({ msg: action.response.responseText, buttons: Ext.Msg.OK, icon: Ext.Msg.INFO });

              }
            });
          }
        }
      });

      var checkbox1 = new Ext.form.Checkbox({
        id: 'clear_table',
        boxLabel: 'Clear table before import CSV'
      });

      var importButton = new Ext.Button({
        text: 'Import',
        handler: function () {
          csvupload_form.getForm().submit({
            url: './tools/CsvUpload/src/upload.php',
            waitMsg: 'Uploading file...',
            params: {
              make_import: 1,
              cols_length: colsLength,
            },
            success: function (form, action) {
              Ext.Msg.show({ msg: action.response.responseText, buttons: Ext.Msg.OK, icon: Ext.Msg.INFO });
            },
            failure: function (form, action) {
              Ext.Msg.show({
                title: 'Error',
                msg: action.response.responseText,
                buttons: Ext.Msg.OK,
                icon: Ext.Msg.ERROR
              });
            }
          });
        }
      });

      var separator = new Ext.form.TextField({
        id: 'separator',
        fieldLabel: 'Separator',
        value: ',',
        width: 180
      });

      var csvupload_form = new Ext.FormPanel({
        id: 'csvupload_form',
        renderTo: Ext.getBody(),
        labelWidth: 120,
        autoHeight: true,
        //        height: 320,
        fileUpload: true,
        autoScroll: true,
        items: [
          csvFileUpload,
          checkbox1,
          separator,
          tablesCombo,
        ],
        buttons: [
          { xtype: 'tbfill' },
          importButton
        ]
      });


      csvupload.superclass.constructor.call(this,
        {
          id: 'csvupload_title',
          title: 'Import CSV',
          //        modal: true,
          width: 700,
          autoHeight: true,
          minHeight: 460,
          //        height: 450,
          //        resizable: true,
          layout: 'fit',
          plain: true,
          bodyStyle: 'padding:5px;',
          buttonAlign: 'center',
          minimizable: true,
          //        maximizable : true,
          listeners: {
            'minimize': function (w) {
              var m = Ext.getCmp('m_adv_win');
              m.add(
                {
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
          items: [csvupload_form]
        });


      this.initWizard = function () {



      };
      removeCpn = function (id) {
        var existingColumns = Ext.getCmp(id);
        if (existingColumns) {
          csvupload_form.remove(existingColumns, true);
          csvupload_form.doLayout();
          console.log(id, existingColumns);
          removeCpn(id);
        }
      };

      this.initWizard();
    }
  });
});

