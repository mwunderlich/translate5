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
/**
 * @class Editor.model.admin.TaskUserAssoc
 * @extends Ext.data.Model
 */
Ext.define('Editor.model.admin.TaskUserAssoc', {
  extend: 'Ext.data.Model',
  fields: [
    {name: 'id', type: 'int'},
    {name: 'taskGuid', type: 'string'},
    {name: 'userGuid', type: 'string'},
    {name: 'login', type: 'string', persist: false},
    {name: 'surName', type: 'string', persist: false},
    {name: 'firstName', type: 'string', persist: false},
    {name: 'longUserName', type: 'string', persist: false, convert: function(v, rec) {
        return Editor.model.admin.User.getLongUserName(rec);
    }},
    {name: 'state', type: 'string'},
    {name: 'role', type: 'string'}
  ],
  validations: [
      {type: 'presence', field: 'taskGuid'},
      {type: 'presence', field: 'userGuid'}//,
      //FIXME make me dynamic? {type: 'inclusion', field: 'state', list: Ext.Object.getKeys(Editor.data.app.utStates)},
      //FIXME make me dynamic? {type: 'inclusion', field: 'role', list: Ext.Object.getKeys(Editor.data.app.utRoles)}
  ],
  idProperty: 'id',
  proxy : {
    type : 'rest',
    url: Editor.data.restpath+'taskuserassoc',
    reader : {
      root: 'rows',
      type : 'json'
    },
    writer: {
      encode: true,
      root: 'data',
      writeAllFields: false
    }
  }
});