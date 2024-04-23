import {createVueApp} from "@res/js/vue";
import ClickByClickAssistant from "../../../../Widgets/ClickByClickAssistant/www/js/ClickByClickAssistant.vue";
import axios from "axios";

const vueAppNewError = '#onlineshop-appnew-error';
const vueAppNew = '#onlineshop-appnew';
const vueAppNewJson = '#onlineshop-appnewjson';

function initAppNewErrorVue() {
    const props = {
        showAssistant: true,
        pagination: true,
        allowClose: true,
        pages: [
            {
                type: 'defaultPage',
                icon: 'password-icon',
                headline: 'Request ungültig',
                subHeadline: document.querySelector(vueAppNewError)?.dataset.errormsg,

                ctaButtons: [
                    {
                        title: 'OK',
                        action: 'close'
                    }]
            }
        ]
    }
    createVueApp(ClickByClickAssistant, props).mount(vueAppNewError);
}

function initAppNewVue() {
    const props = {
        showAssistant: true,
        pagination: true,
        allowClose: true,
        pages: [
            {
                type: 'form',
                dataRequiredForSubmit: {
                    data: JSON.stringify(document.querySelector(vueAppNew).dataset.appnewdata)
                },
                submitType: 'submit',
                submitUrl: 'index.php?module=onlineshops&action=appnew&cmd=createdata',
                headline: document.querySelector(vueAppNew).dataset.heading,
                subHeadline: document.querySelector(vueAppNew).dataset.info,
                form:
                    [
                        {
                            id: 0,
                            name: 'create-shop',
                            inputs: [
                                {
                                    type: 'select',
                                    name: 'shopId',
                                    label: 'Auswahl',
                                    validation: true,
                                    options: JSON.parse(document.querySelector(vueAppNewJson).innerHTML)
                                }]
                        }]
                ,
                ctaButtons: [
                    {
                        title: 'Weiter',
                        type: 'submit',
                        action: 'submit'
                    }]
            }
        ]
    };
    createVueApp(ClickByClickAssistant, props).mount(vueAppNew);
}


if (document.querySelector(vueAppNewError)) {
    initAppNewErrorVue();
}
if (document.querySelector(vueAppNew)) {
    initAppNewVue();
}
if (document.querySelector('#frmappnew')) {
    document.querySelector('#data').addEventListener('paste', function (e) {
        axios.post('index.php?module=onlineshops&action=appnew&cmd=checkdata', {
            data: e.originalEvent.clipboardData.getData('text')
        })
            .then(function (response) {
                document.querySelector('#msgwrapper').innerHTML = response.data;
            })
            .catch(function (error) {
                if (error.response.data.error) {
                    document.querySelector('#msgwrapper').innerHTML = '<div class="error">' + error.response.data.error + '</div>';
                }
            });
    });
    document.querySelector('#data').addEventListener('change', function () {
        axios.post('index.php?module=onlineshops&action=appnew&cmd=checkdata', {data: this.value})
            .then(function (response) {
                document.querySelector('#msgwrapper').innerHTML = response.data;
            })
            .catch(function (error) {
                if (error.response.data.error) {
                    document.querySelector('#msgwrapper').innerHTML = '<div class="error">' + error.response.data.error + '</div>';
                }
            });
    });
}
