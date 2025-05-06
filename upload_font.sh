#!/bin/bash

# Source SFTP configuration
if [ -f "sftp-config.sh" ]; then
    source sftp-config.sh
else
    echo "Error: sftp-config.sh not found!"
    exit 1
fi

# Create a temporary expect script
cat > upload_font.exp << EOL
#!/usr/bin/expect -f
set timeout 20
spawn sftp $SFTP_USER@$SFTP_HOST
expect {
    "yes/no" { send "yes\r"; exp_continue }
    "password:" { send "$SFTP_PASS\r" }
}
expect "sftp>"
send "cd $SFTP_PATH/font\r"
expect "sftp>"
send "put font/poppins.z\r"
expect "sftp>"
send "bye\r"
expect eof
EOL

chmod +x upload_font.exp

echo "Uploading font file..."
./upload_font.exp

# Clean up
rm upload_font.exp
echo "Upload completed." 