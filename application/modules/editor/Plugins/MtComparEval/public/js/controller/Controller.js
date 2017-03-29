
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3
			 http://www.gnu.org/licenses/agpl.html

END LICENSE AND COPYRIGHT
*/

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Die Einstellungen werden in einem Cookie gespeichert
 * @class Editor.controller.Preferences
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.mtComparEval.controller.Controller', {
  extend : 'Ext.app.Controller',
  views: ['Editor.plugins.mtComparEval.view.Panel'],
  models: ['Editor.plugins.mtComparEval.model.Taskmeta'],
  refs: [{
      ref: 'taskTabs',
      selector: 'adminTaskPreferencesWindow > tabpanel'
  },{
      ref: 'resultBox',
      selector: 'mtComparEvalPanel #resultBox'
  },{
      ref: 'startButton',
      selector: 'mtComparEvalPanel button#sendto'
  }],
  init : function() {
    this.control({
        'adminTaskPreferencesWindow': {
            render: this.onParentRender,
            close: this.onParentClose
        },
        'adminTaskPreferencesWindow mtComparEvalPanel button#sendto': {
            click: this.handleStartButton
        }
    });
    
  },
  handleStartButton: function() {
      var me = this;
      me.meta.set('mtCompareEvalState', me.meta.STATE_IMPORTING);
      me.showWaitingForImport();
      me.meta.save({
          success: function() {
              me.startWaitingForImport();
          },
          failure: function() {
              var bar = me.getResultBox().down('progressbar');
              bar && bar.destroy();
              me.showResult('Could not sent Task to MT-ComparEval, try again!');
              me.getStartButton().enable();
          }
      });
  },
  onParentClose: function() {
      if(this.checkImportStateTask) {
          Ext.TaskManager.stop(this.checkImportStateTask);
          delete this.checkImportStateTask;
      }
  },
  /**
   * Checks if all actually loaded tasks are imported completly
   */
  checkImportState: function() {
      var me = this, 
          metaReloaded = function(rec) {
              if(rec.isImporting()) {
                  return;
              }
              var box = me.getResultBox(),
                  bar = box ? box.down('.progressbar') : false;
              me.showImportedMessage(rec);
              bar && bar.destroy();
              Ext.TaskManager.stop(me.checkImportStateTask);
              delete me.checkImportStateTask;
          };
      me.meta.load({
          success: metaReloaded
      });
  },
  showImportedMessage: function(rec) {
      var me = this, 
          msg = 'MT-ComparEval has imported translate5 Task "{0}" as experiment nr {1}.<br /><br /><a href="{2}" target="_blank">open results in MT-ComparEval</a><br /><br />';
      me.showResult(Ext.String.format(msg, me.actualTask.get('taskName'), rec.get('mtCompareEvalId'), rec.get('mtCompareURL')));
      me.getStartButton().setText('Resend Task to MT-ComparEval');
      me.getStartButton().enable();
  },
  showWaitingForImport: function() {
      var me = this;
      me.showResult('');
      me.getResultBox().add({
          xtype: 'progressbar',
          width:250
      }).wait({
          interval: 1000,
          text: 'Importing Task in MT-ComparEval!'
      });
      me.getStartButton().disable();
  },
  startWaitingForImport: function() {
      var me = this;
      if(!me.getResultBox()) {
          return;
      }
      if(!me.checkImportStateTask) {
          me.checkImportStateTask = {
                  run: me.checkImportState,
                  scope: me,
                  interval: 10000
          };
      }
      Ext.TaskManager.start(me.checkImportStateTask);
  },
  showResult: function(msg) {
      this.getResultBox().update(msg);
  },
  /**
   * inject the plugin tab and load the task meta data set
   */
  onParentRender: function(window) {
      var me = this;
      me.actualTask = window.actualTask;
      me.meta = Editor.plugins.mtComparEval.model.Taskmeta.load(me.actualTask.get('taskGuid'), {
          success: function(rec) {
              me.meta = rec;
              if(rec.isImporting()) {
                  me.showWaitingForImport();
                  me.startWaitingForImport();
              }
              if(rec.isImported()) {
                  me.showImportedMessage(rec);
              }
          },
          failure: function() {
              me.showResult('Could not load MT-ComparEval information for this task!');
          }
      });
      this.getTaskTabs().add({xtype: 'mtComparEvalPanel', actualTask: me.actualTask});
  }
});
