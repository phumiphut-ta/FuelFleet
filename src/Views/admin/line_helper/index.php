<div class="space-y-6" x-data="{ activeTab: 'builder', copied: false }">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2.5">
                <i class="fa-solid fa-bullhorn text-indigo-400"></i> ตัวช่วยเตรียมข้อความแจ้งเตือนกลุ่ม LINE
            </h1>
            <p class="text-xs text-slate-400 font-light mt-1">คัดกรองยานพาหนะที่ใกล้หมดโควต้าประจำเดือนปัจจุบัน และเตรียมข้อความอัปเดตประกาศสำหรับกลุ่มแชตได้อย่างง่ายดาย</p>
        </div>
    </div>

    <!-- Feedback messages -->
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

    <!-- Tabs switcher buttons -->
    <div class="flex border-b border-slate-800/60 gap-4">
        <button type="button" @click="activeTab = 'builder'"
            class="pb-3 text-xs font-bold transition focus:outline-none flex items-center gap-2 border-b-2"
            :class="activeTab === 'builder' ? 'text-indigo-400 border-indigo-500' : 'text-slate-500 border-transparent hover:text-slate-300'">
            <i class="fa-solid fa-message"></i> สร้างข้อความประกาศ
        </button>
        <button type="button" @click="activeTab = 'settings'"
            class="pb-3 text-xs font-bold transition focus:outline-none flex items-center gap-2 border-b-2"
            :class="activeTab === 'settings' ? 'text-indigo-400 border-indigo-500' : 'text-slate-500 border-transparent hover:text-slate-300'">
            <i class="fa-solid fa-sliders"></i> ตั้งค่าเทมเพลตและเกณฑ์ลิตรคงเหลือ
        </button>
    </div>

    <!-- Tab Content: Message Builder -->
    <div x-show="activeTab === 'builder'" class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <!-- Live Preview Block -->
        <div class="lg:col-span-2 space-y-4">
            <div class="glass-panel p-6 rounded-2xl border border-slate-800/80 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-300 flex items-center gap-1.5">
                        <i class="fa-regular fa-eye text-indigo-450"></i> ตัวอย่างข้อความที่จะคัดลอก (Live Preview)
                    </h3>
                    <span class="text-[9px] px-1.5 py-0.5 rounded bg-indigo-500/10 text-indigo-450 border border-indigo-500/20 font-mono">UTF-8</span>
                </div>
                
                <div class="relative">
                    <div class="bg-slate-950/70 border border-slate-800/80 rounded-xl p-4 font-mono text-[11px] text-slate-300 leading-relaxed whitespace-pre-wrap select-all max-h-[360px] overflow-y-auto border-dashed scrollbar-thin"
                         id="line-message-preview"><?= htmlspecialchars($interpolatedMessage) ?></div>
                </div>

                <button type="button" 
                        @click="navigator.clipboard.writeText(document.getElementById('line-message-preview').innerText).then(() => { copied = true; setTimeout(() => copied = false, 2500) })" 
                        class="w-full flex items-center justify-center gap-2 py-3 bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white rounded-xl text-xs font-bold transition shadow-lg transform hover:-translate-y-0.5 active:translate-y-0 duration-200">
                    <template x-if="!copied">
                        <span class="flex items-center gap-2">
                            <i class="fa-regular fa-copy text-sm"></i> คัดลอกข้อความสำหรับ LINE
                        </span>
                    </template>
                    <template x-if="copied">
                        <span class="flex items-center gap-2 text-emerald-100 animate-pulse font-extrabold">
                            <i class="fa-solid fa-circle-check text-sm text-emerald-300"></i> คัดลอกสำเร็จ! วางในกลุ่ม LINE ได้เลย
                        </span>
                    </template>
                </button>
            </div>
        </div>

        <!-- Matching Cars Table -->
        <div class="lg:col-span-3 space-y-4">
            <div class="glass-panel p-6 rounded-2xl border border-slate-800/80 space-y-4">
                <h3 class="text-xs font-bold text-slate-350 flex items-center gap-1.5">
                    <i class="fa-solid fa-triangle-exclamation text-rose-400"></i> รายการรถยนต์ที่โควต้าเหลือต่ำกว่าเกณฑ์
                </h3>

                <div class="border border-slate-850 rounded-xl overflow-hidden">
                    <table class="min-w-full divide-y divide-slate-800/60 text-left text-xs">
                        <thead class="bg-slate-900/40 text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3.5">ทะเบียนรถ</th>
                                <th class="px-5 py-3.5">ประเภทน้ำมัน</th>
                                <th class="px-5 py-3.5 text-center">โควต้าเดือนนี้</th>
                                <th class="px-5 py-3.5 text-center">ใช้ไปแล้ว</th>
                                <th class="px-5 py-3.5 text-center">คงเหลือ</th>
                                <th class="px-5 py-3.5 text-center">เกณฑ์แจ้งเตือน</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/40 bg-slate-900/10 text-slate-300">
                            <?php if (empty($lowQuotaCars)): ?>
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center text-slate-500 italic font-light">
                                        ไม่มีรถยนต์ที่ปริมาณน้ำมันคงเหลือต่ำกว่าเกณฑ์ในเดือนปัจจุบัน
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($lowQuotaCars as $car): ?>
                                    <?php 
                                        $remaining = $car['quota_liters'] - $car['used_liters'];
                                    ?>
                                    <tr class="hover:bg-slate-800/10 transition">
                                        <td class="px-5 py-3.5 font-bold text-slate-100 flex items-center gap-2">
                                            <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                            <?= htmlspecialchars($car['license_plate']) ?>
                                        </td>
                                        <td class="px-5 py-3.5 text-slate-400"><?= htmlspecialchars($car['fuel_type']) ?></td>
                                        <td class="px-5 py-3.5 text-center font-mono text-slate-400"><?= number_format($car['quota_liters'], 2) ?> ลิตร</td>
                                        <td class="px-5 py-3.5 text-center font-mono text-amber-400"><?= number_format($car['used_liters'], 2) ?> ลิตร</td>
                                        <td class="px-5 py-3.5 text-center font-mono text-rose-400 font-bold"><?= number_format($remaining, 2) ?> ลิตร</td>
                                        <td class="px-5 py-3.5 text-center font-mono text-slate-400"><?= number_format($car['threshold'], 2) ?> ลิตร</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Content: Settings & Rules -->
    <div x-show="activeTab === 'settings'" class="glass-panel p-6 rounded-2xl border border-slate-800/80">
        <form action="/admin/line-helper/save" method="POST" class="space-y-6">
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Template Setting Area -->
                <div class="lg:col-span-1 space-y-4">
                    <div class="space-y-2">
                        <h3 class="text-sm font-bold text-white flex items-center gap-1.5">
                            <i class="fa-solid fa-file-pen text-indigo-400"></i> ปรับแต่งข้อความประกาศ
                        </h3>
                        <p class="text-[11px] text-slate-500 font-light leading-relaxed">
                            คุณสามารถกำหนดเนื้อความประกาศต้นแบบได้ตามต้องการ โดยวางตัวแปรต่อไปนี้ในเนื้อความเพื่อให้ระบบแทนค่าอัตโนมัติ:
                        </p>
                        <div class="bg-slate-950/40 border border-slate-850 rounded-xl p-3 text-[10px] text-slate-400 font-mono space-y-1.5">
                            <div><strong class="text-indigo-400">{date}</strong> = วันที่ปัจจุบันภาษาไทย (เช่น 23 พ.ค. 2568)</div>
                            <div><strong class="text-indigo-400">{month_year}</strong> = เดือนปีงบปัจจุบัน (เช่น พฤษภาคม 2568)</div>
                            <div><strong class="text-indigo-400">{vehicle_list}</strong> = แสดงรายการรถยนต์ที่น้ำมันคงเหลือน้อยกว่าเกณฑ์แจ้งเตือน</div>
                            <div class="border-t border-slate-850/80 pt-1.5 mt-1.5 text-[9px] text-slate-500 font-sans">ระบุสถิติเจาะจงรายคัน (แทนคำว่า ทะเบียนรถ ด้วยทะเบียนจริง เช่น กข-1234):</div>
                            <div><strong class="text-indigo-400">{used:ทะเบียนรถ}</strong> = ใช้ไป (เช่น {used:กข-1234})</div>
                            <div><strong class="text-indigo-400">{quota:ทะเบียนรถ}</strong> = โควต้าทั้งหมด (เช่น {quota:กข-1234})</div>
                            <div><strong class="text-indigo-400">{remaining:ทะเบียนรถ}</strong> = คงเหลือ (เช่น {remaining:กข-1234})</div>
                        </div>
                    </div>

                    <div>
                        <label for="template_text" class="block text-[11px] font-semibold text-slate-400 mb-2">ข้อความเทมเพลตประกาศกลุ่ม <span class="text-rose-500">*</span></label>
                        <textarea id="template_text" name="template_text" rows="12" required
                            class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 font-sans leading-relaxed"><?= htmlspecialchars($template) ?></textarea>
                    </div>
                </div>

                <!-- Vehicles Specific Limits -->
                <div class="lg:col-span-2 space-y-4">
                    <h3 class="text-sm font-bold text-white flex items-center gap-1.5">
                        <i class="fa-solid fa-car-cog text-indigo-400"></i> กำหนดเกณฑ์น้ำมันคงเหลือต่ำสุด (แยกรายคัน)
                    </h3>
                    <p class="text-[11px] text-slate-500 font-light">
                        ระบุขีดจำกัดปริมาณน้ำมันคงเหลือต่ำสุด (หน่วยเป็นลิตร) ที่ต้องการแจ้งเตือนของรถแต่ละคัน (หากน้ำมันคงเหลือต่ำกว่าหรือเท่ากับค่านี้ รถคันนั้นจะเข้าเงื่อนไขประกาศทันที)
                    </p>

                    <div class="border border-slate-850 rounded-xl overflow-hidden max-h-[440px] overflow-y-auto scrollbar-thin">
                        <table class="min-w-full divide-y divide-slate-800/60 text-left text-xs">
                            <thead class="bg-slate-900/40 text-[10px] text-slate-500 font-bold uppercase tracking-wider sticky top-0 z-10">
                                <tr>
                                    <th class="px-5 py-3.5 bg-slate-900">ทะเบียนรถ</th>
                                    <th class="px-5 py-3.5 bg-slate-900">ประเภทน้ำมัน</th>
                                    <th class="px-5 py-3.5 bg-slate-900 text-center">สถานะใช้งาน</th>
                                    <th class="px-5 py-3.5 bg-slate-900 text-right w-48">เกณฑ์แจ้งเตือนน้ำมันคงเหลือ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/40 bg-slate-900/10 text-slate-300">
                                <?php if (empty($allCars)): ?>
                                    <tr>
                                        <td colspan="4" class="px-5 py-12 text-center text-slate-550 italic font-light">ไม่มีประวัติรายการยานพาหนะในระบบ</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($allCars as $car): ?>
                                        <tr class="hover:bg-slate-800/10 transition">
                                            <td class="px-5 py-3.5 font-semibold text-slate-200"><?= htmlspecialchars($car['license_plate']) ?></td>
                                            <td class="px-5 py-3.5 text-slate-400"><?= htmlspecialchars($car['fuel_type']) ?></td>
                                            <td class="px-5 py-3.5 text-center">
                                                <?php if ($car['status'] === 'Active'): ?>
                                                    <span class="px-2 py-0.5 text-[10px] bg-emerald-500/10 text-emerald-400 rounded-full border border-emerald-500/25">พร้อมใช้งาน</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 text-[10px] bg-amber-500/10 text-amber-400 rounded-full border border-amber-500/25">ระงับการจอง</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-5 py-2.5 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <input type="number" step="0.01" min="0" required
                                                           name="thresholds[<?= $car['id'] ?>]" 
                                                           value="<?= htmlspecialchars($car['threshold']) ?>"
                                                           class="w-24 px-2 py-1.5 border border-slate-800 bg-slate-950/70 rounded-lg text-center text-xs text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition font-mono">
                                                    <span class="text-xs text-slate-500 font-light">ลิตร</span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Submit Settings Form -->
            <div class="flex items-center justify-end border-t border-slate-800/60 pt-4">
                <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-xs font-bold rounded-xl transition shadow-lg transform hover:-translate-y-0.5">
                    <i class="fa-solid fa-save mr-1 text-xs"></i> บันทึกการตั้งค่าทั้งหมด
                </button>
            </div>
        </form>
    </div>
</div>
