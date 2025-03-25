Ext.onReady(function () {
  Ext.QuickTips.init();


  warZone = Ext.extend(Ext.Window,
    {
      constructor: function () {
        var warZone_id;
        var warZone_combo = new Ext.form.ComboBox({
          id: 'warZone_combo',
          fieldLabel: 'Warzone',
          triggerAction: 'all',
          width: 200,
          store: new Ext.data.JsonStore({
            root: 'data',
            autoload: false,
            fields: ['ID', 'VALUE'],
            url: './wizard/src/loc_attr.php',
            baseParams: { action: 'getStore', tablename: 'SATTAB_LOCATIONSITE', fieldname: 'WAR_ZONE' }
          }),
          valueField: 'ID',
          displayField: 'VALUE'
        });

        warZone.superclass.constructor.call(this,
          {
            id: 'warZone_title',
            layout: 'form',
            title: 'Edit Priority',
            width: 450,
            //          height: 470,
            resizable: false,
            plain: true,
            bodyStyle: 'padding:5px 5px 0',
            items: [
              warZone_combo
            ],
            bbar: [
              {
                text: 'Save',
                handler: function () {
                  Ext.Ajax.request({
                    url: './wizard/src/loc_attr.php',
                    params: {
                      action: 'saveattr',
                      locid: warZone_id,
                      tablename: 'SATTAB_LOCATIONSITE_O',
                      data: Ext.encode({ 'WAR_ZONE': warZone_combo.getValue() }, 'data')
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
                      if (res.success == false) {
                        Ext.MessageBox.show({
                          title: 'Error',
                          msg: res.err_msg,
                          buttons: Ext.MessageBox.OK
                        });
                      }
                      else {
                        Ext.MessageBox.show({
                          title: 'Ok',
                          msg: 'Save attribute ok',
                          buttons: Ext.MessageBox.OK
                        });
                      }
                    }
                  })
                }
              }, '->', {
                text: 'Close',
                iconCls: 'btn_close',
                handler: function () {
                  Ext.getCmp(this.el.up('.x-window').id).close();
                }
              }
            ],
            minimizable: true,
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
            }

          });

        this.initWizard = function (cfg) {
          if (cfg.objectId)
            if (cfg.objectId.key == 'locd') {
              warZone_id = cfg.objectId.id;
              Ext.Ajax.request({
                url: './wizard/src/loc_attr.php?action=getValue',
                method: 'POST',
                params: { sattab: 'SATTAB_LOCATIONSITE_O', locid: cfg.objectId.id },
                failure: function () {
                  Ext.MessageBox.show({
                    title: 'Error',
                    msg: 'Internal error',
                    buttons: Ext.MessageBox.OK
                  });
                },
                success: function (result, req) {
                  var res = Ext.util.JSON.decode(result.responseText);
                  if (res.success) {
                    Ext.getCmp('warZone_title').setTitle(res.data[0].NAME);
                    warZone_combo.store.load({ callback: function () { warZone_combo.setValue(res.data[0].WAR_ZONE); } })

                  };
                }
              });
            };
          this.show();
        };
      }
    });

})
