
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
 * @class Editor.plugins.MatchResource.model.TmMt
 * @extends Ext.data.Model
 */
Ext.define('Editor.plugins.MatchResource.model.TmMt', {
  extend: 'Ext.data.Model',
  STATUS_LOADING: 'loading',
  STATUS_ERROR: 'error',
  STATUS_AVAILABLE: 'available',
  STATUS_UNKNOWN: 'unknown',
  STATUS_NOCONNECTION: 'noconnection',
  STATUS_IMPORT: 'import',
  STATUS_NOTLOADED: 'notloaded',
  fields: [
    {name: 'id', type: 'int'},
    {name: 'entityVersion', type: 'integer', critical: true},
    {name: 'name', type: 'string'},
    {name: 'sourceLang', type: 'string'},
    {name: 'targetLang', type: 'string'},
    {name: 'color', type: 'string'},
    {name: 'resourceId', type: 'string'},
    {name: 'status', type: 'string', persist: false},
    {name: 'statusInfo', type: 'string', persist: false},
    {name: 'serviceName', type: 'string'},
    {name: 'serviceType', type: 'string'},
    {name: 'searchable', type: 'boolean'}
  ],
  idProperty: 'id',
  proxy : {
    type : 'rest',//POST for create, GET to get a entity, DELETE to delete an entity, PUT call to edit an entity 
    url: Editor.data.restpath+'plugins_matchresource_tmmt', //same as PHP controller name
    reader : {
      rootProperty: 'rows',
      type : 'json'
    },
    writer: {
      encode: true,
      rootProperty: 'data',
      writeAllFields: false
    }
  }
});