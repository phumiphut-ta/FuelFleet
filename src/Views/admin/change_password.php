<div class="max-w-md mx-auto space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
            <i class="fa-solid fa-key text-indigo-400"></i> เปลี่ยนรหัสผ่านผู้ดูแลระบบ
        </h1>
        <p class="text-xs text-slate-400 font-light mt-1">ตั้งค่ารหัสผ่านใหม่เข้าใช้งานแผงควบคุมระบบ เพื่อรักษาความปลอดภัยของข้อมูลสูงสุด</p>
    </div>

    <!-- Alert Notifications -->
    <?php if (!empty($success)): ?>
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl text-xs flex items-center gap-2.5 glow-emerald animate-pulse">
            <i class="fa-solid fa-circle-check text-md"></i>
            <div>
                <span class="font-bold">สำเร็จ!</span> <?= htmlspecialchars($success) ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl text-xs flex items-center gap-2.5 glow-rose">
            <i class="fa-solid fa-triangle-exclamation text-md"></i>
            <div>
                <span class="font-bold">เกิดข้อผิดพลาด!</span> <?= htmlspecialchars($error) ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Glassmorphism Form Card -->
    <div class="glass-panel p-6 rounded-2xl border border-slate-850 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 right-0 w-36 h-36 bg-indigo-500/5 rounded-full blur-2xl pointer-events-none"></div>
        
        <form action="/admin/change-password" method="POST" class="space-y-4">
            
            <!-- Current Password -->
            <div class="space-y-1.5">
                <label for="current_password" class="text-xs font-semibold text-slate-400 flex items-center gap-1.5">
                    <i class="fa-solid fa-lock text-[10px] text-slate-500"></i> รหัสผ่านปัจจุบัน <span class="text-rose-400">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                        <i class="fa-solid fa-shield-halved text-xs"></i>
                    </span>
                    <input 
                        type="password" 
                        id="current_password" 
                        name="current_password" 
                        required
                        placeholder="••••••••"
                        class="w-full pl-9 pr-4 py-2.5 bg-slate-950/40 border border-slate-850 rounded-xl text-xs placeholder-slate-600 text-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition shadow-inner"
                    >
                </div>
            </div>

            <!-- New Password -->
            <div class="space-y-1.5">
                <label for="new_password" class="text-xs font-semibold text-slate-400 flex items-center gap-1.5">
                    <i class="fa-solid fa-key text-[10px] text-slate-500"></i> รหัสผ่านใหม่ <span class="text-rose-400">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                        <i class="fa-solid fa-lock-open text-xs"></i>
                    </span>
                    <input 
                        type="password" 
                        id="new_password" 
                        name="new_password" 
                        required
                        placeholder="•••••••• (อย่างน้อย 6 ตัวอักษร)"
                        class="w-full pl-9 pr-4 py-2.5 bg-slate-950/40 border border-slate-850 rounded-xl text-xs placeholder-slate-600 text-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition shadow-inner"
                    >
                </div>
            </div>

            <!-- Confirm New Password -->
            <div class="space-y-1.5">
                <label for="confirm_password" class="text-xs font-semibold text-slate-400 flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-check text-[10px] text-slate-500"></i> ยืนยันรหัสผ่านใหม่ <span class="text-rose-400">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                        <i class="fa-solid fa-lock text-xs"></i>
                    </span>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required
                        placeholder="••••••••"
                        class="w-full pl-9 pr-4 py-2.5 bg-slate-950/40 border border-slate-850 rounded-xl text-xs placeholder-slate-600 text-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition shadow-inner"
                    >
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button 
                    type="submit"
                    class="w-full py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-500/10 flex items-center justify-center gap-1.5 transition duration-300 transform hover:-translate-y-0.5 cursor-pointer"
                >
                    <i class="fa-solid fa-floppy-disk text-xs"></i> บันทึกรหัสผ่านใหม่
                </button>
            </div>

        </form>
    </div>
</div>
