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
 * @class Editor.view.qmsubsegments.Window
 * @extends Ext.window.Window
 * @initalGenerated
 */
Ext.define('Editor.view.qmsubsegments.Window', {
    extend: 'Ext.window.Window',
    alias: 'widget.qmSummaryWindow',
    requires: ['Editor.view.qmsubsegments.SummaryTree'],
    height: 612,
    layout: 'fit',
    itemId: 'qmsummaryWindow',
    width: 1024,
    title: 'QM Subsegment Statistik',
    modal: true,
    tab_title_source: '#UT# In Ausgangstext',
    tab_title_target: '#UT# In Zieltext',

    initComponent: function() {
        var me = this;
        if(Editor.data.task.isSourceEditable()) {
            me.items = [me.getTabbed()];
        }
        else {
            me.items = [me.getTarget()];
        }
        me.callParent(arguments);
    },
    getTarget: function() {
        var me = this;
        return {
            xtype: 'qmSummaryTree',
            //creating each time a new store is ugly but simplifies the handling with different tasks and refreshing the store content
            store: Ext.create('Editor.store.QmSummary',{
                storeId: 'QmSummary'
            }),
            title: me.tab_title_target
        };
    },
    getTabbed: function() {
        var me = this;
        return {
            xtype: 'tabpanel',
            items: [me.getTarget(),{
                xtype: 'qmSummaryTree',
                //own store for source summary, add type parameter to this store.
                store: Ext.create('Editor.store.QmSummary',{
                    storeId: 'QmSummarySource',
                    proxy: Ext.applyIf({
                        extraParams: {
                            type: 'source'
                        }
                    }, Editor.store.QmSummary.prototype.proxy)
                }),
                title: me.tab_title_source
            }]
        };
    }
});