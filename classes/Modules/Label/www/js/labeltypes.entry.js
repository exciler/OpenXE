import {createVueApp} from "@res/js/vue";
import LabelEditor from "./LabelEditor.vue";
import {AlertErrorHandler} from "@res/js/ajaxErrorHandler";
import {reloadDataTables} from "@res/js/jqueryBridge";
import axios from "axios";


const props = JSON.parse(document.querySelector('#vueapp_props')?.textContent);
createVueApp(LabelEditor, props).mount('#vueapp');

document.querySelector('#datatablelabels_list')?.addEventListener('click', async (evt) => {
    if (evt.target instanceof Element) {
        let link = evt.target.closest('a.datatablelabels-delete');
        if (link instanceof HTMLElement && link.dataset.id) {
            const confirmValue = confirm('Wirklich löschen?');
            if (confirmValue === false) {
                return;
            }
            await axios.post('index.php?module=datatablelabels&action=edit&cmd=delete', {id: link.dataset.id})
                .catch(AlertErrorHandler)
                .then(() => reloadDataTables());
        }
    }
});

$(document).on('draw.dt', function (e, settings) {
    const tableName = settings.sTableId;
    const $table = $('#' + tableName);

    $table.find('.label-color-preview').each(function (index, element) {
        const $element = $(element);
        const hexColor = $element.data('hexcolor');
        $element.css('background-color', hexColor);
    });
});
