{
	"name": "SkinJson",
	"version": "1.2.0",
	"author": "Jon Robson",
	"url": "https://github.com/jdlrobson/mediawiki-skins-skinjson",
	"descriptionmsg": "skinjson-desc",
	"namemsg": "skinname-skinjson",
	"license-name": "CC0-1.0",
	"type": "skin",
	"requires": {
		"MediaWiki": ">= 1.38.0"
	},
	"ValidSkinNames": {
		"skinjson": {
			"class": "SkinJSON",
			"skippable": true,
			"args": [ {
				"name": "skinjson"
			} ]
		}
	},
	"AutoloadClasses": {
		"SkinJSON": "SkinJSON.php"
	},
	"ResourceModules": {
		"skins.skinjson.debug.styles": {
			"styles": [ "skindebug.css" ],
			"targets": [ "desktop", "mobile" ]
		},
		"skins.skinjson.debug": {
			"es6": true,
			"scripts": [ "skindebug.js" ],
			"targets": [ "desktop", "mobile" ]
		},
		"skins.skinjson": {
			"class": "ResourceLoaderSkinModule",
			"features": {
				"normalize": true,
				"elements": true,
				"content-tables": true,
				"content-links": true,
				"content-links-external": false,
				"content-media": true,
				"interface-category": true,
				"toc": true
			},
			"targets": [ "desktop", "mobile" ]
		}
	},
	"MessagesDirs": {
		"SkinJSON": [
			"i18n"
		]
	},
	"config": {
		"SkinJSONTestUser": "",
		"SkinJSONDebug": true
	},
	"Hooks": {
		"OutputPageBeforeHTML": "SkinJSON::onOutputPageBeforeHTML",
		"RequestContextCreateSkin": "SkinJSON::onRequestContextCreateSkin"
	},
	"ResourceFileModulePaths": {
		"localBasePath": "",
		"remoteSkinPath": "SkinJSON"
	},
	"manifest_version": 1
}
