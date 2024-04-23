import axios from "axios";
import {AlertErrorHandler} from "@res/js/ajaxErrorHandler";
import {createVueApp} from "@res/js/vue";
import App from "./App.vue";

document.querySelector('#suche')?.addEventListener('keyup', (event) => {
    const query = (event.currentTarget as HTMLInputElement).value;
    axios.post('index.php?module=zahlungsweisen&action=create&cmd=suche', {val: query})
        .catch(AlertErrorHandler)
        .then((response) => {
            response?.data?.ausblenden?.forEach((item: string) => {
                const el = document.querySelector<HTMLDivElement>('#'+item);
                if (el)
                    el.style.display = 'none';
            })
            response?.data?.anzeigen?.forEach((item: string) =>  {
                const el = document.querySelector<HTMLDivElement>('#'+item);
                if (el)
                    el.style.display = 'flex';
            })
        })
    ;
});

createVueApp(App,{}).mount('#payment-create');