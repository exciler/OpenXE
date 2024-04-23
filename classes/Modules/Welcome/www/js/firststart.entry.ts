import axios from "axios";
import {AlertErrorHandler} from "@res/js/ajaxErrorHandler";
import {createVueApp} from "@res/js/vue.js";
import ClickByClickAssistant from "../../../../Widgets/ClickByClickAssistant/www/js/ClickByClickAssistant.vue";

axios.post('index.php?module=welcome&action=settings&cmd=startclickbyclick')
    .then(response => {
        createVueApp(ClickByClickAssistant, {
            pagination: true,
            allowClose: false,
            pages: response.data.pages
        }).mount('#welcome-firststart');
    })
    .catch(AlertErrorHandler);

