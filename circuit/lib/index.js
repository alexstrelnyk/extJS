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
				fields: ['CIRCUITTYPEID', 'NAME', 'CIRCUITDEFID']
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
				fields: ['BANDWIDTHID', 'NAME']
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

			let nameTextField = Ext.getCmp('name_field');

			let selectedStartLocId = null;  // stores Start loc ID
			let selectedEndLocId = null;
			let selectedStartNodeId = null;
			let selectedEndNodeId = null;
			let selectedTypeId = null;

			function checkAllCombosAndGenerateName() {
				const startLoc = selectedStartLocId;
				const endLoc = selectedEndLocId;
				const startNode = Ext.getCmp('start_node_combo').getRawValue();
				const endNode = Ext.getCmp('end_node_combo').getRawValue();
				const startPort = Ext.getCmp('start_port_combo');
				const endPort = Ext.getCmp('end_port_combo');
				const circuitType = selectedTypeId;

				if (startLoc && endLoc && startNode && endNode && startPort && endPort && circuitType) {
					Ext.Ajax.request({
						url: './tools/wizardCircuit/src/index.php',
						params: {
							action: 'generate_name',
							startLocId: startLoc,
							endLocId: endLoc,
							startNodeName: startNode,
							endNodeName: endNode,
							startPortName: startPort.getRawValue(),
							startPortId: startPort.getValue(),
							endPortName: endPort.getRawValue(),
							endPortId: endPort.getValue(),
							circuitTypeId: circuitType
						},
						success: function (response) {
							const res = Ext.decode(response.responseText);
							if (res.success && res.data && res.data.circuit_name) {
								nameTextField.setValue(res.data.circuit_name);
							}
						},
						failure: function () {
							Ext.Msg.alert('Error', 'Failed to generate circuit name');
						}
					});
				}
			}







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
									id: 'start_loc_combo',
									fieldLabel: 'Start loc',
									xtype: 'combo',
									mode: 'local',
									triggerAction: 'all',
									editable: true,
									minChars: 2,
									store: startLocStore,
									valueField: 'LOCATIONID',
									displayField: 'NAME',
									width: 200,
									listeners: {
										select: function (combo, record) {
											selectedStartLocId = combo.getValue();


											// Reset child fields
											Ext.getCmp('start_node_combo').clearValue();
											Ext.getCmp('start_port_combo').clearValue();

											Ext.Ajax.request({
												url: './tools/wizardCircuit/src/index.php',
												params: {
													action: 'get_loc',
													locid: selectedStartLocId
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
											checkAllCombosAndGenerateName();

										}

									}

								}]
							},
							{
								items: [{
									id: 'loc_type_combo',
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
											Ext.getCmp('bandwidth_combo').clearValue();
											selectedTypeId = combo.getValue();
											checkAllCombosAndGenerateName();
											console.log(record.data);
											const valueToSet = record.data.CIRCUITDEFID;
											const hiddenField = Ext.getCmp('nodedef_hidden');
											if (hiddenField && valueToSet !== undefined) {
												hiddenField.setValue(valueToSet);
											}
										}
									}
								}]
							},
							{
								items: [{
									id: 'end_loc_combo',
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
											selectedEndLocId = combo.getValue();

											Ext.getCmp('end_node_combo').clearValue();
											Ext.getCmp('end_port_combo').clearValue();

											Ext.Ajax.request({
												url: './tools/wizardCircuit/src/index.php',
												params: {
													action: 'get_loc',
													locid: selectedEndLocId
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
											checkAllCombosAndGenerateName();
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
									id: 'start_node_combo',
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
											selectedStartLocId = Ext.getCmp('start_loc_combo').getValue();
											if (selectedStartLocId) {
												startNodeStore.baseParams.locid = selectedStartLocId; // pass the selected Start loc
												startNodeStore.reload();
											} else {
												Ext.Msg.alert('Error', 'Please select a Start loc first');
												return false; // prevent query
											}
										},
										select: function (combo, record) {
											selectedStartNodeId = combo.getValue();

											Ext.getCmp('start_port_combo').clearValue();

											Ext.Ajax.request({
												url: './tools/wizardCircuit/src/index.php',
												params: {
													action: 'get_node',
													locid: selectedStartNodeId
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

											checkAllCombosAndGenerateName();
										}



									}

								}]
							},
							{
								items: [{
									id: 'bandwidth_combo',
									fieldLabel: 'Bandwidth',
									xtype: 'combo',
									mode: 'remote',
									triggerAction: 'all',
									editable: true,
									minChars: 2,
									store: portBandwidthStore,
									valueField: 'BANDWIDTHID',
									displayField: 'NAME',
									width: 200,
									listeners: {
										beforequery: function () {
											selectedTypeId = Ext.getCmp('loc_type_combo').getValue();
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
											checkAllCombosAndGenerateName();
										}
									}
								}]
							},
							{
								items: [{
									id: 'end_node_combo',
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
											selectedEndLocId = Ext.getCmp('end_loc_combo').getValue();
											if (!selectedEndLocId) {
												Ext.Msg.alert('Error', 'Please select End loc first');
												return false;
											}
											endNodeStore.baseParams.locid = selectedEndLocId; // inject param
											endNodeStore.load();
										},
										select: function (combo, record) {
											selectedEndNodeId = combo.getValue();

											Ext.getCmp('end_port_combo').clearValue();

											Ext.Ajax.request({
												url: './tools/wizardCircuit/src/index.php',
												params: {
													action: 'get_node',
													locid: selectedEndNodeId
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
											checkAllCombosAndGenerateName();
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
									id: 'start_port_combo',
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
											checkAllCombosAndGenerateName();
										}
									}
								}]
							},
							{
								items: [
									nameTextField = new Ext.form.TextField({
										id: 'name_field',
										fieldLabel: 'Name',
										width: 200
									})
								]
							},
							{
								items: [
									{
										xtype: 'hidden',
										id: 'nodedef_hidden'
									}
								]
							},

							{
								items: [{
									id: 'end_port_combo',
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
											checkAllCombosAndGenerateName();
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
							const name = Ext.getCmp('name_field').getValue();
							const startLocId = Ext.getCmp('start_loc_combo').getValue();
							const startPortId = Ext.getCmp('start_port_combo').getValue();
							const endLocId = Ext.getCmp('end_loc_combo').getValue();
							const endPortId = Ext.getCmp('end_port_combo').getValue();
							const startNodeId = Ext.getCmp('start_node_combo').getValue();
							const startPortName = Ext.getCmp('start_port_combo').getRawValue();
							const endNodeId = Ext.getCmp('end_node_combo').getValue();
							const endPortName = Ext.getCmp('end_port_combo').getRawValue();
							const bandwidthId = Ext.getCmp('bandwidth_combo').getValue();
							const circuitdef = Ext.getCmp('nodedef_hidden').getValue();
							const circuitTypeId = Ext.getCmp('loc_type_combo').getValue();

							// Validate required fields
							if (!startPortId || !endPortId || !startNodeId || !startPortName || !endNodeId || !endPortName || !circuitTypeId) {
								Ext.Msg.alert('Error', 'Please fill in all required fields.');
								return;
							}

							Ext.Ajax.request({
								url: './tools/wizardCircuit/src/index.php',
								method: 'POST',
								params: {
									action: circuit_form_id ? 'update_circuit' : 'create_circuit',
									circuitId: circuit_form_id,
									name: name,
									startLocId: startLocId,
									startPortId: startPortId,
									endLocId: endLocId,
									startNodeId: startNodeId,
									endPortId: endPortId,
									startPortName: startPortName,
									endNodeId: endNodeId,
									endPortName: endPortName,
									bandwidthId: bandwidthId,
									circuitdef: circuitdef,
									circuitTypeId: circuitTypeId
								},
								success: function (response) {
									const res = Ext.decode(response.responseText);
									if (res.success) {
										Ext.Msg.alert('Success', 'Circuit ' + (circuit_form_id ? 'updated' : 'created') + ' successfully: ' + res.data.circuit_id);
									} else {
										Ext.Msg.alert('Error', res.message || 'Unknown error occurred.');
									}
								},
								failure: function () {
									Ext.Msg.alert('Error', 'Failed to communicate with the server.');
								}
							});
						}
					},
					{ text: 'Cancel', handler: function () { Ext.getCmp('circuit_form_title').close(); } }
				]
			});

			function setComboValue1(comboId, value) {
				const combo = Ext.getCmp(comboId);
				if (!combo) {
					console.error(`ComboBox с id "${comboId}" не найден.`);
					return;
				}

				const store = combo.getStore();
				if (!store) {
					console.error(`У ComboBox "${comboId}" нет store.`);
					return;
				}

				if (store.getCount() > 0) {
					combo.setValue(value);
					return;
				}

				store.load({
					callback: function (records, operation, success) {
						if (success) {
							combo.setValue(value);
						} else {
							console.error(`could not load store for combo "${comboId}".`);
						}
					}
				});
			}

			function setComboWithDependency(parentComboId, parentValue, childComboId, childValue, paramName = 'locid', extraFieldMapping = null) {
				return new Promise((resolve, reject) => {
					const parentCombo = Ext.getCmp(parentComboId);
					const childCombo = Ext.getCmp(childComboId);

					if (!parentCombo || !childCombo) {
						console.error('Combo not found');
						reject('Combo not found');
						return;
					}

					const parentStore = parentCombo.getStore();
					const childStore = childCombo.getStore();

					parentStore.load({
						callback: function () {
							parentCombo.setValue(parentValue);

							if (extraFieldMapping) {
								const recIndex = parentStore.find(parentCombo.valueField, parentValue);
								if (recIndex !== -1) {
									const rec = parentStore.getAt(recIndex);
									const extraValue = rec.get(extraFieldMapping.fromField);
									const targetCmp = Ext.getCmp(extraFieldMapping.toCmpId);
									if (targetCmp) {
										targetCmp.setValue(extraValue);
									}
								}
							}

							childStore.baseParams[paramName] = parentValue;
							childStore.load({
								callback: function () {
									const index = childStore.find(childCombo.valueField, childValue);
									if (index !== -1) {
										const record = childStore.getAt(index);
										childCombo.setValue(childValue);
										childCombo.fireEvent('select', childCombo, record);
									}
									console.log(parentComboId, childComboId, index, childValue);
									resolve();
								},
								failure: reject
							});
						},
						failure: reject
					});
				});
			}





			this.initWizard = function (cfg) {
				if (cfg.objectId?.key === 'circ') {
					circuit_form_id = cfg.objectId.id;
					Ext.Ajax.request({
						url: './tools/wizardCircuit/src/index.php',
						params: { action: 'get_circuit', id: cfg.objectId.id },
						success: function (result) {
							const res = Ext.decode(result.responseText);
							// console.log(res.data.length);
							if (res.success === true && res.data.length > 0) {
								row = res.data[0];
								console.log(row);
								Ext.getCmp('name_field').setValue(row.NAME);

								(async () => {
									try {
										await setComboWithDependency(
											'start_loc_combo',
											row.CIRCUIT2STARTLOCATION,
											'start_node_combo',
											row.CIRCUIT2STARTNODE
										);

										await setComboWithDependency(
											'start_node_combo',
											row.CIRCUIT2STARTNODE,
											'start_port_combo',
											row.CIRCUIT2STARTPORT,
											'nodeid'
										);

										await setComboWithDependency(
											'loc_type_combo',
											row.CIRCUIT2CIRCUITTYPE,
											'bandwidth_combo',
											row.CIRCUIT2BANDWIDTH,
											'typeid',
											{
												fromField: 'CIRCUITDEFID',
												toCmpId: 'nodedef_hidden'
											}
										);


										await setComboWithDependency(
											'end_loc_combo',
											row.CIRCUIT2ENDLOCATION,
											'end_node_combo',
											row.CIRCUIT2ENDNODE
										);

										await setComboWithDependency(
											'end_node_combo',
											row.CIRCUIT2ENDNODE,
											'end_port_combo',
											row.CIRCUIT2ENDPORT,
											'nodeid'
										);
									} catch (err) {
										console.error('Error combo setup:', err);
									}
								})();


							}
						}
					});
				}
				this.show();
			};
		}
	});
});
