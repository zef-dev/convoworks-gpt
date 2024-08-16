const fs = require('fs');

// Read the version from package.json
const packageJson = require('./package.json');
const version = packageJson.version;

// Update composer.json
const composerJsonPath = './composer.json';
const composerJson = require(composerJsonPath);
composerJson.version = version;
fs.writeFileSync(composerJsonPath, JSON.stringify(composerJson, null, 4), 'utf8');

// Update PHP file where the version is defined
const phpFilePath = './convoworks-gpt.php';
let phpContent = fs.readFileSync(phpFilePath, 'utf8');

// Replace the version in the define() statement
phpContent = phpContent.replace(/define\( 'CONVO_GPT_VERSION', '.*' \);/, `define( 'CONVO_GPT_VERSION', '${version}' );`);

// Replace the version in the plugin header comment
phpContent = phpContent.replace(/(\* Version:\s*)[0-9\.]+/, `$1${version}`);

fs.writeFileSync(phpFilePath, phpContent, 'utf8');

console.log(`Version synchronized to ${version}`);
