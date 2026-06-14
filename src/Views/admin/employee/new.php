<div class="space-y-6" x-data="{
    selectedDivId: '',
    selectedDeptId: '',
    selectedPosId: ''
}">
    <!-- Breadcrumb -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-indigo-400"></i> เพิ่มพนักงานใหม่ในระบบ
            </h1>
            <p class="text-xs text-slate-400 font-light mt-1">บันทึกประวัติบุคลากรสังกัดใหม่ พร้อมเริ่มใช้งานระบบควบคุมการเดินทางและน้ำมันเชื้อเพลิง</p>
        </div>
        <a href="/admin/employees" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/80 text-xs rounded-xl font-medium flex items-center gap-1.5 transition">
            <i class="fa-solid fa-arrow-left"></i> กลับหน้าหลักทะเบียนประวัติ
        </a>
    </div>

    <!-- Form container -->
    <div class="glass-panel p-8 rounded-2xl glow-indigo border border-slate-800/80 max-w-3xl mx-auto relative overflow-hidden">
        <!-- Background decorative glows -->
        <div class="absolute -top-10 -right-10 w-24 h-24 bg-indigo-500/5 rounded-full blur-2xl pointer-events-none"></div>

        <form action="/admin/employees/create" method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- Employee Code -->
                <div>
                    <label for="employee_code" class="block text-xs font-semibold text-slate-400 mb-2">รหัสพนักงาน <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                            <i class="fa-solid fa-id-card text-xs"></i>
                        </div>
                        <input id="employee_code" name="employee_code" type="text" required 
                            class="block w-full pl-9 pr-4 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200" 
                            placeholder="ตัวอย่าง: EMP001">
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
                            class="block w-full pl-9 pr-4 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200" 
                            placeholder="ป้อนชื่อและนามสกุลพนักงาน">
                    </div>
                </div>

                <!-- Division Selection (Optional) -->
                <div>
                    <label for="division_id" class="block text-xs font-semibold text-slate-400 mb-2">กอง</label>
                    <select id="division_id" name="division_id" x-model="selectedDivId" @change="selectedDeptId = ''; selectedPosId = ''"
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <option value="">-- ไม่ระบุกอง (สังกัดส่วนกลาง/ทั่วไป) --</option>
                        <?php foreach ($divisions as $div): ?>
                            <option value="<?= $div['id'] ?>"><?= htmlspecialchars($div['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Department Selection (Optional) -->
                <div>
                    <label for="department_id" class="block text-xs font-semibold text-slate-400 mb-2">แผนก</label>
                    <select id="department_id" name="department_id" x-model="selectedDeptId" @change="selectedPosId = ''"
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
                    <label for="position_id" class="block text-xs font-semibold text-slate-400 mb-2">ตำแหน่งปฏิบัติการ <span class="text-rose-500">*</span></label>
                    <select id="position_id" name="position_id" x-model="selectedPosId" required 
                        @change="
                            let opt = $event.target.options[$event.target.selectedIndex];
                            if (opt && opt.value) {
                                selectedDivId = opt.getAttribute('data-div-id') || '';
                                selectedDeptId = opt.getAttribute('data-dept-id') || '';
                            }
                        "
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <option value="" disabled selected>-- เลือกตำแหน่ง --</option>
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
                    <label for="status" class="block text-xs font-semibold text-slate-400 mb-2">สถานะการทำงาน</label>
                    <select id="status" name="status" 
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <option value="Active">ปฏิบัติงานปกติ (Active)</option>
                        <option value="Transferred">โยกย้ายสังกัด (Transferred)</option>
                        <option value="Retired">เกษียณอายุ (Retired)</option>
                        <option value="Resigned">ลาออก/พ้นสภาพ (Resigned)</option>
                        <option value="Suspended">พักราชการ (Suspended)</option>
                    </select>
                </div>

            </div>

            <!-- Action buttons -->
            <div class="pt-6 border-t border-slate-800/80 flex items-center justify-end gap-3">
                <a href="/admin/employees" class="px-6 py-2.5 border border-slate-850 hover:bg-slate-900 text-slate-400 hover:text-slate-200 text-xs font-semibold rounded-xl transition">
                    ยกเลิก
                </a>
                <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-xs font-semibold rounded-xl shadow-lg shadow-indigo-500/10 transition duration-300 transform hover:-translate-y-0.5">
                    <i class="fa-solid fa-save mr-1.5 text-xs"></i> บันทึกข้อมูลพนักงาน
                </button>
            </div>

        </form>
    </div>
</div>
