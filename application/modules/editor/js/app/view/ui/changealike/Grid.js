
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/*
 * File: app/view/ui/changealike/Grid.js
 *
 * This file was generated by Ext Designer version 1.2.3.
 * http://www.sencha.com/products/designer/
 *
 * This file will be auto-generated each and everytime you export.
 *
 * Do NOT hand edit this file.
 */

Ext.define('Editor.view.ui.changealike.Grid', {
  extend: 'Ext.grid.Panel',

  item_segmentNrInTaskColumn: 'Nr.', 
  item_sourceColumn: 'Quelle', 
  item_targetColumn: 'Ziel', 
  item_filterColumn: 'In aktueller Filterung enthalten', 
  item_sourceMatchColumn: 'Quell-Treffer', 
  item_targetMatchColumn: 'Ziel-Treffer', 
  
  initComponent: function() {
    var me = this;

    Ext.applyIf(me, {
      columns: [
        {
          dataIndex: 'segmentNrInTask',
          filter: {
              type: 'numeric'
          },
          text: me.item_segmentNrInTaskColumn,
          width: 50
        },
        {
          xtype: 'gridcolumn',
          dataIndex: 'source',
          filter: {
              type: 'string'
          },
          tdCls: 'alike-source-field segment-tag-column',
          width: 250, 
          renderer: function(value, metaData, record) {
            if(record.get('sourceMatch')) {
              metaData.style = 'font-style: italic;';
            }
            return value;
          },
          text: me.item_sourceColumn
        },
        {
          xtype: 'gridcolumn',
          dataIndex: 'target',
          filter: {
              type: 'string'
          },
          tdCls: 'alike-target-field segment-tag-column',
          width: 250,
          renderer: function(value, metaData, record) {
            if(record.get('targetMatch')) {
              metaData.style = 'font-style: italic;';
            }
            return value;
          },
          text: me.item_targetColumn
        },
        {
          xtype: 'booleancolumn',
          dataIndex: 'infilter',
          filter: {
              type: 'boolean'
          },
          width: 180,
          text: me.item_filterColumn
        },
        {
          xtype: 'booleancolumn',
          dataIndex: 'sourceMatch',
          filter: {
              type: 'boolean'
          },
          width: 90, 
          text: me.item_sourceMatchColumn
        },
        {
          xtype: 'booleancolumn',
          dataIndex: 'targetMatch',
          filter: {
              type: 'boolean'
          },
          width: 80, 
          text: me.item_targetMatchColumn
        }
      ],
      viewConfig: {
        //loadMask: false
      },
      selModel: Ext.create('Ext.selection.CheckboxModel', {
        injectCheckbox: 3
      })
    });

    me.callParent(arguments);
  }
});