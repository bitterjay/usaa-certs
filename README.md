# USAA Certificate Generator

A PHP-based web application for generating USA Archery certificates from CSV data with a custom background image. Uses mPDF for high-quality PDF generation with advanced font and background image support.

## Features

- Generates professional PDF certificates from CSV data
- High-quality background image handling with mPDF
- Custom Poppins font integration for consistent typography
- Dynamic text positioning with live preview
- Configurable text sizes and positions via UI sliders
- Automatic text centering and spacing
- Preview mode with bounding boxes for precise positioning
- Combines first and last names in red text (#aa1f2e)
- Displays up to three additional fields in blue text (#1c355e) with red separators
- Responsive, user-friendly interface
- Secure file handling and validation
- Supports US Letter size (11" x 8.5") in landscape orientation

## Requirements

- PHP 7.4 or higher
- Composer
- PHP extensions:
  - php-gd (for image processing)
  - php-xml (for XML handling)
  - php-mbstring (for PDF generation)
  - php-curl (for Composer)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/bitterjay/usaa-certs.git
cd usaa-certs
```

2. Install dependencies using Composer:
```bash
php composer.phar install
```

3. Configure your web server (Apache/Nginx) to serve the application, or use PHP's built-in server for testing:
```bash
php -S localhost:8000
```

## Usage

1. Prepare your CSV file with the following structure:
   - Column A: First Name
   - Column B: Last Name
   - Column C: First Detail (e.g., Division)
   - Column D: Second Detail (e.g., Category)
   - Column E: Third Detail (e.g., Year)

2. Prepare your certificate background image:
   - Format: PNG recommended
   - Size: 11" x 8.5" (US Letter Landscape)
   - Resolution: 300 DPI recommended (3300 x 2550 pixels)

3. Using the Application:
   - Open the application in your web browser
   - Upload your prepared CSV file
   - Upload your background image
   - Use the preview interface to adjust text positioning and sizes:
     - Name Y Position
     - Details Y Position
     - Name Font Size
     - Details Font Size
   - Toggle bounding boxes for precise positioning
   - Click "Generate Certificates" when satisfied with the preview
   - Download the generated PDF file

## File Structure

```
usaa-certs/
├── index.php                 # Main interface with preview functionality
├── generate_certificates.php # Certificate generation script using mPDF
├── composer.json            # Dependency configuration
├── composer.phar           # Composer executable
├── deploy.sh              # Deployment script
├── fonts/                # Custom font files
│   └── Poppins-Regular.ttf
└── vendor/              # Composer dependencies including mPDF
```

## Technical Details

- Uses mPDF library for PDF generation
- Custom font integration with Poppins
- Background image handling with mPDF's background-image support
- Text positioning using absolute positioning (mm)
- Font size scaling factor of 1.35 for PDF output
- Automatic text centering using CSS
- Preview mode with live updates

## Deployment

The application includes a deployment script (`deploy.sh`) that handles:
- Git version control operations
- SFTP deployment to production server
- Requires `sftp-config.sh` with server credentials (see `sftp-config.example.sh`)

## Troubleshooting

If you encounter issues:

1. Check PHP error logs (error logging is enabled by default)
2. Verify all required PHP extensions are installed
3. Ensure CSV file format matches the required structure
4. Confirm background image is in a supported format and resolution
5. Check font file permissions if text appears in fallback font

## Support

For support or to report issues, please create an issue in the GitHub repository.

## Version History

- v1.1.0: Migrated from FPDF to mPDF for improved font and background handling
- v1.0.1-alpha: Initial release with FPDF

## License

Copyright © USA Archery. All rights reserved.
