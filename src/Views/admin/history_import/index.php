<div class="space-y-6" x-data="{ activeTab: 'fuel' }">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-file-import text-indigo-400"></i> บันทึกประวัติข้อมูลย้อนหลังจากระบบเดิม (Historical Data Console)
            </h1>
            <p class="text-xs text-slate-400 font-light mt-1">ใช้สำหรับการบันทึกประวัติสะสมเพื่อนำมาใช้แสดงผลเชิงสถิติเปรียบเทียบในส่วนของโควต้าน้ำมันรายเดือนและจุดหมายเดินทางยอดนิยม</p>
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

    <!-- Navigation Tabs -->
    <div class="flex border-b border-slate-800 space-x-2">
        <button type="button" @click="activeTab = 'fuel'" 
            :class="activeTab === 'fuel' ? 'border-indigo-500 text-indigo-400 bg-indigo-500/5' : 'border-transparent text-slate-400 hover:text-slate-200 hover:border-slate-700'"
            class="px-4 py-3 text-xs font-semibold border-b-2 transition duration-200 flex items-center gap-2 rounded-t-xl">
            <i class="fa-solid fa-gas-pump"></i> ประวัติการใช้น้ำมันรายเดือน
        </button>
        <button type="button" @click="activeTab = 'travel'" 
            :class="activeTab === 'travel' ? 'border-indigo-500 text-indigo-400 bg-indigo-500/5' : 'border-transparent text-slate-400 hover:text-slate-200 hover:border-slate-700'"
            class="px-4 py-3 text-xs font-semibold border-b-2 transition duration-200 flex items-center gap-2 rounded-t-xl">
            <i class="fa-solid fa-map-location-dot"></i> สถิติการเดินทางรายจังหวัด (รายปีงบประมาณ)
        </button>
    </div>

    <!-- TAB 1: Fuel Usage History -->
    <div x-show="activeTab === 'fuel'" x-transition class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form Card -->
            <div class="glass-card p-6 rounded-2xl glow-indigo space-y-4">
                <h3 class="text-sm font-semibold text-white border-b border-slate-800 pb-3 mb-2 flex items-center gap-1.5">
                    <i class="fa-solid fa-pen-to-square text-indigo-400"></i> บันทึกข้อมูลการใช้น้ำมันรายเดือน
                </h3>
                
                <form action="/admin/history-import/fuel" method="POST" class="space-y-4 text-xs">
                    <div>
                        <label for="car_id" class="block text-slate-400 font-medium mb-1.5">เลือกทะเบียนรถยนต์หลวง <span class="text-rose-500">*</span></label>
                        <select id="car_id" name="car_id" required
                            class="block w-full px-3 py-2 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition">
                            <option value="" disabled selected>-- เลือกทะเบียนรถ --</option>
                            <?php foreach ($cars as $car): ?>
                                <option value="<?= $car['id'] ?>"><?= htmlspecialchars($car['license_plate']) ?> (<?= htmlspecialchars($car['fuel_type']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="year" class="block text-slate-400 font-medium mb-1.5">ปี ค.ศ. คัดกรอง <span class="text-rose-500">*</span></label>
                            <select id="year" name="year" required
                                class="block w-full px-3 py-2 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition">
                                <?php
                                $currentYear = (int)date('Y');
                                for ($y = $currentYear; $y >= 2020; $y--):
                                ?>
                                    <option value="<?= $y ?>" <?= $y === $currentYear - 1 ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label for="month" class="block text-slate-400 font-medium mb-1.5">เดือน <span class="text-rose-500">*</span></label>
                            <select id="month" name="month" required
                                class="block w-full px-3 py-2 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition">
                                <option value="01">มกราคม (Jan)</option>
                                <option value="02">กุมภาพันธ์ (Feb)</option>
                                <option value="03">มีนาคม (Mar)</option>
                                <option value="04">เมษายน (Apr)</option>
                                <option value="05">พฤษภาคม (May)</option>
                                <option value="06">มิถุนายน (Jun)</option>
                                <option value="07">กรกฎาคม (Jul)</option>
                                <option value="08">สิงหาคม (Aug)</option>
                                <option value="09">กันยายน (Sep)</option>
                                <option value="10">ตุลาคม (Oct)</option>
                                <option value="11">พฤศจิกายน (Nov)</option>
                                <option value="12">ธันวาคม (Dec)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="liters" class="block text-slate-400 font-medium mb-1.5">ปริมาณน้ำมันรวม (ลิตร) <span class="text-rose-500">*</span></label>
                        <input type="number" id="liters" name="liters" step="0.01" min="0.01" required placeholder="เช่น 250.50"
                            class="block w-full px-3 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition">
                    </div>

                    <div>
                        <label for="amount" class="block text-slate-400 font-medium mb-1.5">ยอดเงินรวม (บาท) <span class="text-rose-500">*</span></label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" required placeholder="เช่น 7500.00"
                            class="block w-full px-3 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/10 flex items-center justify-center gap-1.5 transition">
                            <i class="fa-solid fa-circle-plus"></i> บันทึกข้อมูลประวัติน้ำมัน
                        </button>
                    </div>
                </form>
            </div>

            <!-- List Table Card -->
            <div class="lg:col-span-2 glass-card p-6 rounded-2xl glow-indigo space-y-4">
                <h3 class="text-sm font-semibold text-white border-b border-slate-800 pb-3 mb-2 flex items-center gap-1.5">
                    <i class="fa-solid fa-table text-indigo-400"></i> รายการสถิติการใช้น้ำมันย้อนหลังจากระบบเดิมที่บันทึกแล้ว
                </h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-[11px] text-slate-300 text-left border-collapse">
                        <thead>
                            <tr class="text-slate-400 border-b border-slate-800">
                                <th class="py-2.5 px-3">ทะเบียนรถ</th>
                                <th class="py-2.5 px-3">ประเภทน้ำมัน</th>
                                <th class="py-2.5 px-3 text-center">ปี/เดือน</th>
                                <th class="py-2.5 px-3 text-right">ปริมาณน้ำมัน (ลิตร)</th>
                                <th class="py-2.5 px-3 text-right">ยอดเงินรวม (บาท)</th>
                                <th class="py-2.5 px-3 text-center" style="width: 10%;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($fuelHistories)): ?>
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-500">ไม่มีข้อมูลสถิติน้ำมันย้อนหลังจากระบบเก่าถูกบันทึก</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($fuelHistories as $row): ?>
                                    <tr class="border-b border-slate-850 hover:bg-slate-900/10 transition">
                                        <td class="py-2 px-3 font-semibold text-white"><?= htmlspecialchars($row['license_plate']) ?></td>
                                        <td class="py-2 px-3"><?= htmlspecialchars($row['fuel_type']) ?></td>
                                        <td class="py-2 px-3 text-center"><?= date('Y/m', strtotime($row['receipt_date'])) ?></td>
                                        <td class="py-2 px-3 text-right font-mono text-emerald-400"><?= number_format($row['liters'], 2) ?> L</td>
                                        <td class="py-2 px-3 text-right font-mono text-indigo-400"><?= number_format($row['amount'], 2) ?> ฿</td>
                                        <td class="py-2 px-3 text-center">
                                            <form action="/admin/history-import/fuel/delete/<?= $row['id'] ?>" method="POST" onsubmit="return confirm('ยืนยันที่จะลบข้อมูลน้ำมันรายการนี้หรือไม่?')" class="inline">
                                                <button type="submit" class="p-1.5 text-rose-500 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition" title="ลบ">
                                                    <i class="fa-solid fa-trash text-xs"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: Province Travel History -->
    <div x-show="activeTab === 'travel'" x-transition class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form Card -->
            <div class="glass-card p-6 rounded-2xl glow-indigo space-y-4">
                <h3 class="text-sm font-semibold text-white border-b border-slate-800 pb-3 mb-2 flex items-center gap-1.5">
                    <i class="fa-solid fa-pen-to-square text-indigo-400"></i> บันทึกสถิติจังหวัดจุดหมายปลายทาง
                </h3>

                <form action="/admin/history-import/travel" method="POST" class="space-y-4 text-xs">
                    <div>
                        <label for="fy" class="block text-slate-400 font-medium mb-1.5">ปีงบประมาณ ค.ศ. คัดกรอง <span class="text-rose-500">*</span></label>
                        <select id="fy" name="fy" required
                            class="block w-full px-3 py-2 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition">
                            <?php
                            $currentYear = (int)date('Y');
                            for ($y = $currentYear; $y >= 2020; $y--):
                            ?>
                                <option value="<?= $y ?>" <?= $y === $currentYear - 1 ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div>
                        <label for="province" class="block text-slate-400 font-medium mb-1.5">จังหวัดปลายทาง <span class="text-rose-500">*</span></label>
                        <select id="province" name="province" required
                            class="block w-full px-3 py-2 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition">
                            <option value="" disabled selected>-- เลือกจังหวัด --</option>
                            <?php foreach ($provinces as $prov): ?>
                                <option value="<?= htmlspecialchars($prov) ?>"><?= htmlspecialchars($prov) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="count_trips" class="block text-slate-400 font-medium mb-1.5">จำนวนรอบที่เดินทางไป (ครั้ง) <span class="text-rose-500">*</span></label>
                        <input type="number" id="count_trips" name="count_trips" min="1" required placeholder="เช่น 25"
                            class="block w-full px-3 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/10 flex items-center justify-center gap-1.5 transition">
                            <i class="fa-solid fa-circle-plus"></i> บันทึกข้อมูลประวัติการเดินทาง
                        </button>
                    </div>
                </form>
            </div>

            <!-- List Table Card -->
            <div class="lg:col-span-2 glass-card p-6 rounded-2xl glow-indigo space-y-4">
                <h3 class="text-sm font-semibold text-white border-b border-slate-800 pb-3 mb-2 flex items-center gap-1.5">
                    <i class="fa-solid fa-table text-indigo-400"></i> รายการสถิติจังหวัดยอดนิยมย้อนหลังที่บันทึกแล้ว
                </h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-[11px] text-slate-300 text-left border-collapse">
                        <thead>
                            <tr class="text-slate-400 border-b border-slate-800">
                                <th class="py-2.5 px-3 text-center">ปีงบประมาณ ค.ศ.</th>
                                <th class="py-2.5 px-3">จังหวัดจุดหมาย</th>
                                <th class="py-2.5 px-3 text-center">จำนวนครั้งที่เดินทาง</th>
                                <th class="py-2.5 px-3 text-center" style="width: 10%;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($travelHistories)): ?>
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-slate-500">ไม่มีข้อมูลสถิติจังหวัดย้อนหลังถูกบันทึก</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($travelHistories as $row): ?>
                                    <tr class="border-b border-slate-850 hover:bg-slate-900/10 transition">
                                        <td class="py-2 px-3 text-center font-bold text-indigo-400"><?= $row['fy'] ?></td>
                                        <td class="py-2 px-3 font-semibold text-white"><?= htmlspecialchars($row['province']) ?></td>
                                        <td class="py-2 px-3 text-center font-bold text-emerald-400"><?= number_format($row['count_trips']) ?> เที่ยว</td>
                                        <td class="py-2 px-3 text-center">
                                            <form action="/admin/history-import/travel/delete" method="POST" onsubmit="return confirm('ยืนยันที่จะลบข้อมูลการเดินทางของจังหวัดนี้หรือไม่?')" class="inline">
                                                <input type="hidden" name="fy" value="<?= $row['fy'] ?>">
                                                <input type="hidden" name="province" value="<?= htmlspecialchars($row['province']) ?>">
                                                <button type="submit" class="p-1.5 text-rose-500 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition" title="ลบ">
                                                    <i class="fa-solid fa-trash text-xs"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
