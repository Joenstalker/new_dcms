const fs = require('fs');
const path = require('path');

const tenantDir = 'd:/dentistmng/dcms_new/dcms/resources/js/Pages/Tenant';

function processDirectory(dir) {
    const files = fs.readdirSync(dir);
    
    for (const file of files) {
        const fullPath = path.join(dir, file);
        const stat = fs.statSync(fullPath);
        
        if (stat.isDirectory()) {
            processDirectory(fullPath);
        } else if (fullPath.endsWith('.vue')) {
            let content = fs.readFileSync(fullPath, 'utf8');
            
            // Check if it has the brandingState import
            const importString = "import { brandingState } from '@/States/brandingState';\n";
            if (content.includes(importString)) {
                // Remove all occurrences of the import
                content = content.replace(new RegExp("import { brandingState } from '@/States/brandingState';\\r?\\n", 'g'), '');
                
                // Insert it right after <script setup> or <script>
                // We'll look for <script setup>
                const scriptSetupRegex = /<script setup>(\r?\n)/;
                if (scriptSetupRegex.test(content)) {
                    content = content.replace(scriptSetupRegex, `<script setup>$1${importString}`);
                    fs.writeFileSync(fullPath, content, 'utf8');
                    console.log(`Fixed imports in: ${fullPath}`);
                }
            }
        }
    }
}

processDirectory(tenantDir);
console.log('Done!');
