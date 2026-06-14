<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-ban text-indigo-400"></i> ออกคำสั่งระงับใช้งานยานพาหนะ
            </h1>
            <p class="text-xs text-slate-400 font-light mt-1">งดการให้บริการจองรถคันดังกล่าวชั่วคราว เพื่อเข้ากระบวนการบำรุงรักษา ป้องกันความเสี่ยงก่อนออกเดินทาง</p>
        </div>
        <a href="/admin/suspensions" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/80 text-xs rounded-xl font-medium flex items-center gap-1.5 transition">
            <i class="fa-solid fa-arrow-left"></i> กลับหน้าประวัติการระงับใช้รถ
        </a>
    </div>

    <!-- Form Panel -->
    <div class="glass-panel p-8 rounded-2xl border border-slate-800/80 max-w-2xl mx-auto relative">
        <form action="/admin/suspensions/create" method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Vehicle -->
                <div>
                    <label for="car_id" class="block text-xs font-semibold text-slate-400 mb-2">ระบุรถยนต์ราชการที่จะสั่งระงับ <span class="text-rose-500">*</span></label>
                    <select id="car_id" name="car_id" required 
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <option value="" disabled selected>-- เลือกทะเบียนรถยนต์ --</option>
                        <?php foreach ($cars as $car): ?>
                            <option value="<?= $car['id'] ?>" <?= $car['status'] === 'Suspended' ? 'disabled class="text-slate-600"' : '' ?>>
                                <?= htmlspecialchars($car['license_plate']) ?> (<?= htmlspecialchars($car['fuel_type']) ?>) <?= $car['status'] === 'Suspended' ? '[ระงับใช้อยู่]' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Spacer -->
                <div class="hidden md:block"></div>

                <!-- Start Date -->
                <div>
                    <label for="start_date" class="block text-xs font-semibold text-slate-400 mb-2">ระงับใช้งานตั้งแต่วันที่ <span class="text-rose-500">*</span></label>
                    <input id="start_date" name="start_date" type="date" required 
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>

                <!-- End Date -->
                <div>
                    <label for="end_date" class="block text-xs font-semibold text-slate-400 mb-2">ถึงวันที่ <span class="text-rose-500">*</span></label>
                    <input id="end_date" name="end_date" type="date" required 
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
            </div>

            <!-- Reason -->
            <div>
                <label for="reason" class="block text-xs font-semibold text-slate-400 mb-2">สาเหตุการปิดซ่อมบำรุง / ระงับการใช้งาน <span class="text-rose-500">*</span></label>
                <textarea id="reason" name="reason" rows="3" required placeholder="เช่น ปิดตรวจสภาพเช็คระยะ 50,000 กม., สั่งเคลมเปลี่ยนยางล้อรถ, เปลี่ยนหม้อน้ำและซ่อมเกียร์..."
                    class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"></textarea>
            </div>

            <!-- Warning Notice -->
            <div class="bg-amber-500/10 border border-amber-500/20 text-slate-400 px-4 py-3 rounded-xl text-[11px] leading-relaxed flex items-start gap-2">
                <i class="fa-solid fa-circle-info text-amber-500 mt-0.5 text-xs shrink-0"></i>
                <div>
                    <span class="font-bold text-slate-300">หมายเหตุสำคัญ:</span> ระบบจะตรวจสอบการจองใช้งานรถล่วงหน้าของคันดังกล่าวโดยอัตโนมัติ หากตรวจพบรายการที่ชนกันจะรายงานแจ้งเตือนเพื่อสลับรถใช้งานให้ผู้จองทราบต่อไป
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-6 border-t border-slate-800 flex items-center justify-end gap-3">
                <a href="/admin/suspensions" class="px-5 py-2.5 border border-slate-850 hover:bg-slate-900 text-slate-400 hover:text-slate-200 text-xs font-semibold rounded-xl transition">
                    ยกเลิก
                </a>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-rose-500 to-red-600 hover:from-rose-600 hover:to-red-700 text-white text-xs font-semibold rounded-xl transition shadow-lg transform hover:-translate-y-0.5">
                    <i class="fa-solid fa-hand-holding-hand mr-1 text-xs"></i> ยืนยันออกคำสั่งแบนรถ
                </button>
            </div>
        </form>
    </div>
</div>
