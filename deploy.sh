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
send "put fpdf.php\r"
expect "sftp>"
send "put download_file.php\r"
expect "sftp>"
send "put download_prompt.php\r"
expect "sftp>"

# Create font directory
send "mkdir font\r"
expect {
    "Couldn't create directory" { puts "Note: font directory already exists" }
    "sftp>" { puts "Created font directory" }
}
expect "sftp>"

# Upload font files
send "cd font\r"
expect "sftp>"
send "put font/courier.php\r"
expect "sftp>"
send "put font/courierb.php\r"
expect "sftp>"
send "put font/courierbi.php\r"
expect "sftp>"
send "put font/courieri.php\r"
expect "sftp>"
send "put font/helvetica.php\r"
expect "sftp>"
send "put font/helveticab.php\r"
expect "sftp>"
send "put font/helveticabi.php\r"
expect "sftp>"
send "put font/helveticai.php\r"
expect "sftp>"
send "put font/times.php\r"
expect "sftp>"
send "put font/timesb.php\r"
expect "sftp>"
send "put font/timesi.php\r"
expect "sftp>"
send "put font/timesbi.php\r"
expect "sftp>"
send "put font/symbol.php\r"
expect "sftp>"
send "put font/zapfdingbats.php\r"
expect "sftp>"
send "put font/poppins.php\r"
expect "sftp>"
send "put font/poppins.z\r"
expect "sftp>"
send "cd ..\r"
expect "sftp>"

# Create fonts directory for the web font
send "mkdir fonts\r"
expect {
    "Couldn't create directory" { puts "Note: fonts directory already exists" }
    "sftp>" { puts "Created fonts directory" }
}
expect "sftp>"

# Upload font files
send "cd fonts\r"
expect "sftp>"
send "put fonts/Poppins-Bold.ttf\r"
expect "sftp>"
send "cd ..\r"
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
else
    echo "Error during deployment. Please check your credentials and try again."
fi

# Clean up
rm sftp_commands.exp 