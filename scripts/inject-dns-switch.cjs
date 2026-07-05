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
    
    // Print diagnostic directory information to see what files exist on the runner
    const parentDir = path.join(__dirname, '../vendor/nativephp/electron/resources/js');
    console.log('Listing parent directory:', parentDir);
    if (fs.existsSync(parentDir)) {
        try {
            const files = fs.readdirSync(parentDir);
            console.log('Files in parent directory:', files);
            
            const srcDir = path.join(parentDir, 'src');
            if (fs.existsSync(srcDir)) {
                console.log('src directory exists. Files inside src:', fs.readdirSync(srcDir));
            } else {
                console.log('src directory does NOT exist under:', parentDir);
            }
        } catch (err) {
            console.error('Failed to read parent directory:', err.message);
        }
    } else {
        console.log('Parent directory does NOT exist:', parentDir);
        // Print the vendor folder contents
        const vendorDir = path.join(__dirname, '../vendor/nativephp/electron');
        if (fs.existsSync(vendorDir)) {
            console.log('Files in vendor/nativephp/electron:', fs.readdirSync(vendorDir));
        } else {
            console.log('vendor/nativephp/electron does NOT exist');
        }
    }
    process.exit(1);
}
