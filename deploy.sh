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

# Upload PHP files
send "put index.php\r"
expect "sftp>"
send "put generate_certificates.php\r"
expect "sftp>"
send "put error_log.php\r"
expect "sftp>"
send "put composer.json\r"
expect "sftp>"

# Create and set permissions for uploads directory
send "mkdir uploads\r"
expect {
    "Couldn't create directory" { puts "Note: uploads directory already exists" }
    "sftp>" { puts "Created uploads directory" }
}
expect "sftp>"
send "chmod 777 uploads\r"
expect "sftp>"

# Create vendor directory if it doesn't exist
send "mkdir vendor\r"
expect {
    "Couldn't create directory" { puts "Note: vendor directory already exists" }
    "sftp>" { puts "Created vendor directory" }
}
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
    echo "Deployment completed successfully!"
    echo "IMPORTANT: Please install Composer dependencies on the server manually:"
    echo "1. Log into your hosting control panel"
    echo "2. Navigate to the public_html directory"
    echo "3. Run: composer install --no-dev"
else
    echo "Error during deployment. Please check your credentials and try again."
fi

# Clean up
rm sftp_commands.exp 