Ext.onReady(function () {
	Ext.QuickTips.init();

	circuit_form_main = Ext.extend(Ext.Window, {
		constructor: function (cnf) {
			var circuit_form_id;

			const startLocStore = new Ext.data.JsonStore({
				url: './tools/wizardCircuit/src/index.php',
				baseParams: { action: 'get_loc' },
				root: 'data',
				fields: ['LOCATIONID', 'NAME']
			});
			const endLocStore = new Ext.data.JsonStore({
				url: './tools/wizardCircuit/src/index.php',
				baseParams: { action: 'get_loc' },
				root: 'data',
				fields: ['LOCATIONID', 'NAME']
			});
			const locTypeStore = new Ext.data.JsonStore({
				url: './tools/wizardCircuit/src/index.php',
				baseParams: { action: 'get_loc_type' },
				root: 'data',
				fields: ['CIRCUITTYPEID', 'NAME']
			});
			const startNodeStore = new Ext.data.JsonStore({
				url: './tools/wizardCircuit/src/index.php',
				baseParams: { action: 'get_node' },
				root: 'data',
				fields: ['NODEID', 'NAME']
			});
			const endNodeStore = new Ext.data.JsonStore({
				url: './tools/wizardCircuit/src/index.php',
				baseParams: { action: 'get_node' },
				root: 'data',
				fields: ['NODEID', 'NAME']
			});
			const startPortStore = new Ext.data.JsonStore({
				url: './tools/wizardCircuit/src/index.php',
				baseParams: { action: 'get_port' },
				root: 'data',
				fields: ['PORTID', 'NAME']
			});
			const endPortStore = new Ext.data.JsonStore({
				url: './tools/wizardCircuit/src/index.php',
				baseParams: { action: 'get_port' },
				root: 'data',
				fields: ['PORTID', 'NAME']
			});
			const portBandwidthStore = new Ext.data.JsonStore({
				url: './tools/wizardCircuit/src/index.php',
				baseParams: { action: 'get_port_bandwidth' },
				root: 'data',
				fields: ['CIRCUITTYPEBANDWIDTHID', 'CTB2BANDWIDTH']
			});


			// Shared combo config
			const comboDefaults = {
				xtype: 'combo',
				mode: 'local',
				triggerAction: 'all',
				editable: false,
				store: new Ext.data.ArrayStore({
					fields: ['LOCATIONID', 'NAME'],
				}),
				valueField: 'LOCATIONID',
				displayField: 'NAME',
				width: 200
			};

			// Will reference the textfield for "Name"
			let nameTextField;

			let selectedStartLocId = null;  // stores Start loc ID
			let selectedEndLocId = null;
			let selectedStartNodeId = null;
			let selectedEndNodeId = null;
			let selectedTypeId = null;





			const paddingBlock = {
				style: 'background-color: transparent; padding: 12px;',
				layout: 'column',
				border: false,
				defaults: {
					columnWidth: .33,
					layout: 'form',
					border: false,
					style: 'padding: 0 12px;'
				}
			};

			circuit_form_main.superclass.constructor.call(this, {
				id: 'circuit_form_title',
				title: 'Forma 10',
				width: 1050,
				height: 360,
				autoScroll: true,
				layout: 'form',
				border: false,
				plain: true,
				bodyStyle: 'background-color: transparent; padding: 5px;',
				buttonAlign: 'center',
				minimizable: true,
				items: [
					{
						...paddingBlock,
						items: [
							{
								items: [{
									fieldLabel: 'Start loc',
									xtype: 'combo',
									mode: 'remote',
									triggerAction: 'all',
									editable: true,
									minChars: 2,
									store: startLocStore,
									valueField: 'LOCATIONID',
									displayField: 'NAME',
									width: 200,
									listeners: {
										select: function (combo, record) {
											const selectedValue = combo.getValue();
											selectedStartLocId = selectedValue;


											// Reset child fields
											Ext.getCmp('start_node_combo').clearValue();
											Ext.getCmp('start_port_combo').clearValue();

											Ext.Ajax.request({
												url: './tools/wizardCircuit/src/index.php',
												params: {
													action: 'get_loc',
													locid: selectedValue
												},
												success: function (response) {
													const res = Ext.decode(response.responseText);
													if (res.success && res.name) {
														nameTextField.setValue(res.name);
													}
												},
												failure: function () {
													Ext.Msg.alert('Error', 'Failed to load Loc name');
												}
											});
										}

									}

								}]
							},
							{
								items: [{
									fieldLabel: 'Type',
									xtype: 'combo',
									mode: 'remote',
									triggerAction: 'all',
									editable: true,
									minChars: 2,
									store: locTypeStore,
									valueField: 'CIRCUITTYPEID',
									displayField: 'NAME',
									width: 200,
									listeners: {
										select: function (combo, record) {
											const selectedValue = combo.getValue();
											selectedTypeId = selectedValue;
											Ext.Ajax.request({
												url: './tools/wizardCircuit/src/index.php',
												params: {
													action: 'get_loc_type',
													locid: selectedValue
												},
												success: function (response) {
													const res = Ext.decode(response.responseText);
													if (res.success && res.name) {
														nameTextField.setValue(res.name);
													}
												},
												failure: function () {
													Ext.Msg.alert('Error', 'Failed to load Loc name');
												}
											});
										}
									}
								}]
							},
							{
								items: [{
									fieldLabel: 'End loc',
									xtype: 'combo',
									mode: 'remote',
									triggerAction: 'all',
									editable: true,
									minChars: 2,
									store: endLocStore,
									valueField: 'LOCATIONID',
									displayField: 'NAME',
									width: 200,
									listeners: {
										select: function (combo, record) {
											const selectedValue = combo.getValue();
											selectedEndLocId = selectedValue;

											Ext.getCmp('end_node_combo').clearValue();
											Ext.getCmp('end_port_combo').clearValue();

											Ext.Ajax.request({
												url: './tools/wizardCircuit/src/index.php',
												params: {
													action: 'get_loc',
													locid: selectedValue
												},
												success: function (response) {
													const res = Ext.decode(response.responseText);
													if (res.success && res.name) {
														nameTextField.setValue(res.name);
													}
												},
												failure: function () {
													Ext.Msg.alert('Error', 'Failed to load Loc name');
												}
											});
										}


									}
								}]
							}
						]
					},
					{
						...paddingBlock,
						items: [
							{
								items: [{
									fieldLabel: 'Start node',
									xtype: 'combo',
									mode: 'remote',
									triggerAction: 'all',
									editable: true,
									minChars: 2,
									valueField: 'NODEID',
									displayField: 'NAME',
									width: 200,
									store: startNodeStore,
									listeners: {
										beforequery: function () {
											if (selectedStartLocId) {
												startNodeStore.baseParams.locid = selectedStartLocId; // pass the selected Start loc
												startNodeStore.reload();
											} else {
												Ext.Msg.alert('Error', 'Please select a Start loc first');
												return false; // prevent query
											}
										},
										select: function (combo, record) {
											const selectedValue = combo.getValue();
											selectedStartNodeId = selectedValue;

											Ext.getCmp('start_port_combo').clearValue();

											Ext.Ajax.request({
												url: './tools/wizardCircuit/src/index.php',
												params: {
													action: 'get_node',
													locid: selectedValue
												},
												success: function (response) {
													const res = Ext.decode(response.responseText);
													if (res.success && res.name) {
														nameTextField.setValue(res.name);
													}
												},
												failure: function () {
													Ext.Msg.alert('Error', 'Failed to load Node name');
												}
											});
										}


									}

								}]
							},
							{
								items: [{
									fieldLabel: 'Bandwidth',
									xtype: 'combo',
									mode: 'remote',
									triggerAction: 'all',
									editable: true,
									minChars: 2,
									store: portBandwidthStore,
									valueField: 'CIRCUITTYPEBANDWIDTHID',
									displayField: 'CTB2BANDWIDTH',
									width: 200,
									listeners: {
										beforequery: function () {
											if (!selectedTypeId) {
												Ext.Msg.alert('Error', 'Please select Type first');
												return false;
											}
											portBandwidthStore.baseParams.locid = selectedTypeId;
											portBandwidthStore.reload();
										},
										select: function (combo, record) {
											const selectedValue = combo.getValue();
											Ext.Ajax.request({
												url: './tools/wizardCircuit/src/index.php',
												params: {
													action: 'get_port_bandwidth',
													typeid: selectedValue
												},
												success: function (response) {
													const res = Ext.decode(response.responseText);
													if (res.success && res.name) {
														nameTextField.setValue(res.name);
													}
												},
												failure: function () {
													Ext.Msg.alert('Error', 'Failed to load port Bandwidth');
												}
											});
										}
									}
								}]
							},
							{
								items: [{
									fieldLabel: 'End node',
									xtype: 'combo',
									mode: 'remote',
									triggerAction: 'all',
									editable: true,
									minChars: 2,
									store: endNodeStore,
									valueField: 'NODEID',
									displayField: 'NAME',
									width: 200,
									listeners: {
										beforequery: function () {
											if (!selectedEndLocId) {
												Ext.Msg.alert('Error', 'Please select End loc first');
												return false;
											}
											endNodeStore.baseParams.locid = selectedEndLocId; // inject param
											endNodeStore.load();
										},
										select: function (combo, record) {
											const selectedValue = combo.getValue();
											selectedEndNodeId = selectedValue;

											Ext.getCmp('end_port_combo').clearValue();

											Ext.Ajax.request({
												url: './tools/wizardCircuit/src/index.php',
												params: {
													action: 'get_node',
													locid: selectedValue
												},
												success: function (response) {
													const res = Ext.decode(response.responseText);
													if (res.success && res.name) {
														nameTextField.setValue(res.name);
													}
												},
												failure: function () {
													Ext.Msg.alert('Error', 'Failed to load Node name');
												}
											});
										}


									}

								}]
							}
						]
					},
					{
						...paddingBlock,
						items: [
							{
								items: [{
									fieldLabel: 'Start port',
									xtype: 'combo',
									mode: 'remote',
									triggerAction: 'all',
									editable: true,
									minChars: 2,
									store: startPortStore,
									valueField: 'PORTID',
									displayField: 'NAME',
									width: 200,
									listeners: {
										beforequery: function () {
											if (!selectedStartNodeId) {
												Ext.Msg.alert('Error', 'Please select Start node first');
												return false;
											}
											startPortStore.baseParams.nodeid = selectedStartNodeId;
											startPortStore.load();
										},
										select: function (combo, record) {
											const selectedValue = combo.getValue();
											Ext.Ajax.request({
												url: './tools/wizardCircuit/src/index.php',
												params: {
													action: 'get_port',
													nodeid: selectedValue
												},
												success: function (response) {
													const res = Ext.decode(response.responseText);
													if (res.success && res.name) {
														nameTextField.setValue(res.name);
													}
												},
												failure: function () {
													Ext.Msg.alert('Error', 'Failed to load port name');
												}
											});
										}
									}
								}]
							},
							{
								items: [
									nameTextField = new Ext.form.TextField({
										fieldLabel: 'Name',
										width: 200
									})
								]
							},
							{
								items: [{
									fieldLabel: 'End port',
									xtype: 'combo',
									mode: 'remote',
									triggerAction: 'all',
									editable: true,
									minChars: 2,
									store: endPortStore,
									valueField: 'PORTID',
									displayField: 'NAME',
									width: 200,
									listeners: {
										beforequery: function () {
											if (!selectedEndNodeId) {
												Ext.Msg.alert('Error', 'Please select End node first');
												return false;
											}
											endPortStore.baseParams.nodeid = selectedEndNodeId;
											endPortStore.load();
										},
										select: function (combo, record) {
											const selectedValue = combo.getValue();
											Ext.Ajax.request({
												url: './tools/wizardCircuit/src/index.php',
												params: {
													action: 'get_port',
													nodeid: selectedValue
												},
												success: function (response) {
													const res = Ext.decode(response.responseText);
													if (res.success && res.name) {
														nameTextField.setValue(res.name);
													}
												},
												failure: function () {
													Ext.Msg.alert('Error', 'Failed to load port name');
												}
											});
										}
									}
								}]
							}
						]
					}
				],
				buttons: [
					{
						text: 'Save',
						id: 'circuit_form_button2',
						handler: function () {
							Ext.Msg.alert('Info', 'Save clicked');
						}
					},
					{ text: 'Cancel', handler: function () { Ext.getCmp('circuit_form_title').close(); } }
				]
			});

			this.initWizard = function (cfg) {
				if (cfg.objectId?.key === 'locd') {
					Ext.Ajax.request({
						url: './tools/wizardCircuit/src/index.php',
						params: { action: 'getloc', locid: cfg.objectId.id, date_f: cfg.objectId.date },
						success: function (result) {
							const res = Ext.decode(result.responseText);
							if (res.success === true) {
								circuit_form_id = cfg.objectId.id;
							}
						}
					});
					this.show();
				}
			};
		}
	});
});
