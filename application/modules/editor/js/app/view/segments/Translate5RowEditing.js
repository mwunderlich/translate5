
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
/**
 * @class Editor.view.segments.Translate5RowEditing
 * @extends Ext.grid.plugin.RowEditing
 */
Ext.define('Editor.view.segments.Translate5RowEditing', {
    extend: 'Ext.grid.plugin.RowEditing',
    alias: 'plugin.segments.translate5rowediting',
    editingAllowed: true,
    openedRecord: null,
    messages: {
        previousSegmentNotSaved: 'Das Segment konnte nicht zum Bearbeiten geöffnet werden, da das vorherige Segment noch nicht korrekt seine Speicherung beendet hatte. Bitte versuchen Sie es noch einmal. Sollte es dann noch nicht funktionieren, drücken Sie bitte F5. Vielen Dank!',
        edit100pWarning: '#UT#Achtung, Sie editieren einen 100% Match!'
    },
    requires: [
        'Editor.view.segments.Translate5RowEditor'
    ],
    initEditorConfig: function() {
        var me = this,
            grid = me.grid,
            view = me.view,
            headerCt = grid.headerCt,
            cfg = {
                autoCancel: me.autoCancel,
                errorSummary: me.errorSummary,
                fields: headerCt.getGridColumns(),
                hidden: true,
                // keep a reference..
                editingPlugin: me,
                view: view
            };
        return cfg;
    },
    initEditor: function() {
        return new Editor.view.segments.Translate5RowEditor(this.initEditorConfig());
    },
     /**
     * Erweitert die Orginalmethode um die "editingAllowed" Prüfung
     * @param {Editor.model.Segment} record
     * @param {Ext.grid.column.Column/Number} columnHeader The Column object defining the column to be edited, or index of the column.
     * @returns booelean|void
     */
    startEdit: function(record, columnHeader) {
        var me = this,
            started = false;
        //to prevent race-conditions, check if there isalready an openedRecord and if yes show an error (see RowEditor.js function completeEdit for more information)
        if (me.openedRecord !== null) {
            Editor.MessageBox.addError(me.messages.previousSegmentNotSaved,' Das Segment konnte nicht zum Bearbeiten geöffnet werden, da das vorherige Segment noch nicht korrekt gespeichert wurde. Im folgenden der Debug-Werte: this.openedRecord.internalId: ' + this.openedRecord.internalId + ' record.internalId: ' + record.internalId);
            return false;
        }
        if (me.editingAllowed && record.get('editable')) {
            if (record.get('matchRate') == 100 && Editor.data.enable100pEditWarning) {
                Editor.MessageBox.addInfo(me.messages.edit100pWarning, 1.4);
            }
            me.openedRecord = record;
            started = me.callParent(arguments);
            return started;
        }
        return false;
    },
    /**
     * erlaubt das Editieren
     */
    enable: function() {
        this.editingAllowed = true;
    },
    /**
     * deaktiviert das Editieren
     */
    disable: function() {
        this.editingAllowed = false;
    },
    destroy: function() {
        delete this.context;
        delete this.openedRecord;
        this.callParent(arguments);
    },
    /**
     * editorDomCleanUp entfernt die komplette (DOM + Komponente) Instanz des Editors. 
     * Die DOM Elemente des Editors befinden sich innerhalb des Grid View Elements. 
     * Dieses wiederrum wird bei einem Range Change neu erstellt. Die Editor Komponente verliert ihre DOM Elemente,
     * es kommt zu komischen Effekten. Mit dieser Methode wird der komplette Editor entfernt, und wird bei einer 
     * erneuten Verwendung komplett neu erstellt.
     */
    editorDomCleanUp: function() {
      var me = this,
      main,
      columns = me.grid.getView().getGridColumns();
      if(! me.editor) {
          return;
      }
      me.editing = false;
      me.openedRecord = null;
      main = me.editor.mainEditor;
      //enable stored editor body id to be deleted by GC:
      if(main.bodyGenId && Ext.cache[main.bodyGenId]){
          Ext.cache[main.bodyGenId].skipGarbageCollection = false;
          delete Ext.cache[me.editor.mainEditor.bodyGenId].skipGarbageCollection;
      }
      Ext.destroy(me.editor);
      Ext.each(columns, function(column) {
        // in den Columns werden die Componenten zur EditorRow abgelegt. 
        // Nach einem Range Change bestehen zar noch diese Componenten, aber die zugehörigen Dom Elemente fehlen.
        if(column.field){
          column.field.destroy();
          delete column.field;
        }
      });
      delete me.editor;
    }
});