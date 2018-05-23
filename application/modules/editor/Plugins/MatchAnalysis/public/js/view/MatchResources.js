
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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.plugins.MatchAnalysis.view.MatchResources
 * @extends Ext.form.Panel
 */
Ext.define('Editor.plugins.MatchAnalysis.view.MatchResources', {
    extend:'Ext.panel.Panel',
    alias: 'widget.matchResourcesPanel',
    controller: 'matchResourcesPanel',
    requires: [
        'Editor.plugins.MatchAnalysis.view.MatchResourcesViewController',
        'Editor.plugins.MatchResource.view.TaskAssocPanel'
    ],
    mixins:['Editor.controller.admin.IWizardCard'],
    importType:'postimport',
    
    task:null,
    
    strings:{
        wizardTitle:'#UT#Match Resources'
    },
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                    items: [{
                        xtype: 'matchResourceTaskAssocPanel',
                    }]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([ config ]);
    },
    //called when next button is clicked
    triggerNextCard:function(activeItem){
        this.getController().handleNextCardClick();
    },
    //called when skip button is clicked
    triggerSkipCard:function(activeItem){
        this.getController().handleSkipCardClick();
    },

    disableSkipButton:function(){
        return true;
    },
    
    disableContinueButton:function(){
        return false;
    },
    
    disableAddButton:function(){
        return true;
    },
    
    disableCancelButton:function(){
        return false;
    }
    
});