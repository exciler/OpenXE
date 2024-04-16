import axios from "axios";
import {AlertErrorHandler} from "@res/js/ajaxErrorHandler";

axios.post('index.php?module=welcome&action=settings&cmd=startclickbyclick')
    .then(response => {
        new Vue({
            el: '#welcome-firststart',
            data: {
                showAssistant: true,
                pagination: true,
                allowClose: false,
                pages: response.data.pages
            }
        });
    })
    .catch(AlertErrorHandler);