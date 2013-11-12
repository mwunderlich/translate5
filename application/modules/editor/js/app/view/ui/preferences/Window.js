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
/*
 * File: app/view/ui/preferences/Window.js
 *
 * This file was generated by Ext Designer version 1.2.2.
 * http://www.sencha.com/products/designer/
 *
 * This file will be auto-generated each and everytime you export.
 *
 * Do NOT hand edit this file.
 */

Ext.define('Editor.view.ui.preferences.Window', {
  extend: 'Ext.window.Window',

  height: 274,
  itemId: 'preferencesWindow',
  width: 460,
  title: 'Einstellungen',
  modal: true,
  
  //Item Strings:
  item_radiogroup_fieldLabel: 'Verhalten des Wiederholungseditor',
  item_alikeBehaviour_always_boxLabel: 'Immer automatisch ersetzen und Status setzen',
  item_alikeBehaviour_individual_boxLabel: 'Bei jeder Wiederholung einzeln entscheiden',
  item_alikeBehaviour_never_boxLabel: 'Nie automatisch ersetzen und Status setzen',
  item_cancelBtn: 'Abbrechen',
  item_saveBtn: 'Speichern',
  
  initComponent: function() {
    var me = this;

    Ext.applyIf(me, {
      items: [
        {
          xtype: 'form',
          frame: true,
          ui: 'default-framed',
          bodyPadding: 10,
          items: [
            {
              xtype: 'radiogroup',
              fieldLabel: this.item_radiogroup_fieldLabel,
              labelAlign: 'top',
              columns: 1,
              anchor: '100%',
              items: [
                {
                  xtype: 'radiofield',
                  name: 'alikeBehaviour',
                  boxLabel: this.item_alikeBehaviour_always_boxLabel,
                  inputValue: 'always'
                },
                {
                  xtype: 'radiofield',
                  name: 'alikeBehaviour',
                  boxLabel: this.item_alikeBehaviour_individual_boxLabel,
                  inputValue: 'individual'
                },
                {
                  xtype: 'radiofield',
                  name: 'alikeBehaviour',
                  boxLabel: this.item_alikeBehaviour_never_boxLabel,
                  inputValue: 'never'
                }
              ]
            }
          ]
        }
      ],
      dockedItems: [
        {
          xtype: 'toolbar',
          ui: 'footer',
          dock: 'bottom',
          layout: {
            pack: 'end',
            type: 'hbox'
          },
          items: [
            {
              xtype: 'button',
              itemId: 'cancelBtn',
              text: this.item_cancelBtn
            },
            {
              xtype: 'button',
              itemId: 'saveBtn',
              text: this.item_saveBtn
            }
          ]
        }
      ]
    });

    me.callParent(arguments);
  }
});