const fs = require('fs');
const path = require('path');

const file = path.join(__dirname, '../vendor/nativephp/electron/resources/js/src/main/index.js');

if (fs.existsSync(file)) {
    let content = fs.readFileSync(file, 'utf8');
    if (!content.includes('host-rules')) {
        content = content.replace(
            "import {app} from 'electron'",
            "import {app} from 'electron'\n\n// Map all *.localhost subdomains to 127.0.0.1 locally to support offline tenant routing\napp.commandLine.appendSwitch('host-rules', 'MAP *.localhost 127.0.0.1');"
        );
        fs.writeFileSync(file, content, 'utf8');
        console.log('Successfully injected host-rules switch into Electron boot index.js');
    } else {
        console.log('host-rules switch is already present in Electron boot index.js');
    }
} else {
    console.error('Electron boot index.js file not found at:', file);
    process.exit(1);
}
