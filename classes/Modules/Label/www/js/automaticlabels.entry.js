import {createVueApp} from "@res/js/vue";
import {AlertErrorHandler} from "@res/js/ajaxErrorHandler";
import {reloadDataTables} from "@res/js/jqueryBridge";
import axios from "axios";
import AutomaticLabelEditor from "./AutomaticLabelEditor.vue";


const props = JSON.parse(document.querySelector('#vueapp_props')?.textContent);
createVueApp(AutomaticLabelEditor, props).mount('#vueapp');

document.querySelector('#datatablelabels_automaticlabelslist')?.addEventListener('click', async (evt) => {
    if (evt.target instanceof Element) {
        let link = evt.target.closest('a.datatablelabels-automaticlabeldelete');
        if (link instanceof HTMLElement && link.dataset.id) {
            const confirmValue = confirm('Wirklich löschen?');
            if (confirmValue === false) {
                return;
            }
            await axios.post('index.php?module=datatablelabels&action=automaticlabelsedit&cmd=delete', {id: link.dataset.id})
                .catch(AlertErrorHandler)
                .then(() => reloadDataTables());
        }
    }
});