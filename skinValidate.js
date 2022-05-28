$(function () {
    const isAnon = mw.user.isAnon();
    const validateConfig = mw.config.get('wgSkinJSONValidate', {});
    const pageExists = mw.config.get( 'wgCurRevisionId' ) !== 0;
    const pageHasCategories =  mw.config.get( 'wgCategories', [] ).length;

    // Rules objects
    // Key - Label message that is shown in info when false
    // Value - Condition to check; True -> Pass; False -> Fail
    const rules = {
        'Skin does not show the article': $( '.mw-body-content' ).length > 0,
        'Skin does not support site notices (banners)': $( '.skin-json-hook-validation-element-SiteNoticeAfter' ).length > 0,
        'Skin is not responsive': $('meta[name="viewport"]').length > 0,
        'Search may not support autocomplete': $('.mw-searchInput,#searchInput').length > 0,
        'Sidebar may not show main navigation': $( '#n-mainpage-description' ).length !== 0,
        'Sidebar may not support extensions': $(
            '.skin-json-hook-validation-element-SidebarBeforeOutput'
        ).length !== 0
    };
    const rulesAdvancedUsers = {
        'Personal menu may not support gadgets (#p-personal)': $( '#p-personal' ).length !== 0,
        'Edit button may not be standard (#ca-edit)': $('#ca-edit').length !== 0
    };

    if ( validateConfig.wgLogos ) {
        const logos = validateConfig.wgLogos || {};
        rules['Skin may not support wordmarks'] = !(
            Array.from(document.querySelectorAll( 'img' ))
                .filter((n) => {
                    const src = n.getAttribute('src');
                    return ( logos.wordmark && src === logos.wordmark.src ) ||
                        logos.icon;
                } ).length === 0 && $( '.mw-wiki-logo' ).length === 0
        );
    }
    if ( $('.mw-parser-output h2').length > 3 ) {
        rules['Skin may not include a table of content'] = $('.toc').length !== 0;
    }
    if ( pageExists ) {
        rules['History button may not be standard (#ca-history)'] = $('#ca-history').length !== 0;
        rules['Footer may not display copyright'] = $('#footer-info-copyright, #f-list #copyright, .footer-info-copyright').length !== 0;
        rules['Skin may not show language menu'] = $( '.mw-portlet-lang' ).length !== 0;
    }
    if ( mw.loader.getState('ext.uls.interface') !== null ) {
        rules['Skin does not support compact ULS language menu'] = $( '.mw-portlet-lang ul, #p-lang ul, .mw-interlanguage-selector' ).length !== 0;
    }
    if ( pageHasCategories ) {
        rules['Skin may not show categories'] = $( '.mw-normal-catlinks' ).length !== 0;
        rules['Skin may not show hidden categories'] = $( '.mw-hidden-catlinks' ).length !== 0;
    }
    const enabledHooks = Array.from(
        new Set(
            Array.from(
                document.querySelectorAll( '.skin-json-hook-validation-element' )
            ).map((node) => node.dataset.hook )
        )
    );
    [
        'SkinAfterContent',
        'SkinAddFooterLinks',
        'SkinAfterPortlet'
    ].forEach( ( hook ) => {
        rules[`Does not support the ${hook} hook`] = enabledHooks.indexOf( hook ) > -1;
    } );

    const createContainer = () => {
        const createToggle = () => {
            const toggle = document.createElement( 'div' );

            // SkinJSON elements are visible by default
            toggle.classList.add( 'skin-json-toggle' );
            toggle.setAttribute( 'title', 'Toggle SkinJSON elements' );
            toggle.textContent = 'off';

            toggle.addEventListener( 'click', ( ev ) => {
                // Body class use to hide SkinJSON elements in CSS
                document.body.classList.toggle( 'skin-json--hidden' );
                ev.target.classList.toggle( 'skin-json-toggle--on' );
                ev.target.textContent = ev.target.classList.contains( 'skin-json-toggle--on' ) ? 'on' : 'off';
            } );
            return toggle;
        }

        const el = document.createElement( 'div' );
        el.classList.add( 'skin-json-overlay' );
        el.appendChild( createToggle() );
        document.body.appendChild( el );
        return el;
    };

    const container = document.querySelector( '.skin-json-overlay' ) ?? createContainer();

    const scoreToGrade = (s, r) => {
        const total = Object.keys(r).length;
        const name = `${s} / ${total}`;
        const pc = s / total;
        if ( pc > 0.7 ) {
            return { name, label: 'high' };
        } else if ( pc > 0.5 ) {
            return { name, label: 'med' };
        } else {
            return { name, label: 'low' };
        }
    };

    function scoreIt( r, who ) {
        const improvements = [];
        let score = 0;
        Object.keys(r).forEach((rule) => {
            if ( r[rule] === true ) {
                score++;
            } else {
                improvements.push(rule);
            }
        });
        const grade = scoreToGrade( score, r );

        // NOTE: Maybe this should be put into some kind of template,
        // maybe HTML template tag or Mustache or template literals
        const scorebox = document.createElement( 'div' );
        scorebox.classList.add( 'skin-json-score', `skin-json-score-${grade.label}` );
        scorebox.textContent = grade.name;

        const scoreinfo = document.createElement( 'div' );
        scoreinfo.classList.add( 'skin-json-score-info' );
        scoreinfo.innerText = improvements.length ? 
            `Scoring by SkinJSON.\nPossible improvements for ${who}:\n${improvements.join('\n')}` :
            `Skin passed all tests for ${who}`;
        scorebox.appendChild( scoreinfo );

        container.appendChild( scorebox );
        scorebox.addEventListener( 'click', ( ev ) => {
            ev.target.parentNode.removeChild( ev.target );
        } );
    }

    scoreIt( rules, 'Readers' );

    if ( !isAnon ) {
        rulesAdvancedUsers['Skin may not support notifications'] = $( '#pt-notifications-alert' ).length !== 0;
        rulesAdvancedUsers['Personal menu may not support extensions'] = $(
            '#pt-skin-json-hook-validation-user-menu'
        ).length !== 0;
        scoreIt( rulesAdvancedUsers, 'Advanced users' );
    }
});