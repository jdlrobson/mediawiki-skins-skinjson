const init = () => {
	const alertBtn = document.createElement( 'div' );
	alertBtn.textContent = 'Development warning: This skin may break in future MediaWiki versions. Click this message to show deprecation notices.';
	alertBtn.setAttribute( 'class', 'errorbox messagebox' );
	let refnode = document.querySelector('body meta[charset]');
	if ( refnode ) {
		const deprecationMsg = [];
		while ( refnode.previousSibling ) {
			deprecationMsg.unshift( refnode.textContent );
			refnode = refnode.previousSibling;
			// delete the one we just looked at.
			refnode.nextSibling.parentNode.removeChild( refnode.nextSibling );
		}
		deprecationMsg.unshift( refnode.textContent );
		refnode.parentNode.removeChild( refnode );
		alertBtn.addEventListener( 'click', function () {
			text.style.display = 'block';
		} );

		const text = document.createElement( 'pre' );
		text.style.display = 'none';
		text.textContent = deprecationMsg.join( '' );
		alertBtn.appendChild( text );

		const siteNotice = document.querySelector( '#siteNotice' );
		if ( siteNotice ) {
			siteNotice.appendChild( alertBtn );
		} else {
			const bodyContent = document.querySelector( '.mw-body-content' );
			bodyContent.insertBefore( alertBtn, bodyContent.firstChild );
		}
	}

};

if (
	document.readyState === "complete"
) {
	init();
} else {
	document.addEventListener( 'DOMContentLoaded', init );
}
