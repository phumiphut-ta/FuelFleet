<div class="space-y-6" x-data="{ search: '' }">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-car text-indigo-400"></i> ทะเบียนยานพาหนะหลวง (Vehicle Registry)
            </h1>
            <p class="text-xs text-slate-400 font-light mt-1">บริหารจัดการข้อมูลรถยนต์ส่วนราชการ ประเภทพลังงานที่รองรับ และสถานะความพร้อมของตัวรถ</p>
        </div>
        <a href="/admin/cars/new" class="px-4 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-xs rounded-xl font-semibold flex items-center gap-1.5 shadow-lg shadow-indigo-500/10 transition transform hover:-translate-y-0.5">
            <i class="fa-solid fa-plus-circle text-[13px]"></i> ลงทะเบียนรถคันใหม่
        </a>
    </div>

    <!-- Feedback messages -->
    <?php if (!empty($success)): ?>
        <div class="bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 px-4 py-3 rounded-xl text-xs flex items-center space-x-2">
            <i class="fa-solid fa-circle-check text-sm text-emerald-400"></i>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="bg-rose-500/15 border border-rose-500/30 text-rose-300 px-4 py-3 rounded-xl text-xs flex items-center space-x-2">
            <i class="fa-solid fa-circle-exclamation text-sm text-rose-400"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Filter bar -->
    <div class="glass-panel p-4 rounded-xl flex flex-col md:flex-row items-center justify-between gap-4 border border-slate-800/80">
        <div class="relative w-full md:w-96">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </div>
            <input x-model="search" type="text" placeholder="ค้นหาด้วยป้ายทะเบียน ยี่ห้อ หรือน้ำมัน..."
                class="block w-full pl-9 pr-4 py-2 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
        </div>
        <div class="flex items-center space-x-4 text-xs text-slate-400 font-light">
            <span>ทั้งหมด: <strong class="text-white font-semibold"><?= count($cars) ?></strong> คัน</span>
        </div>
    </div>

    <!-- Grid Layout -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($cars)): ?>
            <div class="col-span-full py-12 text-center text-slate-500 text-xs font-light">ไม่พบประวัติยานพาหนะในระบบ</div>
        <?php else: ?>
            <?php foreach ($cars as $car): ?>
                <div x-show="search === '' || 
                        '<?= strtolower($car['license_plate']) ?>'.includes(search.toLowerCase()) || 
                        '<?= strtolower($car['fuel_type']) ?>'.includes(search.toLowerCase())" 
                    class="glass-card p-6 rounded-2xl glow-indigo relative flex flex-col justify-between">
                    
                    <!-- Plate / Accent -->
                    <div class="flex items-start justify-between border-b border-slate-800/80 pb-3.5 mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="h-10 w-10 rounded-xl bg-slate-850 flex items-center justify-center text-slate-400 border border-slate-800">
                                <i class="fa-solid fa-car-side text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-white"><?= htmlspecialchars($car['license_plate']) ?></h3>
                                <p class="text-[10px] text-indigo-400 font-medium tracking-wide uppercase"><i class="fa-solid fa-gas-pump text-[9px] mr-1"></i><?= htmlspecialchars($car['fuel_type']) ?></p>
                            </div>
                        </div>

                        <?php 
                        $statusClasses = [
                            'Active' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                            'Suspended' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                        ];
                        $statusLabels = [
                            'Active' => 'พร้อมใช้งาน',
                            'Suspended' => 'งดใช้งานชั่วคราว',
                        ];
                        $class = $statusClasses[$car['status']] ?? 'bg-slate-500/10 text-slate-400';
                        $label = $statusLabels[$car['status']] ?? $car['status'];
                        ?>
                        <span class="px-2 py-0.5 rounded-full border text-[9px] font-semibold <?= $class ?>">
                            <?= $label ?>
                        </span>
                    </div>

                    <!-- Details -->
                    <div class="text-xs text-slate-400 font-light space-y-1.5 flex-grow mb-6">
                        <p class="truncate"><i class="fa-regular fa-comment-dots text-[11px] mr-1.5 text-slate-500"></i>หมายเหตุ: <?= htmlspecialchars($car['note'] ?: 'ไม่มีข้อมูลหมายเหตุเพิ่มเติม') ?></p>
                        <p><i class="fa-regular fa-calendar-check text-[11px] mr-1.5 text-slate-500"></i>บันทึกเมื่อ: <?= date('d/m/Y', strtotime($car['created_at'])) ?></p>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2 pt-4 border-t border-slate-800/80 mt-auto">
                        <a href="/admin/cars/history/<?= $car['id'] ?>" class="flex-grow text-center py-2 bg-indigo-500/10 hover:bg-indigo-500 text-indigo-400 hover:text-white rounded-xl text-xs font-semibold transition duration-200">
                            <i class="fa-solid fa-history mr-1"></i> ดูประวัติการใช้รถ
                        </a>
                        <a href="/admin/cars/edit/<?= $car['id'] ?>" class="px-3 py-2 bg-slate-850 hover:bg-slate-800 text-slate-300 rounded-xl text-xs border border-slate-800 hover:border-slate-700 transition">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
