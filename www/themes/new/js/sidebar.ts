import '../css/sidebar.css';
import axios from "axios";

const sidebarToggleClass= 'collapsed';
const listItemToggleClass = 'active';
const sidebarSelector = '#sidebar';
const sidebarTriggerSelector = '.sidebar-toggle';
const listItemSelector = '.list-item';
const subMenuSelector = '.sidebar-submenu div';
const isTouch =  'ontouchstart' in window;
let collapseState = null;

function init() {
    attachEvents();
}

function attachEvents() {
    // opens/ closes sidebar
    document.querySelector<HTMLElement>(sidebarTriggerSelector).addEventListener('click', toggleSidebar);

    document.querySelectorAll<HTMLElement>(listItemSelector).forEach((item) => {
        if (isTouch) {
            item.addEventListener('click', evt => {
                let element = evt.target as HTMLElement;
                document.querySelectorAll<HTMLElement>(listItemSelector+' .'+listItemToggleClass)
                    .forEach(item => resetHighlightListItem(item))
                highlightListItem(element)
            });
        } else {
            // mouseenter on non touch device
            item.addEventListener('mouseenter', (evt:Event) => highlightListItem(evt.target as HTMLElement));
            item.addEventListener('mouseleave', (evt:Event) => resetHighlightListItem(evt.target as HTMLElement));
        }
    })
}

function toggleSidebar() {
    const sidebar = document.querySelector<HTMLElement>(sidebarSelector);
    const collapsed = sidebar.classList.toggle(sidebarToggleClass);

    collapseState = collapsed ? 'true' : 'false';

    postSidebarState();
}

function highlightListItem(item: HTMLElement) {
    item.classList.add(listItemToggleClass);

    const submenu = item.querySelector<HTMLElement>(subMenuSelector);
    if (submenu === null)
        return;

    const itemRect = item.getBoundingClientRect();
    const submenuRect = submenu.getBoundingClientRect();

    const top = Math.max(Math.min(window.innerHeight - itemRect.top - submenuRect.height, 0), -itemRect.top);
    submenu.style.top = top+'px';
    if (submenuRect.height > window.innerHeight) {
        submenu.style.height = window.innerHeight + 'px';
        submenu.style.overflow = 'scroll';
    }
}

function resetHighlightListItem(item: HTMLElement) {
    item.classList.remove(listItemToggleClass);
}

function postSidebarState() {
    if(collapseState === null)
        return;

    axios.post('index.php?module=ajax&action=sidebar&cmd=set_collapsed&value=' + collapseState)
        .catch(console.log);
}

export default init;