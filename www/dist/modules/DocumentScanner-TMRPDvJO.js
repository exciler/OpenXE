var d=function(n){var e={elem:{},storage:{fileId:null,previewFileId:null,documentType:"verbindlichkeit",dataTableSettings:{},dragTimer:null,uploadData:[]},selector:{filesTable:"#docscan_files",preview:"#preview-iframe",dialog:"#docscan-files-dialog",dialogTabs:"#filetabs",dialogContent:"#docscan-files-content",documentTableContainer:".document-table-container",documentFilterCheckbox:".document-filter-checkbox",documentAssignButton:".document-assign-action",createLiabilityButton:".create-liability-button",uploadDropzoneWrapper:"#dropzone-wrapper",uploadDropzone:"#dropzone"},init:function(){e.elem.$docscanDialog=n(e.selector.dialog),e.elem.$previewIframe=n(e.selector.preview),e.elem.$dropzone=n(e.selector.uploadDropzone),e.elem.$dropzoneWrapper=n(e.selector.uploadDropzoneWrapper),e.initDialog(),e.attachEvents()},attachEvents:function(){n(document).on("click","table#docscan_files tr",function(){e.onClickFileTableRow(this)}),n(document).on("click","table#docscan_files .docscan-add-button",function(a){a.stopPropagation(),a.preventDefault(),e.onClickAddButton(this)}),n(document).on("click","table#docscan_files .docscan-delete-button",function(a){a.stopPropagation(),a.preventDefault(),e.onClickDeleteButton(this)}),n(document).on("tabsactivate",e.selector.dialogTabs,function(a,t){e.onChangeFileTab(t.newTab,t.oldTab)}),n(document).on("click",e.selector.documentAssignButton,function(a){a.preventDefault(),e.onClickDocumentAssignButton(this)}),n(document).on("change",e.selector.documentFilterCheckbox,function(){e.onChangeTableFilter(this)}),n(document).on("click",e.selector.createLiabilityButton,function(){e.createNewLiability()}),n(document).on("dragover",function(a){var t=a.originalEvent.dataTransfer;typeof t.types&&t.types.indexOf("Files")!==-1&&(e.showDropzone(),window.clearTimeout(e.dragTimer))}),n(document).on("dragleave",function(){e.dragTimer=window.setTimeout(function(){e.storage.previewFileId!==null&&e.hideDropzone()},25)}),e.elem.$dropzoneWrapper.on("drop",function(a){a.preventDefault(),e.handleDroppedFiles(a.dataTransfer.files),e.elem.$dropzone.css("borderColor","#CCC")}),e.elem.$dropzoneWrapper.on("dragover",function(a){a.preventDefault(),e.elem.$dropzone.css("borderColor","darkred")}).on("dragleave",function(){e.elem.$dropzone.css("borderColor","#CCC")})},onChangeTableFilter:function(a){var t=n(a).prop("checked"),o=n(a).data("filter-column");if(!(typeof t>"u"||typeof o>"u")&&n.fn.DataTable.isDataTable(e.elem.$currentDataTable)){var i=e.elem.$currentDataTable.DataTable();t?i.column(o).search("true",!1,!1,!1).draw():i.column(o).search("",!1,!1,!1).draw()}},onClickFileTableRow:function(a){var t=n(a).find(".docscan-add-button"),o=parseInt(t.data("file"));isNaN(o)||o===0||e.storage.previewFileId!==o&&e.previewFileInIframe(o)},onClickAddButton:function(a){var t=n(a),o=parseInt(t.data("file"));if(!(isNaN(o)||o===0)){e.storage.fileId=o,e.displayDialog();var i=t.data("type");if(i!==""&&i!==null){var l={verbindlichkeit:"verbindlichkeit",kassenbuch:"kasse",reisekosten:"reisekosten",bestellung:"bestellung",adresse:"adressen"};e.storage.documentType=l[i],n(e.selector.dialogTabs).tabs("option","active",Object.keys(l).indexOf(i))}}},onClickDeleteButton:function(a){var t=parseInt(n(a).data("file"));if(!(isNaN(t)||t===0)){var o=window.confirm("Möchten Sie die Datei wirklich löschen?");o!==!1&&e.deleteFile(t)}},onChangeFileTab:function(a,t){var o=n(t).data("type");e.destroyDataTable(o),e.storage.documentType=n(a).data("type"),e.loadDataTable()},onClickDocumentAssignButton:function(a){var t=n(a).parents("tr");if(!n.fn.DataTable.isDataTable(e.elem.$currentDataTable)){alert("Unbekannter Fehler #1: DataTable konnte nicht gefunden werden.");return}var o=e.elem.$currentDataTable.DataTable(),i=o.row(t).data(),l=parseInt(i.id);if(isNaN(l)){alert("Unbekannter Fehler #2: ID für Zuweisung konnte nicht gefunden werden.");return}e.assignDocumentKeyword(e.storage.documentType,l)},assignDocumentKeyword:function(a,t){n.ajax({url:"index.php?module=docscan&action=edit&cmd=assign-file",data:{keyword:a,file:e.storage.fileId,object:t},type:"POST",dataType:"json",success:function(o){o.success===!1&&alert("Unbekannter Fehler #3. Datei konnte nicht zugewiesen werden."),e.reloadFilesTable(),e.showDropzone(),e.closeDialog()}})},previewFileInIframe:function(a){n.ajax({url:"index.php?module=docscan&action=preview&id="+a,type:"GET",dataType:"json",success:function(t){if(typeof t.iframe_src>"u"){alert("Unbekannter Fehler #4. Datei konnte nicht abgerufen werden.");return}e.hideDropzone(),e.elem.$previewIframe.attr("src",t.iframe_src),e.storage.previewFileId=a}})},deleteFile:function(a){n.ajax({url:"index.php?module=docscan&action=edit&cmd=delete-file",data:{file:a},type:"POST",dataType:"json",success:function(t){(typeof t.success>"u"||t.success===!1)&&(t.hasOwnProperty("error")?alert(t.error):alert("Unbekannter Fehler #7. Datei konnte nicht gelöscht werden.")),e.reloadFilesTable(),e.showDropzone()}})},initDialog:function(){e.elem.$docscanDialog.dialog({title:"Datei-Zuordnung",modal:!0,minWidth:900,closeOnEscape:!1,autoOpen:!1,resizable:!1}),n(e.selector.dialogTabs).tabs()},displayDialog:function(){e.loadDataTable().then(e.openDialog)},loadDataTable:function(){return e.fetchDataTableHtmlTemplate().then(e.fetchDataTableSettings).then(e.initDataTable)},openDialog:function(){window.setTimeout(function(){e.elem.$docscanDialog.dialog("open"),e.elem.$docscanDialog.dialog("option","height","auto")},150)},closeDialog:function(){e.elem.$docscanDialog.dialog("close")},reloadFilesTable:function(){var a=n(e.selector.filesTable);n.fn.DataTable.isDataTable(a)&&a.DataTable().ajax.reload(null,!1),e.storage.previewFileId=null},initDataTable:function(){var a=e.elem.$docscanDialog.find("#"+e.storage.documentType+"-tab");e.elem.$currentDataTable=a.find("table.display"),e.elem.$currentDataTable.css("width","100%"),n.fn.DataTable.isDataTable(e.elem.$currentDataTable)&&e.elem.$currentDataTable.DataTable().destroy();var t=e.elem.$currentDataTable.DataTable(e.storage.dataTableSettings);t.on("init.dt",function(){n(e.selector.documentTableContainer).show()})},destroyDataTable:function(a){var t=e.elem.$docscanDialog.find("#"+a+"-tab"),o=t.find("table.display");n.fn.DataTable.isDataTable(o)&&(o.DataTable().destroy(),e.elem.$currentDataTable=null)},fetchDataTableHtmlTemplate:function(){return n.ajax({url:"index.php?module=docscan&action=edit&cmd=table-html",data:{type:e.storage.documentType,id:e.storage.fileId},type:"GET",dataType:"html",success:function(a){e.elem.$docscanDialog.find("#"+e.storage.documentType+"-tab").html(a),e.elem.$docscanDialog.find(e.selector.documentTableContainer).hide()}})},fetchDataTableSettings:function(){var a=e.elem.$docscanDialog.find("#"+e.storage.documentType+"-tab"),t=a.find(".module-disabled");return t.length>0?null:n.ajax({url:"index.php?module=docscan&action=edit&cmd=table-settings",data:{type:e.storage.documentType,id:e.storage.fileId},type:"GET",dataType:"json",success:function(o){e.storage.dataTableSettings=o}})},createNewLiability:function(){n.ajax({url:"index.php?module=docscan&action=edit&cmd=create-liability",data:{id:e.storage.fileId},type:"GET",dataType:"json",success:function(a){if(typeof a.success>"u"||a.success===!1){alert("Unbekannter Fehler #5. Verbindlichkeit konnte nicht angelegt werden.");return}if(typeof a.liability>"u"){alert("Unbekannter Fehler #6. Verbindlichkeit konnte nicht angelegt werden.");return}window.location.href="./index.php?module=verbindlichkeit&action=edit&id="+a.liability}})},handleDroppedFiles:function(a){n.each(a,function(t,o){var i=new FileReader;i.onload=function(l){return function(){if(!(l.size===0||l.type==="")){var r=l.type.substr(0,6)==="image/",c=l.type==="application/pdf";if(!r&&!c){alert("Dieser Dateityp wird nicht unterstützt. Bitte laden Sie nur PDFs und Bilder hoch.");return}e.uploadDroppedFiles(l.name,this.result)}}}(a[t]),i.readAsDataURL(o)})},uploadDroppedFiles:function(a,t){typeof a>"u"||typeof t>"u"||n.ajax({type:"POST",url:"index.php?module=docscan&action=list&cmd=drop-file",data:{name:a,data:t},dataType:"json",success:function(o){o&&o.success&&o.success===!0&&e.debounce(function(){e.reloadFilesTable(),typeof o.file<"u"&&e.previewFileInIframe(o.file)},250)}})},showDropzone:function(){e.elem.$dropzone.addClass("active")},hideDropzone:function(){e.elem.$dropzone.removeClass("active")},debounce:function(a,t){var o=this,i=arguments;window.clearTimeout(e.storage.buffer),e.storage.buffer=window.setTimeout(function(){a.apply(o,i)},t||250)}};return{init:e.init}}(jQuery);$(document).ready(function(){$("#docscan-module").length!==0&&d.init()});