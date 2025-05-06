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

echo "Starting deployment to $SFTP_HOST..."

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
send "put index.php\r"
expect "sftp>"
send "put generate_certificates.php\r"
expect "sftp>"
send "put composer.json\r"
expect "sftp>"
send "mkdir uploads\r"
expect {
    "Couldn't create directory" { puts "Note: uploads directory might already exist" }
    "sftp>" { puts "Created uploads directory" }
}
expect "sftp>"
send "chmod 777 uploads\r"
expect "sftp>"
send "bye\r"
expect eof
EOL

chmod +x sftp_commands.exp

echo "Uploading files..."

# Run the expect script
./sftp_commands.exp

# Check if the expect script ran successfully
if [ $? -eq 0 ]; then
    echo "Files uploaded successfully. Running composer install on server..."
    
    # Create SSH script for running composer
    cat > ssh_commands.exp << EOL
#!/usr/bin/expect -f
set timeout 300
spawn ssh $SFTP_USER@$SFTP_HOST
expect {
    "yes/no" { send "yes\r"; exp_continue }
    "password:" { send "$SFTP_PASS\r" }
}
expect "$ "
send "cd $SFTP_PATH\r"
expect "$ "
send "composer install --no-dev\r"
expect "$ "
send "exit\r"
expect eof
EOL

    chmod +x ssh_commands.exp
    ./ssh_commands.exp
    rm ssh_commands.exp
    
    echo "Deployment completed successfully!"
else
    echo "Error during deployment. Please check your credentials and try again."
fi

# Clean up
rm sftp_commands.exp 