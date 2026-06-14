<div class="space-y-6" x-data="{ reportType: 1 }">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-file-pdf text-rose-400"></i> ศูนย์ส่งออกรายงานไฟล์ราชการ (PDF Report Center)
            </h1>
            <p class="text-xs text-slate-400 font-light mt-1">ส่งออกรายงานสรุปข้อมูลในรูปแบบ PDF ที่จัดเรียงหน้ากระดาษเป็นสไตล์หนังสือราชการ/องค์กร พร้อมสำหรับการปริ้นท์แนบใบสลิป</p>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (!empty($success)): ?>
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl text-xs flex items-center gap-2.5 glow-emerald">
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

    <!-- Main split layout: Selector on Left, Filter on Right -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 1 Col: Report Type selection widgets -->
        <div class="glass-card p-6 rounded-2xl glow-indigo space-y-4">
            <h3 class="text-sm font-semibold text-white border-b border-slate-800 pb-3 mb-2 flex items-center gap-1.5">
                <i class="fa-solid fa-layer-group text-indigo-400"></i> เลือกประเภทรายงาน (Report Type)
            </h3>

            <!-- 6 Report Choices -->
            <div class="space-y-2 text-xs">
                <!-- R1 -->
                <button type="button" @click="reportType = 1" :class="reportType === 1 ? 'bg-indigo-500/10 border-indigo-500 text-indigo-300' : 'bg-slate-900/40 border-slate-850 text-slate-400 hover:text-white hover:border-slate-800'"
                    class="w-full text-left p-3 border rounded-xl font-semibold flex items-center gap-2 transition duration-150">
                    <span class="h-6 w-6 rounded-lg bg-indigo-500/10 flex items-center justify-center font-bold text-[10px]">1</span>
                    <span>รายงานการใช้น้ำมันรายเดือน</span>
                </button>

                <!-- R2 -->
                <button type="button" @click="reportType = 2" :class="reportType === 2 ? 'bg-indigo-500/10 border-indigo-500 text-indigo-300' : 'bg-slate-900/40 border-slate-850 text-slate-400 hover:text-white hover:border-slate-800'"
                    class="w-full text-left p-3 border rounded-xl font-semibold flex items-center gap-2 transition duration-150">
                    <span class="h-6 w-6 rounded-lg bg-indigo-500/10 flex items-center justify-center font-bold text-[10px]">2</span>
                    <span>รายงานสถิติการใช้น้ำมันรถยนต์ส่วนกลางรายปีงบประมาณ</span>
                </button>

                <!-- R3 -->
                <button type="button" @click="reportType = 3" :class="reportType === 3 ? 'bg-indigo-500/10 border-indigo-500 text-indigo-300' : 'bg-slate-900/40 border-slate-850 text-slate-400 hover:text-white hover:border-slate-800'"
                    class="w-full text-left p-3 border rounded-xl font-semibold flex items-center gap-2 transition duration-150">
                    <span class="h-6 w-6 rounded-lg bg-indigo-500/10 flex items-center justify-center font-bold text-[10px]">3</span>
                    <span>รายงานจังหวัดจุดหมายยอดนิยมประจำปีงบประมาณ</span>
                </button>

                <!-- R4 -->
                <button type="button" @click="reportType = 4" :class="reportType === 4 ? 'bg-indigo-500/10 border-indigo-500 text-indigo-300' : 'bg-slate-900/40 border-slate-850 text-slate-400 hover:text-white hover:border-slate-800'"
                    class="w-full text-left p-3 border rounded-xl font-semibold flex items-center gap-2 transition duration-150">
                    <span class="h-6 w-6 rounded-lg bg-indigo-500/10 flex items-center justify-center font-bold text-[10px]">4</span>
                    <span>รายงานประวัติการระงับใช้รถชั่วคราว</span>
                </button>

                <!-- R5 -->
                <button type="button" @click="reportType = 5" :class="reportType === 5 ? 'bg-indigo-500/10 border-indigo-500 text-indigo-300' : 'bg-slate-900/40 border-slate-850 text-slate-400 hover:text-white hover:border-slate-800'"
                    class="w-full text-left p-3 border rounded-xl font-semibold flex items-center gap-2 transition duration-150">
                    <span class="h-6 w-6 rounded-lg bg-indigo-500/10 flex items-center justify-center font-bold text-[10px]">5</span>
                    <span>รายงานประวัติความปลอดภัยหลังบ้าน</span>
                </button>

                <!-- R6 -->
                <button type="button" @click="reportType = 6" :class="reportType === 6 ? 'bg-indigo-500/10 border-indigo-500 text-indigo-300' : 'bg-slate-900/40 border-slate-850 text-slate-400 hover:text-white hover:border-slate-800'"
                    class="w-full text-left p-3 border rounded-xl font-semibold flex items-center gap-2 transition duration-150">
                    <span class="h-6 w-6 rounded-lg bg-indigo-500/10 flex items-center justify-center font-bold text-[10px]">6</span>
                    <span>รายงานใบเสร็จค่าน้ำมันประจำเดือนจำแนกรายคัน</span>
                </button>

                <!-- R7 -->
                <button type="button" @click="reportType = 7" :class="reportType === 7 ? 'bg-indigo-500/10 border-indigo-500 text-indigo-300' : 'bg-slate-900/40 border-slate-850 text-slate-400 hover:text-white hover:border-slate-800'"
                    class="w-full text-left p-3 border rounded-xl font-semibold flex items-center gap-2 transition duration-150">
                    <span class="h-6 w-6 rounded-lg bg-indigo-500/10 flex items-center justify-center font-bold text-[10px]">7</span>
                    <span>รายงานการใช้น้ำมันรายปีงบประมาณ</span>
                </button>

                <!-- R8 -->
                <button type="button" @click="reportType = 8" :class="reportType === 8 ? 'bg-indigo-500/10 border-indigo-500 text-indigo-300' : 'bg-slate-900/40 border-slate-850 text-slate-400 hover:text-white hover:border-slate-800'"
                    class="w-full text-left p-3 border rounded-xl font-semibold flex items-center gap-2 transition duration-150">
                    <span class="h-6 w-6 rounded-lg bg-indigo-500/10 flex items-center justify-center font-bold text-[10px]">8</span>
                    <span>รายงานสถิติผู้จองใช้งานรถยนต์ส่วนกลาง</span>
                </button>

                <!-- R9 -->
                <button type="button" @click="reportType = 9" :class="reportType === 9 ? 'bg-indigo-500/10 border-indigo-500 text-indigo-300' : 'bg-slate-900/40 border-slate-850 text-slate-400 hover:text-white hover:border-slate-800'"
                    class="w-full text-left p-3 border rounded-xl font-semibold flex items-center gap-2 transition duration-150">
                    <span class="h-6 w-6 rounded-lg bg-indigo-500/10 flex items-center justify-center font-bold text-[10px]">9</span>
                    <span>รายงานการยกเลิกการจองใช้งานรถ</span>
                </button>

                <!-- R10 -->
                <button type="button" @click="reportType = 10" :class="reportType === 10 ? 'bg-indigo-500/10 border-indigo-500 text-indigo-300' : 'bg-slate-900/40 border-slate-850 text-slate-400 hover:text-white hover:border-slate-800'"
                    class="w-full text-left p-3 border rounded-xl font-semibold flex items-center gap-2 transition duration-150">
                    <span class="h-6 w-6 rounded-lg bg-indigo-500/10 flex items-center justify-center font-bold text-[10px]">10</span>
                    <span>รายงานการจองรถยนต์ประจำเดือน</span>
                </button>
            </div>
        </div>

        <!-- Right 2 Cols: Dynamic filter criteria & print button -->
        <div class="lg:col-span-2 glass-card p-6 rounded-2xl glow-indigo flex flex-col justify-between">
            <div>
                <h3 class="text-sm font-semibold text-white border-b border-slate-800 pb-3 mb-5 flex items-center gap-2">
                    <i class="fa-solid fa-sliders text-indigo-400"></i> กำหนดขอบเขตและฟิลเตอร์รายงาน
                </h3>

                <form action="/admin/reports/generate" method="POST" target="_blank" class="space-y-6">
                    <!-- Dynamic fields bound in input -->
                    <input type="hidden" name="report_type" :value="reportType">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Month Filter (Visible for R1, R6 & R10) -->
                        <div x-show="reportType === 1 || reportType === 6 || reportType === 10" x-transition>
                            <label for="month" class="block text-xs font-semibold text-slate-400 mb-2">เลือกเดือนคัดกรอง</label>
                            <select id="month" name="month" 
                                class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition">
                                <option value="01">มกราคม (Jan)</option>
                                <option value="02">กุมภาพันธ์ (Feb)</option>
                                <option value="03">มีนาคม (Mar)</option>
                                <option value="04">เมษายน (Apr)</option>
                                <option value="05" selected>พฤษภาคม (May)</option>
                                <option value="06">มิถุนายน (Jun)</option>
                                <option value="07">กรกฎาคม (Jul)</option>
                                <option value="08">สิงหาคม (Aug)</option>
                                <option value="09">กันยายน (Sep)</option>
                                <option value="10">ตุลาคม (Oct)</option>
                                <option value="11">พฤศจิกายน (Nov)</option>
                                <option value="12">ธันวาคม (Dec)</option>
                            </select>
                        </div>

                        <!-- Year Filter (Visible for R1, R2, R3, R6, R7, R10) -->
                        <div x-show="reportType === 1 || reportType === 2 || reportType === 3 || reportType === 6 || reportType === 7 || reportType === 10" x-transition>
                            <label for="year" class="block text-xs font-semibold text-slate-400 mb-2">
                                <span x-text="(reportType === 3 || reportType === 7) ? 'เลือกปีงบประมาณ ค.ศ. คัดกรอง' : 'เลือกปี ค.ศ. คัดกรอง'"></span>
                            </label>
                            <select id="year" name="year" 
                                class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition">
                                <?php
                                $currentYear = (int)date('Y');
                                for ($y = $currentYear; $y >= 2024; $y--):
                                ?>
                                    <option value="<?= $y ?>" <?= $y === $currentYear ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Vehicle Filter (Visible for R6 & R10) -->
                        <div class="md:col-span-2" x-show="reportType === 6 || reportType === 10" x-transition>
                            <label for="car_id" class="block text-xs font-semibold text-slate-400 mb-2">
                                <span x-text="reportType === 6 ? 'เลือกทะเบียนรถยนต์หลวงที่จะเปิดรายงานใบเสร็จค่าน้ำมันประจำเดือนจำแนกรายคัน' : 'เลือกทะเบียนรถยนต์หลวงที่ต้องการดูรายงานการจอง'"></span>
                                <span class="text-rose-500">*</span>
                            </label>
                            <select id="car_id" name="car_id" 
                                class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition">
                                <option value="all" x-show="reportType === 10" :selected="reportType === 10">-- แสดงข้อมูลรถยนต์ทุกคัน (All Vehicles) --</option>
                                <option value="" disabled x-show="reportType === 6" :selected="reportType === 6">-- เลือกทะเบียนรถยนต์ --</option>
                                <?php foreach ($cars as $car): ?>
                                    <option value="<?= $car['id'] ?>"><?= htmlspecialchars($car['license_plate']) ?> (<?= htmlspecialchars($car['fuel_type']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date Range Selection (Visible for R8 and R9) -->
                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6" x-show="reportType === 8 || reportType === 9" x-transition>
                            <div>
                                <label for="start_date" class="block text-xs font-semibold text-slate-400 mb-2">ตั้งแต่วันที่ <span class="text-rose-500">*</span></label>
                                <input type="date" id="start_date" name="start_date" 
                                    class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition">
                            </div>
                            <div>
                                <label for="end_date" class="block text-xs font-semibold text-slate-400 mb-2">ถึงวันที่ <span class="text-rose-500">*</span></label>
                                <input type="date" id="end_date" name="end_date" 
                                    class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition">
                            </div>
                        </div>
                    </div>

                    <!-- Print Button -->
                    <div class="pt-6 border-t border-slate-850">
                        <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-rose-500 to-red-600 hover:from-rose-600 hover:to-red-700 text-white font-bold text-sm rounded-xl shadow-lg shadow-rose-500/10 flex items-center justify-center gap-2 transition duration-300 transform hover:-translate-y-0.5">
                            <i class="fa-solid fa-file-pdf text-base"></i> ส่งออกรายงานเป็นไฟล์ PDF ทันที
                        </button>
                    </div>
                </form>
            </div>

            <!-- Notes -->
            <div class="mt-8 pt-6 border-t border-slate-800/80 text-[11px] text-slate-500 leading-relaxed font-light flex items-start gap-2">
                <i class="fa-solid fa-circle-info text-amber-500 mt-0.5 text-xs shrink-0"></i>
                <div>
                    รายงานทั้งหมดถูกจัดวางโครงสร้างอย่างพรีเมียม สไตล์งานสารบรรณองค์กร สำหรับการยื่นรายงานตรวจสอบ และการพิมพ์แนบแนวนอน/แนวตั้งของรูปแนบใบเสร็จ โดยระบบจะทำการบันทึกประวัติการสั่งปริ้นท์ลง **Report Print Log** โดยอัตโนมัติเพื่อความโปร่งใสตรวจสอบย้อนหลังได้ทุกเวลา
                </div>
            </div>
        </div>

    </div>
</div>
