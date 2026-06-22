# Tasks: FuelFleet System Enhancements

- [x] Modify public view `src/Views/public/booking_form.php` to use date inputs
- [x] Modify `src/Controllers/Public/BookingController.php` validation and date formatting
- [x] Modify `src/Services/BookingService.php` overlap text format
- [x] Modify `src/Controllers/Admin/SuspensionController.php` warning format
- [x] Modify `src/Views/admin/car/history.php` list formats
- [x] Update `tests/Unit/Services/BookingServiceTest.php` mock inputs and assertions
- [x] Run test suite (`composer test`) to verify correctness
- [x] Optimize receipt uploading interface (`new.php`) for PDF-first document formats
- [x] Build professional Electronic Document Attachment Certificate layout for PDF receipts in `ReportController.php`
- [x] Run automated tests (`composer test`) to ensure system integrity
- [x] Create walkthrough documentation

## Dynamic PDF Footer Migration
- [x] Create `system_settings` table and seed mock data in `database/schema.sql`
- [x] Update `src/Controllers/Admin/ReportController.php` to fetch PDF footer setting dynamically with fallback
- [x] Run automated test suite (`composer test`) to verify system integrity
- [x] Verify changes manually and document in the walkthrough

## Optional Uploads, Self-Verification, and Receipt Editing
- [x] Update `ReceiptRepositoryInterface.php` with `update` method
- [x] Implement `update` method in `ReceiptRepository.php`
- [x] Implement `updateReceipt` method in `ReceiptService.php`
- [x] Register edit/update routes in `public/index.php`
- [x] Update `ReceiptController.php` (new default status and edit/update actions)
- [x] Remove `required` from the file input in `src/Views/admin/receipt/new.php`
- [x] Add "Edit" button to `src/Views/admin/receipt/index.php`
- [x] Create edit view `src/Views/admin/receipt/edit.php`
- [x] Run test suite (`composer test`) to verify system integrity
- [x] Verify changes manually and document in the walkthrough

## Fiscal Year Popular Destination Report
- [x] Update filter options and names in `src/Views/admin/report/index.php`
- [x] Modify Case 3 in `src/Controllers/Admin/ReportController.php` to use fiscal year and bound inputs
- [x] Run test suite (`composer test`) to ensure system integrity
- [x] Verify changes manually and document in the walkthrough

## Fiscal Year Fuel Quota and Usage Matrix Report (Report 2 Redesign)
- [x] Rename Report 2 UI Selector Label in `src/Views/admin/report/index.php`
- [x] Redesign Report 2 Controller Logic & SQL in `src/Controllers/Admin/ReportController.php`
- [x] Verify using automated tests (`composer test`)
- [x] Verify manually and update walkthrough.md

## Date Range Filters and Excel Export in Fuel Receipts Console
- [x] Add Export Route in `public/index.php`
- [x] Add signatures to `ReceiptRepositoryInterface.php`
- [x] Implement filter and export logic in `ReceiptRepository.php`
- [x] Implement export action and update index action in `ReceiptController.php`
- [x] Redesign search form and update pagination in `src/Views/admin/receipt/index.php`
- [x] Verify using automated tests (`composer test`)
- [x] Verify manually and update walkthrough.md

## Admin Booking Management (Edit and Cancel Bookings)
- [x] Modify `BookingRepositoryInterface.php` to add update method and adjust getOverlappingBookings signature
- [x] Implement database updates and overlap check modifications in `BookingRepository.php`
- [x] Implement `updateBooking` method in `BookingService.php`
- [x] Register admin booking management routes in `public/index.php`
- [x] Create `BookingController.php` for admin booking controls
- [x] Create list page `src/Views/admin/booking/index.php`
- [x] Create edit form page `src/Views/admin/booking/edit.php`
- [x] Add menu link in sidebar `src/Views/layouts/admin.php`
- [x] Run automated tests (`composer test`) to ensure system integrity
- [x] Update walkthrough.md with verification details

## Admin Booking Console Pagination, Filters, and Excel Export
- [x] Declare search, count, and export methods in `BookingRepositoryInterface.php`
- [x] Implement search, count, and export methods in `BookingRepository.php`
- [x] Update `index` method and implement `export` action in admin `BookingController.php`
- [x] Add the export route in `public/index.php`
- [x] Update `src/Views/admin/booking/index.php` to include 5-column filter and pagination
- [x] Run automated tests (`composer test`)
- [x] Update walkthrough.md with verification details

## LINE LIFF Vehicle Fuel Quota View
- [x] Bypass layout in `src/Core/Router.php` for `public/liff_` views
- [x] Register `GET /liff/quotas` route in `public/index.php`
- [x] Create `src/Controllers/Public/LiffController.php`
- [x] Create mobile-first view `src/Views/public/liff_quotas.php`
- [x] Run automated tests (`composer test`) to ensure system integrity
- [x] Verify manually and update walkthrough.md

## Admin Password Change console
- [x] Register change-password routes in `public/index.php`
- [x] Add "เปลี่ยนรหัสผ่าน" link in `src/Views/layouts/admin.php` sidebar footer
- [x] Implement `showChangePassword` and `changePassword` actions in `AuthController.php`
- [x] Create Change Password form view `src/Views/admin/change_password.php`
- [x] Run automated tests (`composer test`) to verify system integrity
- [x] Verify manually and update walkthrough.md

## Booking Statistics PDF Report (Report 8)
- [x] Implement success/error flash inputs in `index` action of `ReportController.php`
- [x] Add Report 8 button and date range fields in `src/Views/admin/report/index.php`
- [x] Parse `start_date`/`end_date` and implement Case 8 in `generate` action of `ReportController.php`
- [x] Run automated tests (`composer test`) to ensure system integrity
- [x] Verify manually and update walkthrough.md

## GitHub Repository Preparation
- [x] Refactor `config/database.php` to support environmental files (.env)
- [x] Create `.env.example` showing template database credentials
- [x] Create `.gitignore` to prevent committing secrets/dependencies/uploads
- [x] Create `.gitkeep` files in `public/uploads` and `public/uploads/tmp`
- [x] Create detailed `README.md` containing documentation & setup guidelines
- [x] Run automated tests (`composer test`) to verify system correctness
- [x] Verify manually and update walkthrough.md

## Admin User Management Console
- [x] Implement CRUD signatures in `AdminUserRepositoryInterface.php`
- [x] Implement CRUD queries in `AdminUserRepository.php`
- [x] Create `AdminUserController.php` with ID 1 protection checks
- [x] Register user management routes in `public/index.php`
- [x] Add menu link in sidebar `src/Views/layouts/admin.php`
- [x] Create list page `src/Views/admin/users/index.php`
- [x] Create creation form page `src/Views/admin/users/new.php`
- [x] Create edit form page `src/Views/admin/users/edit.php`
- [x] Write unit test `tests/Unit/Controllers/AdminUserControllerTest.php`
- [x] Run automated tests (`composer test`) to ensure system integrity
- [x] Verify manually and update walkthrough.md

## Booking Workflow & Agreements
- [x] Run database structure migrations (`migrate_agreements_status.php`)
- [x] Update schema files `schema.sql` and `reset_system.sql`
- [x] Create `AgreementController.php` for admin agreement settings
- [x] Create view `src/Views/admin/agreements/index.php`
- [x] Register new routes in `public/index.php`
- [x] Add sidebar link in `src/Views/layouts/admin.php`
- [x] Update public booking form `booking_form.php` with agreement checkboxes
- [x] Set default booking status to Pending in `BookingRepository.php`
- [x] Support Pending status on FullCalendar and legend indicators in `calendar.php`
- [x] Add public edit form `booking_edit.php` and public edit controller methods
- [x] Add edit booking button to public calendar modal
- [x] Implement admin approval and cancel-with-reason in admin `BookingController.php`
- [x] Update admin booking view with approval and cancellation reasons
- [x] Run automated tests (`composer test`)
- [x] Verify manually and update walkthrough.md
- [x] Push all changes to GitHub Repository
- [x] Add notification badge and alert to admin layout for pending bookings
- [x] Fix public calendar edit link returning 404 in subdirectory configurations
- [x] Fix admin cancel booking form action returning 404 in subdirectory configurations
- [x] Fix overlap check to include Pending bookings during creation and update

## Vehicle Booking Cancellation Report (Report 9)
- [x] Update `src/Views/admin/report/index.php` to include Report 9 select button and date range condition
- [x] Modify `src/Controllers/Admin/ReportController.php` to parse inputs and query cancellations
- [x] Implement Case 9 PDF generation in `ReportController.php` using A4-L format
- [x] Run automated tests (`composer test`) to ensure system integrity
- [x] Verify manually and update walkthrough.md

## Historical Data Import Console (บันทึกประวัติย้อนหลัง)
- [x] Register new routes for history import in `public/index.php`
- [x] Add menu link "บันทึกประวัติย้อนหลัง" in sidebar `src/Views/layouts/admin.php`
- [x] Create controller `src/Controllers/Admin/HistoryImportController.php` with import actions
- [x] Create index view `src/Views/admin/history_import/index.php` with double tabs UI
- [x] Write unit test `tests/Unit/Controllers/HistoryImportControllerTest.php`
- [x] Run automated tests (`composer test`) to ensure system integrity
- [x] Verify manually and update walkthrough.md

## Centered PDF Page Numbering (ใส่เลขหน้าตรงกลางท้ายกระดาษ PDF)
- [x] Configure SetFooter in `ReportController.php` using mPDF centered placeholder format
- [x] Run automated tests (`composer test`) to ensure system integrity
- [x] Verify manually and update walkthrough.md

## LINE Announcement Helper (ตัวช่วยเตรียมข้อความแจ้งเตือนกลุ่ม LINE)
- [x] Create migration `database/migrate_remaining_threshold.php`
- [x] Add `remaining_low_threshold` column and seed template in `database/schema.sql`
- [x] Add static routes to `public/index.php`
- [x] Modify sidebar & top header navigation in `src/Views/layouts/admin.php` for low quota warnings
- [x] Implement controller `src/Controllers/Admin/LineHelperController.php`
- [x] Create view `src/Views/admin/line_helper/index.php`
- [x] Create unit test `tests/Unit/Controllers/LineHelperControllerTest.php`
- [x] Run automated tests (`composer test`)
- [x] Create walkthrough details

## Booking Agreement Reordering (จัดเรียงข้อตกลงและเงื่อนไขการจองรถยนต์)
- [x] Create database migration `database/migrate_agreements_sort_order.php` for `sort_order` column
- [x] Implement reordering swap controller logic in `AgreementController.php`
- [x] Update sorting queries in admin and public Controllers (`AgreementController`, `BookingController`)
- [x] Add reorder Up/Down buttons and design sorting UI in `src/Views/admin/agreements/index.php`
- [x] Write unit tests in `tests/Unit/Controllers/AgreementControllerTest.php`
- [x] Run automated tests (`composer test`) to confirm success
- [x] Update walkthrough.md and stage all code changes
- [x] Commit and push all files to remote Git repository

## Monthly Vehicle Booking PDF Report (รายงานการจองรถยนต์ประจำเดือน)
- [x] Add Report 10 selector button in `src/Views/admin/report/index.php`
- [x] Bind month, year, and vehicle filters to display when Report 10 is selected
- [x] Implement query and PDF template rendering in `ReportController.php` under `case 10`
- [x] Include all requested columns (No, Booking Date, Car Plate, Booker, Date Range, Destination, Purpose) in A4-L landscape format
- [x] Order records by creation timestamp `created_at ASC`
- [x] Support both specific vehicle filter and "All Vehicles" option
- [x] Write unit test for Report 10 in `tests/Unit/Controllers/ReportControllerTest.php`
- [x] Run automated tests (`composer test`) to verify correctness
- [x] Verify manually and document in the walkthrough
- [x] Commit and push code to remote Git repository

## Bug Fixes (แก้ไขบั๊กและปรับปรุงเพิ่มเติม)
- [x] Fix Monthly Fuel Usage Report (Report 1) quota query logic to carry over last effective quota instead of exact match
- [x] Fix CalendarController travel statistics and top bookers query to filter by travel start_time rather than booking_date, aligning with Report 3 and Report 8
- [x] Write unit test for CalendarController instantiation with database mocking

## Thailand Travel Choropleth Heatmap (แผนที่ความถี่การเดินทางปลายทาง)
- [x] Add Leaflet JS & CSS assets to `heatmap.php`
- [x] Redesign `heatmap.php` grid layout to place the map side-by-side with ranking & pie charts
- [x] Implement Leaflet vector rendering with `thailand_apisit.json` and custom province mapping
- [x] Implement dynamic HSL color scaling based on max travel count
- [x] Implement mousemove-based floating HTML tooltip and map legend
- [x] Verify choropleth map dynamically changes on changing fiscal years
- [x] Run test suite (`composer test`) to ensure zero regressions

## Security Hardening (ระบบการป้องกันความปลอดภัยและการโจมตี)
- [x] Configure secure session cookie parameters in bootstrap `public/index.php`
- [x] Implement CSRF class helper `src/Core/Csrf.php` with secure token generation & verification
- [x] Integrate CSRF protection check for POST requests in `Router::resolve`
- [x] Dynamically inject CSRF token hidden fields into all POST forms in `Router::renderView`
- [x] Handle failed CSRF validation with HTTP 403 status and premium security error page `src/Views/error.php`
- [x] Convert direct SQL string concatenations in `ReceiptController.php` and `ReportController.php` to prepared statements
- [x] Write unit tests for CSRF helper and Router CSRF integration
- [x] Verify all unit tests pass with `composer test` and ensure system stability

## Discord Notification Settings (ระบบตั้งค่าการแจ้งเตือน Discord Webhook)
- [x] Create DiscordNotifier core class to trigger embeds for all 9 topics
- [x] Create DiscordSettingsController and views for discord_settings/index
- [x] Integrate Discord settings route and sidebar link in admin layout
- [x] Wire up Booking notifications (Topic 1: New booking, Topic 2: Cancelled booking)
- [x] Wire up Vehicle status notifications (Topic 3: Suspended/Reactivated car)
- [x] Wire up Fuel quotas alerts (Topic 4: Quota low, Topic 5: Quota empty/exceeded)
- [x] Wire up Receipt approvals alerts (Topic 6: Receipt pending, Topic 7: Verification result)
- [x] Wire up Security and audit logs (Topic 8: Login, Change password, Update quota)
- [x] Wire up System Errors global catch hook (Topic 9: Unhandled Exceptions)
- [x] Fix Router to support multiple view rendering calls by changing `include_once` to `include`
- [x] Write unit tests for DiscordSettingsController and DiscordNotifier
- [x] Verify all 62 unit tests pass successfully

## Duplicate Receipt Number Checking Adjustments (การปรับปรุงตรวจสอบเลขที่ใบเสร็จซ้ำ)
- [x] Modify `findByReceiptNumber` in `ReceiptRepository.php` to exclude receipts with 'Cancelled' status, allowing reused numbers
- [x] Verify using automated test suite (`composer test`)

## Discord Low Quota Alert Cycle & LINE Live Preview (การแจ้งเตือนรอบเวลาโควต้าน้ำมันต่ำและ Live Preview)
- [x] Create database migration `database/migrate_last_quota_alert_at.php` to add `last_quota_alert_at` column
- [x] Add `last_quota_alert_at` column with `NULL` default to `database/schema.sql`
- [x] Add alert cycle dropdown selection in Discord settings page `src/Views/admin/discord_settings/index.php`
- [x] Save alert cycle configuration parameter in `DiscordSettingsController.php`
- [x] Implement cycle throttling logic and interpolate LINE Live Preview text in `checkAndSendQuotaAlerts` inside `DiscordNotifier.php`
- [x] Expand unit tests in `tests/Unit/Core/DiscordNotifierTest.php` to verify all cycle timings and reset condition
- [x] Run automated test suite (`composer test`) to confirm success
- [x] Stage, commit, and push all files to remote Git repository

## Vehicle Fuel Quota Combined Column in Dashboard and Heat Maps (การปรับปรุงตารางแสดงผลปริมาณน้ำมันใน Dashboard และ Heat Maps)
- [x] Modify Dashboard view `src/Views/admin/dashboard/index.php` to merge quota columns into "used / quota (remaining)" format
- [x] Modify Heat Maps view `src/Views/public/heatmap.php` to merge quota columns into "used / quota (remaining)" format
- [x] Run automated test suite (`composer test`) to confirm success
- [x] Commit and push changes to remote Git repository


