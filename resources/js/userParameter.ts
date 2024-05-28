import axios from "axios";

const eventTypes = ['change', 'focusout'];
export const AutoSaveUserParameter = function (fieldName: string, paramName: string, callback?: () => void) {

    const el = document.querySelector("[name='" + fieldName + "']");
    if (el !instanceof HTMLInputElement)
        return;

    eventTypes.forEach((eventType) => {
        el.addEventListener(eventType, (evt) => {
            const tgt: HTMLInputElement = evt.currentTarget;
            const value = tgt.type === 'checkbox' ? tgt.checked : tgt.value;
            SaveUserParameter().then(() => callback());
        })
    });
}

const SaveUserParameter = function (paramName: string, value: string|boolean) {
    return axios.post('index.php?module=ajax&action=autosaveuserparameter', {
        name: paramName,
        value: value
    });
}