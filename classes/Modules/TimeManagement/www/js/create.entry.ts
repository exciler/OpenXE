import {createVueApp} from "@res/js/vue";
import TimeManagementCalendar from "@modules/TimeManagement/www/js/TimeManagementCalendar.vue";
import {CalendarOptions, DateSelectArg, EventClickArg, EventDropArg} from "@fullcalendar/core";
import {EventResizeDoneArg} from "@fullcalendar/interaction";
import axios from "axios";
import {AutoSaveUserParameter} from "@res/js/userParameter";

const select = function(arg: DateSelectArg) {
    OpenMode(arg.start, arg.end);
};

const eventDrop = function (arg: EventDropArg|EventResizeDoneArg)
{
    if(arg.event.id > 0) {
        axios.post('index.php?module=zeiterfassung&action=create&cmd=updatezeiterfassung', arg.event)
        .then(() => refetch());
    }
    else {
        alert("Eintrag kann nicht verschoben werden");
        arg.revert();
    }
};

const eventClick = function(arg: EventClickArg) {
    setFormData(arg.event.id);
};

const OpenMode = function(start: Date, end: Date) {

    $("#submitError").html('');
    if(start==end)
    {
        $('#editZeiterfassung').find('#bisZeit').val('');
    }
    else {
        $('#editZeiterfassung').find('#bisZeit').val($.format.date(end, "HH:mm"));
    }

    $('#editZeiterfassung').find('#eventid').val('');
    $('#editZeiterfassung').find('#datum').val($.format.date(start, "dd.MM.yyyy"));
    $('#editZeiterfassung').find('#vonZeit').val($.format.date(start, "HH:mm"));

    $('#editZeiterfassung').find('#aufgabe2').val('');
    $('#editZeiterfassung').find('#beschreibung2').val('');
    $('#editZeiterfassung').find('#ort').val('');
    $('#editZeiterfassung').find('#art2').val('Arbeit');
    $('#editZeiterfassung').find('#internerkommentar').val('');

    $('#editZeiterfassung').find('#projekt_manuell2').val('');
    $('#editZeiterfassung').find('#arbeitspaket2').val('');
    $('#editZeiterfassung').find('#adresse_abrechnung2').val('');
    $('#editZeiterfassung').find('#auftrag').val('');
    $('#editZeiterfassung').find('#auftragpositionid').val('');
    $('#editZeiterfassung').find('#produktion').val('');
    $('#editZeiterfassung').find('#serviceauftrag').val('');

    $(":button:contains('Löschen')").prop("disabled",true).addClass( 'ui-state-disabled' );
    $(":button:contains('Kopieren')").prop("disabled",true).addClass( 'ui-state-disabled' );
    $("#editZeiterfassung").dialog("open");
};

const EditMode = function(data) {

    $(":button:contains('Löschen')").prop("disabled",false).removeClass( 'ui-state-disabled' );
    $(":button:contains('Kopieren')").prop("disabled",false).removeClass( 'ui-state-disabled' );
    $("#submitError").html('');
//  $("#mode").val("edit");
    $('#editZeiterfassung').find('#eventid').val(data.id);
    $('#editZeiterfassung').find('#aufgabe2').val(data.aufgabe);
    $('#editZeiterfassung').find('#beschreibung2').val(data.beschreibung);
    $('#editZeiterfassung').find('#ort').val(data.ort);
    $('#editZeiterfassung').find('#art2').val(data.art);

    $('#editZeiterfassung').find('#internerkommentar').val(data.internerkommentar);
    $('#editZeiterfassung').find('#vonZeit').val(data.vonzeit);
    $('#editZeiterfassung').find('#bisZeit').val(data.biszeit);
    $('#editZeiterfassung').find('#datum').val(data.datum);

    $('#editZeiterfassung').find('#projekt_manuell2').val(data.projekt_manuell);
    $('#editZeiterfassung').find('#arbeitspaket2').val(data.arbeitspaket);
    $('#editZeiterfassung').find('#adresse_abrechnung2').val(data.adresse_abrechnung);
    $('#editZeiterfassung').find('#auftrag').val(data.auftrag);
    $('#editZeiterfassung').find('#auftragpositionid').val(data.auftragpositionid);
    $('#editZeiterfassung').find('#produktion').val(data.produktion);
    $('#editZeiterfassung').find('#serviceauftrag').val(data.serviceauftrag);

    // Öffentlich

    if(data.abrechnen == 1) {
        $('#editZeiterfassung').find('#abrechnen2').prop('checked', true);
    }else {
        $('#editZeiterfassung').find('#abrechnen2').prop('checked', false);
    }
};

const setFormData = function(id) {
        if(id > 0) {
            $.ajax({
                url: './index.php?module=zeiterfassung&action=create&cmd=getzeiterfassung&id='+id,
                dataType: 'json',
                success: function(data) {

                    $('#editZeiterfassung').find('input, textarea').attr('disabled',true);
                    if(data.write == 1)
                    {
                        $('#editZeiterfassung').find('input, textarea').attr('disabled',false);
                    }else{
                        $('#editZeiterfassung').find('#titel').attr('disabled',true);
                    }

                    $('#editZeiterfassung').dialog('open');
                    EditMode(data);
                },
                error: function (request, statusCode, error) { $("#submitError").html("Keine Event-Daten gefunden"); }
            });
        }
    };

$("#aufgabe").autocomplete({
        source: "index.php?module=ajax&action=filter&filtername=zeiterfassungvorlage",
        select: function (event, ui) {
            $.ajax({
                url: 'index.php?module=ajax&action=filter&filtername=zeiterfassungvorlagedetail',
                data: {
                    vorlage: ui.item.label
                },
                method: 'post',
                dataType: 'json',
                success: function (data) {
                    $("#beschreibung").val(data[0]);
                    $("#art").val(data[1]);
                    $("#projekt_manuell").val(data[2]);
                    $("#arbeitspaket").val(data[3]);
                    if (data[4] == 0) {
                        data[4] = '';
                    }
                    $("#adresse_abrechnung").val(data[4]);
                    $("#abrechnen").prop("checked", data[5] == 1 ? true : false);
                }
            });
        }
    });
$("#aufgabe2").autocomplete({
        source: "index.php?module=ajax&action=filter&filtername=zeiterfassungvorlage",
        select: function (event, ui) {
            $.ajax({
                url: 'index.php?module=ajax&action=filter&filtername=zeiterfassungvorlagedetail',
                data: {
                    vorlage: ui.item.label
                },
                method: 'post',
                dataType: 'json',
                success: function (data) {
                    $("#beschreibung2").val(data[0]);
                    $("#art2").val(data[1]);
                    $("#projekt_manuell2").val(data[2]);
                    $("#arbeitspaket2").val(data[3]);
                    if (data[4] == 0) {
                        data[4] = '';
                    }
                    $("#adresse_abrechnung2").val(data[4]);
                    $("#abrechnen2").prop("checked", data[5] == 1 ? true : false);
                }
            });
        }
    });

$("#projekt_manuell").autocomplete({
        source: "index.php?module=ajax&action=filter&filtername=projektname",
        select: function (event, ui) {
            $.ajax({
                url: 'index.php?module=ajax&action=filter&filtername=zeiterfassungprojektdetail',
                data: {
                    projekt: ui.item.label
                },
                method: 'post',
                dataType: 'json',
                success: function (data) {
                    if (data[0]) {
                        $("#adresse_abrechnung").val(data[0]);
                    }
                }
            });
        }
    });
$("#projekt_manuell2").autocomplete({
        source: "index.php?module=ajax&action=filter&filtername=projektname",
        select: function (event, ui) {
            $.ajax({
                url: 'index.php?module=ajax&action=filter&filtername=zeiterfassungprojektdetail',
                data: {
                    projekt: ui.item.label
                },
                method: 'post',
                dataType: 'json',
                success: function (data) {
                    if (data[0]) {
                        $("#adresse_abrechnung2").val(data[0]);
                    }
                }
            });
        }
    });

$("#art2").change(function () {
        if (this.value != 'Arbeit')
            $('#aufgabe2').val(this.value);
        else
            $('#aufgabe2').val('');
        $('#beschreibung2').val('');

    });

$("#editZeiterfassung").dialog({
        autoOpen: false,
        height: 750,
        width: 650,
        modal: true,
        buttons: {
            "Speichern": function () {
                var errMsg = '';

                if ($('#editZeiterfassung').find('#datum').val() == "") errMsg = "Geben Sie bitte ein Datum ein."
                if ($('#editZeiterfassung').find('#aufgabe2').val() == "") errMsg = "Geben Sie bitte eine Aufgabe ein.";
                if ($('#editZeiterfassung').find('#vonZeit').val() == "") errMsg = "Geben Sie bitte Von-Zeit ein.";
                if ($('#editZeiterfassung').find('#bisZeit').val() == "") errMsg = "Geben Sie bitte Bis-Zeit ein.";

                if (errMsg != "")
                    $("#submitError").html('<div class="error">' + errMsg + '</div>');
                else {
                    ZeiterfassungSave();
                }
            },
            "Löschen": function () {
                if (confirm("Soll dieser Eintrag wirklich gelöscht werden?")) {
                    $.ajax({
                        url: 'index.php?module=zeiterfassung&action=create&cmd=delzeiterfassung',
                        data: {
                            //Alle Felder die fürs editieren vorhanden sind
                            id: $('#editZeiterfassung').find('#eventid').val()
                        },
                        method: 'post',
                        dataType: 'json',
                        success: function (data) {
                            if (data.status == 1) {
                                $("#editZeiterfassung").dialog('close');
                                refetch();
                            } else {
                                alert(data.statusText);
                            }
                        }
                    });

                    $(this).dialog("close");
                }
            },
            "Kopieren": function () {
                if (confirm("Soll dieser Eintrag wirklich kopiert werden?")) {
                    $.ajax({
                        url: 'index.php?module=zeiterfassung&action=create&cmd=copyzeiterfassung',
                        data: {
                            id: $('#editZeiterfassung').find('#eventid').val()
                        },
                        method: 'post',
                        dataType: 'json',
                        success: function (data) {
                            if (data.status == 1) {
                                $("#editZeiterfassung").dialog('close');
                                refetch();
                            } else {
                                alert(data.statusText);
                            }
                        }
                    });
                    $(this).dialog("close");
                }
            },

            "Abbrechen": function () {
                $(this).dialog("close");
            }
        },
        close: function () {
            ResetMode();
        }
    });

function ZeiterfassungSave() {
    $.ajax({
        url: 'index.php?module=zeiterfassung&action=create&cmd=savezeiterfassung',
        data: {
            //Alle Felder die fürs editieren vorhanden sind
            id: $('#editZeiterfassung').find('#eventid').val(),
            datum: $('#editZeiterfassung').find('#datum').val(),
            start: $('#editZeiterfassung').find('#vonZeit').val(),
            end: $('#editZeiterfassung').find('#bisZeit').val(),
            aufgabe: $('#editZeiterfassung').find('#aufgabe2').val(),
            beschreibung: $('#editZeiterfassung').find('#beschreibung2').val(),
            ort: $('#editZeiterfassung').find('#ort').val(),
            art: $('#editZeiterfassung').find('#art2').val(),
            internerkommentar: $('#editZeiterfassung').find('#internerkommentar').val(),

            projekt_manuell: $('#editZeiterfassung').find('#projekt_manuell2').val(),
            arbeitspaket: $('#editZeiterfassung').find('#arbeitspaket2').val(),
            adresse_abrechnung: $('#editZeiterfassung').find('#adresse_abrechnung2').val(),
            auftrag: $('#editZeiterfassung').find('#auftrag').val(),
            auftragpositionid: $('#editZeiterfassung').find('#auftragpositionid').val(),
            produktion: $('#editZeiterfassung').find('#produktion').val(),
            serviceauftrag: $('#editZeiterfassung').find('#serviceauftrag').val(),
            abrechnen: $('#editZeiterfassung').find('#abrechnen2').prop("checked")?1:0
        },
        method: 'post',
        dataType: 'json',
        beforeSend: function() {
            App.loading.open();
        },
        success: function(data) {
            App.loading.close();
            if (data.status == 1) {
                $("#editZeiterfassung").dialog('close');
                refetch();
                $('#editZeiterfassung').find('#beschreibung2').val('');
            } else {
                alert(data.statusText);
            }
        }
    });
}

document.querySelector('.vueAction[data-action=create]')?.addEventListener('click', () => {
    OpenMode(new Date(), new Date());
})

const refetch = () => app.$refs.fullCalendar.getApi().refetchEvents();
AutoSaveUserParameter('zeiterfassung_buchen_termine','zeiterfassung_buchen_termine', refetch);
AutoSaveUserParameter('zeiterfassung_buchen_stechuhr','zeiterfassung_buchen_stechuhr', refetch);

const options: CalendarOptions = {
    events: "./index.php?module=zeiterfassung&action=create&cmd=data",
    select: select,
    eventClick: eventClick,
    eventDrop: eventDrop,
    eventResize: eventDrop
};

const app = createVueApp(TimeManagementCalendar, {options}).mount('#vueapp_calendar');