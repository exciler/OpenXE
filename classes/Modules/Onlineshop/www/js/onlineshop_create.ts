import {createVueApp} from "@res/js/vue";
import axios from "axios";
import App from "./App.vue";

function search(event: Event)
{
    const query = (event.currentTarget as HTMLInputElement).value;
    const config = {
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        }
    };
    axios.post('index.php?module=onlineshops&action=create&cmd=suche', {val: query}, config)
        .then((response) => {
            if(response.data !== undefined && response.data != null)
            {
                if(response.data.ausblenden !== undefined && response.data.ausblenden != null)
                {
                    response.data.ausblenden.forEach((item: string) => {
                        const el = document.querySelector<HTMLDivElement>('#'+item);
                        if (el)
                            el.style.display = 'none';
                    });
                }
                if(typeof response.data.anzeigen !== undefined && response.data.anzeigen != null)
                {
                    response.data.anzeigen.forEach((item: string) => {
                        const el = document.querySelector<HTMLDivElement>('#'+item);
                        if (el)
                            el.style.display = 'flex';
                    });
                }
            }
        });
}

document.querySelector('#suche')?.addEventListener('keyup', search);
createVueApp(App, {}).mount('#onlineshop-create');