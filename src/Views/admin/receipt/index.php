<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-file-invoice-dollar text-indigo-400"></i> ตรวจสอบใบเสร็จค่าน้ำมัน (Fuel Receipts Console)
            </h1>
            <p class="text-xs text-slate-400 font-light mt-1">บริหารคลังประวัติค่าน้ำมันรถยนต์หลวง ตรวจสอบความถูกต้องของประเภทน้ำมัน ยอดลิตร และใบเสร็จรูปแนบ</p>
        </div>
        <a href="/admin/receipts/new" class="px-4 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-xs rounded-xl font-semibold flex items-center gap-1.5 shadow-lg shadow-indigo-500/10 transition transform hover:-translate-y-0.5">
            <i class="fa-solid fa-plus-circle text-[13px]"></i> ลงบันทึกใบเสร็จใหม่
        </a>
    </div>

    <!-- Feedback alerts -->
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

    <!-- Search Form -->
    <form action="/admin/receipts" method="GET" class="space-y-4 glass-panel p-4 rounded-xl border border-slate-800/80">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <!-- Search Text -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </div>
                <input name="search" type="text" placeholder="ค้นหาด้วยเลขที่ใบเสร็จ..."
                    value="<?= htmlspecialchars($search ?? '') ?>"
                    class="block w-full pl-9 pr-4 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
            </div>

            <!-- Vehicle Filter -->
            <div>
                <select name="car_id" class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <option value="">-- กรองด้วยทะเบียนรถ --</option>
                    <?php foreach ($cars as $car): ?>
                        <option value="<?= $car['id'] ?>" <?= isset($carId) && $carId == $car['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($car['license_plate']) ?> (<?= htmlspecialchars($car['fuel_type']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Employee Filter -->
            <div>
                <select name="employee_id" class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <option value="">-- กรองด้วยพนักงาน --</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>" <?= isset($employeeId) && $employeeId == $emp['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($emp['full_name']) ?> (<?= htmlspecialchars($emp['employee_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Start Date Filter -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500 gap-1.5">
                    <i class="fa-solid fa-calendar-day text-[11px]"></i>
                    <span class="text-[10px] font-semibold text-slate-400">เริ่ม:</span>
                </div>
                <input name="start_date" type="date"
                    value="<?= htmlspecialchars($startDate ?? '') ?>"
                    class="block w-full pl-[58px] pr-3 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
            </div>

            <!-- End Date Filter -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500 gap-1.5">
                    <i class="fa-solid fa-calendar-day text-[11px]"></i>
                    <span class="text-[10px] font-semibold text-slate-400">ถึง:</span>
                </div>
                <input name="end_date" type="date"
                    value="<?= htmlspecialchars($endDate ?? '') ?>"
                    class="block w-full pl-[52px] pr-3 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-3 border-t border-slate-800/40">
            <div class="text-xs text-slate-400 font-light">
                พบทั้งหมด: <strong class="text-white font-semibold"><?= $totalCount ?? 0 ?></strong> รายการ
            </div>
            <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                <?php if (!empty($search) || !empty($carId) || !empty($employeeId) || !empty($startDate) || !empty($endDate)): ?>
                    <a href="/admin/receipts" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-350 text-xs rounded-xl font-medium border border-slate-750 transition">
                        ล้างฟิลเตอร์ทั้งหมด
                    </a>
                <?php endif; ?>
                <a href="/admin/receipts/export?search=<?= urlencode($search) ?>&car_id=<?= urlencode($carId ?? '') ?>&employee_id=<?= urlencode($employeeId ?? '') ?>&start_date=<?= urlencode($startDate ?? '') ?>&end_date=<?= urlencode($endDate ?? '') ?>" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs rounded-xl font-semibold flex items-center gap-1.5 shadow-lg shadow-emerald-500/10 transition">
                    <i class="fa-solid fa-file-excel text-[13px]"></i> ส่งออก Excel
                </a>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs rounded-xl font-semibold shadow-lg shadow-indigo-500/10 transition">
                    กรองข้อมูล
                </button>
            </div>
        </div>
    </form>

    <!-- Table list of receipts -->
    <div class="glass-panel rounded-2xl border border-slate-850 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800/60 text-left text-xs">
                <thead class="bg-slate-900/40 text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4">เลขที่ใบเสร็จ</th>
                        <th class="px-6 py-4">ยานพาหนะ</th>
                        <th class="px-6 py-4">พนักงาน</th>
                        <th class="px-6 py-4 text-center">ประเภทน้ำมัน</th>
                        <th class="px-6 py-4 text-right">จำนวนลิตร</th>
                        <th class="px-6 py-4 text-right">ยอดบาท</th>
                        <th class="px-6 py-4 text-right">ราคา/ลิตร</th>
                        <th class="px-6 py-4 text-center">ภาพแนบ</th>
                        <th class="px-6 py-4">สถานะ</th>
                        <th class="px-6 py-4 text-right">การจัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40 bg-slate-900/10 text-slate-300 font-light">
                    <?php if (empty($receipts)): ?>
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-slate-500">ไม่พบข้อมูลประวัติใบเสร็จในฐานข้อมูล</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($receipts as $r): ?>
                            <tr class="hover:bg-slate-800/10 transition">
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-indigo-400">
                                    <?= htmlspecialchars($r['receipt_number']) ?>
                                    <p class="text-[9px] text-slate-500 font-light mt-0.5"><?= date('d/m/Y', strtotime($r['receipt_date'])) ?></p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-semibold text-slate-200"><?= htmlspecialchars($r['license_plate']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-slate-400 font-medium"><?= htmlspecialchars($r['employee_name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-[10px] font-semibold text-indigo-400"><?= htmlspecialchars($r['fuel_type']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-semibold text-slate-300"><?= number_format($r['liters'], 2) ?> L</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-bold text-white"><?= number_format($r['amount'], 2) ?> ฿</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-slate-500"><?= number_format($r['price_per_liter'], 2) ?> ฿</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php if ($r['file_path']): ?>
                                        <?php 
                                        $ext = strtolower(pathinfo($r['file_path'], PATHINFO_EXTENSION));
                                        $iconClass = $ext === 'pdf' ? 'fa-regular fa-file-pdf' : 'fa-regular fa-image';
                                        $btnClass = $ext === 'pdf' ? 'bg-rose-500/10 border-rose-500/20 text-rose-400 hover:bg-rose-500' : 'bg-indigo-500/10 border-indigo-500/20 text-indigo-400 hover:bg-indigo-500';
                                        ?>
                                        <a href="<?= htmlspecialchars($r['file_path']) ?>" target="_blank" class="inline-flex items-center justify-center h-8 w-8 rounded-lg border <?= $btnClass ?> hover:text-white transition">
                                            <i class="<?= $iconClass ?>"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-[10px] text-slate-650 italic">ไม่มีไฟล์</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusClasses = [
                                        'Pending verification' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                        'Verified' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                        'Cancelled' => 'bg-rose-500/10 text-rose-400 border-rose-500/20'
                                    ];
                                    $statusLabels = [
                                        'Pending verification' => 'รอตรวจอนุมัติ',
                                        'Verified' => 'อนุมัติแล้ว',
                                        'Cancelled' => 'ยกเลิกใบเสร็จ'
                                    ];
                                    $class = $statusClasses[$r['status']] ?? 'bg-slate-500/10 text-slate-400';
                                    $label = $statusLabels[$r['status']] ?? $r['status'];
                                    ?>
                                    <span class="px-2 py-0.5 rounded-full border text-[9px] font-semibold <?= $class ?>">
                                        <?= $label ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-semibold space-x-1">
                                    <a href="/admin/receipts/edit/<?= $r['id'] ?>" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-750 hover:text-white rounded-lg transition duration-200 inline-block align-middle">
                                        <i class="fa-solid fa-pen-to-square text-[10px] mr-1"></i> แก้ไข
                                    </a>
                                    <?php if ($r['status'] === 'Pending verification'): ?>
                                        <form action="/admin/receipts/verify/<?= $r['id'] ?>" method="POST" class="inline">
                                            <button type="submit" class="px-2.5 py-1 bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500 hover:text-white rounded-lg transition duration-200 align-middle">
                                                อนุมัติ
                                            </button>
                                        </form>
                                        <form action="/admin/receipts/cancel/<?= $r['id'] ?>" method="POST" class="inline" onsubmit="return confirm('ยืนยันปฏิเสธและยกเลิกใบเสร็จน้ำมันนี้?');">
                                            <button type="submit" class="px-2.5 py-1 bg-rose-500/10 text-rose-400 hover:bg-rose-500 hover:text-white rounded-lg transition duration-200 align-middle">
                                                ยกเลิก
                                            </button>
                                        </form>
                                    <?php elseif ($r['status'] === 'Verified'): ?>
                                        <form action="/admin/receipts/cancel/<?= $r['id'] ?>" method="POST" class="inline" onsubmit="return confirm('ยืนยันยกเลิกใบเสร็จน้ำมันนี้?');">
                                            <button type="submit" class="px-2.5 py-1 bg-rose-500/10 text-rose-400 hover:bg-rose-500 hover:text-white rounded-lg transition duration-200 align-middle">
                                                ยกเลิก
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <!-- Summary Row -->
                        <tr class="bg-slate-950/60 font-semibold border-t border-slate-800 text-slate-200">
                            <td colspan="4" class="px-6 py-4 text-right font-bold text-slate-400">ยอดรวมค่าน้ำมันสะสม (ไม่รวมรายการที่ยกเลิก):</td>
                            <td class="px-6 py-4 text-right font-bold text-indigo-400 whitespace-nowrap"><?= number_format($totalLiters, 2) ?> L</td>
                            <td class="px-6 py-4 text-right font-bold text-emerald-400 whitespace-nowrap"><?= number_format($totalAmount, 2) ?> ฿</td>
                            <td class="px-6 py-4 text-right text-slate-550 text-[10px]">
                                <?php if ($totalLiters > 0): ?>
                                    เฉลี่ย <?= number_format($totalAmount / $totalLiters, 2) ?> ฿/L
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td colspan="3" class="px-6 py-4 text-slate-500 font-light text-[10px] italic text-right">คำนวณสะสมอัตโนมัติ</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Section -->
        <?php if ($totalPages > 1): ?>
            <div class="flex flex-col sm:flex-row items-center justify-between border-t border-slate-800/80 p-4 bg-slate-900/10 text-xs gap-3">
                <div class="text-slate-400 font-light">
                    แสดงหน้า <strong class="text-white font-semibold"><?= $page ?></strong> จากทั้งหมด <strong class="text-white font-semibold"><?= $totalPages ?></strong> หน้า (พบข้อมูลทั้งหมด <?= $totalCount ?> รายการ)
                </div>
                <div class="flex items-center gap-1">
                    <!-- Previous Page -->
                    <?php if ($page > 1): ?>
                        <a href="?search=<?= urlencode($search) ?>&car_id=<?= urlencode($carId ?? '') ?>&employee_id=<?= urlencode($employeeId ?? '') ?>&start_date=<?= urlencode($startDate ?? '') ?>&end_date=<?= urlencode($endDate ?? '') ?>&page=<?= $page - 1 ?>" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/80 rounded-lg transition font-medium flex items-center gap-1">
                            <i class="fa-solid fa-angle-left text-[10px]"></i> ก่อนหน้า
                        </a>
                    <?php else: ?>
                        <span class="px-3 py-1.5 bg-slate-900/60 text-slate-600 border border-slate-850 rounded-lg cursor-not-allowed font-medium select-none flex items-center gap-1">
                            <i class="fa-solid fa-angle-left text-[10px]"></i> ก่อนหน้า
                        </span>
                    <?php endif; ?>

                    <!-- Page numbers -->
                    <?php
                    $range = 2; // Show 2 pages before and after the current page
                    for ($i = 1; $i <= $totalPages; $i++):
                        if ($i == 1 || $i == $totalPages || ($i >= $page - $range && $i <= $page + $range)):
                            if ($i == $page):
                    ?>
                                <span class="px-3 py-1.5 bg-indigo-500/10 text-indigo-400 border border-indigo-500/30 rounded-lg font-bold">
                                    <?= $i ?>
                                </span>
                            <?php else: ?>
                                <a href="?search=<?= urlencode($search) ?>&car_id=<?= urlencode($carId ?? '') ?>&employee_id=<?= urlencode($employeeId ?? '') ?>&start_date=<?= urlencode($startDate ?? '') ?>&end_date=<?= urlencode($endDate ?? '') ?>&page=<?= $i ?>" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-350 border border-slate-750 hover:text-white rounded-lg transition">
                                    <?= $i ?>
                                </a>
                            <?php endif; ?>
                        <?php elseif ($i == 2 || $i == $totalPages - 1): ?>
                            <span class="px-1.5 text-slate-600 font-light">...</span>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <!-- Next Page -->
                    <?php if ($page < $totalPages): ?>
                        <a href="?search=<?= urlencode($search) ?>&car_id=<?= urlencode($carId ?? '') ?>&employee_id=<?= urlencode($employeeId ?? '') ?>&start_date=<?= urlencode($startDate ?? '') ?>&end_date=<?= urlencode($endDate ?? '') ?>&page=<?= $page + 1 ?>" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/80 rounded-lg transition font-medium flex items-center gap-1">
                            ถัดไป <i class="fa-solid fa-angle-right text-[10px]"></i>
                        </a>
                    <?php else: ?>
                        <span class="px-3 py-1.5 bg-slate-900/60 text-slate-600 border border-slate-850 rounded-lg cursor-not-allowed font-medium select-none flex items-center gap-1">
                            ถัดไป <i class="fa-solid fa-angle-right text-[10px]"></i>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
