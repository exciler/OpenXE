import axios from "axios";
import {ready} from "@res/js/dom";

function init() {
    const span = document.createElement('span')
    span.classList.add('duplicate_address_error');
    span.style.visibility = 'hidden';
    span.style.color = 'red';
    span.innerHTML = 'Möglicherweise doppelt';
    document.getElementById('name').after(span);
    registerEvents();
}

function registerEvents() {
    document.querySelectorAll('#name, #strasse, #plz, #ort')
        .forEach((item) => { item.addEventListener('blur', checkDuplicate)})
}

function checkDuplicate() {
    const nameValue = document.getElementById('name').value;
    const streetValue = document.getElementById('strasse').value;
    const zipcodeValue = document.getElementById('plz').value;
    const placeValue = document.getElementById('ort').value;

    axios.post('index.php?module=adresse&action=create&cmd=duplicate', {
        name: nameValue,
        street: streetValue,
        zipcode: zipcodeValue,
        place: placeValue
    }).then(response => {
        document.querySelector('.duplicate_address_error')
            .style.visibility = response.data ? 'visible' : 'hidden';
    });
}

ready(init);