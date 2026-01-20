#!/bin/bash

# Script to remove ALL comments from PHP files
# Will remove: docblocks (/** */), single-line comments (//), and block comments (/* */)

MODULES="Auth Enrollments Schemes"

echo "=== Removing ALL comments from Services in: $MODULES ==="
echo ""

for MODULE in $MODULES; do
    echo "Processing $MODULE module..."
    
    # Find all PHP files in Services
    find "Modules/$MODULE/app/Services" -name "*.php" -type f | while read -r file; do
        echo "  Cleaning: $file"
        
        # Create backup
        cp "$file" "$file.bak"
        
        # Remove comments using sed
        # 1. Remove /** ... */ docblocks (multiline)
        # 2. Remove // single-line comments
        # 3. Remove /* ... */ block comments
        
        perl -i -pe '
            # Remove single-line // comments (but preserve URLs like http://)
            s{//(?![:/]).*$}{};
            
            # Remove /* ... */ block comments (single line)
            s{/\*.*?\*/}{}g;
        ' "$file"
        
        # Remove multi-line docblocks /** ... */
        perl -i -0777 -pe 's{/\*\*.*?\*/}{}gs' "$file"
        
        # Remove empty lines that were left behind (max 1 empty line)
        perl -i -0777 -pe 's/\n\n\n+/\n\n/gs' "$file"
        
    done
    
    echo "  ✓ Done with $MODULE"
    echo ""
done

echo "=== Cleanup Complete ==="
echo ""
echo "To restore backups: find Modules -name '*.php.bak' -exec sh -c 'mv \"\$1\" \"\${1%.bak}\"' _ {} \;"
echo "To remove backups: find Modules -name '*.php.bak' -delete"
