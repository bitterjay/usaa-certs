#!/bin/bash

# Source SFTP configuration
if [ -f "sftp-config.sh" ]; then
    source sftp-config.sh
else
    echo "Error: sftp-config.sh not found!"
    exit 1
fi

# Verify configuration
if [ -z "$SFTP_HOST" ] || [ -z "$SFTP_USER" ] || [ -z "$SFTP_PASS" ] || [ -z "$SFTP_PATH" ]; then
    echo "Error: SFTP configuration incomplete!"
    exit 1
fi

# Get list of modified files from git
MODIFIED_FILES=$(git status -s | grep -E '^ *M' | awk '{print $2}')
UNTRACKED_FILES=$(git status -s | grep -E '^\?\?' | awk '{print $2}')

if [ -z "$MODIFIED_FILES" ] && [ -z "$UNTRACKED_FILES" ]; then
    echo "No modified or new files to upload."
    exit 0
fi

echo "The following files will be deployed:"
echo "Modified files:"
for file in $MODIFIED_FILES; do
    echo "  - $file"
done
echo "New files:"
for file in $UNTRACKED_FILES; do
    echo "  - $file"
done

# Prompt for commit message
echo ""
echo "Enter commit message (or press enter to use default):"
read COMMIT_MSG
if [ -z "$COMMIT_MSG" ]; then
    COMMIT_MSG="Update: $(date '+%Y-%m-%d %H:%M:%S')"
fi

# Handle Git operations
echo "Performing Git operations..."
# Add all modified files
for file in $MODIFIED_FILES; do
    git add "$file"
done
# Add all untracked files
for file in $UNTRACKED_FILES; do
    git add "$file"
done
# Commit changes
git commit -m "$COMMIT_MSG"
# Push changes
git push

# If git operations failed, ask whether to continue with SFTP
if [ $? -ne 0 ]; then
    echo "Git operations failed. Continue with SFTP deployment? (y/n)"
    read CONTINUE
    if [ "$CONTINUE" != "y" ]; then
        exit 1
    fi
fi

echo "Starting SFTP deployment to $SFTP_HOST..."

# Create a temporary expect script for SFTP automation
cat > sftp_commands.exp << EOL
#!/usr/bin/expect -f
set timeout 20
spawn sftp $SFTP_USER@$SFTP_HOST
expect {
    "yes/no" { send "yes\r"; exp_continue }
    "password:" { send "$SFTP_PASS\r" }
}
expect "sftp>"
send "cd $SFTP_PATH\r"
expect "sftp>"

EOL

# Add commands for modified files
for file in $MODIFIED_FILES; do
    if [ -f "$file" ]; then
        echo "Will upload modified file: $file"
        echo "send \"put $file\r\"" >> sftp_commands.exp
        echo "expect \"sftp>\"" >> sftp_commands.exp
    fi
done

# Add commands for untracked files
for file in $UNTRACKED_FILES; do
    if [ -f "$file" ]; then
        echo "Will upload new file: $file"
        # Create directory if needed
        dir=$(dirname "$file")
        if [ "$dir" != "." ]; then
            echo "send \"mkdir -p $dir\r\"" >> sftp_commands.exp
            echo "expect \"sftp>\"" >> sftp_commands.exp
        fi
        echo "send \"put $file\r\"" >> sftp_commands.exp
        echo "expect \"sftp>\"" >> sftp_commands.exp
    fi
done

# Add exit command
echo "send \"bye\r\"" >> sftp_commands.exp
echo "expect eof" >> sftp_commands.exp

chmod +x sftp_commands.exp

echo "Uploading changed files..."

# Run the expect script
./sftp_commands.exp

# Check if the expect script ran successfully
if [ $? -eq 0 ]; then
    echo "✓ Git operations completed"
    echo "✓ SFTP deployment completed"
    echo "All operations finished successfully!"
else
    echo "✓ Git operations completed"
    echo "✗ SFTP deployment failed"
    echo "Error during SFTP deployment. Please check your credentials and try again."
fi

# Clean up
rm sftp_commands.exp 