<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-indigo-400"></i> แก้ไขข้อมูลคำขอจองใช้รถส่วนกลาง (Edit Booking)
            </h1>
            <p class="text-xs text-slate-400 font-light mt-1">อัปเดตรายละเอียดผู้จอง ทะเบียนรถยนต์หลวง วันเดินทาง หรือจังหวัดปลายทางภารกิจ</p>
        </div>
        <a href="/admin/bookings" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-750 text-xs rounded-xl font-medium flex items-center gap-1.5 transition">
            <i class="fa-solid fa-arrow-left"></i> กลับแผงควบคุมการจอง
        </a>
    </div>

    <!-- Alert / Validation messages -->
    <?php if (!empty($error)): ?>
        <div class="bg-rose-500/15 border border-rose-500/30 text-rose-300 px-4 py-3 rounded-xl text-xs flex items-center space-x-2 animate-pulse">
            <i class="fa-solid fa-circle-exclamation text-sm text-rose-400"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Form container -->
    <div class="glass-panel p-8 rounded-2xl border border-slate-800/80 max-w-4xl mx-auto relative overflow-hidden" 
        x-data="{ 
            provinceSearch: '',
            selectedProvinces: <?= htmlspecialchars(json_encode($booking['provinces'] ?? []), ENT_QUOTES, 'UTF-8') ?>,
            provincesList: <?= htmlspecialchars(json_encode($provinces), ENT_QUOTES, 'UTF-8') ?>,
            toggleProvince(prov) {
                if (this.selectedProvinces.includes(prov)) {
                    this.selectedProvinces = this.selectedProvinces.filter(p => p !== prov);
                } else {
                    this.selectedProvinces.push(prov);
                }
            }
        }">

        <form action="/admin/bookings/update/<?= $booking['id'] ?>" method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Booker (Employee) -->
                <div>
                    <label for="employee_id" class="block text-xs font-semibold text-slate-400 mb-2">ผู้จองใช้งาน (พนักงาน) <span class="text-rose-500">*</span></label>
                    <?php
                    $groupedEmployees = [];
                    foreach ($employees as $emp) {
                        $divName = $emp['division_name'] ?: 'ส่วนกลาง / ไม่ระบุกอง';
                        $groupedEmployees[$divName][] = $emp;
                    }
                    ?>
                    <select id="employee_id" name="employee_id" required 
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <option value="" disabled>-- เลือกชื่อผู้จอง --</option>
                        <?php foreach ($groupedEmployees as $divName => $emps): ?>
                            <optgroup label="<?= htmlspecialchars($divName) ?>" class="text-[10px] font-semibold text-indigo-400 bg-slate-950">
                                <?php foreach ($emps as $emp): ?>
                                    <option value="<?= $emp['id'] ?>" <?= $booking['employee_id'] == $emp['id'] ? 'selected' : '' ?> class="text-xs text-slate-300 bg-slate-950">
                                        [<?= htmlspecialchars($emp['employee_code']) ?>] <?= htmlspecialchars($emp['full_name']) ?> (<?= htmlspecialchars($emp['position_name'] ?? 'พนักงาน') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Vehicle -->
                <div>
                    <label for="car_id" class="block text-xs font-semibold text-slate-400 mb-2">ระบุรถยนต์ราชการที่จะใช้เดินทาง <span class="text-rose-500">*</span></label>
                    <select id="car_id" name="car_id" required 
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <option value="" disabled>-- เลือกยานพาหนะ --</option>
                        <?php foreach ($cars as $car): ?>
                            <option value="<?= $car['id'] ?>" <?= $booking['car_id'] == $car['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($car['license_plate']) ?> - รองรับ [<?= htmlspecialchars($car['fuel_type']) ?>] <?= htmlspecialchars($car['note'] ? "({$car['note']})" : "") ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Start Date -->
                <div>
                    <label for="start_time" class="block text-xs font-semibold text-slate-400 mb-2">วันที่เริ่มเดินทาง <span class="text-rose-500">*</span></label>
                    <input id="start_time" name="start_time" type="date" required 
                        value="<?= htmlspecialchars(date('Y-m-d', strtotime($booking['start_time']))) ?>"
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>

                <!-- End Date -->
                <div>
                    <label for="end_time" class="block text-xs font-semibold text-slate-400 mb-2">วันที่เดินทางกลับ <span class="text-rose-500">*</span></label>
                    <input id="end_time" name="end_time" type="date" required 
                        value="<?= htmlspecialchars(date('Y-m-d', strtotime($booking['end_time']))) ?>"
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>

                <!-- Searchable checklist of destination provinces -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-400 mb-2">ระบุจังหวัดปลายทาง (เดินทางข้ามจังหวัดสามารถเลือกได้หลายรายการ) <span class="text-rose-500">*</span></label>
                    
                    <div class="border border-slate-850 rounded-2xl bg-slate-950/30 p-5 space-y-4">
                        <!-- Search field -->
                        <div class="relative w-full">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                                <i class="fa-solid fa-magnifying-glass text-xs"></i>
                            </div>
                            <input x-model="provinceSearch" type="text" placeholder="พิมพ์ชื่อจังหวัดเพื่อกรองค้นหาตัวเลือก..."
                                class="block w-full pl-9 pr-4 py-2 border border-slate-800/80 bg-slate-950/80 rounded-xl text-xs text-slate-300 placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        </div>

                        <!-- Selected provinces tags -->
                        <div class="flex flex-wrap gap-1.5 min-h-[32px] p-2 bg-slate-900/30 border border-slate-850 rounded-xl">
                            <template x-if="selectedProvinces.length === 0">
                                <span class="text-[11px] text-slate-550 font-light italic px-2">ยังไม่ได้เลือกจังหวัดปลายทาง</span>
                            </template>
                            <template x-for="prov in selectedProvinces" :key="prov">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-indigo-500/10 text-indigo-300 border border-indigo-500/25 text-[11px]">
                                    <span x-text="prov"></span>
                                    <button type="button" @click="toggleProvince(prov)" class="ml-1.5 text-indigo-400 hover:text-white transition"><i class="fa-solid fa-xmark text-[10px]"></i></button>
                                </span>
                            </template>
                        </div>

                        <!-- Scrollable provinces checklist -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 max-h-[160px] overflow-y-auto pr-2 border-t border-slate-850 pt-4">
                            <template x-for="prov in provincesList" :key="prov">
                                <label x-show="provinceSearch === '' || prov.toLowerCase().includes(provinceSearch.toLowerCase())"
                                    class="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer p-1.5 rounded-lg hover:bg-slate-900/40 select-none transition">
                                    <input type="checkbox" name="provinces[]" :value="prov" :checked="selectedProvinces.includes(prov)"
                                        @change="toggleProvince(prov)"
                                        class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0 transition">
                                    <span x-text="prov" :class="selectedProvinces.includes(prov) ? 'text-indigo-400 font-semibold' : 'text-slate-400'"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Purpose -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-400 mb-2">จุดประสงค์ภารกิจในการเดินทางไปราชการ <span class="text-rose-500">*</span></label>
                    <textarea id="purpose" name="purpose" rows="3" required placeholder="ระบุภารกิจในการขอยืมยานพาหนะ เช่น เดินทางไปจัดสัมมนาฝึกอบรม ณ สำนักงานสาขา, ออกตรวจวัดคุณภาพน้ำเสียประจำปี..."
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"><?= htmlspecialchars($booking['purpose'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Submit buttons -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-800/40">
                <a href="/admin/bookings" class="px-5 py-2.5 bg-slate-850 hover:bg-slate-800 text-slate-330 border border-slate-800 hover:border-slate-700 text-xs rounded-xl font-semibold transition">
                    ยกเลิกและย้อนกลับ
                </a>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-xs rounded-xl font-bold shadow-lg shadow-indigo-500/10 transition transform hover:-translate-y-0.5">
                    บันทึกการแก้ไข
                </button>
            </div>
        </form>
    </div>
</div>
