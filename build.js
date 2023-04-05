#!/usr/bin/env/node

const archiver = require('archiver')
const { spawn, spawnSync } = require("node:child_process");
const { copyFileSync, readFileSync, existsSync, statSync, mkdirSync, readdirSync, writeFileSync, renameSync, rmSync } = require("node:fs");
const path = require("node:path");

const yargs = require("yargs");
const { hideBin } = require("yargs/helpers");

const fs = require('fs');

const noop = (...args) => {};
const pad_number = (num) => num < 10 ? `0${num}` : num;

const fullpath = (p) => path.resolve(path.normalize(p));

// haha
const empty_promise = (fail = false) => new Promise((resolve, reject) => !fail ? resolve(null) : reject(new Error('Empty promise')));


const argv = yargs(hideBin(process.argv))
    // composer file
    .alias('composer-file', 'cf')
    .default('composer-file', 'composer.json')
    
    // logging on/off
    .boolean('v')
    .alias('v', 'verbose')
    .default('v', true)
    .argv;

const BASE_DIR = fullpath('.');
const WORKSPACE = fullpath(path.join(BASE_DIR, '.workspace'));
const DIST_DIR = fullpath(path.join(BASE_DIR, 'dist'));

const pjson = require(fullpath(path.join(BASE_DIR, 'package.json')));
const current_version = pjson.version;
let verbose = argv.verbose;
let composer_file = argv.cf;

const LOG = (...args) => verbose && console.log(...args);

LOG(`Build started for v${current_version}\nComposer file to use:\t${composer_file}\n`);
LOG(`Resolved paths\n==========================================================\nBase dir:\t${BASE_DIR}\nWorkspace:\t${WORKSPACE}\nDist dir:\t${DIST_DIR}`);

ensureWorkspaceFolder();
doFullBuild();


// MAIN BUILD
/**
 * Runs the build script updating both yarn and composer dependencies.
 */
function doFullBuild()
{
    console.time("Full build");
    
    
    _writeVersionToFiles( current_version);
    
    _ensureRequiredFiles();

    process.chdir(WORKSPACE);
    
    LOG("Starting composer");
    taskSync(
            'composer',
            ['update', '--no-dev'],
            { env: { ...process.env, 'COMPOSER': composer_file } },
            null,
            ( err) => {
                console.error('Composer failed with reason', err);
                process.exit(1);
            }
    );
    
    LOG("Removing not required");
    fs.unlinkSync( fullpath('./composer.json'));
    fs.unlinkSync( fullpath('./composer.lock'));
    
    const package_content = path.join( DIST_DIR, 'convoworks-gpt');
    copyRecursiveSync( WORKSPACE, package_content);
    
    const destination = fullpath(path.join(BASE_DIR, 'convoworks-gpt-v'+current_version+'.zip'));
//    const destination = fullpath(path.join(DIST_DIR, 'convoworks-gpt-v'+current_version+'.zip'));
    
    LOG(`Zipping files from ${DIST_DIR} to ${destination}\n`);
        
    const zip = zipDirectory( DIST_DIR, destination);
    
    
    zip.then(
        () => {
            LOG(`Zipping files done`);
            _wrapUp( "Full build done");
            process.exit(0);
        }
    ).catch(
        ( err) => {
            console.error('Failed to zip files with reason', err);
            process.exit(1);
        }
    );
}


/**
 * Takes a new version and writes them to the necessary files.
 * @param {string} version Version to write to `package.json` and `convo-plugin.php`
 */
function _writeVersionToFiles( version)
{
    const php_plugin_file_path = fullpath( path.join( './', "convoworks-gpt.php"));
    let php_plugin_file = readFileSync( php_plugin_file_path, { encoding: "utf-8", flag: "r+" });
    
    const php_composer_file_path = fullpath( path.join( './', "composer.json"));
    let php_composer_file = readFileSync( php_composer_file_path, { encoding: "utf-8", flag: "r+" });
    const new_composer = JSON.parse( php_composer_file);
    new_composer.version = version;

    php_plugin_file = php_plugin_file
        .replace(/Version:\s.+/g, `Version: ${version}`);

    writeFileSync(
        fullpath( php_composer_file_path),
        JSON.stringify( new_composer, null, 4)
    );
    writeFileSync(
        php_plugin_file_path,
        php_plugin_file
    );
}

// UTIL
/**
 * Ensures that the `.workspace` directory exists. If it doesn't, it will be created with the structure
 * `.workspace/dist/convoworks-wp` for future builds.
 * @returns {boolean} Returns `true` if the `.workspace` folder is present
 * and a full build is not required, `false` otherwise.
 */
function ensureWorkspaceFolder() {
    if ( existsSync(WORKSPACE)) {
        LOG("Workspace directory exist, going to remove it.");
        rmSync( WORKSPACE, { recursive: true, force: true });
    }
    
    if ( existsSync(DIST_DIR)) {
        rmSync( DIST_DIR, { recursive: true, force: true });
    }
    
    LOG("Creating .workspace directory");
    mkdirSync(".workspace", { recursive: true });
    
    const dist = path.join( DIST_DIR, 'convoworks-gpt');
    
    
    
    
    LOG("Creating dist directory");
    mkdirSync( dist, { recursive: true });
}

/**
 * Make sure that all the files necessary for the build process to function are present
 * in the `.workspace` directory. If a file is missing, it will error and exit out of the process.
 */
function _ensureRequiredFiles() {
    const required_files = [
        composer_file, 'README.md', 'CHANGELOG.md', 'convoworks-gpt.php'
    ];

    const required_folders = [
        'src'
    ];

    for (const file of required_files) {
        let file_path = fullpath(file);
        let workspace_path = fullpath(`.workspace/${file}`);

        if (!existsSync(file_path)) {
            console.error(`Missing required file: ${file_path}! Aborting build.`);
            process.exit(1);
        }

        if (!existsSync(workspace_path)) {
            copyFileSync(file_path, workspace_path);
        }
    }

    for (const folder of required_folders) {
        copyRecursiveSync(folder, `.workspace/${folder}`);
    }
}


/**
 * Changes CWD to the root of the project, and copies `package.json` and `convo-plugin.php` over from the workspace directory to keep the newly updated version between builds.
 * @returns {number} 0 on success, 1 on failure.
 */
function _solidifyVersion()
{
    try {
        copyFileSync(fullpath(path.join(WORKSPACE, "package.json")),     fullpath(path.join(BASE_DIR, "package.json")));
        copyFileSync(fullpath(path.join(WORKSPACE, "convo-plugin.php")), fullpath(path.join(BASE_DIR, "convo-plugin.php")));
        return 0;
    } catch (err) {
        console.error('Failed to solidify version by copying package.json and convo-plugin.php back to root.');
        console.error(err);
        return 1;
    }
}

/**
 * Runs `yarn run gulp zip` in the workspace directory to zip all the files from `dist`. After that, calls `_solidifyVersion` to ensure new version updates between builds.
 * Finally, logs out the time elapsed since the build started based on the `timeLabel` provided.
 * @param {string} timeLabel Timer label to end and log out.
 */
function _wrapUp(timeLabel)
{
    console.timeEnd(timeLabel);
    return;
}


// UTIL


/**
 * Wrap a `spawn` call into a `Promise`.
 * @param {string} cmd Command to pass to the `spawn` function
 * @param {Array<string>} args Set of arguments for the `spawn` function
 * @param {object} opts A map of options for `spawn`
 * @param {string} passToStdin If the process opens its STDIN, what to pass through. @TODO TEMPORARY
 * @return {Promise<number>} Returns a `Promise` containing the code with which the process exited
 */
function task(cmd, args, opts, passToStdin = null)
{
    opts = { shell: process.platform === 'win32', ...opts };

    const p = new Promise(function (resolve, reject) {
        const spawned_process = spawn(cmd, args, opts);

        LOG('Spawned', cmd, args);

        spawned_process.on('exit', (output) => {
            if (output !== 0) {
                LOG(cmd, args, 'exited with non 0');
                return reject(output);
            }

            LOG(cmd, args, 'exited with output', output);
            return resolve(output);
        });

        spawned_process.stdout.on('data', (data) => {
            LOG(data.toString());
        })
        
        spawned_process.on('error', (err) => {
            LOG(cmd, args, 'errored');
            return reject(err);
        })
        
        if (passToStdin) {
            LOG('Writing', passToStdin, 'to', cmd);
            spawned_process.stdin.setDefaultEncoding('utf-8');
            spawned_process.stdin.write(`${passToStdin}\n`);
            spawned_process.stdin.end();
        }
    });

    return p;
}

/**
 * Spawn a synchronous process and wait for it to finish, calling the provided `done` callback upon completion.
 * @param {string} cmd Command to pass to the `spawnSync` function
 * @param {Array<string>} args List of arguments to pass to the `spawnSync` function
 * @param {object} opts Map of options to pass to `spawnSync`
 * @param {(results: { code: number, output: string }) => void} done Callback to trigger once the process is complete
 * @param {(err: Error) => void} onError Optional, callback to trigger if the process outputs to `stderr` or errors in any other way.
 */
function taskSync(cmd, args, opts, done = null, onError = null)
{
    done = done || noop;
    onError = onError || noop;
    opts = { shell: process.platform === 'win32', ...opts };

    const spawned_process = spawnSync(cmd, args, opts);

    LOG('Sync spawned', cmd, args);

    if (spawned_process.status !== 0) {
        if (spawned_process.error) {
            onError(spawned_process.error);
            return;
        }

        if (spawned_process.stderr) {
            onError(new Error(spawned_process.stderr.toString()));
            return;
        }
    }
    
    LOG('Sync task finished');

    done({ code: spawned_process.status, output: spawned_process.stdout.toString() });
}

/**
 * Copies files from `src` to `dest`, and if `src` is a directory, it will be resursively traversed and copied, essentially behaving like `cp -r`.
 * @param {string} src Source file to copy. If `src` is a directory, it will be recursively copied to `dest`
 * @param {string} dest Destination path to copy `src` to.
 */
function copyRecursiveSync(src, dest)
{
    const exists = existsSync(src);
    const stats = exists && statSync(src);
    const is_directory = exists && stats.isDirectory();
    if (is_directory) {
        if (!existsSync(dest)) {
            mkdirSync(dest);
        }
        readdirSync(src).forEach(function (childItemName) {
            copyRecursiveSync(fullpath(path.join(src, childItemName)), fullpath(path.join(dest, childItemName)));
        });
    } else {
        copyFileSync(src, dest);
    }
};

/**
 * @param {String} sourceDir: /some/folder/to/compress
 * @param {String} outPath: /path/to/created.zip
 * @returns {Promise}
 */
function zipDirectory(sourceDir, outPath) {
  const archive = archiver('zip', { zlib: { level: 9 }});
  const stream = fs.createWriteStream(outPath);

  return new Promise((resolve, reject) => {
    archive
      .directory(sourceDir, false)
      .on('error', err => reject(err))
      .pipe(stream)
    ;

    stream.on('close', () => resolve());
    archive.finalize();
  });
}




