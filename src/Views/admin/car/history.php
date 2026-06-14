<div class="space-y-6" x-data="{ tab: 'bookings' }">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <div class="h-12 w-12 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/10">
                    <i class="fa-solid fa-folder-open text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white tracking-tight">ประวัติยานพาหนะ: <?= htmlspecialchars($car['license_plate']) ?></h1>
                    <p class="text-xs text-slate-400 font-light">ประเภทน้ำมัน: <span class="text-indigo-400 font-semibold"><?= htmlspecialchars($car['fuel_type']) ?></span> &bull; สรุปประวัติกิจกรรมย้อนหลังในฐานข้อมูล</p>
                </div>
            </div>
        </div>
        <a href="/admin/cars" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/80 text-xs rounded-xl font-medium flex items-center gap-1.5 transition">
            <i class="fa-solid fa-arrow-left"></i> กลับหน้าทะเบียนรถยนต์
        </a>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Total Bookings -->
        <div class="glass-panel p-5 rounded-2xl border border-slate-800 flex items-center justify-between">
            <div>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">จำนวนการจองใช้งานทั้งหมด</p>
                <h2 class="text-2xl font-extrabold text-white mt-1"><?= count($bookings) ?> ครั้ง</h2>
            </div>
            <div class="h-10 w-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-400"><i class="fa-solid fa-calendar-check text-lg"></i></div>
        </div>
        
        <!-- Total Fuel Expenditures -->
        <div class="glass-panel p-5 rounded-2xl border border-slate-800 flex items-center justify-between">
            <div>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">ประวัติการเติมน้ำมัน</p>
                <h2 class="text-2xl font-extrabold text-white mt-1"><?= count($fuels) ?> รายการ</h2>
            </div>
            <div class="h-10 w-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-400"><i class="fa-solid fa-gas-pump text-lg"></i></div>
        </div>

        <!-- Quota settings -->
        <div class="glass-panel p-5 rounded-2xl border border-slate-800 flex items-center justify-between">
            <div>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">ประวัติประวัติโควต้า</p>
                <h2 class="text-2xl font-extrabold text-white mt-1"><?= count($quotas) ?> เวอร์ชัน</h2>
            </div>
            <div class="h-10 w-10 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-400"><i class="fa-solid fa-coins text-lg"></i></div>
        </div>
    </div>

    <!-- Tab navigation -->
    <div class="flex items-center space-x-1 border-b border-slate-800 pb-px">
        <button @click="tab = 'bookings'" :class="tab === 'bookings' ? 'text-white border-indigo-500' : 'text-slate-400 border-transparent hover:text-white'"
            class="px-4 py-2.5 font-semibold text-xs border-b-2 transition duration-200 flex items-center gap-1.5 focus:outline-none">
            <i class="fa-solid fa-calendar-alt text-sm"></i> ประวัติการจองใช้งาน
        </button>
        <button @click="tab = 'fuels'" :class="tab === 'fuels' ? 'text-white border-indigo-500' : 'text-slate-400 border-transparent hover:text-white'"
            class="px-4 py-2.5 font-semibold text-xs border-b-2 transition duration-200 flex items-center gap-1.5 focus:outline-none">
            <i class="fa-solid fa-gas-pump text-sm"></i> ประวัติการเติมน้ำมัน
        </button>
        <button @click="tab = 'quotas'" :class="tab === 'quotas' ? 'text-white border-indigo-500' : 'text-slate-400 border-transparent hover:text-white'"
            class="px-4 py-2.5 font-semibold text-xs border-b-2 transition duration-200 flex items-center gap-1.5 focus:outline-none">
            <i class="fa-solid fa-coins text-sm"></i> ประวัติโควต้าน้ำมันรายเดือน
        </button>
    </div>

    <!-- Tab contents -->
    <div class="glass-panel p-6 rounded-2xl border border-slate-850">
        
        <!-- Tab 1: Bookings -->
        <div x-show="tab === 'bookings'" class="space-y-4">
            <h3 class="text-sm font-semibold text-white border-b border-slate-800 pb-3 mb-4">ตารางจองใช้งานรถย้อนหลัง</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800/60 text-left text-xs">
                    <thead class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3">ผู้จอง</th>
                            <th class="py-3">จุดประสงค์ในการเดินทาง</th>
                            <th class="py-3">เวลาเริ่มต้น</th>
                            <th class="py-3">เวลาสิ้นสุด</th>
                            <th class="py-3">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40 text-slate-300 font-light">
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-500">ไม่มีข้อมูลการจองย้อนหลัง</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $b): ?>
                                <tr class="hover:bg-slate-800/10">
                                    <td class="py-3 font-medium text-slate-200"><?= htmlspecialchars($b['employee_name']) ?></td>
                                    <td class="py-3"><?= htmlspecialchars($b['purpose']) ?></td>
                                    <td class="py-3"><?= date('d/m/Y', strtotime($b['start_time'])) ?></td>
                                    <td class="py-3"><?= date('d/m/Y', strtotime($b['end_time'])) ?></td>
                                    <td class="py-3">
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-semibold border <?= $b['status'] === 'Confirmed' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border-rose-500/20' ?>">
                                            <?= $b['status'] === 'Confirmed' ? 'ยืนยันแล้ว' : 'ยกเลิกแล้ว' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab 2: Fuels -->
        <div x-show="tab === 'fuels'" class="space-y-4">
            <h3 class="text-sm font-semibold text-white border-b border-slate-800 pb-3 mb-4">ประวัติใบเสร็จเติมน้ำมัน</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800/60 text-left text-xs">
                    <thead class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3">เลขที่ใบเสร็จ</th>
                            <th class="py-3">วันที่เติม</th>
                            <th class="py-3">ผู้บันทึก</th>
                            <th class="py-3">จำนวนลิตร</th>
                            <th class="py-3">ยอดเงิน (บาท)</th>
                            <th class="py-3">ราคา/ลิตร</th>
                            <th class="py-3">เลขไมล์รถ</th>
                            <th class="py-3">สถานะใบเสร็จ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40 text-slate-300 font-light">
                        <?php if (empty($fuels)): ?>
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-500">ไม่มีประวัติการเติมน้ำมัน</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fuels as $f): ?>
                                <tr class="hover:bg-slate-800/10">
                                    <td class="py-3 font-semibold text-indigo-400"><?= htmlspecialchars($f['receipt_number']) ?></td>
                                    <td class="py-3"><?= date('d/m/Y', strtotime($f['receipt_date'])) ?></td>
                                    <td class="py-3 font-medium text-slate-200"><?= htmlspecialchars($f['employee_name']) ?></td>
                                    <td class="py-3"><?= number_format($f['liters'], 2) ?> ลิตร</td>
                                    <td class="py-3 font-bold text-white"><?= number_format($f['amount'], 2) ?> ฿</td>
                                    <td class="py-3"><?= number_format($f['price_per_liter'], 2) ?> ฿</td>
                                    <td class="py-3"><?= $f['mileage'] ? number_format($f['mileage']) . ' km' : '-' ?></td>
                                    <td class="py-3">
                                        <?php
                                        $receiptClasses = [
                                            'Pending verification' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                            'Verified' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                            'Cancelled' => 'bg-rose-500/10 text-rose-400 border-rose-500/20'
                                        ];
                                        $receiptLabels = [
                                            'Pending verification' => 'รอการตรวจสอบ',
                                            'Verified' => 'ตรวจสอบแล้ว',
                                            'Cancelled' => 'ยกเลิกใบเสร็จ'
                                        ];
                                        $class = $receiptClasses[$f['status']] ?? 'bg-slate-505/10 text-slate-400';
                                        $label = $receiptLabels[$f['status']] ?? $f['status'];
                                        ?>
                                        <span class="px-2 py-0.5 rounded-full border text-[9px] font-semibold <?= $class ?>">
                                            <?= $label ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab 3: Quotas -->
        <div x-show="tab === 'quotas'" class="space-y-4">
            <h3 class="text-sm font-semibold text-white border-b border-slate-800 pb-3 mb-4">ประวัติการปรับเวอร์ชันโควต้าน้ำมันรายเดือน</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800/60 text-left text-xs">
                    <thead class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3">เดือนที่มีผลบังคับใช้</th>
                            <th class="py-3">ปริมาณโควต้าน้ำมัน (ลิตร)</th>
                            <th class="py-3">วันที่บันทึกปรับปรุง</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40 text-slate-300 font-light">
                        <?php if (empty($quotas)): ?>
                            <tr>
                                <td colspan="3" class="py-8 text-center text-slate-500">ไม่มีข้อมูลประวัติโควต้าของรถคันนี้</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($quotas as $q): ?>
                                <tr class="hover:bg-slate-800/10">
                                    <td class="py-3 font-semibold text-indigo-400"><?= date('M Y', strtotime($q['effective_month'])) ?></td>
                                    <td class="py-3 font-bold text-white"><?= number_format($q['monthly_quota'], 2) ?> ลิตร</td>
                                    <td class="py-3"><?= date('d/m/Y H:i', strtotime($q['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
