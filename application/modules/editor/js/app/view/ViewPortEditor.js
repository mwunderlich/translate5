/*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor Javascript GUI and build on ExtJs 4 lib
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
 
 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty
 for any legal issue, that may arise, if you use these FLOSS exceptions and recommend
 to stick to GPL 3. For further information regarding this topic please see the attached 
 license.txt of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.view.ViewPortEditor
 * @extends Ext.container.Viewport
 */
Ext.define('Editor.view.ViewPortEditor', {
    extend: 'Ext.container.Viewport',
    requires: [
      'Editor.view.fileorder.Tree',
      'Editor.view.fileorder.ReferenceTree',
      'Editor.view.segments.Grid',
      'Editor.view.segments.MetaPanel'
    ],

    layout: {
      type: 'border'
    },

    //Item Strings:
    items_north_title: 'Header',
    items_west_title: '__untranslated__Dateien',
    
    initComponent: function() {
      var me = this,
          items = [me.getNorth(), {
              xtype: 'panel',
              region: 'east',
              itemId: 'metapanel',
              collapsible: true,
              animCollapse: !Ext.isIE, //BugID 3
              layout: {type:'accordion'},
              items: [{
                  xtype: 'segments.metapanel'
              },{
                  xtype: 'commentWindow'
              }],
              width: 260
          },{
              region: 'center',
              xtype: 'segments.grid',
              itemId: 'segmentgrid'
          },{
              xtype: 'panel',
              resizable: true,
              resizeHandles: 'e',
              title: me.items_west_title,
              width: 150,
              collapsible: true,
              layout: {type:'accordion'},
              animCollapse: !Ext.isIE, //BugID 3
              region: 'west',
              itemId: 'filepanel',
              items: [{
                xtype: 'fileorder.tree'
              },{
                xtype: 'referenceFileTree'
              }]
          }];
      Ext.applyIf(me, {
          items: items
      });
      me.callParent(arguments);
    },
    getNorth: function() {
        return {
            xtype: 'headPanel',
            region: 'north'
        };
    }
});