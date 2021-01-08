
import mustache from 'mustache';
import TEMPLATE_PARTIALS from './templates.json';

const DATA_CANNOT_RENDER = {
	'html-title': 'Error',
	'html-body-content': '<div class="errorbox">This page cannot be rendered here.</div>'
};


const template = TEMPLATE_PARTIALS.skin;

function loadPage(title) {
	document.body.innerHTML = '';
	fetch(`https://skins-demo.wmflabs.org/w/index.php?useskin=skinjson&&origin=*&title=${title}`)
		.then((r) => r.json())
		.then((json) => {
			const templateData = json || DATA_CANNOT_RENDER;
			document.body.innerHTML = mustache.render(
				template, templateData, TEMPLATE_PARTIALS
			);
		});
}
window.addEventListener( 'click', (ev) => {
    if(ev.target.tagName === 'A') {
    	window.location.hash = ev.target.getAttribute('href');
        ev.preventDefault();
    }
})

function loadFromHash() {
	let title;
	const m = window.location.hash.match(/\/wiki\/(.*)/);
	if (m && m[1]) {
		title = m[1];
	} else {
		title = 'JSON';
	}
	loadPage(title);
}
loadFromHash();
window.onhashchange = function () {
	loadFromHash();
}