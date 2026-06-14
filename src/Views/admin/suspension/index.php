<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-ban text-indigo-400"></i> บันทึกสั่งระงับการใช้รถยนต์ (Suspension Logs)
            </h1>
            <p class="text-xs text-slate-400 font-light mt-1">ออกคำสั่งแบนหรือระงับการใช้รถยนต์ราชการชั่วคราว สำหรับงานซ่อมบำรุง ตรวจสภาพ หรือเคลมประกันภัย</p>
        </div>
        <a href="/admin/suspensions/new" class="px-4 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-xs rounded-xl font-semibold flex items-center gap-1.5 shadow-lg shadow-indigo-500/10 transition transform hover:-translate-y-0.5">
            <i class="fa-solid fa-plus-circle text-[13px]"></i> ออกคำสั่งระงับใช้รถ
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
    <?php if (!empty($warning)): ?>
        <div class="bg-amber-500/15 border border-amber-500/30 text-amber-300 px-4 py-3 rounded-xl text-xs flex flex-col space-y-1.5">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-triangle-exclamation text-sm text-amber-400"></i>
                <span class="font-bold">ตรวจสอบพบการจองทับซ้อนชั่วคราว!</span>
            </div>
            <p class="font-light text-slate-300 leading-relaxed pl-6"><?= htmlspecialchars($warning) ?></p>
        </div>
    <?php endif; ?>

    <!-- Table List -->
    <div class="glass-panel rounded-2xl border border-slate-850 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800/60 text-left text-xs">
                <thead class="bg-slate-900/40 text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4">ทะเบียนรถยนต์</th>
                        <th class="px-6 py-4">วันที่เริ่มระงับ</th>
                        <th class="px-6 py-4">วันที่สิ้นสุดระงับ</th>
                        <th class="px-6 py-4">สาเหตุการระงับใช้รถ</th>
                        <th class="px-6 py-4">ผู้สั่งคำสั่ง</th>
                        <th class="px-6 py-4">สถานะ</th>
                        <th class="px-6 py-4 text-right">การดำเนินงาน</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40 bg-slate-900/10 text-slate-300">
                    <?php if (empty($suspensions)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500 font-light">ไม่มีประวัติการระงับใช้งานรถยนต์ในระบบ</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($suspensions as $s): ?>
                            <tr class="hover:bg-slate-800/10 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-white font-bold"><?= htmlspecialchars($s['license_plate']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= date('d/m/Y', strtotime($s['start_date'])) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= date('d/m/Y', strtotime($s['end_date'])) ?></td>
                                <td class="px-6 py-4 max-w-xs truncate font-light text-slate-400" title="<?= htmlspecialchars($s['reason']) ?>"><?= htmlspecialchars($s['reason']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-slate-400"><?= htmlspecialchars($s['admin_name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-full border text-[9px] font-semibold <?= $s['status'] === 'Active' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : 'bg-slate-500/10 text-slate-400 border-slate-500/20' ?>">
                                        <?= $s['status'] === 'Active' ? 'กำลังปิดปรับปรุง' : 'ยกเลิกคำสั่งแล้ว' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-semibold">
                                    <?php if ($s['status'] === 'Active'): ?>
                                        <form action="/admin/suspensions/cancel/<?= $s['id'] ?>" method="POST" class="inline" onsubmit="return confirm('ยืนยันยกเลิกคำสั่งปิดปรับปรุงและนำรถคันนี้กลับมาใช้งานปกติ?');">
                                            <button type="submit" class="px-3 py-1.5 bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500 hover:text-white rounded-lg transition">
                                                <i class="fa-solid fa-circle-check"></i> เปิดใช้งานปกติ
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-slate-600 font-light text-[11px]">- ยกเลิกแล้ว -</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
