<div class="space-y-6" x-data="{
    showEditDivision: false,
    editDivisionId: null,
    editDivisionName: '',

    showDeleteDivision: false,
    deleteDivisionId: null,
    deleteDivisionName: '',

    showEditDepartment: false,
    editDepartmentId: null,
    editDepartmentName: '',
    editDepartmentDivisionId: '',

    showDeleteDepartment: false,
    deleteDepartmentId: null,
    deleteDepartmentName: '',

    showEditPosition: false,
    editPositionId: null,
    editPositionName: '',
    editPositionDivisionId: '',
    editPositionDepartmentId: '',

    showDeletePosition: false,
    deletePositionId: null,
    deletePositionName: '',

    selectedDivId: ''
}">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-folder-tree text-indigo-400"></i> จัดการข้อมูลหลักองค์กร (Master Data)
            </h1>
            <p class="text-xs text-slate-400 font-light mt-1">บริหารโครงสร้างองค์กร: กอง → แผนก → ตำแหน่งงาน</p>
        </div>
        <a href="/admin/employees" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/80 text-xs rounded-xl font-medium flex items-center gap-1.5 transition">
            <i class="fa-solid fa-arrow-left"></i> กลับหน้าทะเบียนพนักงาน
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

    <!-- Hierarchy Overview Banner -->
    <div class="flex items-center gap-3 text-xs text-slate-400 bg-slate-900/40 border border-slate-800 rounded-xl px-4 py-3">
        <i class="fa-solid fa-sitemap text-indigo-400"></i>
        <span><span class="text-indigo-300 font-semibold">กอง</span> มีได้หลาย <span class="text-purple-300 font-semibold">แผนก</span> &rarr; แต่ละแผนกมีหลาย <span class="text-amber-300 font-semibold">ตำแหน่งงาน</span> &rarr; พนักงานบางคนอาจไม่มีกองหรือแผนก</span>
    </div>

    <!-- Grid Columns for Division, Department, Position -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- 1. Division (กอง) -->
        <div class="glass-card p-6 rounded-2xl glow-indigo">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4 mb-4">
                <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                    <i class="fa-solid fa-building text-indigo-400"></i> กอง
                </h3>
                <span class="text-[10px] text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded-full font-medium"><?= count($divisions) ?> รายการ</span>
            </div>

            <!-- List -->
            <div class="space-y-2 max-h-[300px] overflow-y-auto mb-6 pr-2">
                <?php if (empty($divisions)): ?>
                    <p class="text-xs text-slate-500 font-light text-center py-4">ไม่มีข้อมูลกอง</p>
                <?php else: ?>
                    <?php foreach ($divisions as $div): ?>
                        <div class="flex items-center justify-between bg-slate-900/40 border border-slate-850 p-2 py-1.5 rounded-xl text-xs hover:border-slate-700 transition">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-building text-indigo-400/60 text-[10px]"></i>
                                <span class="text-slate-200 font-semibold"><?= htmlspecialchars($div['name']) ?></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="text-[9px] text-slate-600 font-light mr-1">#<?= $div['id'] ?></span>
                                <button type="button" @click="editDivisionId = <?= $div['id'] ?>; editDivisionName = <?= htmlspecialchars(json_encode($div['name'])) ?>; showEditDivision = true" 
                                        class="text-slate-500 hover:text-indigo-400 hover:bg-indigo-500/10 p-1 rounded-lg transition" 
                                        title="แก้ไข">
                                    <i class="fa-solid fa-pen text-[9px]"></i>
                                </button>
                                <button type="button" @click="deleteDivisionId = <?= $div['id'] ?>; deleteDivisionName = <?= htmlspecialchars(json_encode($div['name'])) ?>; showDeleteDivision = true" 
                                        class="text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 p-1 rounded-lg transition" 
                                        title="ลบ">
                                    <i class="fa-solid fa-trash-can text-[9px]"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Form -->
            <form action="/admin/master/division/create" method="POST" class="pt-4 border-t border-slate-800/80 space-y-2">
                <label class="block text-[11px] font-semibold text-slate-400">เพิ่มกองใหม่</label>
                <div class="flex gap-2">
                    <input type="text" name="name" required placeholder="ป้อนชื่อกอง"
                        class="block w-full px-3 py-2 border border-slate-800 bg-slate-900/60 rounded-xl text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-xl transition flex items-center gap-1 shrink-0">
                        <i class="fa-solid fa-plus text-[10px]"></i> เพิ่ม
                    </button>
                </div>
            </form>
        </div>

        <!-- 2. Department (แผนก) -->
        <div class="glass-card p-6 rounded-2xl glow-indigo">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4 mb-4">
                <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                    <i class="fa-solid fa-network-wired text-purple-400"></i> แผนก
                </h3>
                <span class="text-[10px] text-purple-400 bg-purple-500/10 px-2 py-0.5 rounded-full font-medium"><?= count($departments) ?> รายการ</span>
            </div>

            <!-- List grouped by division -->
            <div class="space-y-1.5 max-h-[300px] overflow-y-auto mb-6 pr-2">
                <?php if (empty($departments)): ?>
                    <p class="text-xs text-slate-500 font-light text-center py-4">ไม่มีข้อมูลแผนก</p>
                <?php else: ?>
                    <?php
                    $prevDiv = null;
                    foreach ($departments as $dept):
                        $divLabel = $dept['division_name'] ?? 'ไม่ระบุกอง';
                        if ($divLabel !== $prevDiv):
                            $prevDiv = $divLabel;
                    ?>
                        <p class="text-[10px] font-bold text-slate-600 uppercase tracking-wider pt-2 pl-1">
                            <i class="fa-solid fa-building mr-1 text-indigo-400/50"></i><?= htmlspecialchars($divLabel) ?>
                        </p>
                    <?php endif; ?>
                        <div class="flex items-center justify-between bg-slate-900/40 border border-slate-850 p-2 py-1.5 rounded-xl text-xs hover:border-slate-700 transition ml-2">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-network-wired text-purple-400/60 text-[10px]"></i>
                                <span class="text-slate-200 font-medium"><?= htmlspecialchars($dept['name']) ?></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="text-[9px] text-slate-600 mr-1">#<?= $dept['id'] ?></span>
                                <button type="button" @click="editDepartmentId = <?= $dept['id'] ?>; editDepartmentName = <?= htmlspecialchars(json_encode($dept['name'])) ?>; editDepartmentDivisionId = <?= htmlspecialchars(json_encode($dept['division_id'] ?? '')) ?>; showEditDepartment = true" 
                                        class="text-slate-500 hover:text-purple-400 hover:bg-purple-500/10 p-1 rounded-lg transition" 
                                        title="แก้ไข">
                                    <i class="fa-solid fa-pen text-[9px]"></i>
                                </button>
                                <button type="button" @click="deleteDepartmentId = <?= $dept['id'] ?>; deleteDepartmentName = <?= htmlspecialchars(json_encode($dept['name'])) ?>; showDeleteDepartment = true" 
                                        class="text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 p-1 rounded-lg transition" 
                                        title="ลบ">
                                    <i class="fa-solid fa-trash-can text-[9px]"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Form -->
            <form action="/admin/master/department/create" method="POST" class="pt-4 border-t border-slate-800/80 space-y-2">
                <label class="block text-[11px] font-semibold text-slate-400">เพิ่มแผนกใหม่</label>
                <select name="division_id"
                    class="block w-full px-3 py-2 border border-slate-800 bg-slate-900/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition">
                    <option value="">— ไม่ระบุกอง —</option>
                    <?php foreach ($divisions as $div): ?>
                        <option value="<?= $div['id'] ?>"><?= htmlspecialchars($div['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="flex gap-2">
                    <input type="text" name="name" required placeholder="ป้อนชื่อแผนก"
                        class="block w-full px-3 py-2 border border-slate-800 bg-slate-900/60 rounded-xl text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition">
                    <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white text-xs font-semibold rounded-xl transition flex items-center gap-1 shrink-0">
                        <i class="fa-solid fa-plus text-[10px]"></i> เพิ่ม
                    </button>
                </div>
            </form>
        </div>

        <!-- 3. Position (ตำแหน่ง) -->
        <div class="glass-card p-6 rounded-2xl glow-indigo">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4 mb-4">
                <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                    <i class="fa-solid fa-briefcase text-amber-400"></i> ตำแหน่งพนักงาน
                </h3>
                <span class="text-[10px] text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-full font-medium"><?= count($positions) ?> รายการ</span>
            </div>

            <!-- List grouped by department -->
            <div class="space-y-1.5 max-h-[300px] overflow-y-auto mb-6 pr-2">
                <?php if (empty($positions)): ?>
                    <p class="text-xs text-slate-500 font-light text-center py-4">ไม่มีข้อมูลตำแหน่ง</p>
                <?php else: ?>
                    <?php
                    $prevDept = null;
                    foreach ($positions as $pos):
                        $deptLabel = $pos['department_name'] ?? 'ไม่ระบุแผนก';
                        if ($deptLabel !== $prevDept):
                            $prevDept = $deptLabel;
                    ?>
                        <p class="text-[10px] font-bold text-slate-600 uppercase tracking-wider pt-2 pl-1">
                            <i class="fa-solid fa-network-wired mr-1 text-purple-400/50"></i><?= htmlspecialchars($deptLabel) ?>
                            <?php if (!empty($pos['division_name'])): ?>
                                <span class="text-slate-700 font-normal normal-case"> · <?= htmlspecialchars($pos['division_name']) ?></span>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                        <div class="flex items-center justify-between bg-slate-900/40 border border-slate-850 p-2 py-1.5 rounded-xl text-xs hover:border-slate-700 transition ml-2">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-briefcase text-amber-400/60 text-[10px]"></i>
                                <span class="text-slate-200 font-medium"><?= htmlspecialchars($pos['name']) ?></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="text-[9px] text-slate-600 mr-1">#<?= $pos['id'] ?></span>
                                <button type="button" @click="editPositionId = <?= $pos['id'] ?>; editPositionName = <?= htmlspecialchars(json_encode($pos['name'])) ?>; editPositionDivisionId = <?= htmlspecialchars(json_encode($pos['division_id'] ?? '')) ?>; editPositionDepartmentId = <?= htmlspecialchars(json_encode($pos['department_id'] ?? '')) ?>; showEditPosition = true" 
                                        class="text-slate-500 hover:text-amber-400 hover:bg-amber-500/10 p-1 rounded-lg transition" 
                                        title="แก้ไข">
                                    <i class="fa-solid fa-pen text-[9px]"></i>
                                </button>
                                <button type="button" @click="deletePositionId = <?= $pos['id'] ?>; deletePositionName = <?= htmlspecialchars(json_encode($pos['name'])) ?>; showDeletePosition = true" 
                                        class="text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 p-1 rounded-lg transition" 
                                        title="ลบ">
                                    <i class="fa-solid fa-trash-can text-[9px]"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Form -->
            <form action="/admin/master/position/create" method="POST" class="pt-4 border-t border-slate-800/80 space-y-2">
                <label class="block text-[11px] font-semibold text-slate-400">เพิ่มตำแหน่งงานใหม่</label>
                
                <!-- Division Select (Required) -->
                <select name="division_id" x-model="selectedDivId" required
                    class="block w-full px-3 py-2 border border-slate-800 bg-slate-900/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 transition">
                    <option value="">— เลือกกองที่สังกัด (บังคับ) —</option>
                    <?php foreach ($divisions as $div): ?>
                        <option value="<?= $div['id'] ?>"><?= htmlspecialchars($div['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Department Select (Optional, Filtered by Selected Division) -->
                <select name="department_id"
                    class="block w-full px-3 py-2 border border-slate-800 bg-slate-900/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 transition">
                    <option value="">— ไม่มีแผนก (สังกัดกองโดยตรง) —</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" 
                                data-div-id="<?= $dept['division_id'] ?? '' ?>"
                                x-show="selectedDivId === '' || selectedDivId == '<?= $dept['division_id'] ?? '' ?>'">
                            <?= htmlspecialchars($dept['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="flex gap-2">
                    <input type="text" name="name" required placeholder="ป้อนชื่อตำแหน่ง"
                        class="block w-full px-3 py-2 border border-slate-800 bg-slate-900/60 rounded-xl text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 transition">
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white text-xs font-semibold rounded-xl transition flex items-center gap-1 shrink-0">
                        <i class="fa-solid fa-plus text-[10px]"></i> เพิ่ม
                    </button>
                </div>
            </form>
        </div>

    </div>

    <?php $basePath = \App\Core\Request::getBasePath(); ?>
    <!-- ========================================== -->
    <!-- 1. Edit Division Modal -->
    <!-- ========================================== -->
    <div x-show="showEditDivision" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" 
         style="display: none;" 
         x-transition>
        <div class="max-w-md w-full glass-card border border-slate-800 rounded-2xl glow-indigo p-6 relative overflow-hidden" 
             @click.away="showEditDivision = false">
            <button @click="showEditDivision = false" class="absolute top-4 right-4 text-slate-500 hover:text-white transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
            <h3 class="text-base font-bold text-white mb-4 flex items-center gap-2">
                <i class="fa-solid fa-building text-indigo-400"></i> แก้ไขกอง
            </h3>
            <form :action="'<?= $basePath ?>/admin/master/division/update/' + editDivisionId" method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">ชื่อกอง</label>
                    <input type="text" name="name" x-model="editDivisionName" required
                           class="block w-full px-3 py-2 border border-slate-800 bg-slate-900/60 rounded-xl text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showEditDivision = false"
                            class="flex-1 py-2 bg-slate-800 hover:bg-slate-750 text-slate-300 text-xs font-semibold rounded-xl transition">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-xl transition">
                        บันทึกการเปลี่ยนแปลง
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 2. Delete Division Modal -->
    <!-- ========================================== -->
    <div x-show="showDeleteDivision" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" 
         style="display: none;" 
         x-transition>
        <div class="max-w-md w-full glass-card border border-rose-500/20 rounded-2xl glow-rose p-6 relative overflow-hidden" 
             @click.away="showDeleteDivision = false">
            <button @click="showDeleteDivision = false" class="absolute top-4 right-4 text-slate-500 hover:text-white transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
            <h3 class="text-base font-bold text-white mb-2 flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-rose-400"></i> ยืนยันการลบกอง
            </h3>
            <p class="text-xs text-slate-400 mb-4 leading-relaxed">
                คุณแน่ใจหรือไม่ว่าต้องการลบกอง <span class="text-white font-semibold" x-text="deleteDivisionName"></span>?
                <br><span class="text-rose-400/90 text-[10px] mt-1 block">* หมายเหตุ: แผนกที่สังกัดกองนี้จะถูกเปลี่ยนสถานะเป็น "ไม่ระบุกอง"</span>
            </p>
            <form :action="'<?= $basePath ?>/admin/master/division/delete/' + deleteDivisionId" method="POST" class="flex gap-3">
                <button type="button" @click="showDeleteDivision = false"
                        class="flex-1 py-2 bg-slate-800 hover:bg-slate-750 text-slate-300 text-xs font-semibold rounded-xl transition font-medium">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="flex-1 py-2 bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold rounded-xl transition font-medium">
                    ยืนยันลบข้อมูล
                </button>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 3. Edit Department Modal -->
    <!-- ========================================== -->
    <div x-show="showEditDepartment" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" 
         style="display: none;" 
         x-transition>
        <div class="max-w-md w-full glass-card border border-slate-800 rounded-2xl glow-indigo p-6 relative overflow-hidden" 
             @click.away="showEditDepartment = false">
            <button @click="showEditDepartment = false" class="absolute top-4 right-4 text-slate-500 hover:text-white transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
            <h3 class="text-base font-bold text-white mb-4 flex items-center gap-2">
                <i class="fa-solid fa-network-wired text-purple-400"></i> แก้ไขแผนก
            </h3>
            <form :action="'<?= $basePath ?>/admin/master/department/update/' + editDepartmentId" method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">สังกัดกอง</label>
                    <select name="division_id" x-model="editDepartmentDivisionId"
                            class="block w-full px-3 py-2 border border-slate-800 bg-slate-900/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition">
                        <option value="">— ไม่ระบุกอง —</option>
                        <?php foreach ($divisions as $div): ?>
                            <option value="<?= $div['id'] ?>"><?= htmlspecialchars($div['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">ชื่อแผนก</label>
                    <input type="text" name="name" x-model="editDepartmentName" required
                           class="block w-full px-3 py-2 border border-slate-800 bg-slate-900/60 rounded-xl text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showEditDepartment = false"
                            class="flex-1 py-2 bg-slate-800 hover:bg-slate-750 text-slate-300 text-xs font-semibold rounded-xl transition">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="flex-1 py-2 bg-purple-600 hover:bg-purple-500 text-white text-xs font-semibold rounded-xl transition">
                        บันทึกการเปลี่ยนแปลง
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 4. Delete Department Modal -->
    <!-- ========================================== -->
    <div x-show="showDeleteDepartment" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" 
         style="display: none;" 
         x-transition>
        <div class="max-w-md w-full glass-card border border-rose-500/20 rounded-2xl glow-rose p-6 relative overflow-hidden" 
             @click.away="showDeleteDepartment = false">
            <button @click="showDeleteDepartment = false" class="absolute top-4 right-4 text-slate-500 hover:text-white transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
            <h3 class="text-base font-bold text-white mb-2 flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-rose-400"></i> ยืนยันการลบแผนก
            </h3>
            <p class="text-xs text-slate-400 mb-4 leading-relaxed">
                คุณแน่ใจหรือไม่ว่าต้องการลบแผนก <span class="text-white font-semibold" x-text="deleteDepartmentName"></span>?
                <br><span class="text-rose-400/90 text-[10px] mt-1 block">* หมายเหตุ: ตำแหน่งงานที่สังกัดแผนกนี้จะถูกเปลี่ยนสถานะเป็น "ไม่ระบุแผนก"</span>
            </p>
            <form :action="'<?= $basePath ?>/admin/master/department/delete/' + deleteDepartmentId" method="POST" class="flex gap-3">
                <button type="button" @click="showDeleteDepartment = false"
                        class="flex-1 py-2 bg-slate-800 hover:bg-slate-750 text-slate-300 text-xs font-semibold rounded-xl transition font-medium">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="flex-1 py-2 bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold rounded-xl transition font-medium">
                    ยืนยันลบข้อมูล
                </button>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 5. Edit Position Modal -->
    <!-- ========================================== -->
    <div x-show="showEditPosition" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" 
         style="display: none;" 
         x-transition>
        <div class="max-w-md w-full glass-card border border-slate-800 rounded-2xl glow-indigo p-6 relative overflow-hidden" 
             @click.away="showEditPosition = false">
            <button @click="showEditPosition = false" class="absolute top-4 right-4 text-slate-500 hover:text-white transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
            <h3 class="text-base font-bold text-white mb-4 flex items-center gap-2">
                <i class="fa-solid fa-briefcase text-amber-400"></i> แก้ไขตำแหน่งงาน
            </h3>
            <form :action="'<?= $basePath ?>/admin/master/position/update/' + editPositionId" method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">สังกัดกอง</label>
                    <select name="division_id" x-model="editPositionDivisionId" required
                            class="block w-full px-3 py-2 border border-slate-800 bg-slate-900/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 transition mb-3">
                        <option value="">— เลือกกองที่สังกัด (บังคับ) —</option>
                        <?php foreach ($divisions as $div): ?>
                            <option value="<?= $div['id'] ?>"><?= htmlspecialchars($div['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">สังกัดแผนก</label>
                    <select name="department_id" x-model="editPositionDepartmentId"
                            class="block w-full px-3 py-2 border border-slate-800 bg-slate-900/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 transition">
                        <option value="">— ไม่มีแผนก (สังกัดกองโดยตรง) —</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>"
                                    data-div-id="<?= $dept['division_id'] ?? '' ?>"
                                    x-show="editPositionDivisionId === '' || editPositionDivisionId == '<?= $dept['division_id'] ?? '' ?>'">
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">ชื่อตำแหน่งงาน</label>
                    <input type="text" name="name" x-model="editPositionName" required
                           class="block w-full px-3 py-2 border border-slate-800 bg-slate-900/60 rounded-xl text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 transition">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showEditPosition = false"
                            class="flex-1 py-2 bg-slate-800 hover:bg-slate-750 text-slate-300 text-xs font-semibold rounded-xl transition">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="flex-1 py-2 bg-amber-600 hover:bg-amber-500 text-white text-xs font-semibold rounded-xl transition font-medium">
                        บันทึกการเปลี่ยนแปลง
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 6. Delete Position Modal -->
    <!-- ========================================== -->
    <div x-show="showDeletePosition" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" 
         style="display: none;" 
         x-transition>
        <div class="max-w-md w-full glass-card border border-rose-500/20 rounded-2xl glow-rose p-6 relative overflow-hidden" 
             @click.away="showDeletePosition = false">
            <button @click="showDeletePosition = false" class="absolute top-4 right-4 text-slate-500 hover:text-white transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
            <h3 class="text-base font-bold text-white mb-2 flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-rose-400"></i> ยืนยันการลบตำแหน่งงาน
            </h3>
            <p class="text-xs text-slate-400 mb-4 leading-relaxed">
                คุณแน่ใจหรือไม่ว่าต้องการลบตำแหน่ง <span class="text-white font-semibold" x-text="deletePositionName"></span>?
                <br><span class="text-rose-400/90 text-[10px] mt-1 block">* คำเตือน: ระบบจะไม่อนุญาตให้ลบ หากตำแหน่งนี้มีรายชื่อพนักงานใช้อยู่เพื่อป้องกันโครงสร้างเสียหาย</span>
            </p>
            <form :action="'<?= $basePath ?>/admin/master/position/delete/' + deletePositionId" method="POST" class="flex gap-3">
                <button type="button" @click="showDeletePosition = false"
                        class="flex-1 py-2 bg-slate-800 hover:bg-slate-750 text-slate-300 text-xs font-semibold rounded-xl transition font-medium">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="flex-1 py-2 bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold rounded-xl transition font-medium">
                    ยืนยันลบข้อมูล
                </button>
            </form>
        </div>
    </div>

</div>
