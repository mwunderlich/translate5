
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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
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
 * Own Tooltip, bindable to iframes per boundFrame option
 * 
 * @class Editor.view.ToolTip
 * @extends Ext.tip.ToolTip
 */
Ext.define('Editor.view.ToolTip', {
    extend : 'Ext.tip.ToolTip',
    //enable own ToolTips only for the following img classes 
    delegate : 'img.ownttip', // accepts only simple selectors (no commas) so
    // define a own tooltip class
    renderTo : Ext.getBody(),
    strings: {
        severity: '#UT#Gewichtung'
    },
    listeners : {
        // Change content dynamically depending on which element triggered
        // the show.
        beforeshow : function(tip) {
            var t = tip.triggerElement,
                fly = Ext.fly(t); 
            if(fly.hasCls('qmflag')) {
                this.handleQmFlag(t, tip);
            } else if (Ext.get(t).hasCls('changemarkup')) {
                this.handleChangeMarkup(t, tip);
            }
            //else if hasClass for other ToolTip Types
        }
    },

    onTargetOver: function(e) {
        e.preventDefault(); //prevent title tags to be shown in IE
        this.callParent(arguments);
    },
    
    handleQmFlag: function(t, tip) {
        var me = this, 
            qmtype,
            cache = Editor.qmFlagTypeCache,
            meta = {sevTitle: me.strings.severity};

        qmtype = t.className.match(/qmflag-([0-9]+)/);
        if(qmtype && qmtype.length > 1) {
            meta.cls = t.className.split(' ');
            meta.sev = Ext.StoreMgr.get('Severities').getById(meta.cls.shift());
            meta.sev = meta.sev ? meta.sev.get('text') : '';
            meta.qmid = qmtype[1];
            meta.comment = Ext.fly(t).getAttribute('data-comment');
            meta.qmtype = cache[meta.qmid] ? cache[meta.qmid] : 'Unknown Type'; //impossible => untranslated
        }
        if(!me.qmflagTpl) {
            me.qmflagTpl = new Ext.Template('<b>{qmtype}</b><br />{sevTitle}: {sev}<br />{comment}');
            me.qmflagTpl.compile();
        }
        tip.update(me.qmflagTpl.apply(meta));		
    },
    
    handleChangeMarkup: function(markupNode, tip) {
        var me = this,
            markupNodeUsername,
            markupNodeTimestamp;
        if (markupNode.hasAttribute('data-username')) {
            markupNodeUsername = markupNode.getAttribute('data-username');
        }
        if (markupNode.hasAttribute('data-timestamp')) {
            markupNodeTimestamp = parseInt(markupNode.getAttribute('data-timestamp'));
        }
        var tplData = {
            changemarkupUser : markupNodeUsername,
            changemarkupDate : Ext.Date.format(new Date(markupNodeTimestamp),'Y-m-d H:i')
        };
        if(!me.changemarkupTpl) {
            me.changemarkupTpl = new Ext.Template('<b>Changed by:</b><br>{changemarkupUser}<br>{changemarkupDate}');
            me.changemarkupTpl.compile();
        }
        tip.update(me.changemarkupTpl.apply(tplData));
    },
    
    /**
     * Override of default setTarget, only change see below.
     * Must be respected on ExtJS updates!
     */
    setTarget: function(target) {
        var me = this,
            listeners;
 
        if (me.targetListeners) {
            me.targetListeners.destroy();
        }
 
        if (target) {
            me.target = target = Ext.get(target.el || target);
            listeners = {
                mouseover: 'onTargetOver',
                mouseout: 'onTargetOut',
                mousemove: 'onMouseMove',
                tap: 'onTargetTap',
                scope: me,
                destroyable: true,
                delegated: false    //this is the only change in comparision to the original code
            };
 
            me.targetListeners = target.on(listeners);
        } else {
            me.target = null;
        }
    }
    
});