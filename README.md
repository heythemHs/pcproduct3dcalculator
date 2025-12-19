# PC Product 3D Calculator

A comprehensive PrestaShop module for 3D printing services that allows customers to upload STL/OBJ files, automatically calculates volume and weight, and provides instant price estimates based on configurable materials and options.

## Features

### Front Office (Customer Side)
- **File Upload**: Drag & drop or click to upload STL/OBJ 3D model files
- **Automatic Analysis**: Real-time volume calculation using signed tetrahedron method
- **Material Selection**: Choose from configurable materials (PLA, ABS, PETG, TPU, Nylon, etc.)
- **Infill Control**: Adjustable infill percentage slider (0-100%)
- **Real-time Pricing**: Instant price calculation based on:
  - Material cost per gram
  - Calculated weight (volume × density × infill factor)
  - Setup fees and minimum price rules
- **Cart Integration**: Add 3D print quotes directly to shopping cart
- **Quote Request**: Optional quote request for complex orders

### Back Office (Admin Side)
- **Material Management**: Full CRUD for materials with:
  - Name and description
  - Density (g/cm³)
  - Price per gram
  - Color indicator
  - Active/inactive status
  - Drag-and-drop position ordering
- **Upload Management**: View and manage all customer uploads
  - File details (volume, weight, price)
  - Status tracking (pending, calculated, ordered, etc.)
  - Link to associated orders
  - Bulk cleanup of old uploads
- **Configurable Settings**:
  - Maximum file size
  - Minimum price
  - Setup fee
  - Default infill percentage
  - Infill surcharge (optional)
  - Display options (show/hide volume and weight)
  - Automatic cleanup period

## Technical Implementation

### 3D File Parsing
The module includes a complete PHP-based 3D file parser (`Pc3dFileParser`) that:
- Supports ASCII and Binary STL files
- Supports OBJ files with polygon triangulation
- Calculates volume using the signed tetrahedron method
- Validates file format, size, and MIME type

### Price Calculation Formula
```
weight = volume × material_density × (shell_factor + infill_factor)
price = (weight × price_per_gram) + setup_fee + infill_modifier
final_price = max(price, minimum_price)
```

### Security Features
- Secure file upload with validation
- Files stored with hashed filenames
- .htaccess protection for upload directory
- CSRF protection on all forms
- User ownership validation for delete operations

## Installation

1. Copy the `pcproduct3dcalculator` folder to your PrestaShop `modules` directory
2. Go to Back Office → Modules → Module Manager
3. Search for "3D" and install "3D Printing Product Calculator"
4. The module will:
   - Create database tables for materials and uploads
   - Install default materials (PLA, ABS, PETG, TPU, Nylon)
   - Create admin menu under "3D Calculator"
   - Set up default configuration

## Configuration

Navigate to **3D Calculator → Settings** in the admin menu:

### General Settings
- **Enable Module**: Turn the calculator on/off
- **Maximum File Size**: Limit upload size (default: 10MB)
- **Default Infill**: Starting infill percentage (default: 20%)
- **Quote Product**: Restrict to specific product or show on all products

### Pricing Settings
- **Minimum Price**: Lowest quote price allowed
- **Setup Fee**: Fixed fee added to all quotes
- **Infill Surcharge**: Extra charge per % above 20% infill

### Display Settings
- **Show Volume**: Display calculated volume to customers
- **Show Weight**: Display estimated weight to customers
- **Allow Quote Requests**: Enable/disable quote request button

## File Structure

```
pcproduct3dcalculator/
├── pcproduct3dcalculator.php      # Main module file
├── classes/
│   ├── Pc3dFileParser.php         # STL/OBJ parser
│   ├── Pc3dMaterial.php           # Material model
│   └── Pc3dUpload.php             # Upload model
├── controllers/
│   ├── admin/
│   │   ├── AdminPc3dMaterialsController.php
│   │   ├── AdminPc3dUploadsController.php
│   │   ├── AdminPc3dSettingsController.php
│   │   └── AdminPc3dParentController.php
│   └── front/
│       ├── quote.php              # Quote page controller
│       └── ajax.php               # AJAX handler
├── views/
│   ├── css/
│   │   └── pcproduct3dcalculator.css
│   ├── js/
│   │   └── pcproduct3dcalculator.js
│   └── templates/
│       ├── admin/
│       │   └── upload-view.tpl
│       ├── front/
│       │   └── quote.tpl
│       └── hook/
│           ├── cart-summary.tpl
│           └── product-calculator.tpl
└── upload/                        # Secure upload directory
```

## Hooks Used

- `displayHeader` - Add CSS/JS to frontend
- `displayShoppingCart` - Show uploads in cart
- `displayProductAdditionalInfo` - Calculator on product pages
- `actionOrderStatusPostUpdate` - Update upload status on order changes
- `actionCartSave` - Cart integration

## Requirements

- PrestaShop 1.7.0.0 or higher
- PHP 7.1 or higher
- MySQL 5.6 or higher

## Customization

### Adding Materials
Go to **3D Calculator → Materials** and click "Add new material":
1. Enter material name (e.g., "Carbon Fiber PETG")
2. Set density (g/cm³) - check manufacturer specs
3. Set price per gram based on your costs
4. Choose a display color
5. Add optional description
6. Save and position in the list

### Styling
Override CSS by creating a theme-specific stylesheet or modify `views/css/pcproduct3dcalculator.css`. CSS custom properties (variables) are used for easy color theming.

## API Reference

### AJAX Endpoints

All AJAX requests go through `/module/pcproduct3dcalculator/ajax`:

- `action=upload` - Upload and analyze a 3D file
- `action=calculate` - Recalculate price with new options
- `action=getMaterials` - Get list of active materials
- `action=deleteUpload` - Delete an upload
- `action=addToCart` - Add upload to shopping cart

## License

MIT License

## Support

For issues and feature requests, please use the GitHub issue tracker.
