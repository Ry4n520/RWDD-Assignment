const navbar = document.getElementById('navbar');
const openButton = document.getElementById('open-sidebar-button');

const media= window.matchMedia('(width < 700px)');

media.addEventListener('change', (e) => updateNavbar(e));

function updateNavbar(e){
    const isMobile = e.matches;
    if(isMobile){
        navbar.setAttribute('inert', '');
    } else {
        navbar.removeAttribute('inert');
    }
}

function openSidebar(){
    navbar.classList.add('show');
    openButton.setAttribute('aria-expanded', 'true');
    navbar.removeAttribute('inert');
}

function closeSidebar(){
    navbar.classList.remove('show');
    openButton.setAttribute('aria-expanded', 'false');
    navbar.setAttribute('inert', '');
}

updateNavbar(media)

/* Theme toggle: bright (light) and dark modes */
const themeToggle = document.getElementById('theme-toggle');
function applyTheme(isLight){
    if(isLight){
        document.body.classList.add('light');
    } else {
        document.body.classList.remove('light');
    }
}

// Initialize theme from localStorage or prefers-color-scheme
try{
    const stored = localStorage.getItem('theme');
    if(stored){
        applyTheme(stored === 'light');
    } else {
        const prefersLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
        applyTheme(prefersLight);
    }
}catch(e){
    // localStorage may be unavailable
}

if(themeToggle){
    themeToggle.addEventListener('click', ()=>{
        const isLight = document.body.classList.toggle('light');
        try{ localStorage.setItem('theme', isLight ? 'light' : 'dark'); }catch(e){}
    });
}