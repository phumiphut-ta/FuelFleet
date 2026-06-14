<div class="min-h-[70vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 glass-panel p-10 rounded-2xl glow-indigo border border-slate-800 relative overflow-hidden">
        <!-- Accent Glow -->
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-purple-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="text-center">
            <div class="mx-auto h-12 w-12 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20 mb-4">
                <i class="fa-solid fa-shield-halved text-white text-xl"></i>
            </div>
            <h2 class="text-2xl font-bold tracking-tight text-white">
                แผงควบคุมระบบดูแล <span class="text-gradient-purple font-extrabold">Console</span>
            </h2>
            <p class="mt-2 text-xs text-slate-400 font-light">กรุณาลงชื่อเข้าใช้งานสำหรับผู้ดูแลระบบและแอดมินยานพาหนะ</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-rose-500/15 border border-rose-500/30 text-rose-300 px-4 py-3 rounded-xl text-xs flex items-center space-x-2 animate-bounce">
                <i class="fa-solid fa-circle-exclamation text-sm text-rose-400"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form class="mt-8 space-y-6" action="/admin/login" method="POST">
            <div class="space-y-4">
                <!-- Username Input -->
                <div>
                    <label for="username" class="block text-xs font-semibold text-slate-400 mb-1.5">ชื่อผู้ใช้งาน (Username)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                            <i class="fa-solid fa-user text-sm"></i>
                        </div>
                        <input id="username" name="username" type="text" required 
                            class="block w-full pl-10 pr-4 py-3 border border-slate-800 bg-slate-900/50 rounded-xl text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200" 
                            placeholder="ป้อนชื่อผู้ใช้แอดมิน">
                    </div>
                </div>

                <!-- Password Input -->
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-400 mb-1.5">รหัสผ่าน (Password)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                            <i class="fa-solid fa-lock text-sm"></i>
                        </div>
                        <input id="password" name="password" type="password" required 
                            class="block w-full pl-10 pr-4 py-3 border border-slate-800 bg-slate-900/50 rounded-xl text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200" 
                            placeholder="ป้อนรหัสผ่าน">
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div>
                <button type="submit" 
                    class="group relative w-full flex justify-center py-3.5 px-4 border border-transparent text-sm font-semibold rounded-xl text-white bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-lg shadow-indigo-500/10 transition duration-300 transform hover:-translate-y-0.5">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3 text-indigo-200 group-hover:text-white transition duration-200">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    </span>
                    ลงชื่อเข้าใช้งาน
                </button>
            </div>
        </form>

        <div class="text-center mt-6">
            <a href="/" class="text-xs text-indigo-400 hover:text-indigo-300 font-light flex items-center justify-center gap-1.5 hover:underline transition">
                <i class="fa-solid fa-arrow-left"></i> กลับสู่หน้าจองสำหรับบุคคลทั่วไป
            </a>
        </div>
    </div>
</div>
