# SkinJSON

Returns skin data as a SkinJSON to help you develop a [MediaWiki skin](https://www.mediawiki.org/wiki/Manual:How_to_make_a_MediaWiki_skin). 

# Install
* Make sure you have MediaWiki setup.
* Clone this repository in your mediawiki/skins folder.
* Rename the folder to SkinJson
* Add the following to LocalSettings.php
```
wfLoadSkin('SkinJson');
```
* Navigate to the page you want to debug and add `?useskin=skinjson` to the URL e.g. http://localhost:8888/w/index.php/Main_page?useskin=skinjson
* Install a Chrome or Firefox extension for prettifying JSON to allow exploration of the data.

# Skins that extend SkinMustache

The skin also adds a special URL to inspect data in skins which extend the SkinMustache class providing their own skin data.

To activate this mode, you must make use of the `useskin` and `useformat` query string parameters.

For example to inspect SkinVector's custom data use the following query string on any URL:
`?useskin=vector&useformat=json`
