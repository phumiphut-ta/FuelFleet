<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-car-plus text-indigo-400"></i> ลงทะเบียนยานพาหนะคันใหม่
            </h1>
            <p class="text-xs text-slate-400 font-light mt-1">ลงทะเบียนประวัติรถยนต์คันใหม่ และกำหนดคุณสมบัติการใช้น้ำมันเพื่อเริ่มจองรถ</p>
        </div>
        <a href="/admin/cars" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/80 text-xs rounded-xl font-medium flex items-center gap-1.5 transition">
            <i class="fa-solid fa-arrow-left"></i> กลับหน้าทะเบียนรถยนต์
        </a>
    </div>

    <!-- Form Panel -->
    <div class="glass-panel p-8 rounded-2xl border border-slate-800/80 max-w-2xl mx-auto relative">
        <form action="/admin/cars/create" method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Plate -->
                <div>
                    <label for="license_plate" class="block text-xs font-semibold text-slate-400 mb-2">ป้ายทะเบียนรถราชการ <span class="text-rose-500">*</span></label>
                    <input id="license_plate" name="license_plate" type="text" required placeholder="ตัวอย่าง: กข-1234"
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200">
                </div>

                <!-- Fuel Type -->
                <div>
                    <label for="fuel_type" class="block text-xs font-semibold text-slate-400 mb-2">ประเภทน้ำมันที่รองรับ <span class="text-rose-500">*</span></label>
                    <select id="fuel_type" name="fuel_type" required 
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <option value="" disabled selected>-- เลือกประเภทน้ำมัน --</option>
                        <option value="Diesel">ดีเซล (Diesel)</option>
                        <option value="Gasohol 95">แก๊สโซฮอล์ 95 (Gasohol 95)</option>
                        <option value="Gasohol 91">แก๊สโซฮอล์ 91 (Gasohol 91)</option>
                        <option value="E20">แก๊สโซฮอล์ E20 (E20)</option>
                        <option value="E85">แก๊สโซฮอล์ E85 (E85)</option>
                        <option value="Benzene">เบนซิน (Benzene)</option>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-xs font-semibold text-slate-400 mb-2">สถานะเริ่มต้น</label>
                    <select id="status" name="status" 
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <option value="Active">พร้อมใช้งาน (Active)</option>
                        <option value="Suspended">งดใช้งานชั่วคราว/ซ่อมแซม (Suspended)</option>
                    </select>
                </div>

                <!-- Color Picker -->
                <div>
                    <label for="color" class="block text-xs font-semibold text-slate-400 mb-2">สีสัญลักษณ์ของรถบนปฏิทิน (Event Color) <span class="text-rose-500">*</span></label>
                    <div class="flex items-center gap-3">
                        <input id="color" name="color" type="color" required 
                            value="#4f46e5"
                            class="h-10 w-16 border border-slate-800 bg-slate-950/60 rounded-xl cursor-pointer p-1">
                        <span class="text-[10px] text-slate-500 leading-normal">สีกิจกรรมจองรถคันนี้บนหน้าปฏิทินส่วนกลาง</span>
                    </div>
                </div>
            </div>

            <!-- Note -->
            <div>
                <label for="note" class="block text-xs font-semibold text-slate-400 mb-2">บันทึกเพิ่มเติม</label>
                <textarea id="note" name="note" rows="3" placeholder="ระบุประเภทรถ หรือฝ่ายที่รับผิดชอบหลัก เช่น รถตู้ส่วนกลาง กองช่าง..."
                    class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"></textarea>
            </div>

            <!-- Action Buttons -->
            <div class="pt-6 border-t border-slate-800 flex items-center justify-end gap-3">
                <a href="/admin/cars" class="px-5 py-2.5 border border-slate-850 hover:bg-slate-900 text-slate-400 hover:text-slate-200 text-xs font-semibold rounded-xl transition">
                    ยกเลิก
                </a>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-xs font-semibold rounded-xl transition shadow-lg transform hover:-translate-y-0.5">
                    <i class="fa-solid fa-save mr-1.5 text-xs"></i> บันทึกข้อมูลรถ
                </button>
            </div>
        </form>
    </div>
</div>
