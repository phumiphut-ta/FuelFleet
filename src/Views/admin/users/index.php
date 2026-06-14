<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-user-shield text-indigo-400"></i> จัดการรายชื่อผู้ดูแลระบบ (Admin Users Management)
            </h1>
            <p class="text-xs text-slate-400 font-light mt-1">เพิ่ม ลบ หรือแก้ไขข้อมูลบัญชีผู้ดูแลระบบภายในองค์กร (ยกเว้นผู้ดูแลระบบหลักลำดับแรกสุด)</p>
        </div>
        <a href="/admin/users/new" class="px-4 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-xs rounded-xl font-semibold flex items-center gap-1.5 shadow-lg shadow-indigo-500/10 transition transform hover:-translate-y-0.5">
            <i class="fa-solid fa-user-plus text-[13px]"></i> เพิ่มผู้ดูแลระบบใหม่
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

    <!-- Table List -->
    <div class="glass-panel rounded-2xl border border-slate-850 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800/60 text-left text-xs">
                <thead class="bg-slate-900/40 text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4">ID</th>
                        <th class="px-6 py-4">ชื่อผู้ใช้ (Username)</th>
                        <th class="px-6 py-4">ชื่อ-นามสกุล</th>
                        <th class="px-6 py-4">บทบาท (Role)</th>
                        <th class="px-6 py-4">วันที่เพิ่มเข้าสู่ระบบ</th>
                        <th class="px-6 py-4 text-right">การดำเนินงาน</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40 bg-slate-900/10 text-slate-300">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500 font-light">ไม่พบบัญชีผู้ดูแลระบบในระบบ</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr class="hover:bg-slate-800/10 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-slate-500 font-mono">#<?= $u['id'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-white font-semibold">
                                    <div class="flex items-center space-x-2">
                                        <i class="fa-solid fa-circle-user text-slate-500"></i>
                                        <span><?= htmlspecialchars($u['username']) ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-slate-300"><?= htmlspecialchars($u['full_name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($u['id'] === 1): ?>
                                        <span class="px-2.5 py-0.5 rounded-full border text-[9px] font-bold bg-indigo-500/10 text-indigo-400 border-indigo-500/20">
                                            <i class="fa-solid fa-crown text-[8px] mr-1"></i>ผู้ดูแลระบบหลัก
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-0.5 rounded-full border text-[9px] font-semibold bg-slate-500/10 text-slate-400 border-slate-500/20">
                                            ผู้ช่วยดูแลระบบ
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-slate-400"><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?> น.</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-semibold">
                                    <?php if ($u['id'] === 1): ?>
                                        <span class="text-slate-600 font-light text-[11px] px-3"><i class="fa-solid fa-lock text-[10px] mr-1"></i>ระบบคุ้มครองความปลอดภัย</span>
                                    <?php else: ?>
                                        <a href="/admin/users/edit/<?= $u['id'] ?>" class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-500/10 text-indigo-400 hover:bg-indigo-500 hover:text-white rounded-lg transition mr-1.5">
                                            <i class="fa-solid fa-pen-to-square text-[11px]"></i> แก้ไข
                                        </a>
                                        <form action="/admin/users/delete/<?= $u['id'] ?>" method="POST" class="inline" onsubmit="return confirm('คุณต้องการลบผู้ดูแลระบบรายนี้ใช่หรือไม่? การกระทำนี้ไม่สามารถกู้คืนได้!');">
                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 bg-rose-500/10 text-rose-400 hover:bg-rose-500 hover:text-white rounded-lg transition">
                                                <i class="fa-solid fa-trash text-[11px]"></i> ลบ
                                            </button>
                                        </form>
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
