
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

Ext.define('Editor.view.admin.task.filter.DateFilter', {
	extend:'Ext.form.FieldSet',
    alias: 'widget.editorAdminTaskFilterDateFilter',
	columnWidth: 0.5,
	defaults: {anchor: '100%'},
	layout: 'anchor',
	title:null,//this should be set from the instance config
	filterLabel:null,//this should be set from the instance config
	filterProperty:null,//this should be set from the instance config
	strings:{
		eqText: '#UT#am',
		gtText: '#UT#nach',
		ltText: '#UT#bevor'
	},
	initConfig: function(instanceConfig) {
        var me = this,
        	config;
        config = {
			title:instanceConfig.title,
			items: [{
				xtype: 'datefield',
				anchor: '100%',
				fieldLabel: me.strings.gtText,
				itemId: 'gtDate',
				filter:{
					 operator: 'gt',
					 property:instanceConfig.filterProperty,
					 type:'date',
					 textLabel:instanceConfig.filterLabel
				 }
			},{
				xtype: 'datefield',
				anchor: '100%',
				fieldLabel: me.strings.ltText,
				itemId: 'ltDate',
				filter:{
					 operator: 'lt',
					 property:instanceConfig.filterProperty,
					 type:'date',
					 textLabel:instanceConfig.filterLabel
				 }
			},{
				xtype: 'datefield',
				anchor: '100%',
				fieldLabel: me.strings.eqText,
				naitemIdme: 'eqDate',
				filter:{
					 operator: 'eq',
					 property:instanceConfig.filterProperty,
					 type:'date',
					 textLabel:instanceConfig.filterLabel
				 }
			}
		]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
	},

	setValue:function(value,record){
		var me=this,
			operator=record.get('operator'),
			field=me.down('#'+operator+'Date');
		field && field.setValue(value);
	}
});
