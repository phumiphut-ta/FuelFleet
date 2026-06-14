<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-indigo-400"></i> เพิ่มผู้ดูแลระบบใหม่
            </h1>
            <p class="text-xs text-slate-400 font-light mt-1">ลงทะเบียนบัญชีแผงควบคุมหลังบ้านเพิ่มเติม เพื่อช่วยอำนวยความสะดวกในระบบจัดการรถและน้ำมัน</p>
        </div>
        <a href="/admin/users" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/80 text-xs rounded-xl font-medium flex items-center gap-1.5 transition">
            <i class="fa-solid fa-arrow-left"></i> กลับหน้ารายการผู้ช่วยแอดมิน
        </a>
    </div>

    <!-- Form Panel -->
    <div class="glass-panel p-8 rounded-2xl border border-slate-800/80 max-w-2xl mx-auto relative">
        <!-- Error Notice -->
        <?php if (!empty($error)): ?>
            <div class="mb-6 bg-rose-500/15 border border-rose-500/30 text-rose-300 px-4 py-3 rounded-xl text-xs flex items-center space-x-2">
                <i class="fa-solid fa-circle-exclamation text-sm text-rose-400"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form action="/admin/users/create" method="POST" class="space-y-6">
            <div class="space-y-4">
                <!-- Username -->
                <div>
                    <label for="username" class="block text-xs font-semibold text-slate-400 mb-2">ชื่อผู้ใช้ (Username) <span class="text-rose-500">*</span></label>
                    <input id="username" name="username" type="text" required placeholder="เช่น helper.admin"
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <span class="text-[10px] text-slate-500 font-light mt-1 block">ชื่อผู้ใช้สำหรับใช้ลงชื่อเข้าใช้งานระบบหลังบ้าน (ห้ามซ้ำซ้อน)</span>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-400 mb-2">รหัสผ่านสำหรับลงชื่อเข้าใช้ <span class="text-rose-500">*</span></label>
                    <input id="password" name="password" type="password" required placeholder="อย่างน้อย 6 ตัวอักษร"
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>

                <!-- Full Name -->
                <div>
                    <label for="full_name" class="block text-xs font-semibold text-slate-400 mb-2">ชื่อ-นามสกุลจริงผู้ใช้งาน <span class="text-rose-500">*</span></label>
                    <input id="full_name" name="full_name" type="text" required placeholder="เช่น นายสมหมาย รักรถหลวง"
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-6 border-t border-slate-800 flex items-center justify-end gap-3">
                <a href="/admin/users" class="px-5 py-2.5 border border-slate-850 hover:bg-slate-900 text-slate-400 hover:text-slate-200 text-xs font-semibold rounded-xl transition">
                    ยกเลิก
                </a>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-xs font-semibold rounded-xl transition shadow-lg transform hover:-translate-y-0.5">
                    <i class="fa-solid fa-save mr-1 text-xs"></i> บันทึกข้อมูล
                </button>
            </div>
        </form>
    </div>
</div>
