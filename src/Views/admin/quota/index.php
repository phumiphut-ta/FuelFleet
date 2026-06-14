<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-coins text-indigo-400"></i> ควบคุมโควต้าน้ำมันรายรถ (Fuel Quota Console)
            </h1>
            <p class="text-xs text-slate-400 font-light mt-1">บริหารจัดการโควต้าน้ำมันรายเดือนจำกัดปริมาณลิตร คุมรายจ่ายพลังงานแต่ละคัน และบันทึกประวัติการปรับเวอร์ชันโควต้า</p>
        </div>
    </div>

    <!-- Alerts -->
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

    <!-- Split layout: Quick Form on Left, History List on Right -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- 1. Left 1 Col: Quick update form & active limits -->
        <div class="glass-card p-6 rounded-2xl glow-indigo flex flex-col justify-between">
            <div>
                <h3 class="text-sm font-semibold text-white border-b border-slate-800 pb-3 mb-5 flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-indigo-400"></i> ปรับปรุงโควต้าน้ำมันรถยนต์
                </h3>

                <form action="/admin/quotas/update" method="POST" class="space-y-4">
                    <!-- Vehicle Select -->
                    <div>
                        <label for="car_id" class="block text-xs font-semibold text-slate-400 mb-2">เลือกยานพาหนะหลวง</label>
                        <select id="car_id" name="car_id" required 
                            class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <option value="" disabled selected>-- เลือกทะเบียนรถยนต์ --</option>
                            <?php foreach ($cars as $car): ?>
                                <option value="<?= $car['id'] ?>">
                                    <?= htmlspecialchars($car['license_plate']) ?> (<?= htmlspecialchars($car['fuel_type']) ?>) - ปัจจุบัน: <?= number_format($currentQuotas[$car['id']], 2) ?> ลิตร
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Quota limit in liters -->
                    <div>
                        <label for="monthly_quota" class="block text-xs font-semibold text-slate-400 mb-2">จำกัดปริมาณน้ำมันโควต้า (ลิตร / เดือน)</label>
                        <input id="monthly_quota" name="monthly_quota" type="number" step="0.01" required placeholder="เช่น 300.00"
                            class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>

                    <!-- Effective Month -->
                    <div>
                        <label for="effective_month" class="block text-xs font-semibold text-slate-400 mb-2">เดือนที่มีผลบังคับใช้ (Effective Month)</label>
                        <select id="effective_month" name="effective_month" required 
                            class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <?php
                            $currentVal = date('Y-m');
                            $thaiMonths = [
                                1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
                                5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
                                9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
                            ];
                            // Generate months from 12 months ago to 24 months in the future
                            for ($i = -12; $i <= 24; $i++):
                                $time = strtotime("$i months");
                                $val = date('Y-m', $time);
                                $m = (int)date('n', $time);
                                $y = (int)date('Y', $time) + 543;
                                $label = $thaiMonths[$m] . ' ' . $y;
                            ?>
                                <option value="<?= $val ?>" <?= $val === $currentVal ? 'selected' : '' ?> class="bg-slate-950 text-slate-300">
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-xs font-semibold rounded-xl shadow-lg transition duration-200 transform hover:-translate-y-0.5">
                            <i class="fa-solid fa-save mr-1"></i> บันทึกโควต้าเวอร์ชันใหม่
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="mt-6 pt-6 border-t border-slate-800/80 text-[11px] text-slate-500 leading-relaxed font-light">
                <span class="font-bold text-slate-400"><i class="fa-solid fa-circle-info text-amber-500 mr-1"></i>เกณฑ์ระบบ Quota Versioning:</span> 
                เมื่อปรับโควต้าใหม่ ระบบจะไม่เขียนทับข้อมูลการจำกัดของเดือนก่อนหน้า แต่จะบันทึกเป็นประวัติเวอร์ชัน เพื่อให้รายงานและตัวคำนวณสถิติน้ำมันรายเดือนในอดีตแสดงผลได้อย่างเที่ยงตรงไม่เคลื่อนคลาด
            </div>
        </div>

        <!-- 2. Right 2 Cols: Historical Adjustments logs -->
        <div class="lg:col-span-2 glass-card p-6 rounded-2xl glow-indigo">
            <h3 class="text-sm font-semibold text-white border-b border-slate-800 pb-3 mb-5 flex items-center gap-2">
                <i class="fa-solid fa-history text-indigo-400"></i> ประวัติปรับเปลี่ยนน้ำมันโควต้าทั้งหมด (Quota History Versioning)
            </h3>

            <div class="overflow-x-auto max-h-[480px] overflow-y-auto pr-2">
                <table class="min-w-full divide-y divide-slate-800/60 text-left text-xs">
                    <thead class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3">ทะเบียนรถ</th>
                            <th class="py-3">ประเภทน้ำมัน</th>
                            <th class="py-3 text-right">โควต้าน้ำมันลิตร</th>
                            <th class="py-3 text-center">เดือนที่มีผลใช้</th>
                            <th class="py-3 text-right">วันที่ลงบันทึก</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40 text-slate-300 font-light">
                        <?php if (empty($quotas)): ?>
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-500">ไม่มีข้อมูลการปรับปรุงโควต้าย้อนหลัง</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($quotas as $q): ?>
                                <tr class="hover:bg-slate-800/10 transition">
                                    <td class="py-3 font-bold text-white"><?= htmlspecialchars($q['license_plate']) ?></td>
                                    <td class="py-3 text-slate-400"><?= htmlspecialchars($q['fuel_type']) ?></td>
                                    <td class="py-3 text-right font-bold text-indigo-400"><?= number_format($q['monthly_quota'], 2) ?> L</td>
                                    <td class="py-3 text-center font-medium"><?= date('M Y', strtotime($q['effective_month'])) ?></td>
                                    <td class="py-3 text-right text-slate-500"><?= date('d/m/Y H:i', strtotime($q['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
