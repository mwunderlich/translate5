
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
 * @class Editor.view.segments.column.SegmentNrInTask
 * @extends Editor.view.ui.segments.column.SegmentNrInTask
 * @initalGenerated
 */
Ext.define('Editor.view.segments.column.SegmentNrInTask', {
    extend: 'Ext.grid.column.Column',

    itemId: '',
    width: 50,
    tdCls: 'segmentNrInTask',
    dataIndex: 'segmentNrInTask',
    text: '#UT#Nr.',
    alias: 'widget.segmentNrInTaskColumn',
    mixins: [
        'Editor.view.segments.column.BaseMixin',
        'Editor.view.segments.column.InfoToolTipMixin'
    ],
    isErgonomicVisible: true,
    isErgonomicSetWidth: true,
    ergonomicWidth: 60,
    otherRenderers: null,
    filter: {
        type: 'numeric'
    },
    initComponent: function() {
        this.scope = this; //so that renderer can access this object instead the whole grid.
        this.callParent(arguments);
    },
    
    editor: {
        xtype: 'displayfield',
        getModelData: function() {
            return null;
        },
        ownQuicktip: true,
        renderer: function(value, field) {
            var context = field.ownerCt.context,
                qtip, cell;
            if(context && context.row){
                cell = Ext.fly(context.row).down('td.segmentNrInTask');
                if(cell) {
                    qtip = cell.getAttribute('data-qtip');
                    field.getEl().dom.setAttribute('data-qtip', qtip);
                }
            }
            return value;
        }
    },
    renderer: function(v, meta, record) {
        meta.tdAttr = 'data-qtip="'+this.renderInfoQtip(record)+'"';
        return v;
    }
});