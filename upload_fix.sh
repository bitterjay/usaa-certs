#!/bin/bash

# Source SFTP configuration
if [ -f "sftp-config.sh" ]; then
    source sftp-config.sh
else
    echo "Error: sftp-config.sh not found!"
    exit 1
fi

# Create a temporary expect script for SFTP automation
cat > upload_fix.exp << EOL
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
send "put test_download.php\r"
expect "sftp>"
send "bye\r"
expect eof
EOL

chmod +x upload_fix.exp

echo "Uploading fixed certificate download functionality..."

# Run the expect script
./upload_fix.exp

# Clean up
rm upload_fix.exp

echo "Done!" 