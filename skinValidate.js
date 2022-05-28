$(function () {
    const isAnon = mw.user.isAnon();
    const validateConfig = mw.config.get('wgSkinJSONValidate', {});
    const pageExists = mw.config.get( 'wgCurRevisionId' ) !== 0;
    const pageHasCategories =  mw.config.get( 'wgCategories', [] ).length;

    const rules = [
        {
            title: 'Skin does not show the article',
            description: '',
            condition: $( '.mw-body-content' ).length > 0
        },
        {
            title: 'Skin does not support site notices (banners)',
            description: '',
            condition: $( '.skin-json-hook-validation-element-SiteNoticeAfter' ).length > 0
        },
        {
            title: 'Skin is not responsive',
            description: '',
            condition: $( 'meta[name="viewport"]' ).length > 0
        },
        {
            title: 'Search may not support autocomplete',
            description: '',
            condition: $( '.mw-searchInput,#searchInput' ).length > 0
        },
        {
            title: 'Sidebar may not show main navigation',
            description: '',
            condition: $( '#n-mainpage-description' ).length !== 0
        },
        {
            title: 'Sidebar may not support extensions',
            description: '',
            condition: $( '.skin-json-hook-validation-element-SidebarBeforeOutput' ).length !== 0
        }
    ];

    const rulesAdvancedUsers = [
        {
            title: 'Personal menu may not support gadgets (#p-personal)',
            description: '',
            condition: $( '#p-personal' ).length !== 0
        },
        {
            title: 'Edit button may not be standard (#ca-edit)',
            description: '',
            condition: $( '#ca-edit' ).length !== 0
        }
    ];

    if ( validateConfig.wgLogos ) {
        rules.push( 
            {
                title: 'Skin may not support wordmarks',
                description: '',
                condition: () => {
                    const logos = validateConfig.wgLogos || {};
                    !( Array.from(document.querySelectorAll( 'img' ))
                        .filter((n) => {
                            const src = n.getAttribute('src');
                            return ( logos.wordmark && src === logos.wordmark.src ) ||
                                logos.icon;
                        } ).length === 0 && $( '.mw-wiki-logo' ).length === 0
                    );
                }
            }
        );
    }

    if ( $('.mw-parser-output h2').length > 3 ) {
        rules.push( 
            {
                title: 'Skin may not include a table of content',
                description: '',
                condition: $('.toc').length !== 0
            }
        );
    }

    if ( pageExists ) {
        rules.push( 
            {
                title: 'History button may not be standard (#ca-history)',
                description: '',
                condition: $('#ca-history').length !== 0
            },
            {
                title: 'Footer may not display copyright',
                description: '',
                condition: $('#footer-info-copyright, #f-list #copyright, .footer-info-copyright').length !== 0
            },
            {
                title: 'Skin may not show language menu',
                description: '',
                condition: $( '.mw-portlet-lang' ).length !== 0
            }
        );
    }

    if ( mw.loader.getState('ext.uls.interface') !== null ) {
        rules.push( 
            {
                title: 'Skin does not support compact ULS language menu',
                description: '',
                condition: $( '.mw-portlet-lang ul, #p-lang ul, .mw-interlanguage-selector' ).length !== 0
            }
        );
    }

    if ( pageHasCategories ) {
        rules.push( 
            {
                title: 'Skin may not show categories',
                description: '',
                condition: $( '.mw-normal-catlinks' ).length !== 0
            },
            {
                title: 'Skin may not show hidden categories',
                description: '',
                condition: $( '.mw-hidden-catlinks' ).length !== 0
            }
        );
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
        rules.push( 
            {
                title: `Does not support the ${hook} hook`,
                description: '',
                condition: enabledHooks.indexOf( hook ) > -1
            }
        );
    } );

    const createContainer = () => {
        const createToggle = () => {
            const toggle = document.createElement( 'div' );

            // SkinJSON elements are visible by default
            toggle.classList.add( 'skin-json-toggle' );
            toggle.setAttribute( 'title', 'Toggle SkinJSON elements' );
            toggle.textContent = 'hide';

            toggle.addEventListener( 'click', ( ev ) => {
                // Body class use to hide SkinJSON elements in CSS
                document.body.classList.toggle( 'skin-json--hidden' );
                ev.target.classList.toggle( 'skin-json-toggle--on' );
                ev.target.textContent = ev.target.classList.contains( 'skin-json-toggle--on' ) ? 'show' : 'hide';
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
        r.forEach( ( rule ) => {
            if (  rule.condition === true ) {
                score++;
            } else {
                improvements.push( rule.title );
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
    }

    scoreIt( rules, 'Readers' );

    if ( !isAnon ) {
        rulesAdvancedUsers.push( 
            {
                title: 'Skin may not support notifications',
                description: '',
                condition: $( '#pt-notifications-alert' ).length !== 0
            },
            {
                title: 'Personal menu may not support extensions',
                description: '',
                condition: $( '#pt-skin-json-hook-validation-user-menu' ).length !== 0
            }
        );
        scoreIt( rulesAdvancedUsers, 'Advanced users' );
    }
});