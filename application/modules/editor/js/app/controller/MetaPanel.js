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
 * MetaPanel Controller
 * @class Editor.controller.MetaPanel
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.MetaPanel', {
  extend : 'Ext.app.Controller',
  requires: ['Editor.view.qmsubsegments.AddFlagFieldset'],
  messages: {
    gridEndReached: 'Ende der Segmente erreicht!',
    gridStartReached: 'Start der Segmente erreicht!'
  },
  refs : [{
    ref : 'metaPanel',
    selector : '#metapanel'
  },{
    ref : 'metaTermPanel',
    selector : '#metapanel #metaTermPanel'
  },{
    ref : 'leftBtn',
    selector : '#metapanel #goAlternateLeftBtn'
  },{
    ref : 'rightBtn',
    selector : '#metapanel #goAlternateRightBtn'
  },{
      ref : 'navi',
      selector : '#metapanel #naviToolbar'
  },{
    ref : 'segmentGrid',
    selector : '#segmentgrid'
  }],
  hideLeftRight: false,
  init : function() {
      var me = this;
      me.control({
      '#metapanel #cancelSegmentBtn' : {
        click : me.cancel
      },
      '#metapanel #saveSegmentBtn' : {
        click : me.save
      },
      '#metapanel #saveNextSegmentBtn' : {
        click : me.saveNext
      },
      '#metapanel #savePreviousSegmentBtn' : {
        click : me.savePrevious
      },
      '#metapanel #goAlternateLeftBtn' : {
          click : me.goToAlternate
      },
      '#metapanel #goAlternateRightBtn' : {
          click : me.goToAlternate
      },
      '#metapanel' : {
          show : me.layout
      },
      'segmentsHtmleditor': {
          afteriniteditor: me.initEditor
      },
      '#segmentgrid': {
          afterrender: me.initEditPluginHandler
      }
    });
  },
  /**
   * Gibt die RowEditing Instanz des Grids zurück
   * @returns Editor.view.segments.RowEditing
   */
  getEditPlugin: function() {
    return this.getSegmentGrid().editingPlugin;
  },
  initEditPluginHandler: function() {
      var me = this, 
          multiEdit = me.getSegmentGrid().query('.contentEditableColumn').length > 1,
          useChangeAlikes = Editor.app.authenticatedUser.isAllowed('useChangeAlikes', Editor.data.task);

    //Diese Events können erst in onlauch gebunden werden, in init existiert das Plugin noch nicht
      me.getEditPlugin().on('beforeedit', me.startEdit, me);
      me.getEditPlugin().on('canceledit', me.cancelEdit, me);
      me.getEditPlugin().on('edit', me.saveEdit, me);
      me.getEditPlugin().on('canCompleteEdit', me.canCompleteEdit, me);
    
      me.getLeftBtn().setVisible(multiEdit && ! useChangeAlikes);
      me.getRightBtn().setVisible(multiEdit && ! useChangeAlikes);
  },
  /**
   * binds strg + enter as save segment combination
   * @param editor
   */
  initEditor: function(editor){
      var me = this,
          keyev = Ext.EventManager.useKeyDown ? 'keydown' : 'keypress';
      Ext.EventManager.on(editor.getDoc(), keyev, function(e){
          if(e.ctrlKey && e.getKey() == e.ENTER) {
              me.saveNext();
          }
      });
  },
  /**
   * Handler für save Button
   */
  layout: function() {
    this.getNavi().doLayout(); //FIXME noch was anderes layouten?
  },
  /**
   * Handler für save Button
   */
  save: function() {
    this.fireEvent('saveSegment');
  },
  /**
   * Handler for saveNext Button
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  saveNext: function() {
      return this.saveOtherRow(1, this.messages.gridEndReached);
  },
  /**
   * Handler for savePrevious Button
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  savePrevious: function() {
      return this.saveOtherRow(-1, this.messages.gridStartReached);
  },
  /**
   * save and go to other row
   * @param {Integer} rowIdxChange positive or negative integer value to choose the index of the next row
   * @param {String} errorText
   * @return {Boolean} true if there is a next segment, false otherwise
   */
  saveOtherRow: function(rowIdxChange, errorText) {
      var me = this,
          grid = me.getSegmentGrid(),
          selModel = grid.getSelectionModel(),
          ed = me.getEditPlugin(),
          rec = ed.openedRecord,
          store = grid.store,
          lastColumnIdx = 0,
          newRec = store.getAt(store.indexOf(rec) + rowIdxChange);
      while(newRec && !newRec.get('editable')) {
          newRec = store.getAt(store.indexOf(newRec) + rowIdxChange);
      }
      Ext.Array.each(grid.columns, function(col, idx) {
          if(col.dataIndex == ed.editor.getEditedField()) {
              lastColumnIdx = idx;
          }
      });
      me.fireEvent('saveSegment', {
          scope: me,
          segmentUsageFinished: function(){
              if(newRec !== undefined){
                  //editing by selection handler must be disabled, otherwise saveChainStart will be triggered twice
                  ed.disableEditBySelect = true;
                  selModel.select(newRec);
                  Ext.defer(ed.startEdit, 100, ed, [newRec, lastColumnIdx]); //defer reduces problems with editorDomCleanUp see comment on Bug 38
                  ed.disableEditBySelect = false;
              }
              else{
                  Editor.MessageBox.addInfo(errorText);
              }
          }
      });
      return (newRec !== undefined);
  },
  /**
   * Handler für cancel Button
   */
  cancel: function() {
    this.getEditPlugin().cancelEdit();
  },
  /**
   * Editor.view.segments.RowEditing beforeedit handler, initiert das MetaPanel mit den Daten
   * @param {Object} context
   */
  startEdit: function(context) {
    var me = this,
        mp = me.getMetaPanel(),
        segmentId = context.record.get('id');
    
    me.record = context.record;
    me.getMetaTermPanel().getLoader().load({params: {id: segmentId}});
    //bindStore(me.record.terms());
    me.loadRecord(me.record);
    //FIXME here doLayout???
    mp.show();
  },
  /**
   * lädt die konkreten record ins Meta Panel 
   * @param {Ext.data.Model} record
   */
  loadRecord: function(record) {
    var me = this,
        mp = me.getMetaPanel(),
        form = mp.down('#metaInfoForm'),
        values = record.getQmAsArray(),
        qmBoxes = mp.query('#metaQm .checkbox');
    statBoxes = mp.query('#metaStates .radio');
    Ext.each(statBoxes, function(box){
      box.setValue(false);
    });
    form.loadRecord(record);
    Ext.each(qmBoxes, function(box){
      box.setValue(Ext.Array.contains(values, box.inputValue));
    });
  },
  /**
   * Editor.view.segments.RowEditing edit handler, Speichert die Daten aus dem MetaPanel im record
   */
  saveEdit: function() {
    var me = this,
        mp = me.getMetaPanel(),
        form = mp.down('#metaInfoForm'),
        qmBoxes = mp.query('#metaQm .checkbox'),
        quality = [];
    Ext.each(qmBoxes, function(box){box.getValue() && quality.push(box.inputValue);});
    me.record.set('stateId', form.getValues().stateId);
    me.record.setQmFromArray(quality);
    //close the metapanel
    me.cancelEdit();
  },
  /**
   * Editor.view.segments.RowEditing canceledit handler
   */
  cancelEdit: function() {
    this.getMetaPanel().hide();
  },
  /**
   * Move the editor about one editable field
   */
  goToAlternate: function(btn, ev) {
    var me = this,
        direction = (btn.itemId == 'goAlternateLeftBtn' ? -1 : 1),
        info = me.getColInfo(),
        idx = info && info.foundIdx,
        cols = info && info.columns,
        store = me.getSegmentGrid().store,
        newRec = store.getAt(store.indexOf(me.getEditPlugin().openedRecord) + direction);
    
    if(info === false) {
      return;
    }
    
    //check if there exists a next/prev row, if not we dont need to move the editor.
    while(newRec && !newRec.get('editable')) {
        newRec = store.getAt(store.indexOf(newRec) + direction);
    }
    
    if(cols[idx + direction]) {
      info.plug.editor.changeColumnToEdit(cols[idx + direction]);
      return;
    }
    if(direction > 0) {
        //goto next segment and first col
        if(newRec) {
            info.plug.editor.changeColumnToEdit(cols[0]);
        }
        me.saveNext();
    }
    else {
        //goto prev segment and last col
        if(newRec) {
            info.plug.editor.changeColumnToEdit(cols[cols.length - 1]);
        }
        me.savePrevious();
    }
  },
  /**
   * returns the visible columns and which column has actually the editor
   * @return {Object}
   */
  getColInfo: function() {
    var me = this,
        plug = me.getEditPlugin(),
        columns = me.getSegmentGrid().query('.contentEditableColumn:not([hidden])'),
        foundIdx = false,
        current = plug.editor.getEditedField();
    
    if(!plug || !plug.editor) {
        return false;
    }
    
    Ext.Array.each(columns, function(col, idx) {
      if(col.dataIndex == current) {
        foundIdx = idx;
      }
    });
    if(foundIdx === false) {
      Ext.Error.raise('current dataIndex not found in visible columns!');
      return false;
    }

    return {
      plug: plug,
      columns: columns,
      foundIdx: foundIdx
    };
  }
});
