const fs = require('fs-extra');
const archiver = require('archiver');
const { exec } = require('child_process');
const packageJson = require('./package.json');

const version = packageJson.version; // Get version from package.json
const buildDir = `dist/temp`; // Temporary build directory
const outputDir = `build`; // Final output directory
const pluginSlug = `convoworks-gpt`; // Final output directory
const pluginDir = `${buildDir}/${pluginSlug}-v${version}`; // Directory name with version
const rootFiles = ['src', `${pluginSlug}.php`, 'composer.json', 'composer.lock', 'README.md', 'CHANGELOG.md'];

// Step 1: Prepare directories
fs.ensureDirSync(pluginDir);
fs.ensureDirSync(outputDir);

// Step 2: Copy relevant files
rootFiles.forEach( (file) => {
    fs.copySync(file, `${pluginDir}/${file}`);
})

// Step 3: Run Composer
exec(`cd ${pluginDir} && composer install --no-dev`, (error, stdout, stderr) => {
    if (error) {
        console.error(`exec error: ${error}`);
        return;
    }

    console.log(`stdout: ${stdout}`);
    console.error(`stderr: ${stderr}`);

    // Step 4: Create a zip file
    const output = fs.createWriteStream(`${outputDir}/${pluginSlug}-v${version}.zip`);
    const archive = archiver('zip', {
        zlib: { level: 9 } // Compression level
    });

    archive.on('warning', function(err) {
        if (err.code === 'ENOENT') {
            console.warn(err);
        } else {
            throw err;
        }
    });

    archive.on('error', function(err) {
        throw err;
    });

    archive.pipe(output);
    archive.directory(pluginDir, `${pluginSlug}`);
    archive.finalize();

    // Clean up temporary directory after archiving
    output.on('close', function() {
        console.log(archive.pointer() + ' total bytes');
        console.log('Archiver has been finalized and the output file descriptor has closed.');

        fs.emptyDir(buildDir, err => {
            if (err) return console.error(`Error emptying temporary build directory: ${err}`);

            fs.remove(buildDir, err => {
                if (err) return console.error(`Error removing temporary build directory: ${err}`);
                console.log('Cleaned up temporary build directory.');
                process.exit(0);
            });
          });
    });
});
