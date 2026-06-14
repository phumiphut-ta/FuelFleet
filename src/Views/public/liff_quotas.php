<?php
// Parse Thai Date string
$monthsThai = [
    '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน',
    '05' => 'พฤษภาคม', '06' => 'มิถุนายน', '07' => 'กรกฎาคม', '08' => 'สิงหาคม',
    '09' => 'กันยายน', '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'
];
list($year, $month) = explode('-', $currentMonth);
$thaiYear = (int)$year + 543;
$thaiMonthName = $monthsThai[$month] ?? '';
$thaiDateString = "$thaiMonthName $thaiYear";
?>
<!DOCTYPE html>
<html lang="th" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>โควต้าน้ำมันยานพาหนะ - FuelFleet LIFF</title>
    
    <!-- Tailwind CSS Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        darkBg: '#080b11',
                        slateCard: 'rgba(15, 23, 42, 0.45)',
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
    
    <style>
        body {
            background-color: #080b11;
            font-family: 'Outfit', 'Sarabun', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        .liff-glass-card {
            background: rgba(22, 28, 45, 0.55);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        .text-neon-emerald {
            color: #10b981;
            text-shadow: 0 0 10px rgba(16, 185, 129, 0.3);
        }
        .text-neon-rose {
            color: #f43f5e;
            text-shadow: 0 0 10px rgba(244, 63, 94, 0.3);
        }
    </style>
</head>
<body class="text-slate-200 min-h-screen relative overflow-x-hidden flex flex-col selection:bg-indigo-500 selection:text-white pb-8">

    <!-- Background glowing decorative spheres -->
    <div class="absolute top-[-5%] left-[-15%] w-[65vw] h-[65vw] rounded-full bg-indigo-900/10 blur-[100px] pointer-events-none animate-pulse-slow"></div>
    <div class="absolute bottom-[20%] right-[-15%] w-[65vw] h-[65vw] rounded-full bg-purple-900/10 blur-[100px] pointer-events-none animate-pulse-slow"></div>

    <!-- Main Mobile Container -->
    <div x-data="{ 
        searchQuery: '',
        selectedFuel: '',
        cars: <?= htmlspecialchars(json_encode($carQuotas), ENT_QUOTES, 'UTF-8') ?>,
        filteredCars() {
            return this.cars.filter(car => {
                const query = this.searchQuery.toLowerCase().trim();
                const matchesSearch = car.license_plate.toLowerCase().includes(query) || 
                                      car.fuel_type.toLowerCase().includes(query) ||
                                      (car.note && car.note.toLowerCase().includes(query));
                const matchesFuel = this.selectedFuel === '' || car.fuel_type === this.selectedFuel;
                return matchesSearch && matchesFuel;
            });
        }
    }" class="w-full max-w-md mx-auto px-4 pt-5 z-10 flex-grow">

        <!-- Header / Brand Console -->
        <div class="flex items-center justify-between mb-5 bg-slate-900/40 p-3 rounded-2xl border border-slate-800/60 liff-glass-card">
            <div class="flex items-center space-x-3">
                <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                    <i class="fa-solid fa-gas-pump text-white text-lg"></i>
                </div>
                <div>
                    <h1 class="text-base font-bold tracking-tight text-white flex items-center gap-1.5">
                        Fuel<span class="text-gradient-purple font-extrabold">Fleet</span>
                        <span class="px-1.5 py-0.5 text-[9px] bg-indigo-500/20 text-indigo-300 rounded border border-indigo-500/30">LIFF</span>
                    </h1>
                    <p class="text-[10px] text-slate-400 font-light">โควต้าน้ำมันเดือน <?= $thaiDateString ?></p>
                </div>
            </div>
            <!-- Pulsing Active Indicator -->
            <div class="flex items-center space-x-1.5 bg-emerald-500/10 px-2.5 py-1 rounded-full border border-emerald-500/20">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-ping"></span>
                <span class="text-[10px] font-medium text-emerald-400">LIFF Active</span>
            </div>
        </div>

        <!-- Sticky Search & Filters Panel -->
        <div class="mb-5 space-y-3">
            <!-- Search input -->
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input 
                    type="text" 
                    x-model="searchQuery" 
                    placeholder="ค้นหาเลขทะเบียน หรือประเภทน้ำมัน..." 
                    class="w-full pl-9 pr-8 py-2.5 bg-slate-950/40 border border-slate-850 rounded-xl text-xs placeholder-slate-500 text-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition shadow-inner"
                >
                <!-- Clear Button -->
                <button 
                    x-show="searchQuery !== ''" 
                    @click="searchQuery = ''" 
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-200 transition"
                    style="display: none;"
                >
                    <i class="fa-solid fa-circle-xmark text-xs"></i>
                </button>
            </div>

            <!-- Quick Fuel Filter Pills -->
            <div class="flex items-center space-x-2 overflow-x-auto pb-1 scrollbar-none">
                <button 
                    @click="selectedFuel = ''" 
                    :class="selectedFuel === '' ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-slate-900/40 text-slate-400 border-slate-850'" 
                    class="px-3.5 py-1.5 rounded-lg border text-[10px] font-semibold whitespace-nowrap transition cursor-pointer"
                >
                    ทั้งหมด
                </button>
                <button 
                    @click="selectedFuel = 'Diesel'" 
                    :class="selectedFuel === 'Diesel' ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-slate-900/40 text-slate-400 border-slate-850'" 
                    class="px-3.5 py-1.5 rounded-lg border text-[10px] font-semibold whitespace-nowrap transition cursor-pointer"
                >
                    Diesel
                </button>
                <button 
                    @click="selectedFuel = 'Gasohol 95'" 
                    :class="selectedFuel === 'Gasohol 95' ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-slate-900/40 text-slate-400 border-slate-850'" 
                    class="px-3.5 py-1.5 rounded-lg border text-[10px] font-semibold whitespace-nowrap transition cursor-pointer"
                >
                    Gasohol 95
                </button>
                <button 
                    @click="selectedFuel = 'Gasohol 91'" 
                    :class="selectedFuel === 'Gasohol 91' ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-slate-900/40 text-slate-400 border-slate-850'" 
                    class="px-3.5 py-1.5 rounded-lg border text-[10px] font-semibold whitespace-nowrap transition cursor-pointer"
                >
                    Gasohol 91
                </button>
            </div>
        </div>

        <!-- Vehicle Quota Status List -->
        <div class="space-y-4">
            
            <!-- Dynamic Cards via Alpine -->
            <template x-for="car in filteredCars()" :key="car.id">
                <div class="liff-glass-card p-4.5 rounded-2xl border border-slate-850/80 relative overflow-hidden flex flex-col transition hover:border-slate-700/80 duration-300">
                    
                    <!-- Decorative License Left Bar -->
                    <div 
                        class="absolute left-0 top-0 bottom-0 w-1.5" 
                        :style="{ backgroundColor: car.color }"
                    ></div>

                    <!-- Card Header -->
                    <div class="flex items-start justify-between mb-3.5 pl-2">
                        <div class="flex items-center space-x-2">
                            <span class="font-black text-base text-white tracking-wide" x-text="car.license_plate"></span>
                            <span 
                                class="px-2 py-0.5 rounded text-[8px] font-extrabold uppercase tracking-wide border bg-slate-950/40 text-slate-300 border-slate-800"
                                x-text="car.fuel_type"
                            ></span>
                        </div>

                        <!-- Status Badge -->
                        <div>
                            <template x-if="!car.has_quota && car.liters_used == 0">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border text-[9px] font-bold bg-slate-800 text-slate-400 border-slate-700">
                                    <i class="fa-solid fa-ban text-[8px]"></i> ไม่ได้กำหนดโควต้า
                                </span>
                            </template>
                            <template x-if="car.has_quota || car.liters_used > 0">
                                <div>
                                    <template x-if="car.is_over_quota">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border text-[9px] font-bold bg-rose-500/10 text-rose-400 border-rose-500/20">
                                            <i class="fa-solid fa-triangle-exclamation animate-pulse"></i> เกินโควต้า
                                        </span>
                                    </template>
                                    <template x-if="!car.is_over_quota">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border text-[9px] font-bold bg-emerald-500/10 text-emerald-400 border-emerald-500/20">
                                            <i class="fa-solid fa-circle-check"></i> ปกติ
                                        </span>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Metrics Grid (3 Columns) -->
                    <div class="grid grid-cols-3 gap-2 bg-slate-950/20 p-2.5 rounded-xl border border-slate-900/50 pl-4 pr-3.5 mb-3 text-xs">
                        <div class="flex flex-col">
                            <span class="text-[9px] text-slate-500 font-bold">โควต้าเดือนนี้</span>
                            <span class="text-sm font-extrabold text-slate-200 mt-0.5">
                                <template x-if="car.has_quota">
                                    <span><span x-text="Number(car.quota_liters).toFixed(0)"></span> <span class="text-[9px] font-light text-slate-400">ลิตร</span></span>
                                </template>
                                <template x-if="!car.has_quota">
                                    <span class="text-slate-500 text-xs font-normal">ไม่ได้กำหนด</span>
                                </template>
                            </span>
                        </div>
                        <div class="flex flex-col border-l border-slate-900/80 pl-3">
                            <span class="text-[9px] text-slate-500 font-bold">ใช้ไปแล้ว</span>
                            <span class="text-sm font-extrabold text-slate-200 mt-0.5">
                                <span x-text="Number(car.liters_used).toFixed(2)"></span> <span class="text-[9px] font-light text-slate-400">ลิตร</span>
                            </span>
                        </div>
                        <div class="flex flex-col border-l border-slate-900/80 pl-3">
                            <span class="text-[9px] text-slate-500 font-bold">คงเหลือ</span>
                            <span 
                                class="text-sm font-black mt-0.5"
                                :class="car.is_over_quota ? 'text-neon-rose font-black' : (car.has_quota ? 'text-neon-emerald font-black' : 'text-slate-500 font-normal')"
                            >
                                <template x-if="car.has_quota || car.liters_used > 0">
                                    <span><span x-text="Number(car.remaining_liters).toFixed(2)"></span> <span class="text-[9px] font-light text-slate-400">ลิตร</span></span>
                                </template>
                                <template x-if="!car.has_quota && car.liters_used == 0">
                                    <span class="text-xs">—</span>
                                </template>
                            </span>
                        </div>
                    </div>

                    <!-- Progress Bar Section -->
                    <div class="space-y-1.5 pl-2">
                        <div class="flex items-center justify-between text-[9px] text-slate-450 font-medium">
                            <span class="text-slate-400">สัดส่วนโควต้าคงเหลือ</span>
                            <span class="font-bold">
                                <template x-if="car.has_quota">
                                    <span :class="car.is_over_quota ? 'text-neon-rose' : 'text-neon-emerald'" x-text="car.percentage + '%'"></span>
                                </template>
                                <template x-if="!car.has_quota">
                                    <span class="text-slate-500 font-normal">—</span>
                                </template>
                            </span>
                        </div>
                        <div class="w-full h-2.5 bg-slate-950/60 rounded-full overflow-hidden border border-slate-900">
                            <div 
                                class="h-full rounded-full transition-all duration-500 ease-out"
                                :class="car.is_over_quota ? 'bg-gradient-to-r from-rose-500 to-pink-600 shadow-[0_0_12px_rgba(244,63,94,0.4)]' : (car.has_quota ? 'bg-gradient-to-r from-emerald-400 to-teal-500 shadow-[0_0_12px_rgba(16,185,129,0.4)]' : 'bg-slate-800')"
                                :style="{ width: Math.max(0, Math.min(100, car.percentage)) + '%' }"
                            ></div>
                        </div>
                    </div>

                    <!-- Car Note Section -->
                    <template x-if="car.note && car.note.trim() !== ''">
                        <div class="mt-3.5 pt-2.5 border-t border-slate-900/50 text-[10px] font-light flex items-center gap-1.5 pl-2 text-slate-400">
                            <i class="fa-solid fa-circle-info text-slate-500 text-[9px]"></i>
                            <span x-text="car.note"></span>
                        </div>
                    </template>

                </div>
            </template>

            <!-- Empty Search State -->
            <div 
                x-show="filteredCars().length === 0" 
                class="liff-glass-card p-10 rounded-2xl border border-dashed border-slate-800 text-center text-slate-500 mt-6"
                style="display: none;"
            >
                <div class="h-12 w-12 rounded-full bg-slate-900/60 flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-car-burst text-lg text-slate-600"></i>
                </div>
                <p class="text-xs font-semibold text-slate-400">ไม่พบข้อมูลรถยนต์ที่ค้นหา</p>
                <p class="text-[10px] text-slate-500 mt-1 font-light">ทดลองเปลี่ยนคำค้นหา หรือเลือกตัวกรองประเภทน้ำมันอื่น</p>
            </div>

        </div>

    </div>

    <!-- Minimal Mobile Footer -->
    <footer class="w-full text-center text-[9px] text-slate-600 font-light mt-auto pt-8">
        <p>© 2026 FuelFleet™ System. บริหารจัดการโควต้าน้ำมันแบบเรียลไทม์</p>
        <p class="mt-0.5">LINE LIFF Mobile Console</p>
    </footer>

</body>
</html>
