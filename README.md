# USAA Certificate Generator

A PHP-based web application for generating USA Archery certificates from Excel/CSV data with a custom background image.

## Features

- Generates PDF certificates from Excel/CSV data
- Supports custom background images
- Combines first and last names in red text
- Displays three additional fields in blue text with red separators
- Centers all text elements on the page
- Automatically skips empty rows
- Clean, user-friendly interface
- Secure file handling

## Requirements

- PHP 7.4 or higher
- Composer
- PHP extensions:
  - php-gd (for image processing)
  - php-zip (for Excel file handling)
  - php-xml (for Excel file handling)
  - php-mbstring (for PDF generation)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/usarchery/usaa-certs.git
cd usaa-certs
```

2. Install dependencies using Composer:
```bash
composer install
```

3. Set up permissions:
```bash
mkdir uploads
chmod 777 uploads
```

4. Configure your web server (Apache/Nginx) to serve the application, or use PHP's built-in server for testing:
```bash
php -S localhost:8000
```

## Usage

1. Prepare your Excel/CSV file with the following structure:
   - Column A: First Name
   - Column B: Last Name
   - Column C: First Detail (e.g., Division)
   - Column D: Second Detail (e.g., Category)
   - Column E: Third Detail (e.g., Year)

2. Prepare your certificate background image:
   - Recommended format: PNG or JPG
   - Resolution: At least 2480 x 3508 pixels (A4 size at 300 DPI)
   - Orientation: Landscape

3. Using the Application:
   - Open the application in your web browser
   - Upload your prepared Excel/CSV file
   - Upload your background image
   - Click "Generate Certificates"
   - Download the generated PDF file

## File Structure

```
usaa-certs/
├── index.php              # Main upload interface
├── generate_certificates.php  # Certificate generation script
├── composer.json         # Dependency configuration
├── uploads/             # Temporary file storage (created automatically)
└── vendor/              # Composer dependencies
```

## Security Considerations

- All uploaded files are temporarily stored in the `uploads` directory
- Files are automatically deleted after certificate generation
- File types are validated before processing
- Session-based file handling for improved security

## Troubleshooting

If you encounter issues:

1. Check PHP error logs for detailed error messages
2. Verify file permissions on the uploads directory
3. Ensure all required PHP extensions are installed
4. Validate Excel/CSV file format matches the required structure
5. Confirm background image is in a supported format and resolution

## Support

For support or to report issues, please contact USA Archery IT support or create an issue in the GitHub repository.

## License

Copyright © USA Archery. All rights reserved. 