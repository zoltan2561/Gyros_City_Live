document.addEventListener('DOMContentLoaded', () => {
    const rail = document.querySelector('.city-menu-categories');
    const active = rail?.querySelector('[aria-current="page"]');
    if (!rail || !active || window.innerWidth > 991) return;
    const offset = active.getBoundingClientRect().left - rail.getBoundingClientRect().left;
    rail.scrollLeft += offset - (rail.clientWidth - active.offsetWidth) / 2;
});
