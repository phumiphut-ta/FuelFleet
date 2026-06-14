<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FuelFleet - ระบบบริหารจัดการรถยนต์ราชการและการจองใช้งาน</title>
    
    <!-- Tailwind CSS Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        darkBg: '#0b0f19',
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
    
    <!-- FullCalendar CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.8/locales/th.global.min.js"></script>
</head>
<body class="bg-darkBg min-h-screen flex flex-col antialiased selection:bg-indigo-500 selection:text-white relative">

    <!-- Background decorative glows -->
    <div class="absolute top-[-10%] left-[-10%] w-[50vw] h-[50vw] rounded-full bg-indigo-900/10 blur-[120px] pointer-events-none animate-pulse-slow"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[50vw] h-[50vw] rounded-full bg-purple-900/10 blur-[120px] pointer-events-none animate-pulse-slow"></div>

    <!-- Header Navigation -->
    <header class="sticky top-0 z-50 w-full px-6 py-4 glass-panel border-b border-slate-800">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- Brand -->
            <a href="/" class="flex items-center space-x-3 group">
                <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform duration-300">
                    <i class="fa-solid fa-gas-pump text-white text-lg"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-white flex items-center gap-1.5">
                        Fuel<span class="text-gradient-purple font-extrabold">Fleet</span>
                    </h1>
                    <p class="text-xs text-slate-400 font-light">ระบบบริหารจัดการรถและน้ำมันเชื้อเพลิง</p>
                </div>
            </a>
            
            <!-- Navigation Links -->
            <nav class="flex items-center space-x-1 bg-slate-900/50 p-1.5 rounded-xl border border-slate-800/80 overflow-x-auto whitespace-nowrap scrollbar-none max-w-full">
                <a href="/" class="px-4 py-2 text-sm rounded-lg font-medium text-slate-300 hover:text-white hover:bg-slate-800/50 transition">
                    <i class="fa-regular fa-calendar-days mr-1.5 text-indigo-400"></i>ปฏิทินการจอง
                </a>
                <a href="/booking/new" class="px-4 py-2 text-sm rounded-lg font-medium text-slate-300 hover:text-white hover:bg-slate-800/50 transition">
                    <i class="fa-solid fa-plus-circle mr-1.5 text-indigo-400"></i>จองรถยนต์
                </a>
                <a href="/heatmap" class="px-4 py-2 text-sm rounded-lg font-medium text-slate-300 hover:text-white hover:bg-slate-800/50 transition">
                    <i class="fa-solid fa-map-location-dot mr-1.5 text-indigo-400"></i>แผนที่ความร้อน
                </a>
                <a href="/receipts/recent" class="px-4 py-2 text-sm rounded-lg font-medium text-slate-300 hover:text-white hover:bg-slate-800/50 transition">
                    <i class="fa-solid fa-receipt mr-1.5 text-indigo-400"></i>ใบเสร็จล่าสุด
                </a>
            </nav>

            <!-- Admin Button / Clock -->
            <div class="flex items-center space-x-4">
                <div class="hidden lg:flex flex-col text-right text-xs text-slate-400 border-r border-slate-800 pr-4">
                    <span id="digital-clock" class="font-medium text-white">00:00:00</span>
                    <span class="font-light text-slate-500">วันเสาร์ที่ 30 พ.ค. 2569</span>
                </div>
                
                <?php if (\App\Core\AuthMiddleware::isAdminLoggedIn()): ?>
                    <a href="/admin/dashboard" class="px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-medium text-sm rounded-xl shadow-lg shadow-emerald-500/10 flex items-center gap-2 transition duration-300 transform hover:-translate-y-0.5">
                        <i class="fa-solid fa-toolbox"></i> แผงควบคุมแอดมิน
                    </a>
                <?php else: ?>
                    <a href="/admin/login" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-medium text-sm rounded-xl border border-slate-700/80 hover:border-slate-600 flex items-center gap-2 transition duration-300">
                        <i class="fa-solid fa-lock text-indigo-400"></i> เข้าสู่ระบบแอดมิน
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow max-w-7xl mx-auto w-full px-6 py-8">
        {{content}}
    </main>

    <!-- Footer -->
    <footer class="w-full py-6 text-center border-t border-slate-800/80 bg-slate-950/20 text-slate-500 text-xs font-light">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-4">
            <div>
                <?php
                    try {
                        $__db = \App\Core\Database::getConnection();
                        $__footerText = $__db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'footer_copyright' LIMIT 1")->fetchColumn();
                    } catch (\Throwable $e) {
                        $__footerText = null;
                    }
                    echo htmlspecialchars($__footerText ?: '© 2026 FuelFleet™. ระบบบริหารรถส่วนราชการ. สงวนลิขสิทธิ์ทั้งหมด.');
                ?>
            </div>
            <div class="flex items-center space-x-4">
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500 animate-ping"></span> พร้อมใช้งานในเครือข่าย</span>
                <a href="/admin/login" class="hover:text-slate-400 transition">ผู้ดูแลระบบ</a>
            </div>
        </div>
    </footer>


    <!-- Digital Clock Script -->
    <script>
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const clockEl = document.getElementById('digital-clock');
            if (clockEl) {
                clockEl.textContent = `${hours}:${minutes}:${seconds}`;
            }
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>
