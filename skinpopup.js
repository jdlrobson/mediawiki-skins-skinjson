const type = 'skinjson-tooltip';
const ENABLED_CLASS = 'skin-skinjson-popups-enabled';

const fetchPreviewForTitle = ( title, el ) => {
	const deferred = $.Deferred();
	const hook = el.dataset.hook;
	const info = el.dataset.info;
	const url = `https://www.mediawiki.org/wiki/Manual:Hooks/${hook}`;
	const code = info ? `<p>Use the following check inside the hook body: <pre>${info}</pre>.</p>` : '';
	deferred.resolve( {
		title: 'Hello world',
		extract: [
			`<p>Modifications to this part of the skin can be made using the <strong><a href="${url}">${hook}</a></strong> hook.</p>
			${code}`
		],
		url,
		type,
		languageCode: 'en',
		languageDirection: 'ltr',
		thumbnail: undefined,
		pageId: -1
	} );
	return deferred;
};

// hide all descriptions
function init() {
	document.querySelectorAll( '.skin-json-validation-element__description' ).forEach( ( node ) => {
		const prev = node.previousElementSibling;
		if ( prev.classList.contains( 'skin-json-validation-element__title' ) ) {
			prev.dataset.info = node.textContent;
			node.parentNode.removeChild( node );
		}
	} );
	document.querySelectorAll( '.skin-json-validation-element__title' ).forEach( ( node ) => {
		node.dataset.hook = node.textContent;
		node.textContent = '';
	} );
	document.body.classList.add( ENABLED_CLASS );
}

if ( !document.body.classList.contains( ENABLED_CLASS ) ) {
	init();
}

module.exports = {
	type,
	enabled: mw.user.options.get( 'skinjson-popups' ),
	selector: '.skin-json-validation-element__title',
	gateway: {
		fetchPreviewForTitle
	}
};
