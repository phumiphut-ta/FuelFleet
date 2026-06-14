<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-receipt text-indigo-400"></i> ประวัติการบันทึกใบเสร็จล่าสุด 10 รายการ
            </h1>
            <p class="text-xs text-slate-400 font-light mt-1">รายการสลิปใบเสร็จรับเงินค่าน้ำมันรถราชการที่พนักงานลงบันทึกในระบบล่าสุด เพื่อความโปร่งใสและการตรวจสอบโควต้า</p>
        </div>
        <a href="/" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/80 text-xs rounded-xl font-medium flex items-center gap-1.5 transition">
            <i class="fa-solid fa-arrow-left"></i> กลับหน้าหลักปฏิทิน
        </a>
    </div>

    <!-- Receipts Grid/Table Panel -->
    <div class="glass-panel rounded-2xl border border-slate-850 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800/60 text-left text-xs">
                <thead class="bg-slate-900/40 text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4">วันและเวลาที่บันทึก</th>
                        <th class="px-6 py-4">เลขที่ใบเสร็จ</th>
                        <th class="px-6 py-4">ผู้เบิก</th>
                        <th class="px-6 py-4">ยานพาหนะหลวง</th>
                        <th class="px-6 py-4">ประเภทพลังงาน</th>
                        <th class="px-6 py-4 text-right">ปริมาณเติม (ลิตร)</th>
                        <th class="px-6 py-4 text-right">ยอดบาทรวม</th>
                        <th class="px-6 py-4 text-center">หลักฐานไฟล์แนบ</th>
                        <th class="px-6 py-4">สถานะการตรวจสอบ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40 bg-slate-900/10 text-slate-300 font-light">
                    <?php if (empty($receipts)): ?>
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-slate-500">ไม่พบประวัติการบันทึกใบเสร็จน้ำมันในระบบขณะนี้</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($receipts as $r): ?>
                            <tr class="hover:bg-slate-800/10 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-slate-400 text-[11px]">
                                    <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-indigo-400">
                                    <?= htmlspecialchars($r['receipt_number']) ?>
                                    <p class="text-[9px] text-slate-500 font-light mt-0.5">วันที่ใบเสร็จ: <?= date('d/m/Y', strtotime($r['receipt_date'])) ?></p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-slate-300">
                                    <span class="inline-flex items-center gap-1.5">
                                        <i class="fa-regular fa-user text-slate-500 text-[10px]"></i>
                                        <?= htmlspecialchars($r['employee_name'] ?? 'ไม่ระบุ') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-semibold text-slate-200">
                                    <span class="inline-flex items-center gap-1.5">
                                        <i class="fa-solid fa-car-side text-slate-500"></i>
                                        <?= htmlspecialchars($r['license_plate']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-[10px] font-semibold text-indigo-300">
                                    <?= htmlspecialchars($r['fuel_type']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-semibold text-slate-300">
                                    <?= number_format($r['liters'], 2) ?> L
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-bold text-white">
                                    <?= number_format($r['amount'], 2) ?> ฿
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php if ($r['file_path']): ?>
                                        <?php 
                                        $ext = strtolower(pathinfo($r['file_path'], PATHINFO_EXTENSION));
                                        $iconClass = $ext === 'pdf' ? 'fa-regular fa-file-pdf' : 'fa-regular fa-image';
                                        $btnClass = $ext === 'pdf' ? 'bg-rose-500/10 border-rose-500/20 text-rose-400 hover:bg-rose-500' : 'bg-indigo-500/10 border-indigo-500/20 text-indigo-400 hover:bg-indigo-500';
                                        ?>
                                        <a href="<?= htmlspecialchars($r['file_path']) ?>" target="_blank" 
                                           class="inline-flex items-center justify-center h-8 w-8 rounded-lg border <?= $btnClass ?> hover:text-white transition"
                                           title="เปิดดูหลักฐานแนบ">
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
                                        'Pending verification' => 'รอตรวจสอบอนุมัติ',
                                        'Verified' => 'อนุมัติผ่านแล้ว',
                                        'Cancelled' => 'ยกเลิกใบเสร็จ'
                                    ];
                                    $class = $statusClasses[$r['status']] ?? 'bg-slate-500/10 text-slate-400';
                                    $label = $statusLabels[$r['status']] ?? $r['status'];
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
</div>
