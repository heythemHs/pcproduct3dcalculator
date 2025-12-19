# pcproduct3dcalculator

Proof-of-concept PrestaShop module that lets customers upload 3D files (STL/OBJ), pick a material and infill, and shows placeholder pricing details in the cart. It also scaffolds back-office material management and database tables for future pricing logic.

## Features
- Module install/uninstall hooks with database tables for materials and uploads.
- Back-office tab placeholder (`AdminPcproduct3dcalculator`) to manage materials.
- Front-office quote page template with upload, material, and infill fields.
- Cart hook template to display uploaded files and estimated prices once calculation is added.
- Basic stylesheet for the front components.

## Notes
- Real STL/OBJ parsing, file storage, validation, and price calculation still need to be implemented.
- Adjust `_PS_VERSION_` range in `pcproduct3dcalculator.php` if targeting a specific PrestaShop version.

## Structure
- `pcproduct3dcalculator.php` — core module registration, hooks, DB schema setup.
- `controllers/admin` — admin list controller stub for materials.
- `controllers/front` — front controller that renders the quote form.
- `views/templates` — Smarty templates for the front quote page and cart summary.
- `views/css` — minimal styling for the quote and cart summary blocks.
