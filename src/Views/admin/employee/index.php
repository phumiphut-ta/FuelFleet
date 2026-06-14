<div class="space-y-6" x-data="{ search: '' }">
    <!-- Header bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-users text-indigo-400"></i> ทะเบียนประวัติพนักงาน (Employee Registry)
            </h1>
            <p class="text-xs text-slate-400 font-light mt-1">จัดการรายชื่อ ข้อมูลการสังกัด และประวัติการโยกย้ายตำแหน่งของบุคลากรภายในองค์กร</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/admin/master" class="px-4 py-2 bg-slate-900 border border-slate-800 hover:bg-slate-850 hover:border-slate-700 text-slate-300 text-xs rounded-xl font-semibold flex items-center gap-1.5 transition">
                <i class="fa-solid fa-folder-tree"></i> ข้อมูลหลักองค์กร
            </a>
            <a href="/admin/employees/new" class="px-4 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-xs rounded-xl font-semibold flex items-center gap-1.5 shadow-lg shadow-indigo-500/10 transition transform hover:-translate-y-0.5">
                <i class="fa-solid fa-plus-circle text-[13px]"></i> เพิ่มพนักงานใหม่
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
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

    <!-- Search Input & Stats summary -->
    <div class="glass-panel p-4 rounded-xl flex flex-col md:flex-row items-center justify-between gap-4 border border-slate-800/80">
        <div class="relative w-full md:w-96">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </div>
            <input x-model="search" type="text" placeholder="ค้นหาด้วยชื่อ รหัส หรือตำแหน่งงาน..."
                class="block w-full pl-9 pr-4 py-2 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
        </div>
        <div class="flex items-center space-x-4 text-xs text-slate-400 font-light shrink-0">
            <span>ทั้งหมด: <strong class="text-white font-semibold"><?= count($employees) ?></strong> คน</span>
            <span class="h-4 w-px bg-slate-800"></span>
            <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> ปฏิบัติงานอยู่</span>
        </div>
    </div>

    <!-- Employee Table / Cards Grid -->
    <div class="glass-panel rounded-2xl border border-slate-850 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800/60 text-left text-xs">
                <thead class="bg-slate-900/40 text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4">รหัสพนักงาน</th>
                        <th class="px-6 py-4">ชื่อ - นามสกุล</th>
                        <th class="px-6 py-4">กอง</th>
                        <th class="px-6 py-4">แผนก</th>
                        <th class="px-6 py-4">ตำแหน่ง</th>
                        <th class="px-6 py-4">สถานะ</th>
                        <th class="px-6 py-4 text-right">การจัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40 bg-slate-900/10">
                    <?php if (empty($employees)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500 font-light">ไม่พบบุคลากรในทะเบียนประวัติ</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($employees as $emp): ?>
                            <tr x-show="search === '' || 
                                    '<?= strtolower(addslashes($emp['employee_code'])) ?>'.includes(search.toLowerCase()) || 
                                    '<?= strtolower(addslashes($emp['full_name'])) ?>'.includes(search.toLowerCase()) || 
                                    '<?= strtolower(addslashes($emp['position_name'])) ?>'.includes(search.toLowerCase())"
                                class="hover:bg-slate-800/20 transition duration-150">
                                
                                <td class="px-6 py-4 whitespace-nowrap text-indigo-400 font-semibold"><?= htmlspecialchars($emp['employee_code']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-slate-200"><?= htmlspecialchars($emp['full_name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-slate-400"><?= htmlspecialchars($emp['division_name'] ?? 'ไม่สังกัด') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-slate-400"><?= htmlspecialchars($emp['department_name'] ?? 'ไม่สังกัด') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-slate-300 font-medium"><?= htmlspecialchars($emp['position_name'] ?? 'ไม่ระบุ') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                    $statusClasses = [
                                        'Active' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                        'Transferred' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                        'Retired' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
                                        'Resigned' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                        'Suspended' => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
                                    ];
                                    $statusLabels = [
                                        'Active' => 'ปฏิบัติงานปกติ',
                                        'Transferred' => 'โยกย้ายสังกัด',
                                        'Retired' => 'เกษียณอายุ',
                                        'Resigned' => 'ลาออก/พ้นสภาพ',
                                        'Suspended' => 'พักราชการ',
                                    ];
                                    $class = $statusClasses[$emp['status']] ?? 'bg-slate-500/10 text-slate-400 border-slate-500/20';
                                    $label = $statusLabels[$emp['status']] ?? $emp['status'];
                                    ?>
                                    <span class="px-2 py-0.5 rounded-full border text-[10px] font-semibold <?= $class ?>">
                                        <?= $label ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-semibold">
                                    <a href="/admin/employees/edit/<?= $emp['id'] ?>" class="px-3 py-1.5 bg-indigo-500/10 text-indigo-400 hover:bg-indigo-500 hover:text-white rounded-lg transition">
                                        <i class="fa-solid fa-user-pen"></i> แก้ไข / ดูประวัติ
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
