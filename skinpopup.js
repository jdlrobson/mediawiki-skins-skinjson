const type = 'skinjson-tooltip';

const fetchPreviewForTitle = ( title, el ) => {
	const deferred = $.Deferred();
	const hook = el.dataset.hook;
	const info = el.dataset.info;
	const url = `https://www.mediawiki.org/wiki/Manual:Hooks/${hook}`;
	let code = info ? `<p>Use the following check inside the hook body: <pre>${info}</pre>.</p>` : '';
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

module.exports = {
	type,
	selector: '.skin-json-validation-element__title',
	gateway: {
		fetchPreviewForTitle
	}
};
