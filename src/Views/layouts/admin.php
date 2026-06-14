<?php
try {
    $__db = \App\Core\Database::getConnection();
    $__pendingCount = (int)$__db->query("SELECT COUNT(*) FROM car_booking WHERE status = 'Pending'")->fetchColumn();
    
    // Check if there are vehicles with remaining liters <= threshold
    $__currentMonthStart = date('Y-m-01');
    $__currentMonthEnd   = date('Y-m-t');
    $__lowQuotaCarsCount = (int)$__db->query("
        SELECT COUNT(*)
        FROM (
            SELECT
                c.id,
                COALESCE(q.monthly_quota, 0) AS quota_liters,
                COALESCE(SUM(r.liters), 0) AS used_liters,
                COALESCE(c.remaining_low_threshold, 20.00) AS threshold
            FROM car_detail c
            LEFT JOIN car_quota_history q
                ON q.car_id = c.id
                AND q.id = (
                    SELECT id FROM car_quota_history
                    WHERE car_id = c.id
                    ORDER BY effective_month DESC
                    LIMIT 1
                )
            LEFT JOIN gas_receipt r
                ON r.car_id = c.id
                AND r.status = 'Verified'
                AND r.receipt_date BETWEEN '{$__currentMonthStart}' AND '{$__currentMonthEnd}'
            WHERE c.status = 'Active'
            GROUP BY c.id, q.monthly_quota, c.remaining_low_threshold
        ) AS car_summary
        WHERE (quota_liters - used_liters) <= threshold AND quota_liters > 0
    ")->fetchColumn();
} catch (\Throwable $e) {
    $__pendingCount = 0;
    $__lowQuotaCarsCount = 0;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FuelFleet - แผงจัดการระบบหลังบ้าน (Admin Console)</title>
    
    <!-- Tailwind CSS Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        darkBg: '#090d16',
                        slateCard: 'rgba(30, 41, 59, 0.4)',
                        accentPurple: '#818cf8',
                    }
                }
            }
        }
    </script>
    
    <!-- Custom Style -->
    <link rel="stylesheet" href="/css/style.css">
    
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-darkBg min-h-screen flex flex-col md:flex-row antialiased selection:bg-indigo-500 selection:text-white"
      x-data="{ sidebarOpen: false }">

    <!-- Sidebar Navigation -->
    <aside class="w-full md:w-64 glass-panel border-r border-slate-800 flex-col z-30 shrink-0 md:min-h-screen md:flex"
           :class="sidebarOpen ? 'flex fixed inset-0 bg-darkBg/98' : 'hidden'">
        <!-- Brand / Header -->
        <div class="p-6 border-b border-slate-800/80 flex items-center justify-between">
            <a href="/admin/dashboard" class="flex items-center space-x-3 group">
                <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                    <i class="fa-solid fa-gas-pump text-white text-md"></i>
                </div>
                <div>
                    <h2 class="text-md font-bold tracking-tight text-white flex items-center gap-1">
                        Fuel<span class="text-gradient-purple font-extrabold">Console</span>
                    </h2>
                    <span class="text-[10px] text-emerald-400 font-medium px-1.5 py-0.5 bg-emerald-500/10 rounded-full border border-emerald-500/20"><i class="fa-solid fa-shield-halved text-[9px] mr-1"></i>แอดมิน</span>
                </div>
            </a>
            <!-- Close Sidebar Menu (Mobile Only) -->
            <button @click="sidebarOpen = false" class="md:hidden text-slate-400 hover:text-white transition p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Session User Info -->
        <div class="px-6 py-4 border-b border-slate-850 flex items-center space-x-3 bg-slate-900/10">
            <div class="h-9 w-9 rounded-full bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                <i class="fa-solid fa-user-tie text-md"></i>
            </div>
            <div class="overflow-hidden">
                <p class="text-xs font-semibold text-slate-200 truncate"><?= htmlspecialchars($_SESSION['admin_user']['full_name'] ?? 'ผู้ดูแลระบบ') ?></p>
                <p class="text-[10px] text-slate-500 truncate"><?= htmlspecialchars($_SESSION['admin_user']['role'] ?? 'ผู้จัดการระบบ') ?></p>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-grow p-4 space-y-1.5 overflow-y-auto">
            <p class="text-[10px] font-bold text-slate-600 tracking-wider uppercase px-3 mb-2">ข้อมูลวิเคราะห์</p>
            <a href="/admin/dashboard" class="flex items-center px-3 py-2 text-xs font-medium rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/40 transition gap-2.5">
                <i class="fa-solid fa-chart-line text-slate-500 text-sm w-5"></i>แดชบอร์ด
            </a>

            <p class="text-[10px] font-bold text-slate-600 tracking-wider uppercase px-3 pt-4 mb-2">การจัดการข้อมูลหลัก</p>
            <a href="/admin/employees" class="flex items-center px-3 py-2 text-xs font-medium rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/40 transition gap-2.5">
                <i class="fa-solid fa-users text-slate-500 text-sm w-5"></i>ทะเบียนพนักงาน
            </a>
            <a href="/admin/cars" class="flex items-center px-3 py-2 text-xs font-medium rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/40 transition gap-2.5">
                <i class="fa-solid fa-car text-slate-500 text-sm w-5"></i>ทะเบียนยานพาหนะ
            </a>
            <a href="/admin/history-import" class="flex items-center px-3 py-2 text-xs font-medium rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/40 transition gap-2.5">
                <i class="fa-solid fa-file-import text-slate-500 text-sm w-5"></i>บันทึกประวัติย้อนหลัง
            </a>

            <p class="text-[10px] font-bold text-slate-600 tracking-wider uppercase px-3 pt-4 mb-2">การควบคุมและโอนโควต้า</p>
            <a href="/admin/quotas" class="flex items-center px-3 py-2 text-xs font-medium rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/40 transition gap-2.5">
                <i class="fa-solid fa-coins text-slate-500 text-sm w-5"></i>โควต้าน้ำมันรายเดือน
            </a>
            <a href="/admin/suspensions" class="flex items-center px-3 py-2 text-xs font-medium rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/40 transition gap-2.5">
                <i class="fa-solid fa-ban text-slate-500 text-sm w-5"></i>คำสั่งระงับการใช้รถ
            </a>
            <a href="/admin/bookings" class="flex items-center justify-between px-3 py-2 text-xs font-medium rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/40 transition">
                <span class="flex items-center gap-2.5">
                    <i class="fa-regular fa-calendar-check text-slate-500 text-sm w-5"></i>จัดการการจองรถยนต์
                </span>
                <?php if ($__pendingCount > 0): ?>
                    <span class="px-1.5 py-0.5 text-[9px] font-extrabold bg-amber-500 text-slate-950 rounded-full animate-pulse">
                        <?= $__pendingCount ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="/admin/agreements" class="flex items-center px-3 py-2 text-xs font-medium rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/40 transition gap-2.5">
                <i class="fa-solid fa-list-check text-slate-500 text-sm w-5"></i>ข้อตกลงการจองรถยนต์
            </a>
            <a href="/admin/receipts" class="flex items-center px-3 py-2 text-xs font-medium rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/40 transition gap-2.5">
                <i class="fa-solid fa-file-invoice-dollar text-slate-500 text-sm w-5"></i>ตรวจสอบใบเสร็จน้ำมัน
            </a>
            <a href="/admin/line-helper" class="flex items-center justify-between px-3 py-2 text-xs font-medium rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/40 transition">
                <span class="flex items-center gap-2.5">
                    <i class="fa-solid fa-bullhorn text-slate-500 text-sm w-5"></i>ตัวช่วยแจ้งเตือน LINE
                </span>
                <?php if ($__lowQuotaCarsCount > 0): ?>
                    <span class="px-1.5 py-0.5 text-[9px] font-extrabold bg-rose-500 text-white rounded-full animate-pulse">
                        <?= $__lowQuotaCarsCount ?>
                    </span>
                <?php endif; ?>
            </a>

            <p class="text-[10px] font-bold text-slate-600 tracking-wider uppercase px-3 pt-4 mb-2">ความปลอดภัยและรายงาน</p>
            <a href="/admin/reports" class="flex items-center px-3 py-2 text-xs font-medium rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/40 transition gap-2.5">
                <i class="fa-solid fa-file-pdf text-slate-500 text-sm w-5"></i>ศูนย์ออกรายงาน PDF
            </a>
            <a href="/admin/audit-logs" class="flex items-center px-3 py-2 text-xs font-medium rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/40 transition gap-2.5">
                <i class="fa-solid fa-history text-slate-500 text-sm w-5"></i>ประวัติการใช้งานระบบ
            </a>
            <a href="/admin/users" class="flex items-center px-3 py-2 text-xs font-medium rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/40 transition gap-2.5">
                <i class="fa-solid fa-user-shield text-slate-500 text-sm w-5"></i>จัดการผู้ดูแลระบบ
            </a>
        </nav>

        <!-- Sidebar Footer Buttons -->
        <div class="p-4 border-t border-slate-850 space-y-1 text-[11px]">
            <a href="/admin/change-password" class="flex items-center px-3 py-2 text-slate-400 hover:text-white hover:bg-slate-850 rounded-lg transition gap-2">
                <i class="fa-solid fa-key text-sm w-4 text-indigo-400"></i>เปลี่ยนรหัสผ่าน
            </a>
            <a href="/" class="flex items-center px-3 py-2 text-slate-400 hover:text-white hover:bg-slate-850 rounded-lg transition gap-2">
                <i class="fa-regular fa-eye text-sm w-4"></i>ดูหน้าสาธารณะ
            </a>
            <a href="/admin/logout" class="flex items-center px-3 py-2 text-rose-400 hover:text-white hover:bg-rose-950/20 rounded-lg transition gap-2">
                <i class="fa-solid fa-arrow-right-from-bracket text-sm w-4"></i>ออกจากระบบ
            </a>
        </div>
    </aside>

    <!-- Main Workspace Container -->
    <div class="flex-grow flex flex-col min-w-0">
        <!-- Top Workspace Bar -->
        <header class="h-16 border-b border-slate-850 px-6 flex items-center justify-between bg-slate-900/10 backdrop-blur-md sticky top-0 z-20">
            <div class="flex items-center space-x-3">
                <!-- Hamburger Menu Toggle Button (Mobile Only) -->
                <button @click="sidebarOpen = true" class="md:hidden text-slate-400 hover:text-white transition p-2 -ml-2 rounded-lg bg-slate-900/50 border border-slate-800">
                    <i class="fa-solid fa-bars text-sm"></i>
                </button>
                <h3 class="text-sm font-semibold text-slate-200">แผงควบคุมระบบบริหารจัดการรถและน้ำมันเชื้อเพลิง</h3>
            </div>
            <div class="flex items-center space-x-4 text-xs text-slate-400">
                <?php if ($__pendingCount > 0): ?>
                    <a href="/admin/bookings" class="flex items-center gap-1.5 px-3 py-1 bg-amber-500/10 border border-amber-500/20 rounded-full text-amber-450 hover:bg-amber-500/20 transition animate-pulse">
                        <i class="fa-solid fa-bell text-[11px] text-amber-400"></i>
                        <span class="text-[10px] font-extrabold tracking-tight">การจองรออนุมัติ <?= $__pendingCount ?> รายการ</span>
                    </a>
                <?php endif; ?>
                <?php if ($__lowQuotaCarsCount > 0): ?>
                    <a href="/admin/line-helper" class="flex items-center gap-1.5 px-3 py-1 bg-rose-500/10 border border-rose-500/20 rounded-full text-rose-400 hover:bg-rose-500/20 transition animate-pulse">
                        <i class="fa-solid fa-bullhorn text-[11px] text-rose-450"></i>
                        <span class="text-[10px] font-extrabold tracking-tight">โควต้าใกล้หมด <?= $__lowQuotaCarsCount ?> คัน (ควรแจ้งเตือน)</span>
                    </a>
                <?php endif; ?>
                <span class="flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-ping"></span> <span class="hidden sm:inline">เชื่อมต่อฐานข้อมูลสำเร็จ</span></span>
            </div>
        </header>
        
        <!-- Inner page view content container -->
        <main class="flex-grow p-6 overflow-y-auto max-w-[1600px] w-full mx-auto">
            {{content}}
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-800/60 py-3 px-6 text-[11px] text-slate-600 text-center">
            <?php
                try {
                    $__db = \App\Core\Database::getConnection();
                    $__footerText = $__db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'footer_copyright' LIMIT 1")->fetchColumn();
                } catch (\Throwable $e) {
                    $__footerText = null;
                }
                echo htmlspecialchars($__footerText ?: '© 2026 FuelFleet™. ระบบบริหารรถส่วนราชการ. สงวนลิขสิทธิ์ทั้งหมด.');
            ?>
        </footer>
    </div>

</body>
</html>
