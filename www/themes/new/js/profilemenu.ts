import "../css/profilemenu.css";

const profileMenuSelector = '#profile-menu';
const profileInfoNameSelector = '#profile-info-name';
const wrapperSelector = '#profile-info-wrapper, #profile-picture-wrapper';

const eventtypes = ['click', 'touch'];
document.querySelectorAll(wrapperSelector).forEach(value => {
  eventtypes.forEach(type => value.addEventListener(type, (ev) => {
      ev.stopPropagation();
      setClasses();
  }))
})

eventtypes.forEach(type => document.addEventListener(type, () => setClasses(false)));

function setClasses(force?: boolean) {
    force = document.querySelector(profileMenuSelector)?.classList.toggle('visible', force);
    document.querySelector(profileInfoNameSelector)?.classList.toggle('menu-visible', force);
}