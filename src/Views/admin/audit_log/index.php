<div class="space-y-6" x-data="{ search: '' }">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-history text-indigo-400"></i> ประวัติกิจกรรมผู้ใช้งานระบบ (Audit Logs)
            </h1>
            <p class="text-xs text-slate-400 font-light mt-1">เฝ้าระวังความปลอดภัยของข้อมูล บันทึกกิจกรรมการสร้าง อัปเดต หรือยกเลิกใบเสร็จน้ำมันและการจองใช้งานของแอดมิน</p>
        </div>
    </div>

    <!-- Filter bar -->
    <div class="glass-panel p-4 rounded-xl flex flex-col md:flex-row items-center justify-between gap-4 border border-slate-800/80">
        <div class="relative w-full md:w-96">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </div>
            <input x-model="search" type="text" placeholder="ค้นหาด้วยกิจกรรม ผู้ปฏิบัติงาน หรือชื่อตาราง..."
                class="block w-full pl-9 pr-4 py-2 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
        </div>
        <div class="flex items-center space-x-4 text-xs text-slate-400 font-light">
            <span>ทั้งหมด: <strong class="text-white font-semibold"><?= count($logs) ?></strong> กิจกรรม</span>
        </div>
    </div>

    <!-- Table List -->
    <div class="glass-panel rounded-2xl border border-slate-850 overflow-hidden">
        <div class="overflow-x-auto max-h-[600px] overflow-y-auto pr-2">
            <table class="min-w-full divide-y divide-slate-800/60 text-left text-xs">
                <thead class="bg-slate-900/40 text-[10px] text-slate-500 font-bold uppercase tracking-wider sticky top-0 z-10">
                    <tr>
                        <th class="px-6 py-4">วันและเวลา</th>
                        <th class="px-6 py-4">ผู้ปฏิบัติการ (User)</th>
                        <th class="px-6 py-4">กิจกรรม (Action)</th>
                        <th class="px-6 py-4">โมดูล/ตาราง</th>
                        <th class="px-6 py-4">ID อ้างอิง</th>
                        <th class="px-6 py-4">ข้อมูลเดิม (Previous Value)</th>
                        <th class="px-6 py-4">ข้อมูลใหม่ (New Value)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40 bg-slate-900/10 text-slate-300 font-light">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500 font-light">ไม่มีประวัติกิจกรรมบันทึกในระบบ</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $l): ?>
                            <tr x-show="search === '' || 
                                    '<?= strtolower(addslashes($l['username'] ?? 'public')) ?>'.includes(search.toLowerCase()) || 
                                    '<?= strtolower(addslashes($l['action'])) ?>'.includes(search.toLowerCase()) || 
                                    '<?= strtolower(addslashes($l['table_name'] ?? '')) ?>'.includes(search.toLowerCase())"
                                class="hover:bg-slate-800/10 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-slate-550 font-semibold"><?= date('d/m/Y H:i:s', strtotime($l['timestamp'])) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-2">
                                        <div class="h-6 w-6 rounded-full bg-slate-800 flex items-center justify-center text-[10px] font-bold text-indigo-400">
                                            <?= strtoupper(substr($l['username'] ?? 'P', 0, 1)) ?>
                                        </div>
                                        <div>
                                            <span class="font-medium text-slate-200"><?= htmlspecialchars($l['admin_name'] ?? 'บุคคลทั่วไป / ระบบ') ?></span>
                                            <p class="text-[9px] text-slate-550 font-light">@<?= htmlspecialchars($l['username'] ?? 'public') ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $actionClasses = [
                                        'Create' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                        'Update' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                        'Deactivate' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                        'Cancel booking' => 'bg-rose-500/10 text-rose-450 border-rose-500/20',
                                        'Login' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
                                        'Logout' => 'bg-slate-500/10 text-slate-400 border-slate-500/20'
                                    ];
                                    $class = $actionClasses[$l['action']] ?? 'bg-slate-500/10 text-slate-400 border-slate-500/20';
                                    ?>
                                    <span class="px-2.5 py-0.5 rounded-full border text-[9px] font-semibold <?= $class ?>">
                                        <?= htmlspecialchars($l['action']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-slate-400"><?= htmlspecialchars($l['table_name'] ?? '-') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-indigo-400 font-semibold">#<?= $l['record_id'] ?? '-' ?></td>
                                <td class="px-6 py-4 max-w-xs truncate text-[11px] text-slate-500 font-light" title="<?= htmlspecialchars($l['previous_value'] ?? '') ?>">
                                    <?= htmlspecialchars($l['previous_value'] ?? '-') ?>
                                </td>
                                <td class="px-6 py-4 max-w-xs truncate text-[11px] text-slate-400 font-light" title="<?= htmlspecialchars($l['new_value'] ?? '') ?>">
                                    <?= htmlspecialchars($l['new_value'] ?? '-') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
