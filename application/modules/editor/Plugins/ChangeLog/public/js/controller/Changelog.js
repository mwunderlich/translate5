
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
 * @class Editor.plugins.MatchResource.controller.Editor
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.ChangeLog.controller.Changelog', {
  extend: 'Ext.app.Controller',
  views: ['Editor.plugins.ChangeLog.view.Changelog'],
  models: ['Editor.plugins.ChangeLog.model.Changelog'],
  stores:['Editor.plugins.ChangeLog.store.Changelog'],
  btnText: '#UT#Änderungsprotokoll der Version ',
  refs:[{
      ref: 'ChangeLogWindow',
      selector: '#changeLogWindow'
  }],
  listen: {
      component:{
    	'#adminTaskGrid #pageingtoolbar':{
    		render:'addButtonToTaskOverviewToolbar'
    	},
    	'#btnCloseWindow':{
    		click:'btnCloseWindowClick'
    	},
    	'#adminTaskGrid #pageingtoolbar #changelogbutton':{
    	    click:'changeLogButtonClick'
    	}
    	
      }
  },
  init: function(){
      var me = this;
      me.callParent(arguments);
  },
  addButtonToTaskOverviewToolbar:function(pageingToolbar,event){
      var me = this,
          changelogfilter;
      pageingToolbar.add(['-',{
          xtype:'button',
          itemId:'changelogbutton',
          text: me.btnText+Editor.data.app.version
      }]);
      
      if(Editor.data.plugins.ChangeLog.lastSeenChangelogId>0){
          changelogfilter='[{"operator":"gt","value":'+Editor.data.plugins.ChangeLog.lastSeenChangelogId+',"property":"id"}]'; 
          me.loadChangelogStore(changelogfilter);
      }
      if(Editor.data.plugins.ChangeLog.lastSeenChangelogId<0){
          me.loadChangelogStore();
      }
  },
  changeLogButtonClick:function(){
      var me=this;
      me.loadChangelogStore();
  },
  loadChangelogStore:function(initalFilter){
      var me = this, win,
          store = me.getEditorPluginsChangeLogStoreChangelogStore(),
          params = {};
      if(initalFilter) {
          params.filter = initalFilter;
          //disable paging, if we want paging on initial load, 
          // we have to change the lastInsertedid in PHP (without max then)
          params.limit = 0; 
      }
      
      //for window creation the store suppressNextFilter must be set to true, otherwise the rendering with filters would trigger a load
      win = me.getChangeLogWindow() || Ext.widget('changeLogWindow',{changeLogStore: store});
      //disable the suppressing again after store init, so that filters can process normally
      store.suppressNextFilter = false;
      store.clearFilter();
      store.load({
          params: params,
          scope: me,
          callback: function(records, operation, success) {
             if(records && records.length>0){
                 win.show();
                 win.down('pagingtoolbar').updateBarInfo();
                 win.down('pagingtoolbar').setVisible(!initalFilter);
             }
          }
       });
  },
  btnCloseWindowClick:function(){
	  this.getChangeLogWindow().close();
  }
});
