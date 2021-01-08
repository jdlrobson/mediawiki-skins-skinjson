const fs = require('fs');
const templateDirectoryPath = `/Users/jrobson/git/core/skins/Vector/includes/templates/`;
const templateDirectory = fs.readdirSync( templateDirectoryPath, {
    withFileTypes: true
} );
const templates = {};
templateDirectory.forEach( (file) => {
    if ( !file.isDirectory() ) {
        const template = fs.readFileSync(`${templateDirectoryPath}/${file.name}`).toString();
        templates[ file.name.replace('.mustache', '') ] = template;
    }
});
fs.writeFileSync(`${__dirname}/templates.json`, JSON.stringify(templates));
