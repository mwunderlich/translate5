
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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

Ext.define('Editor.view.admin.task.PreferencesWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.adminTaskPreferencesWindow',
    requires: [
               'Editor.view.admin.task.PreferencesWindowViewModel',
               'Editor.view.admin.task.UserAssoc',
               'Editor.view.admin.task.Preferences',
               'Editor.view.admin.task.TaskAttributes'
               ],
    itemId: 'adminTaskPreferencesWindow',
    title: '#UT#Einstellungen zu Aufgabe "{0}"',
    strings: {
        close: '#UT#Fenster schließen'
    },
    layout: 'fit',
    modal: true,
    viewModel: {
        type: 'taskpreferences'
    },
    initComponent: function() {
        var me = this,
            vm = me.lookupViewModel();
        vm.set('currentTask', me.actualTask);
        me.callParent(arguments);
    },
    initConfig: function(instanceConfig) {
        var me = this,
            task = me.initialConfig.actualTask,
            auth = Editor.app.authenticatedUser,
            tabs = [],
            config;
        
        if(auth.isAllowed('editorChangeUserAssocTask')) {
            tabs.push({
                xtype: 'adminTaskUserAssoc'
            });
        }
        if(auth.isAllowed('editorWorkflowPrefsTask')) {
            tabs.push({
                xtype: 'editorAdminTaskPreferences'
            });
        }

        if(auth.isAllowed('editorEditTaskRealDeliveryDate') || 
            auth.isAllowed('editorEditTaskOrderDate') || 
            auth.isAllowed('editorEditTaskPm') || 
            auth.isAllowed('editorEditTaskDeliveryDate') ||
            auth.isAllowed('editorEditTaskTaskName')){
            tabs.push({
                xtype: 'taskattributes'
            });
        }
        
        config = {
            height: Math.min(800, parseInt(Ext.getBody().getViewSize().height * 0.8)),
            width: 1000,
            title: Ext.String.format(me.title, task.get('taskName')),
            items : [{
                xtype: 'tabpanel',
                activeTab: 0,
                items: tabs
            }],
            dockedItems: [{
                xtype : 'toolbar',
                dock : 'bottom',
                layout: {
                    type: 'hbox',
                    pack: 'end'
                },
                items : [{
                    xtype : 'button',
                    iconCls : 'ico-cancel',
                    itemId : 'close-btn',
                    text : me.strings.close
                }]
            }]
        };

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
});
