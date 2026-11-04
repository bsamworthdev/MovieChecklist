const fs = require('node:fs');
const path = require('node:path');
const {spawnSync} = require('node:child_process');

const root = path.resolve(__dirname, '..');
const directory = path.join(root, 'tests/js');
const files = fs.readdirSync(directory).filter(file => file.endsWith('.cjs')).sort();
for (const file of files) {
    process.stdout.write(`\nRunning ${file}\n`);
    const result = spawnSync(process.execPath, [path.join(directory, file)], {cwd: root, stdio: 'inherit'});
    if (result.error) {process.stderr.write(`${result.error.message}\n`); process.exit(1);}
    if (result.status !== 0) process.exit(result.status || 1);
}
