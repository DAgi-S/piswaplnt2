Create a new module in our existing web platform called "Warranty Certificate Generator".

Requirements:
1. The module should allow authenticated admin users to generate and download a PDF Warranty Certificate for a specific client.
2. The certificate must follow the structure below:
   - Customer Name
   - Company Name
   - Service(s) Provided
   - Serial/Reference Number
   - Invoice/Contract Date
   - Installation/Delivery Date
   - Certificate Date
   - Warranty Duration (default: 12 months)
   - Warranty Terms and Conditions (see detailed content structure provided)
   - Authorized by Lebawi Net Trading plc (with dynamic date and optional signature image)
3. Integrate this module with our current database so client/service data can be auto-filled when available.
4. Use a PDF generation library (like jsPDF for frontend or PDFKit/WeasyPrint for backend) compatible with our current stack.
5. Allow form input to manually enter or override values if no DB link is available.
6. Make the module available from the admin dashboard with minimal visual disruption.
7. Ensure proper access control — only internal admins can generate certificates.
8. Store generated certificates optionally or allow immediate download without saving.
9. Follow our project’s existing structure, framework, and coding conventions.
10. Ensure this module does **not** affect current deployments or production logic — isolate new code in a clean sub-directory or component/module.

Return:
- Backend route and controller setup
- Frontend form UI for inputs
- PDF rendering logic
- Sample styling template for the certificate
- Example database model or mapping (if needed)
