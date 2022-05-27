$(function () {
    const isAnon = mw.user.isAnon();
    const validateConfig = mw.config.get('wgSkinJSONValidate', {});
    const pageExists = mw.config.get( 'wgCurRevisionId' ) !== 0;
    const pageHasCategories =  mw.config.get( 'wgCategories', [] ).length;
    const rulesAdvancedUsers = {
        'Does not show personal menu in a gadget compatible way': $( '#p-personal' ).length !== 0,
        'Does not have the #ca-edit edit button': $('#ca-edit').length !== 0
    };
    const rules = {
        'Does not show the article': $( '.mw-body-content' ).length > 0,
        'Does not supports site notices (banners)': $( '.skin-json-banner-validation-element' ).length > 0,
        'Is not responsive': $('meta[name="viewport"]').length > 0,
        'May not show sidebar main navigation': $( '#n-mainpage-description' ).length !== 0,
        'May not support search autocomplete': $('.mw-searchInput,#searchInput').length > 0,
        'Supports extensions extending the sidebar': $(
            '.skin-json-validation-element-SidebarBeforeOutput'
        ).length !== 0
    };
    if ( validateConfig.wgLogos ) {
        const logos = validateConfig.wgLogos || {};
        rules['Does not seem to support wordmarks'] = !(
            Array.from(document.querySelectorAll( 'img' ))
                .filter((n) => {
                    const src = n.getAttribute('src');
                    return ( logos.wordmark && src === logos.wordmark.src ) ||
                        logos.icon;
                } ).length === 0 && $( '.mw-wiki-logo' ).length === 0
        );
    }
    if ( $('.mw-parser-output h2').length > 3 ) {
        rules['May not include a table of contents'] = $('.toc').length !== 0;
    }
    if ( pageExists ) {
        rules['May not link to the history page in the standard way'] = $('#ca-history').length !== 0;
        rules['May not display copyright'] = $('#footer-info-copyright, #f-list #copyright, .footer-info-copyright').length !== 0;
        rules['May not support languages'] = $( '.mw-portlet-lang' ).length !== 0;
    }
    if ( mw.loader.getState('ext.uls.interface') !== null ) {
        rules['Does not support ULS compact language links'] = $( '.mw-portlet-lang ul, #p-lang ul, .mw-interlanguage-selector' ).length !== 0;
    }
    if ( pageHasCategories ) {
        rules['May not support hidden categories'] = $( '.mw-hidden-catlinks' ).length !== 0;
        rules['May not support categories'] = $( '.mw-normal-catlinks' ).length !== 0;
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

        let container = document.querySelector( '.skinjson-scores' );
        if ( !container ) {
            container = document.createElement( 'div' );
            container.classList.add( 'skinjson-scores' );
            document.body.appendChild( container );
        }

        $( '<div>' ).addClass(
            `skinjson-score skinjson-score-${grade.label}`
        ).attr(
            'title', 
            improvements.length ?
                `Scoring by SkinJSON.\nPossible improvements for ${who}:\n${improvements.join('\n')}` :
                `Skin passed all tests for ${who}`
        ).text( grade.name ).on( 'click', (ev) => {
            ev.target.parentNode.removeChild( ev.target );
        }).appendTo( container );
    }
    scoreIt( rules, 'Readers' );

    if ( !isAnon ) {
        rulesAdvancedUsers['May not support notifications'] = $( '#pt-notifications-alert' ).length !== 0;
        rulesAdvancedUsers['Supports extensions extending personal tools'] = $(
            '#pt-skin-json-hook-validation-user-menu'
        ).length !== 0;
        scoreIt( rulesAdvancedUsers, 'Advanced users' );
    }
});