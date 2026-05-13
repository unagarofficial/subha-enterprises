PROMPT 1 — Laravel Project Setup + Database

Tum ek Gold/Silver Jewelry Distribution Software bana rahe ho Laravel mein.
Business naam: Subha Enterprises, Machilipatnam.

PROJECT CONTEXT:
- Stack: Laravel 11, MySQL, Blade templates, Bootstrap 5, jQuery
- Multi-branch + Multi-financial-year system
- Jewelry types: Covering, Plating, Yoshita brand
- Party Types: C = Customer, S = Supplier
- GST: In-State (CGST+SGST), Out-State (IGST)

TASK: Laravel project setup karo aur ye sabhi MySQL tables banao:

1. firms (firm_code PK auto, firm_name, address, place, phone, mobile, website, tin_no, ho_code, type CHAR(1))
2. branches (br_code PK auto, br_name, br_place)
3. financial_years (id PK auto, year_name, start_date, end_date, is_active TINYINT)
4. categories (cat_code PK auto starts 1000, cat_name)
5. uoms (uom_code PK auto, uom_name)
6. taxes (tax_code PK auto, tax_name, tax_percent DECIMAL(5,2))
7. parties (br_code FK, party_type CHAR(1), party_code PK auto, party_name, address, place, state, phone, mobile, inout_state TINYINT, tin_grn_flag TINYINT, tin_grn_no CHAR(30))
8. products (cat_code FK, mat_code VARCHAR(50) PK, mat_name, uom FK, sale_rate DECIMAL(18,2), y_rate DECIMAL(18,2), b_rate DECIMAL(18,2), br_code FK)
9. designs (cat_code FK, design_code VARCHAR(50), design_desc, uom FK, rate DECIMAL(18,2), y_rate DECIMAL(18,2), b_rate DECIMAL(18,2))
10. users (id PK auto, user_name, password, user_type ENUM('ADMIN','USER'), br_code FK)
11. stock (br_code FK, mat_code FK, cat_code FK, ob DECIMAL(18,3), rcpts DECIMAL(18,3), issues DECIMAL(18,3), cl_stock DECIMAL(18,3))
12. purchase_hdr (br_code FK, inv_no INT, inv_date DATE, party_code FK, gross DECIMAL(18,2), tax_rate DECIMAL(5,2), tax_amount DECIMAL(18,2), nett DECIMAL(18,2), fin_year_id FK, PRIMARY KEY(br_code, inv_no))
13. purchase_dtl (br_code FK, inv_no FK, sl_no INT, mat_code FK, qty DECIMAL(18,3), uom FK, rate DECIMAL(18,2), amount DECIMAL(18,2), narration, cat_code FK, po_no INT NULL, inv_date DATE)
14. sale_hdr (ho_code, br_code FK, inv_no INT, inv_date DATE, party_code FK, gross DECIMAL(18,2), tax_rate DECIMAL(5,2), tax_amount DECIMAL(18,2), nett DECIMAL(18,2), bill_type VARCHAR(20), is_locked TINYINT DEFAULT 0, ord_no INT NULL, sale_type TINYINT DEFAULT 1, fin_year_id FK, PRIMARY KEY(br_code, inv_no, sale_type))
15. sale_dtl (br_code FK, inv_no FK, sl_no INT, mat_code FK, qty DECIMAL(18,3), uom FK, rate DECIMAL(18,2), s_value DECIMAL(18,2), narration, inv_date DATE, sale_type TINYINT DEFAULT 1)
16. sale_rtn_hdr (ho_code, br_code FK, inv_no INT, inv_date DATE, party_code FK, gross DECIMAL(18,2), tax_rate DECIMAL(5,2), tax_amount DECIMAL(18,2), nett DECIMAL(18,2), bill_type VARCHAR(20), fin_year_id FK)
17. sale_rtn_dtl (br_code FK, inv_no FK, sl_no INT, mat_code FK, qty DECIMAL(18,3), uom FK, rate DECIMAL(18,2), s_value DECIMAL(18,2), narration, inv_date DATE)
18. order_hdr (ho_code, br_code FK, ord_no INT, ord_date DATE, party_code FK, is_locked TINYINT DEFAULT 0, inv_no INT NULL, ord_type TINYINT DEFAULT 1, fin_year_id FK)
19. order_dtl (br_code FK, ord_no FK, sl_no INT, mat_code FK, narration, ord_qty DECIMAL(18,3), uom FK, bill_qty DECIMAL(18,3), ex_qty DECIMAL(18,3), po_no INT NULL, po_date DATE NULL, p_qty DECIMAL(18,3), pb_no INT NULL, req_qty DECIMAL(18,3), ord_type TINYINT DEFAULT 1)
20. prtn_hdr (ho_code, br_code FK, inv_no INT, inv_date DATE, party_code FK, gross DECIMAL(18,2), tax_rate DECIMAL(5,2), tax_amount DECIMAL(18,2), nett DECIMAL(18,2), fin_year_id FK)
21. prtn_dtl (br_code FK, inv_no FK, sl_no INT, mat_code FK, qty DECIMAL(18,3), uom FK, rate DECIMAL(18,2), s_value DECIMAL(18,2), narration, inv_date DATE)
22. purchase_rtn_hdr (br_code FK, inv_no INT, inv_date DATE, party_code FK, gross DECIMAL(18,2), tax_rate DECIMAL(5,2), tax_amount DECIMAL(18,2), nett DECIMAL(18,2), rtn_type VARCHAR(20), fin_year_id FK)
23. purchase_rtn_dtl (br_code FK, inv_no FK, sl_no INT, mat_code FK, qty DECIMAL(18,3), uom FK, rate DECIMAL(18,2), amount DECIMAL(18,2), narration, inv_date DATE)
24. branch_issue_hdr (iss_no INT PK auto, iss_date DATE, br_code FK)
25. branch_issue_dtl (iss_no FK, sl_no INT, br_code FK, item_code FK, order_qty INT, sent_qty INT, po_no INT NULL)
26. sys_para (admin_name VARCHAR(50), admin_pw VARCHAR(255), user_name VARCHAR(50), user_pw VARCHAR(255))

Aur ye bhi karo:
- .env mein MySQL config
- Database seeders: 1 Admin user, 1 Branch, 1 Financial Year (2024-2050)
- php artisan migrate --seed run karne ke instructions
- Folder structure explain karo: Controllers, Models, Views folders









PROMPT 2 — Authentication System

PROJECT: Subha Enterprises Gold Jewelry Software (Laravel 11)
Tables already banaye hain: branches, financial_years, users, sys_para

TASK: Login/Logout system banao — ye CUSTOM authentication hai (Laravel Breeze nahi).

Login Form fields:
1. Branch Name — Dropdown (branches table se br_name, value = br_code)
2. Financial Year — Dropdown (financial_years table se year_name, value = id)
3. User Name — Text input
4. Password — Password input  
5. Login Date — Date picker (default = aaj ki date, changeable)

Login Logic:
- users table mein user_name aur password check karo (bcrypt)
- Jo branch select ki wahi session mein save karo
- Jo financial year select ki wahi session mein save karo
- Login date bhi session mein save karo
- Login ke baad dashboard pe redirect karo

Session mein ye store karo:
- session('br_code') — selected branch
- session('br_name') — branch name display ke liye
- session('fin_year_id') — financial year id
- session('fin_year_name') — year name display ke liye
- session('login_date') — user selected date
- session('user_id') — logged in user id
- session('user_name') — user name
- session('user_type') — ADMIN ya USER

Middleware banao 'auth.check' — jo har route pe check kare session exist karta hai ya nahi.

Dashboard page (simple):
- Top pe "Welcome [username] | Branch: [branch name] | Year: [year] | Date: [date]" dikhao
- Logout button
- Menu bar: Masters | Transactions | Reports | Utilities

Navigation menu:
Masters: Firm Info, Branch, Category, UOM, Tax, Party, Product, Design, User, Stock Opening
Transactions: Purchase, Self Purchase, Sale (Type1), Sale (Type2), Sale Return, Purchase Return, Order (Type1), Order (Type2), Estimation Invoice, Stock Transfer
Reports: (list)
Utilities: Financial Year, System Parameters, Change Password









PROMPT 3 — Masters: Firm Info + Branch + Category + UOM + Tax

PROJECT: Subha Enterprises Gold Jewelry Software (Laravel 11)
Already done: Database, Login, Session (br_code, fin_year_id, login_date session mein hai)

TASK: Ye 5 simple Masters banao with full CRUD (List, Add, Edit, Delete):

Layout: Shared layout use karo jisme top navbar aur side menu hai.

--- MASTER 1: Firm Information ---
Table: firms
Fields:
- firm_code (auto, show only)
- firm_name (text, required)
- address (textarea)
- place (text, required)
- phone (number, 10 digit)
- mobile (number, 10 digit)
- website (text)
- tin_no (text, 15 char - GST number)
- ho_code (number - which branch is HO)
- type (dropdown: H=Head Office, B=Branch)
Note: Ye single record hoga mostly — Add sirf ek baar, baad mein sirf Edit.

--- MASTER 2: Branch Master ---
Table: branches
Fields:
- br_code (auto, show only)
- br_name (text, required, unique)
- br_place (text)
List mein: br_code, br_name, br_place, Edit/Delete buttons

--- MASTER 3: Category Master ---
Table: categories
Fields:
- cat_code (auto starts from 1000)
- cat_name (text, required, unique)
List mein: cat_code, cat_name, Edit/Delete buttons
Examples: COVERING, PLATING, YOSHITA

--- MASTER 4: UOM Master ---
Table: uoms
Fields:
- uom_code (auto)
- uom_name (text, required, unique, max 20 chars)
List mein: uom_code, uom_name, Edit/Delete buttons
Examples: PCS, GMS, SET, DZ, BOX

--- MASTER 5: Tax Master ---
Table: taxes
Fields:
- tax_code (auto)
- tax_name (text - e.g. "GST 3%")
- tax_percent (decimal 5,2 - actual percentage number)
List mein: tax_code, tax_name, tax_percent, Edit/Delete buttons

For all 5 masters:
- Datatable with search/sort (use DataTables.js)
- Add button opens modal form (ya alag page)
- Edit inline ya modal
- Delete with confirm dialog
- Success/Error flash messages
- Validation with error display







PROMPT 4 — Master: Party (Customer + Supplier)

PROJECT: Subha Enterprises Gold Jewelry Software (Laravel 11)
Already done: Database, Login, Session, Basic Masters

TASK: Party Master banao — ye Customer aur Supplier dono handle karta hai.

Table: parties
Route prefix: /masters/party

LIST PAGE:
- Tabs ya dropdown filter: "All" | "Customers" | "Suppliers"
- Columns: Party Code, Party Name, Place, State, Mobile, Type (C/S), TIN/GRN No, Actions
- Search box, sortable columns (DataTables)
- "Add Customer" button aur "Add Supplier" button alag alag

ADD/EDIT FORM:
Fields (with validation):
1. Branch Code — Dropdown (branches table se, default = session('br_code'))
2. Party Type — Radio: Customer (C) / Supplier (S) [required]
3. Party Code — Auto generated (show only on edit)
4. Party Name — Text [required, max 100]
5. Address — Textarea [max 150]
6. Place — Text [required, max 50]
7. State — Dropdown — Indian states list:
   Andhra Pradesh, Telangana, Karnataka, Tamil Nadu, Maharashtra, Delhi, Gujarat, Rajasthan, West Bengal, Uttar Pradesh, Madhya Pradesh, Bihar, Punjab, Haryana, Kerala, Odisha, Jharkhand, Chhattisgarh, Uttarakhand, Himachal Pradesh, Jammu & Kashmir, Goa, Assam, Manipur, Meghalaya, Mizoram, Nagaland, Tripura, Arunachal Pradesh, Sikkim [required]
8. Phone — Number [10 digits, optional]
9. Mobile — Number [10 digits, optional]
10. In/Out State — Toggle/Radio: "In-State" (0) / "Out-State" (1)
    [default = In-State, used for GST: In-State=CGST+SGST, Out-State=IGST]
11. GST Registered — Checkbox: Yes/No (tin_grn_flag)
12. GST/TIN No — Text [15 chars, show/required only when GST Registered = Yes]
    [jQuery se: checkbox tick hone pe field enable, warna disable+clear]

Validation rules:
- party_name unique per branch per party_type
- mobile 10 digits
- tin_grn_no required if tin_grn_flag = 1, must be 15 chars

Delete: soft delete (ya hard delete with check — koi transaction hai toh delete mat karo, error do)







PROMPT 5 — Master: Product + Design + Stock Opening

PROJECT: Subha Enterprises Gold Jewelry Software (Laravel 11)
Already done: Database, Login, Session, Party Master
Tables needed: products, designs, stock

TASK: Ye 3 masters banao:

--- MASTER 1: Product Master ---
Table: products
Route: /masters/product

LIST PAGE:
- Filter by Category (dropdown from categories table)
- Filter by Branch
- Columns: Mat Code, Mat Name, Category, UOM, Sale Rate, Y-Rate, B-Rate, Branch, Edit/Delete
- DataTables with search

ADD/EDIT FORM:
1. Category — Dropdown [categories table, cat_name] [required]
2. Material Code — Text [unique, required, max 50, e.g. "COV-001", "PLT-CHAIN-22K"]
3. Material Name — Text [required, max 100]
4. UOM — Dropdown [uoms table, uom_name] [required]
5. Sale Rate — Decimal (18,2) [customer ko sell karne ka rate] [optional]
6. Y-Rate (Yoshita Rate) — Decimal (18,2) [optional]
7. B-Rate (Bangalore Rate) — Decimal (18,2) [optional]
8. Branch — Dropdown [branches table, default = session branch] [required]

--- MASTER 2: Design Master ---
Table: designs
Route: /masters/design

LIST PAGE:
- Filter by Category
- Columns: Design Code, Description, Category, UOM, Rate, Y-Rate, B-Rate, Edit/Delete

ADD/EDIT FORM:
1. Category — Dropdown [categories] [required]
2. Design Code — Text [required, max 50]
3. Design Description — Text [required, max 255]
4. UOM — Dropdown [uoms] [required]
5. Rate — Decimal (18,2)
6. Y-Rate — Decimal (18,2)
7. B-Rate — Decimal (18,2)

--- MASTER 3: Stock Opening Balance ---
Table: stock
Route: /masters/stock

Purpose: Year start pe each item ka opening balance enter karna.
NOTE: Receipts aur Issues auto-calculate honge transactions se. Yahan sirf OB enter hota hai.

LIST PAGE:
- Filter by Category, Branch
- Columns: Mat Code, Mat Name, Category, OB, Receipts (auto), Issues (auto), Closing Stock (auto)
- Closing Stock = OB + Receipts - Issues (calculated, not stored manually)

ADD/EDIT FORM:
1. Branch — Dropdown [branches, default = session] [required]
2. Material Code — Searchable dropdown [products table, show mat_code + mat_name] [required]
3. Category — Auto-fill from product selection (read only)
4. Opening Balance (OB) — Decimal (18,3) [required, default 0]

Note: Ek branch + mat_code combination unique hoga. Duplicate pe error do.

Receipts calculation: SUM(purchase_dtl.qty) WHERE mat_code = X AND br_code = Y AND fin_year
Issues calculation: SUM(sale_dtl.qty) WHERE mat_code = X AND br_code = Y AND fin_year







PROMPT 6 — Master: User Management + System Parameters

PROJECT: Subha Enterprises Gold Jewelry Software (Laravel 11)
Already done: Database, Login, All Previous Masters

TASK: User Management aur System Parameters banao.
NOTE: Ye sirf ADMIN user dekh sakta hai. Middleware check karo session('user_type') == 'ADMIN'.

--- FEATURE 1: User Master ---
Table: users
Route: /utilities/users (ADMIN only)

LIST:
- Columns: User Code, User Name, Branch, User Type, Actions (Edit, Delete, Reset Password)
- Delete: sirf agar woh currently logged-in user nahi hai

ADD/EDIT FORM:
1. User Name — Text [required, unique, max 50]
2. Password — Password [required on Add, optional on Edit — blank rakho toh change mat karo]
3. Confirm Password — [match validation]
4. Branch — Dropdown [branches table]
5. User Type — Dropdown: ADMIN / USER

--- FEATURE 2: Change Password ---
Route: /utilities/change-password (all users)

Form:
1. Current Password — Password [required, verify against DB]
2. New Password — Password [required, min 6]
3. Confirm New Password — [must match]

--- FEATURE 3: System Parameters ---
Table: sys_para
Route: /utilities/system-parameters (ADMIN only)

Single record form (no list — sirf ek record hoga):
1. Admin Name — Text
2. Admin Password — Password
3. User Name — Text
4. User Password — Password

--- FEATURE 4: Financial Year Management ---
Table: financial_years
Route: /utilities/financial-year (ADMIN only)

LIST:
- Columns: Year Name, Start Date, End Date, Active, Actions
- Only one year Active at a time

ADD FORM:
1. Year Name — Text [e.g. "2024-2025"] [required]
2. Start Date — Date [required]
3. End Date — Date [required, after start_date]
4. Set as Active — Toggle

Business rule: Nayi year add karne pe pichli year ka closing stock is year ka opening balance ban jayega automatically — ek button do "Copy Closing Stock as OB" jo stock table mein OB update kare.







PROMPT 7 — Transaction: Purchase Entry

PROJECT: Subha Enterprises Gold Jewelry Software (Laravel 11)
Already done: All Masters, Auth, Session
Tables: purchase_hdr, purchase_dtl, stock (update karna hoga)

TASK: Purchase Entry form banao — Supplier se maal kharidne ka entry.
Route: /transactions/purchase

LIST PAGE (Purchase Register):
- Columns: Inv No, Date, Supplier Name, Gross, Tax%, Tax Amt, Net, Branch, Actions (View, Edit, Delete, Print)
- Filter: Date From, Date To, Supplier (dropdown), Branch
- Total row at bottom

ADD/EDIT FORM — Header Section:
1. Branch — Dropdown [branches, default = session br_code] [required]
2. Invoice No — Auto generated (max inv_no + 1 per branch per year, show only)
3. Invoice Date — Date [default = session login_date] [required]
4. Supplier — Searchable dropdown [parties where party_type='S', show party_name + place] [required]
5. Gross Amount — Decimal, READ ONLY (auto-sum from details)
6. Tax — Dropdown [taxes table, show tax_name] → Tax% field auto-fill
7. Tax% — Decimal (auto-fill from tax selection, but editable)
8. Tax Amount — Decimal, READ ONLY (auto: Gross × Tax% / 100)
9. Net Amount — Decimal, READ ONLY (auto: Gross + Tax Amount)

ADD/EDIT FORM — Detail Section (dynamic rows):
Ek table jisme multiple items add ho sakein (Add Row button):
| Sl | Material | Qty | UOM | Rate | Amount | Narration | Category | PO No | Delete |

For each row:
- Sl No — Auto (1,2,3...)
- Material Code — Searchable [products table, show mat_code + mat_name]
  → On select: UOM auto-fill, Rate auto-fill from b_rate, Category auto-fill
- Qty — Decimal [required, > 0]
- UOM — Text (auto-filled, readonly)
- Rate — Decimal [required, > 0]
- Amount — Decimal READ ONLY (auto: Qty × Rate)
- Narration — Text [optional]
- Category — Dropdown [categories, auto-filled but editable]
- PO No — Number [optional]
- Delete Row — Button (minimum 1 row required)

On Save:
1. purchase_hdr insert karo
2. purchase_dtl insert karo (sab rows)
3. stock table UPDATE: rcpts += qty for each mat_code (agar record nahi hai toh insert with ob=0)
4. stock table: cl_stock = ob + rcpts - issues recalculate

On Delete:
1. purchase_dtl delete karo
2. purchase_hdr delete karo  
3. stock table: rcpts -= qty for each item (reverse karo)

PRINT BUTTON:
- "Print Invoice" button jo ek clean print-friendly page open kare:
  Header: Firm Name, Address, "PURCHASE INVOICE"
  Bill To: Supplier Name, Address, GST No
  Table: Sl, Item Name, Qty, UOM, Rate, Amount
  Footer: Gross, Tax (%), Tax Amount, Net Amount
  Amount in Words (PHP function banao Indian format mein)







PROMPT 8 — Transaction: Sale Entry (Type 1 + Type 2)

PROJECT: Subha Enterprises Gold Jewelry Software (Laravel 11)
Already done: Purchase Entry, All Masters, Auth
Tables: sale_hdr, sale_dtl, stock (update karna)

TASK: Sale Entry banao — Customer ko maal bechne ka entry.
Sale Type 1 = Cash/Retail Sale (sale_type=1)
Sale Type 2 = Credit/Wholesale Sale (sale_type=2)
Dono ka form same hai, sirf alag route aur list hoga.

Routes:
- /transactions/sale/type1 — Cash Sale
- /transactions/sale/type2 — Credit Sale

LIST PAGE:
- Columns: Inv No, Date, Customer Name, Gross, Tax, Net, Bill Type, Locked, Actions
- Filter: Date From, Date To, Customer, Bill Type, Branch
- Lock/Unlock button (ADMIN only)
- Print bill directly from list

ADD/EDIT FORM — Header:
1. Branch — Dropdown [branches, default = session] [required]
2. HO Code — Auto from firm info (hidden)
3. Invoice No — Auto generated (show only)
4. Invoice Date — Date [default = login_date] [required]
5. Customer — Searchable dropdown [parties where party_type='C'] [required]
   → On select: show party details (place, state, GST no) below for reference
6. Bill Type — Dropdown: CASH / CREDIT / COVERING / PLATING [required]
7. Order No — Searchable [order_hdr table, show pending orders for selected customer]
   → On select: auto-fill detail rows from order_dtl
8. Gross — Decimal READ ONLY (auto-sum)
9. Tax — Dropdown [taxes table] → Tax% auto-fill
10. Tax% — Decimal (auto-fill, editable)
11. Tax Amount — READ ONLY (Gross × Tax% / 100)
    → If In-State party: show as CGST: X + SGST: X
    → If Out-State party: show as IGST: total
12. Net — READ ONLY (Gross + Tax Amount)
13. Lock — Toggle (default Unlocked, ADMIN can lock/unlock)

Detail rows (same dynamic table as Purchase):
| Sl | Material | Qty | UOM | Rate | Sale Value | Narration | Delete |

- Material select: UOM auto-fill, Rate auto-fill from sale_rate (editable)
- Sale Value = Qty × Rate (auto)

On Save:
1. sale_hdr insert
2. sale_dtl insert
3. stock UPDATE: issues += qty for each item
4. cl_stock recalculate
5. If ord_no linked: update order_hdr set inv_no = new inv_no

On Edit: Locked bills edit nahi hone chahiye (show message)

On Delete:
1. Sirf unlocked bills delete ho sakein
2. stock reverse karo

PRINT FORMATS (Bill Type se decide):
Button: "Print Bill" → opens print page

Format 1 — COVERING/CASH bill:
- Firm letterhead
- "SALE INVOICE" title
- Customer details
- Items table: Sl, Item Name, Qty, UOM, Rate, Amount
- GST breakdown (CGST+SGST ya IGST)
- Net amount
- Amount in Words

Format 2 — PLATING bill: Same structure, slight layout difference

Format 3 — YOSHITA bill: Yoshita brand header logo area

Note: Ek bill ke liye Print format dropdown do jisme user choose kare kaunsa format print karna hai.







PROMPT 9 — Transaction: Sale Return + Purchase Return

PROJECT: Subha Enterprises Gold Jewelry Software (Laravel 11)
Already done: Purchase, Sale, All Masters
Tables: sale_rtn_hdr, sale_rtn_dtl, purchase_rtn_hdr, purchase_rtn_dtl, stock

TASK: Sale Return aur Purchase Return dono banao.

--- PART 1: SALE RETURN ---
Route: /transactions/sale-return
Table: sale_rtn_hdr + sale_rtn_dtl

PURPOSE: Customer ne maal wapas kiya.

LIST:
- Columns: Return Inv No, Date, Customer, Gross, Tax, Net, Bill Type, Actions

ADD FORM — Header:
1. Branch — Dropdown [session branch]
2. Invoice No — Auto generated
3. Invoice Date — Date [default login_date]
4. Customer — Searchable [parties type C]
5. Reference Sale Invoice — Optional search [sale_hdr table for this customer]
   → On select: auto-fill items from that sale (editable)
6. Bill Type — Dropdown: COVERING / PLATING
7. Gross — READ ONLY auto
8. Tax — Dropdown [taxes]
9. Tax% — Auto-fill
10. Tax Amount — READ ONLY
11. Net — READ ONLY

Detail rows (same as sale):
| Sl | Material | Qty | UOM | Rate | Sale Value | Narration | Delete |

On Save:
1. sale_rtn_hdr + sale_rtn_dtl insert
2. stock UPDATE: issues -= qty (stock wapas aaya) → rcpts nahi badlega, issues kam hoga
3. cl_stock recalculate

--- PART 2: PURCHASE RETURN ---
Route: /transactions/purchase-return
Table: purchase_rtn_hdr + purchase_rtn_dtl

PURPOSE: Supplier ko maal wapas kiya.

LIST:
- Columns: Return Inv No, Date, Supplier, Gross, Tax, Net, Return Type, Actions
- Print return document

ADD FORM — Header:
1. Branch — Dropdown
2. Invoice No — Auto
3. Invoice Date — Date
4. Supplier — Searchable [parties type S]
5. Reference Purchase Invoice — Optional [purchase_hdr]
6. Return Type — Dropdown: COVERING / PLATING (print format decide karta hai)
7. Gross, Tax, Tax Amount, Net — same as purchase

Detail rows:
| Sl | Material | Qty | UOM | Rate | Amount | Narration | Delete |

On Save:
1. purchase_rtn_hdr + purchase_rtn_dtl insert
2. stock UPDATE: rcpts -= qty (jo purchase kiya tha woh kam hua)
3. cl_stock recalculate

PRINT for Purchase Return:
- "PURCHASE RETURN" heading
- Firm details + Supplier details
- Items table
- Gross, Tax, Net
- Two formats: Covering aur Plating (layout slightly different)







PROMPT 10 — Transaction: Order / Estimation

PROJECT: Subha Enterprises Gold Jewelry Software (Laravel 11)
Already done: Purchase, Sale, Returns, All Masters
Tables: order_hdr, order_dtl

TASK: Order / Estimation Entry banao — Customer ka order lena + Estimation Bill print karna.
Order Type 1 = Type 1 orders (ord_type=1)
Order Type 2 = Type 2 orders (ord_type=2)
Routes: /transactions/order/type1 aur /transactions/order/type2

LIST PAGE:
- Columns: Order No, Date, Customer, Status (Open/Locked/Converted), Actions
- Filter: Date, Customer, Status (Open = inv_no IS NULL, Converted = inv_no NOT NULL)
- Status Badge: 
  → inv_no IS NULL + is_locked=0 : "OPEN" (green)
  → is_locked=1 : "LOCKED" (orange)
  → inv_no IS NOT NULL : "CONVERTED TO BILL" (blue)

ADD/EDIT FORM — Header:
1. Branch — Dropdown [session]
2. HO Code — Auto
3. Order No — Auto generated (show only)
4. Order Date — Date [default login_date]
5. Customer — Searchable [parties type C] [required]
6. Lock — Toggle (default Unlocked)

Detail rows (dynamic table):
| Sl | Material | Narration | Order Qty | UOM | Bill Qty | Extra Qty | PO No | PO Date | P Qty | PB No | Req Qty | Delete |

- Material select → UOM auto-fill
- Order Qty = customer ne kitna manga
- Bill Qty = actually bill mein kitna diya (manually enter)
- Extra Qty = extra diya
- Req Qty = Order Qty - Bill Qty (auto-calculate, readonly)
- PO No, PO Date, P Qty, PB No = Purchase Order reference fields (optional)

Buttons on View/Edit:
1. "Lock Order" — is_locked = 1 (confirm dialog)
2. "Convert to Sale Bill" — 
   → Ek popup: Sale Type 1 ya Type 2 choose karo, Bill Type choose karo
   → sale_hdr insert (party = order's customer)
   → sale_dtl insert (mat_code, qty = bill_qty, rate from products.sale_rate)
   → order_hdr.inv_no = new sale inv_no update karo
   → Redirect to sale edit page for that invoice

PRINT — Estimation Bill:
- "ESTIMATION BILL" heading
- Firm letterhead
- Customer: Name, Address, Place
- Table: Sl, Item Description, Qty, UOM, Rate, Amount
- Total Amount
- Note at bottom: "This is an estimate, not a tax invoice"
- Date, Authorized Signature







PROMPT 11 — Transaction: Estimation Invoice + Stock Transfer

PROJECT: Subha Enterprises Gold Jewelry Software (Laravel 11)
Already done: Purchase, Sale, Returns, Order
Tables: prtn_hdr, prtn_dtl, branch_issue_hdr, branch_issue_dtl

TASK: Estimation/Prathan Invoice aur Stock Transfer banao.

--- PART 1: ESTIMATION / PRATHAN INVOICE ---
Route: /transactions/estimation-invoice
Tables: prtn_hdr + prtn_dtl

PURPOSE: Ye Order se alag ek internal estimation invoice hai — party ko quotation dene ke liye.

LIST:
- Columns: Inv No, Date, Party Name, Gross, Tax, Net, Print, Edit, Delete

ADD/EDIT FORM — Header:
1. Branch — Dropdown [session]
2. HO Code — Auto
3. Invoice No — Auto generated
4. Invoice Date — Date [login_date default]
5. Party — Searchable [all parties, both C and S]
6. Gross — READ ONLY auto
7. Tax — Dropdown [taxes]
8. Tax% — Auto
9. Tax Amount — READ ONLY
10. Net — READ ONLY

Detail rows:
| Sl | Material | Qty | UOM | Rate | Sale Value | Narration | Delete |
- Material → UOM, Rate auto-fill from sale_rate

On Save: prtn_hdr + prtn_dtl insert. Stock update NAHI karna (ye sirf paper estimate hai).

PRINT:
- "ESTIMATION INVOICE" heading
- Firm + Party details
- Items table with amount
- Gross, Tax, Net
- Amount in words

--- PART 2: STOCK TRANSFER / BRANCH ISSUE ---
Route: /transactions/stock-transfer
Tables: branch_issue_hdr + branch_issue_dtl

PURPOSE: Head Office se Branch ko maal bhejna.

LIST:
- Columns: Issue No, Date, To Branch, Total Items, Print, Edit, Delete

ADD/EDIT FORM — Header:
1. Issue No — Auto generated (PK auto)
2. Issue Date — Date [login_date default]
3. To Branch — Dropdown [branches, exclude current session branch]

Detail rows:
| Sl | Item Code | Item Name | Order Qty | Sent Qty | PO No | Delete |

- Item Code — Searchable [products table]
- Order Qty — Number (kitna order tha)
- Sent Qty — Number (actually kitna bheja)
- PO No — Number optional

On Save:
1. branch_issue_hdr + branch_issue_dtl insert
2. Stock update:
   → FROM branch (session): issues += sent_qty
   → TO branch: rcpts += sent_qty
3. cl_stock recalculate for both branches

PRINT — Stock Transfer Document:
- "STOCK TRANSFER" heading
- From Branch, To Branch, Date, Issue No
- Items table: Item Code, Item Name, Order Qty, Sent Qty, UOM
- Signature boxes: Sent By, Received By







PROMPT 12 — Reports Part 1: Sales Reports

PROJECT: Subha Enterprises Gold Jewelry Software (Laravel 11)
Already done: All Transactions, All Masters
Tables used: sale_hdr, sale_dtl, parties, products, categories, branches

TASK: Sales Reports banao.
Route prefix: /reports/sales/

--- REPORT 1: Daily Sales Report ---
Route: /reports/sales/daily

Filter form:
- Date From [date, default = first day of current month]
- Date To [date, default = today]
- Branch [dropdown, All option bhi]
- Include Tax [checkbox, default checked]
- Show button

Report Output (table):
Columns: Date | Invoice No | Customer Name | Place | Items (count) | Gross | Tax% | Tax Amt | Net

Group by Date with subtotals per day:
- Ye date wise grouped dikhao
- Har date ke baad: Day Total row (Gross sum, Tax sum, Net sum)
- Bottom: Grand Total row

--- REPORT 2: Party-wise / Area Sales Report ---
Route: /reports/sales/party-wise

Filter:
- Date From, Date To
- Party [searchable dropdown, All option]
- Branch [All option]
- Show button

Report Output:
Group by Customer:
  Customer Name | Place | State
    → Date | Inv No | Items | Gross | Tax | Net
  Customer Total →

Grand Total at bottom.

--- REPORT 3: Weekly Sales Report ---
Route: /reports/sales/weekly

Filter:
- Week Start Date
- Week End Date (default: +7 days from start)
- Branch

Report:
- Day-wise summary for the week
- Each day: Total invoices, Total Gross, Tax, Net
- Week Total at bottom

--- REPORT 4: Customer-wise Weekly Sale ---
Route: /reports/sales/customer-weekly

Filter: Week From, Week To, Branch

Report:
Group by Customer:
- Customer Name
- Each day column (Mon-Sun) with amount
- Row total per customer
- Column totals at bottom

--- ALL REPORTS COMMON FEATURES: ---
1. Print button — browser print CSS (clean layout, no navbar)
2. Export to Excel button — use Laravel Excel package (maatwebsite/excel)
3. Date range validation (from <= to)
4. If no data: "No records found" message
5. Amounts format: Indian format (e.g., 1,23,456.00)
6. Session branch auto-select in filter







PROMPT 13 — Reports Part 2: Purchase + Stock Reports

PROJECT: Subha Enterprises Gold Jewelry Software (Laravel 11)
Already done: All Transactions, Sales Reports
Tables: purchase_hdr, purchase_dtl, purchase_rtn_hdr, purchase_rtn_dtl, stock, products, parties

TASK: Purchase aur Stock Reports banao.

--- REPORT 1: Purchase Report ---
Route: /reports/purchase/register

Filter:
- Date From, Date To
- Supplier [searchable dropdown, parties type S, All option]
- Branch [All option]
- Category [categories dropdown, All option]

Report Table:
Columns: Date | Inv No | Supplier | Place | Category | Item | Qty | UOM | Rate | Amount | Tax | Net

Group by Supplier:
- Supplier subtotal per supplier
- Grand total at bottom

--- REPORT 2: Weekly Purchase Report ---
Route: /reports/purchase/weekly

Filter: Week From, Week To, Branch

Report:
Day-wise: Total invoices, Total Amount per day
Week Total at bottom

--- REPORT 3: Purchase Return Report ---
Route: /reports/purchase/returns

Filter: Date From, Date To, Supplier, Return Type (Covering/Plating/All), Branch

Report:
Same structure as Purchase Register but for returns.

--- REPORT 4: Self Purchase Report ---
Route: /reports/purchase/self-purchase

Filter: Date From, Date To, Supplier (artisans), Branch, Type (Covering/Plating/All)

Report:
- Same as purchase register
- Print formats: Covering format, Plating format, Voucher format (3 print buttons)

--- REPORT 5: Stock Report (Current Stock) ---
Route: /reports/stock/current

Filter:
- Branch [session default, All option]
- Category [All option]
- Show Zero Stock [checkbox — zero stock items hide/show]

Report Table:
Columns: Mat Code | Item Name | Category | UOM | Opening Balance | Receipts | Issues | Closing Stock

Receipts = SUM of all purchase_dtl.qty + branch_issue received
Issues = SUM of all sale_dtl.qty + branch_issue sent
Closing = OB + Receipts - Issues

Footer: Category-wise totals, Grand total

--- REPORT 6: Stock Closing Report ---
Route: /reports/stock/closing

Filter:
- As On Date [date picker]
- Branch, Category

Report:
Same as current stock but calculated UP TO selected date only.
(Receipts aur Issues filter karo by date <= selected date)

--- COMMON FEATURES (all reports): ---
- Print button (print-friendly CSS)
- Export Excel
- Indian number format (1,23,456.00)
- "No records" message







PROMPT 14 — Reports Part 3: Party Ledger + Product Lists + Parcel

PROJECT: Subha Enterprises Gold Jewelry Software (Laravel 11)
Already done: All previous reports
Tables: sale_hdr, sale_dtl, purchase_hdr, purchase_dtl, sale_rtn_hdr, purchase_rtn_hdr, parties, products

TASK: Ye reports banao:

--- REPORT 1: Party Ledger ---
Route: /reports/party-ledger

PURPOSE: Party (Customer ya Supplier) ke saare transactions ka account statement — Credit aur Debit.

Filter:
- Party [searchable dropdown, REQUIRED — All nahi chalega] 
- Date From [required]
- Date To [required]
- Branch [All option]

Report Output:
Header: "Account Ledger of [Party Name] from [Date From] to [Date To]"

Table:
S.No | Date | Narration | Credit | Debit | Balance

Rows (chronological order by date):
- Opening Balance: (balance before date from) — Debit column
- Sales: Narration = "Invoice No: [inv_no] Sale Bill" → Debit entry (party owes us)
- Sale Returns: Narration = "Return against Inv: [inv_no]" → Credit entry (we owe party)
- Purchases: Narration = "Invoice No: [inv_no] Purchase" → Credit entry (we owe supplier)
- Purchase Returns: Narration = "Return Inv: [inv_no]" → Debit entry (supplier owes us)

Running balance column.
Footer: Closing Balance (highlighted)

PRINT format:
Clean ledger format like shown in .txt file:
"Account Ledger Of [NAME] From [DATE] to [DATE]
Sno  AcDate  Narration  Credit  Debit"

--- REPORT 2: Product List Reports ---
Route: /reports/products/list

Filter:
- Category [All option]
- Rate Type [dropdown]: 
  → Sale Rate (SALERATE column)
  → Yoshita Rate (Y-Rate)
  → Bangalore Rate (B-Rate)
  → All Rates (show all 3 columns)
- Branch [All]

Report:
Columns depend on Rate Type selected:
If "All Rates": Mat Code | Item Name | Category | UOM | Sale Rate | Y-Rate | B-Rate
If single rate: Mat Code | Item Name | Category | UOM | [Selected Rate]

Group by Category with subtotals.

--- REPORT 3: Price List ---
Route: /reports/products/price-list

Filter: Category, Rate Type (same as above)

Report: Clean price list format (for giving to customers):
- Firm header
- Category grouping
- Clean 2-column layout: Item Name | Rate

--- REPORT 4: Party List Report ---
Route: /reports/parties/list

Filter:
- Party Type [Customer / Supplier / All]
- State [All option]
- Branch

Report:
S.No | Party Code | Party Name | Address | Place | State | Mobile | GST No | Type

--- REPORT 5: Parcel List Report ---
Route: /reports/parcel/list

PURPOSE: Kisi date range mein kitne parcels gaye customers ko.

Filter:
- Date From [required]
- Date To [required]
- Branch

Report:
Date-wise grouped:
  Date: [DD-MMM-YYYY]
    Customer Name | Place | Items | Qty | Amount
  Day Total →

Group Footer: Per customer total per day.
Grand Total at bottom.

(Data source: sale_hdr + sale_dtl grouped by date and customer)

--- COMMON FOR ALL: ---
- Print (browser print)
- Export Excel (Laravel Excel)
- Amount in Words function for bill reprints







PROMPT 15 — Dashboard + Final Polish

PROJECT: Subha Enterprises Gold Jewelry Software (Laravel 11)
Almost complete — ye final step hai.

TASK: Dashboard banao aur pura software polish karo.

--- DASHBOARD ---
Route: / (home after login)

Show these widgets/cards:

Row 1 — Today's Summary (session login_date ke liye):
1. Today's Sales — Total Net Amount + Invoice Count
2. Today's Purchases — Total Net Amount
3. Low Stock Items — Count of items jinka cl_stock <= 10

Row 2 — Month Summary (current financial year mein current month):
4. Monthly Sales Chart — Bar chart (day-wise last 30 days, use Chart.js)
5. Top 5 Customers (by sale amount this month) — Table

Row 3 — Quick Links:
- New Sale Entry button
- New Purchase Entry button  
- View Stock button
- Party Ledger button

Row 4 — Recent Transactions:
- Last 10 Sale entries (Date, Customer, Net, Status)
- Last 10 Purchase entries (Date, Supplier, Net)

--- GLOBAL POLISH ---

1. Number Format Helper:
   - Banao ek PHP helper function: formatIndian(1234567.89) → "12,34,567.89"
   - Amount in Words function: amountInWords(1234.50) → "One Thousand Two Hundred Thirty Four Rupees and Fifty Paise Only"
   - Use karo: Indian numbering system (lakhs, crores)

2. Print CSS:
   Ek global print.css banao:
   - Nav bar, sidebar, buttons print mein hide ho jaye
   - Print ke liye clean white background
   - Company header auto-come from firm info

3. Flash Messages:
   - Success (green), Error (red), Warning (yellow) — Bootstrap alerts with auto-dismiss after 3 seconds

4. Loading Spinner:
   - Form submit hone pe loading overlay dikhao

5. Confirmation Dialogs:
   - Delete pe SweetAlert2 confirmation

6. Auto-logout:
   - 60 minutes inactivity ke baad session expire + login page

7. Bill Number Format:
   - Format: [BRCODE]-[YEAR]-[SEQNO] e.g., "1-2024-00045"

8. Keyboard Shortcuts (forms mein):
   - Enter key pe next field pe jao
   - F2 pe save/submit
   - Escape pe cancel/close modal

9. Mobile Responsive:
   - Bootstrap 5 use hai toh basic responsiveness hogi
   - Tables: horizontal scroll on small screens

10. Error Pages:
    - 404, 403, 500 custom pages with "Go to Dashboard" button

FINAL CHECKLIST verify karo:
- [ ] Login/Logout working
- [ ] All Masters: Firm, Branch, Category, UOM, Tax, Party, Product, Design, User, Stock
- [ ] All Transactions: Purchase, Self Purchase, Sale T1, Sale T2, Sale Return, Purchase Return, Order T1, Order T2, Estimation Invoice, Stock Transfer  
- [ ] All Reports: Daily Sales, Party Sales, Weekly Sales, Customer Weekly, Purchase, Weekly Purchase, Self Purchase, Stock Current, Stock Closing, Party Ledger, Product List, Price List, Party List, Parcel List
- [ ] Utilities: Financial Year, System Para, Change Password, User Management
- [ ] Dashboard working
- [ ] Print working for all bills
- [ ] Excel export working
- [ ] Stock auto-update on all transactions