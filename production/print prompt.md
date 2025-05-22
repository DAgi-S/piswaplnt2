Of course! Here's a ✨ **clean and organized Cursor AI prompt** you can use to **add `print_settings.php`** to your main system properly:

---

### ✅ Cursor AI Prompt: Add `print_settings.php` for Managing Print Layouts

> ```
> Add a new file named `print_settings.php` inside the `include/` folder in our production management system.
> 
> 🧠 The purpose of this file:
> - Centralize and manage **print layouts**, styles, and settings for different printable pages (like invoices, receipts, sales reports, purchase orders, etc.)
> - Make it easy to update print formats without editing each file manually.
> 
> ✨ The file should:
> 
> 1. Define general print settings (e.g., page margins, fonts, logo size, header/footer formats).
> 2. Allow **different print templates** for different modules if needed (example: `sales_invoice`, `purchase_order`, `delivery_note`).
> 3. Provide functions like:
> ```php
> function get_print_styles($template_name = 'default') {
>     $styles = [
>         'default' => [
>             'margin' => '10mm',
>             'font' => 'Arial, sans-serif',
>             'logo_width' => '150px',
>             'header_alignment' => 'center',
>         ],
>         'sales_invoice' => [
>             'margin' => '15mm',
>             'font' => 'Tahoma, sans-serif',
>             'logo_width' => '120px',
>             'header_alignment' => 'left',
>         ],
>         // Add other templates here
>     ];
>     return $styles[$template_name] ?? $styles['default'];
> }
> ```
> 
> 4. In each PHP page that generates printable content:
>     - Include `print_settings.php` at the top.
>     - Call `get_print_styles('template_name')` to apply the styles.
> 
> 5. Do not affect or modify any existing functionality in the project.
> 6. Do not hardcode any design into individual print pages anymore — load all print UI settings dynamically from this file.
> 
> Bonus (optional):
> - Allow users to upload their logo and print footer from Admin Panel.
> - In future, support customizable layouts saved in the database.
> ```

---

### 📄 Example Usage in Your Print Files
In your existing `invoice_print.php` or similar:

```php
include('include/print_settings.php');
$print_style = get_print_styles('sales_invoice');
```

Then use `$print_style['font']`, `$print_style['margin']`, etc., inside your HTML.

---




Creating a database table for print settings
Adding functions to save/retrieve settings from the database
Creating an admin interface to manage these settings
Would you like me to:
Add database integration for the print settings?
Create an admin interface to manage print settings?
Or are you satisfied with the current implementation

In your print pages, include the settings like this:

require_once 'include/print_settings.php';
$print_style = get_print_styles('sales_invoice');
$print_css = generate_print_css('sales_invoice');
$company_info = get_print_company_info();