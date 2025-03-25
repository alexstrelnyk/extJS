Ext.onReady(function () {
	Ext.QuickTips.init();


	CableLenght = Ext.extend(Ext.Window,
		{
			constructor: function () {
				var WCableLenght_1 = 'WCableLenght_';
				var WCableLenght_width = 350;
				var WCableLenght_tablename = '';
				var WCableLenght_locid = 0;

				var wcLoad = new Ext.LoadMask(Ext.getBody(), { msg: "Loading..." });

				var WCableLenght_store = new Ext.data.JsonStore({
					id: 0,
					pageSize: 10,
					autoload: true,
					autoSave: false,
					root: 'data',
					url: './wizard/src/cable_lenght.php',
					fields: ['ID', 'PORTID', 'NAME', 'STATUS', 'PARAM', 'BANDWIDTH', 'p1l', 'p1s', 'p2l', 'p2s', 'p3l', 'p3s', 'p4l', 'p4s'],
					writer: {
					},
					extraParams: {
						xaction: 'read'
					},
					reader: {
						successProperty: 'success',
						messageProperty: 'message'
					}
				});

				var WCableLenght_grid = new Ext.grid.EditorGridPanel({
					id: 'WCableLenght_grid',
					loadMask: { msg: 'Loaded...' },
					frame: true,
					height: 375,
					clicksToEdit: true,
					autoExpandColumn: true,
					store: WCableLenght_store,
					sm: new Ext.grid.RowSelectionModel({ singleSelect: true, listeners: { select: function () { alert('select'); } } }),
					cm: new Ext.grid.ColumnModel(
						{
							defaults:
							{
								sortable: false,
								width: 80
							},
							columns:
								[
									{ id: 'ID', header: 'ID', hidden: true, dataIndex: 'ID' },
									{ header: 'Port ID', dataIndex: 'PORTID', width: 50 },
									{ header: 'Name', dataIndex: 'NAME', width: 150 },
									{ header: 'Status', dataIndex: 'STATUS', width: 50 },
									{ header: 'Speed', dataIndex: 'PARAM', width: 100 },
									{ header: 'Bandwidth', dataIndex: 'BANDWIDTH' },
									{ header: 'Pair1 (length)', dataIndex: 'P1L' },
									{ header: 'Pair1 (state)', dataIndex: 'P1S' },
									{ header: 'Pair2 (length)', dataIndex: 'P2L' },
									{ header: 'Pair2 (state)', dataIndex: 'P2S' },
									{ header: 'Pair3 (length)', dataIndex: 'P3L' },
									{ header: 'Pair3 (state)', dataIndex: 'P3S' },
									{ header: 'Pair4 (length)', dataIndex: 'P4L' },
									{ header: 'Pair4 (state)', dataIndex: 'P4S' }
								]
						}),
					viewConfig: {
						getRowClass: function (record, index, rowParams, store) {
							var hasError = false;
							var portID = record.get('PORTID');
							var name = record.get('NAME');
							var bandwidth = record.get('BANDWIDTH');
							var P1L = parseInt(record.get('P1L'));
							var P2L = parseInt(record.get('P2L'));
							var P3L = parseInt(record.get('P3L'));
							var P4L = parseInt(record.get('P4L'));


							if (typeof P1L !== 'undefined' && typeof P2L !== 'undefined') {
								if (Math.abs(P1L - P2L) >= 2) {
									hasError = true;
								}
							}

							if (typeof P3L !== 'undefined' && typeof P4L !== 'undefined' && (P3L > 0 || P4L > 0)) {
								if (typeof P1L !== 'undefined' && typeof P3L !== 'undefined') {
									if (Math.abs(P1L - P3L) >= 2) {
										hasError = true;
									}
								}
								if (typeof P1L !== 'undefined' && typeof P4L !== 'undefined') {
									if (Math.abs(P1L - P4L) >= 2) {
										hasError = true;
									}
								}
								if (typeof P2L !== 'undefined' && typeof P3L !== 'undefined') {
									if (Math.abs(P2L - P3L) >= 2) {
										hasError = true;
									}
								}
								if (typeof P2L !== 'undefined' && typeof P4L !== 'undefined') {
									if (Math.abs(P2L - P4L) >= 2) {
										hasError = true;
									}
								}
								if (typeof P3L !== 'undefined' && typeof P4L !== 'undefined') {
									if (Math.abs(P3L - P4L) >= 2) {
										hasError = true;
									}
								}
							}



							if (hasError) {
								Ext.MessageBox.show({
									title: 'Error',
									msg: ('<font color=red><b>Cross connection error!</b></font>'),
									buttons: Ext.MessageBox.OK
								});
								return 'error_row';
							}

							return 'none';
						}
					}
					//	 listeners: {}
				});
				var WCableLenght_form = new Ext.form.FormPanel({
					id: WCableLenght_1 + 'form',
					layout: 'form',
					labelWidth: 150,
					autoWidth: true,
					height: WCableLenght_grid.height + 3,
					//                    autoHeight: true,
					autoScroll: true,
					items: [WCableLenght_grid]
				});
				var WCableLenght_func1 = function (i) {
					var v = Ext.getCmp(i);
					v.store.load({ callback: function () { v.setValue(v.getValue()) } });
				};

				CableLenght.superclass.constructor.call(this,
					{
						layout: 'form',
						title: 'FTTB_LKD_lenght',
						width: 1300,
						//height: 470,
						autoHeight: true,
						resizable: false,
						plain: true,
						bodyStyle: 'padding:5px 5px 0',
						items: [
							WCableLenght_form
						],
						tbar: [
							WCableLenght_form.add(
								new Ext.form.ComboBox({
									id: 'commutator',
									fieldLabel: 'commutator_field',
									triggerAction: 'all',
									width: WCableLenght_width,
									store: new Ext.data.JsonStore({
										root: 'data',
										fields: ['ID', 'VALUE'],
										url: './wizard/src/cable_lenght.php',
										baseParams: { action: 'getCommutators' }
									}),
									valueField: 'ID',
									displayField: 'VALUE'
								})
							),
							'-',
							{
								text: 'Get',
								id: 'get_btn',
								handler: function () {
									var commutator_val = Ext.getCmp('commutator').getValue();
									if (commutator_val.length) {
										wcLoad.show();
										Ext.Ajax.request({
											timeout: 1800000,
											url: './wizard/src/cable_lenght.php',
											params: {
												action: 'getDataFromCommutators',
												commutator: commutator_val,
												com_name: Ext.getCmp('commutator').lastSelectionText,
												data: Ext.encode(WCableLenght_form.getForm().getFieldValues(), 'data')
											},
											failure: function () {
												Ext.MessageBox.show({
													title: 'Error',
													msg: (res.message ? res.message : 'Internal error'),
													buttons: Ext.MessageBox.OK
												});
												wcLoad.hide();
												Ext.getCmp('WCableLenght_Save').setDisabled(false);
											},
											success: function (result, action) {
												var res = Ext.decode(result.responseText);
												var store = WCableLenght_grid.getStore();
												store.removeAll();
												wcLoad.hide();
												if (!res.success) {
													Ext.MessageBox.show({
														title: 'Error',
														msg: (res.message ? res.message : 'Internal error'),
														buttons: Ext.MessageBox.OK
													});
													return;
												};
												for (var i in res.data) {
													var u = new store.recordType(res.data[i]);
													store.add(u);
												}
											}
										});
									} else {
										Ext.MessageBox.show({
											title: 'Error',
											msg: 'Please select some commutator',
											buttons: Ext.MessageBox.OK
										});
									}
								}
							}, '->',
							{
								text: 'Last data',
								id: 'getLastData',
								handler: function () {
									var commutator_val = Ext.getCmp('commutator').getValue();
									if (commutator_val.length) {
										wcLoad.show();
										Ext.Ajax.request({
											timeout: 1800000,
											url: './wizard/src/cable_lenght.php',
											params: {
												action: 'getLastData',
												commutator: commutator_val
											},
											failure: function () {
												Ext.MessageBox.show({
													title: 'Error',
													msg: (res.message ? res.message : 'Internal error'),
													buttons: Ext.MessageBox.OK
												});
												wcLoad.hide();
												Ext.getCmp('WCableLenght_Save').setDisabled(false);
											},
											success: function (result, action) {
												var res = Ext.decode(result.responseText);
												var store = WCableLenght_grid.getStore();
												store.removeAll();
												wcLoad.hide();
												if (!res.success) {
													Ext.MessageBox.show({
														title: 'Error',
														msg: (res.message ? res.message : 'Internal error'),
														buttons: Ext.MessageBox.OK
													});
													return;
												};
												for (var i in res.data) {
													var u = new store.recordType(res.data[i]);
													store.add(u);
												}
											}
										});
									} else {
										Ext.MessageBox.show({
											title: 'Error',
											msg: 'Please select some commutator',
											buttons: Ext.MessageBox.OK
										});
									}
								}
							}, '-',
							{
								text: 'Export',
								id: 'WCableLenght_S',
								handler: function () {
									var commutator_val = Ext.getCmp('commutator').getValue();
									if (commutator_val.length) {

										var url = './wizard/src/cable_lenght.php?action=export&commutator=' + encodeURIComponent(commutator_val) + '&commutator_name=' + Ext.getCmp('commutator').lastSelectionText;

										//console.log(url);
										window.location.href = url;

									} else {
										Ext.MessageBox.show({
											title: 'Error',
											msg: 'Please select some commutator',
											buttons: Ext.MessageBox.OK
										});
									}

								}
							}
						],
						bbar: [
							'->',
							{
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
						this.show();
				};
			}
		});

})
