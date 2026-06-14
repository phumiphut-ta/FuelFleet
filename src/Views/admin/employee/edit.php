<div class="space-y-6" x-data="{
    selectedDivId: <?= json_encode($employee['division_id'] ?? '') ?>,
    selectedDeptId: <?= json_encode($employee['department_id'] ?? '') ?>,
    selectedPosId: <?= json_encode($employee['position_id'] ?? '') ?>
}">
    <!-- Breadcrumb Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-user-gear text-indigo-400"></i> แก้ไขข้อมูลและดูประวัติพนักงาน
            </h1>
            <p class="text-xs text-slate-400 font-light mt-1">ปรับปรุงข้อมูลประวัติพนักงาน และประเมินประวัติการปรับย้ายฝ่าย/ตำแหน่งย้อนหลัง</p>
        </div>
        <a href="/admin/employees" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/80 text-xs rounded-xl font-medium flex items-center gap-1.5 transition">
            <i class="fa-solid fa-arrow-left"></i> กลับหน้าหลักทะเบียนประวัติ
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="bg-rose-500/15 border border-rose-500/30 text-rose-300 px-4 py-3 rounded-xl text-xs flex items-center space-x-2">
            <i class="fa-solid fa-circle-exclamation text-sm text-rose-400"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Two Columns Layout: Profile Form & History Timeline -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left 2 Cols: Form -->
        <div class="lg:col-span-2 glass-card p-6 rounded-2xl glow-indigo">
            <h3 class="text-sm font-semibold text-white border-b border-slate-800 pb-3 mb-5 flex items-center gap-2">
                <i class="fa-regular fa-id-card text-indigo-400"></i> ข้อมูลประวัติพนักงานปัจจุบัน
            </h3>

            <form action="/admin/employees/update/<?= $employee['id'] ?>" method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Code -->
                    <div>
                        <label for="employee_code" class="block text-xs font-semibold text-slate-400 mb-2">รหัสพนักงาน <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                                <i class="fa-solid fa-id-card text-xs"></i>
                            </div>
                            <input id="employee_code" name="employee_code" type="text" required 
                                value="<?= htmlspecialchars($employee['employee_code']) ?>"
                                class="block w-full pl-9 pr-4 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200">
                        </div>
                    </div>

                    <!-- Full Name -->
                    <div>
                        <label for="full_name" class="block text-xs font-semibold text-slate-400 mb-2">ชื่อ - นามสกุล <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                                <i class="fa-solid fa-signature text-xs"></i>
                            </div>
                            <input id="full_name" name="full_name" type="text" required 
                                value="<?= htmlspecialchars($employee['full_name']) ?>"
                                class="block w-full pl-9 pr-4 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200">
                        </div>
                    </div>

                    <!-- Division Selection (Optional) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-2">กอง</label>
                        <select name="division_id" x-model="selectedDivId" @change="selectedDeptId = ''; selectedPosId = ''"
                            class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <option value="">-- ไม่ระบุกอง (สังกัดส่วนกลาง/ทั่วไป) --</option>
                            <?php foreach ($divisions as $div): ?>
                                <option value="<?= $div['id'] ?>">
                                    <?= htmlspecialchars($div['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Department Selection (Optional) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-2">แผนก</label>
                        <select name="department_id" x-model="selectedDeptId" @change="selectedPosId = ''"
                            class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <option value="">-- ไม่มีแผนก (สังกัดตรงกับกอง หรือไม่มีแผนก) --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>"
                                        data-div-id="<?= $dept['division_id'] ?? '' ?>"
                                        x-show="selectedDivId === '' || selectedDivId == '<?= $dept['division_id'] ?? '' ?>'">
                                    <?= htmlspecialchars($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Position Selection (Required) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-2">ตำแหน่ง <span class="text-rose-500">*</span></label>
                        <select name="position_id" x-model="selectedPosId" required 
                            @change="
                                let opt = $event.target.options[$event.target.selectedIndex];
                                if (opt && opt.value) {
                                    selectedDivId = opt.getAttribute('data-div-id') || '';
                                    selectedDeptId = opt.getAttribute('data-dept-id') || '';
                                }
                            "
                            class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <option value="" disabled>-- เลือกตำแหน่ง --</option>
                            <?php foreach ($positions as $pos): ?>
                                <option value="<?= $pos['id'] ?>"
                                        data-div-id="<?= $pos['division_id'] ?? '' ?>"
                                        data-dept-id="<?= $pos['department_id'] ?? '' ?>"
                                        x-show="(selectedDivId === '' && selectedDeptId === '') || 
                                                (selectedDeptId !== '' && selectedDeptId == '<?= $pos['department_id'] ?? '' ?>') || 
                                                (selectedDeptId === '' && selectedDivId !== '' && selectedDivId == '<?= $pos['division_id'] ?? '' ?>' && '<?= $pos['department_id'] ?? '' ?>' === '')">
                                    <?= htmlspecialchars($pos['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Status Selection -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-2">สถานะการทำงาน</label>
                        <select name="status" 
                            class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <option value="Active" <?= $employee['status'] === 'Active' ? 'selected' : '' ?>>ปฏิบัติงานปกติ (Active)</option>
                            <option value="Transferred" <?= $employee['status'] === 'Transferred' ? 'selected' : '' ?>>โยกย้ายสังกัด (Transferred)</option>
                            <option value="Retired" <?= $employee['status'] === 'Retired' ? 'selected' : '' ?>>เกษียณอายุ (Retired)</option>
                            <option value="Resigned" <?= $employee['status'] === 'Resigned' ? 'selected' : '' ?>>ลาออก/พ้นสภาพ (Resigned)</option>
                            <option value="Suspended" <?= $employee['status'] === 'Suspended' ? 'selected' : '' ?>>พักราชการ (Suspended)</option>
                        </select>
                    </div>

                </div>

                <div class="pt-6 border-t border-slate-800 flex items-center justify-end gap-3">
                    <a href="/admin/employees" class="px-5 py-2 border border-slate-850 hover:bg-slate-900 text-slate-400 text-xs font-semibold rounded-xl transition">
                        ยกเลิก
                    </a>
                    <button type="submit" class="px-5 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-xs font-semibold rounded-xl shadow-lg shadow-indigo-500/10 transition transform hover:-translate-y-0.5">
                        <i class="fa-solid fa-floppy-disk mr-1 text-xs"></i> อัปเดตและบันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>

        <!-- Right Col: History Timeline -->
        <div class="glass-card p-6 rounded-2xl glow-indigo flex flex-col">
            <h3 class="text-sm font-semibold text-white border-b border-slate-800 pb-3 mb-5 flex items-center gap-2">
                <i class="fa-solid fa-history text-indigo-400"></i> ประวัติการโยกย้ายสังกัด/ตำแหน่ง
            </h3>

            <!-- Timeline -->
            <div class="flex-grow space-y-5 overflow-y-auto max-h-[400px] pr-2">
                <?php if (empty($assignments)): ?>
                    <p class="text-xs text-slate-500 text-center py-8 font-light">ไม่มีประวัติการโยกย้ายสังกัดย้อนหลัง</p>
                <?php else: ?>
                    <div class="relative border-l border-slate-800 ml-3 pl-5 space-y-6">
                        <?php foreach ($assignments as $index => $assign): ?>
                            <div class="relative">
                                <!-- Dot indicator -->
                                <span class="absolute -left-[26px] top-1.5 flex items-center justify-center h-3 w-3 rounded-full border border-indigo-500 bg-slate-950">
                                    <span class="h-1.5 w-1.5 rounded-full <?= $index === 0 ? 'bg-indigo-400 animate-ping' : 'bg-slate-700' ?>"></span>
                                </span>

                                <div class="space-y-1">
                                    <!-- Date period -->
                                    <p class="text-[10px] text-slate-500 font-semibold uppercase tracking-wider">
                                        <?= date('d M Y', strtotime($assign['start_date'])) ?> 
                                        - 
                                        <?= $assign['end_date'] ? date('d M Y', strtotime($assign['end_date'])) : '<span class="text-emerald-400">ปัจจุบัน</span>' ?>
                                    </p>
                                    
                                    <!-- Position -->
                                    <p class="text-xs font-bold text-slate-200"><?= htmlspecialchars($assign['position_name'] ?? 'ไม่ระบุ') ?></p>
                                    
                                    <!-- Division / Dept -->
                                    <p class="text-[11px] text-slate-400 font-light">
                                        <?= htmlspecialchars($assign['division_name'] ?? 'ไม่ระบุ') ?> &bull; <?= htmlspecialchars($assign['department_name'] ?? 'ไม่ระบุ') ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
