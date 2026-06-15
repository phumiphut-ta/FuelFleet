<div class="min-h-screen flex flex-col items-center justify-center bg-slate-900 text-white p-6">
    <div class="max-w-md text-center">
        <h1 class="text-7xl font-extrabold text-rose-500 drop-shadow-lg mb-6">⚠️</h1>
        <h2 class="text-2xl font-bold mb-4"><?= htmlspecialchars($message ?? 'เกิดข้อผิดพลาดในการประมวลผล') ?></h2>
        <p class="text-slate-400 mb-8 font-light">กรุณาย้อนกลับ โหลดหน้าหลักใหม่ และทำรายการอีกครั้ง หากยังพบปัญหากรุณาติดต่อผู้ดูแลระบบ</p>
        <a href="/" class="inline-block px-6 py-3 bg-gradient-to-r from-rose-500 to-red-600 hover:from-rose-600 hover:to-red-700 text-white font-semibold rounded-xl shadow-lg transition duration-300 transform hover:-translate-y-0.5">
            กลับสู่หน้าหลัก
        </a>
    </div>
</div>
