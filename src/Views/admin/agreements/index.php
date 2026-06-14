<div class="space-y-6" x-data="{ editingId: null, editText: '' }">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-list-check text-indigo-400"></i> จัดการข้อตกลงและเงื่อนไขการจองรถยนต์
            </h1>
            <p class="text-xs text-slate-400 font-light mt-1">กำหนดสัญญารับทราบหรือข้อตกลงที่ผู้ใช้บริการต้องกดเช็คติ๊กยอมรับทุกข้อก่อนทำการยืนยันการจองรถยนต์ส่วนกลาง</p>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Add Agreement Form -->
        <div class="lg:col-span-1">
            <div class="glass-panel p-6 rounded-2xl border border-slate-800/80 sticky top-24 space-y-4">
                <h3 class="text-sm font-bold text-white flex items-center gap-1.5"><i class="fa-solid fa-plus-circle text-indigo-400"></i> เพิ่มข้อความข้อตกลง</h3>
                
                <form action="/admin/agreements/create" method="POST" class="space-y-4">
                    <div>
                        <label for="agreement_text" class="block text-[11px] font-semibold text-slate-400 mb-2">รายละเอียดเงื่อนไข / ข้อตกลง <span class="text-rose-500">*</span></label>
                        <textarea id="agreement_text" name="agreement_text" rows="4" required placeholder="เช่น ผู้ขับขี่ต้องทำความสะอาดเศษขยะในรถยนต์หลังใช้งานเสร็จ..."
                            class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"></textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-xs font-bold rounded-xl transition shadow-lg transform hover:-translate-y-0.5">
                        <i class="fa-solid fa-save mr-1 text-xs"></i> เพิ่มข้อตกลง
                    </button>
                </form>
            </div>
        </div>

        <!-- List View -->
        <div class="lg:col-span-2">
            <div class="glass-panel rounded-2xl border border-slate-850 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-800/60 text-left text-xs">
                        <thead class="bg-slate-900/40 text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4 w-16 text-center">ลำดับ</th>
                                <th class="px-6 py-4 w-24 text-center">จัดเรียง</th>
                                <th class="px-6 py-4">ข้อตกลงการจองรถยนต์</th>
                                <th class="px-6 py-4 text-right w-48">การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/40 bg-slate-900/10 text-slate-300">
                            <?php if (empty($agreements)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-slate-550 italic font-light">ยังไม่มีการกำหนดข้อตกลงในระบบ ปุ่มจองรถสาธารณะจะแสดงผลได้โดยไม่ต้องเช็คเลือกกล่องเงื่อนไข</td>
                                </tr>
                            <?php else: ?>
                                <?php $idx = 1; $total = count($agreements); foreach ($agreements as $a): ?>
                                    <tr class="hover:bg-slate-800/10 transition">
                                        <td class="px-6 py-4 text-center font-mono text-slate-500"><?= $idx ?></td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <?php if ($idx > 1): ?>
                                                    <form action="/admin/agreements/reorder" method="POST" class="inline">
                                                        <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                                        <input type="hidden" name="direction" value="up">
                                                        <button type="submit" class="p-1.5 bg-slate-850 hover:bg-indigo-600 text-slate-400 hover:text-white rounded-lg transition duration-200" title="เลื่อนขึ้น">
                                                            <i class="fa-solid fa-arrow-up text-[10px]"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="p-1.5 bg-slate-900/40 text-slate-700 rounded-lg cursor-not-allowed opacity-30" disabled>
                                                        <i class="fa-solid fa-arrow-up text-[10px]"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($idx < $total): ?>
                                                    <form action="/admin/agreements/reorder" method="POST" class="inline">
                                                        <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                                        <input type="hidden" name="direction" value="down">
                                                        <button type="submit" class="p-1.5 bg-slate-850 hover:bg-indigo-600 text-slate-400 hover:text-white rounded-lg transition duration-200" title="เลื่อนลง">
                                                            <i class="fa-solid fa-arrow-down text-[10px]"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="p-1.5 bg-slate-900/40 text-slate-700 rounded-lg cursor-not-allowed opacity-30" disabled>
                                                        <i class="fa-solid fa-arrow-down text-[10px]"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <!-- Normal View -->
                                            <div x-show="editingId !== <?= $a['id'] ?>" class="text-slate-200 leading-relaxed font-light">
                                                <?= htmlspecialchars($a['agreement_text']) ?>
                                            </div>
                                            
                                            <!-- Inline Edit View -->
                                            <div x-show="editingId === <?= $a['id'] ?>" class="space-y-2" style="display: none;">
                                                <form :id="'edit-form-' + <?= $a['id'] ?>" action="/admin/agreements/update/<?= $a['id'] ?>" method="POST" class="flex flex-col gap-2">
                                                    <textarea name="agreement_text" x-model="editText" rows="2" required
                                                        class="block w-full px-3 py-2 border border-slate-800 bg-slate-950 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition"></textarea>
                                                    <div class="flex gap-2 justify-end">
                                                        <button type="button" @click="editingId = null" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-[10px] font-semibold transition">ยกเลิก</button>
                                                        <button type="submit" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-[10px] font-bold transition">บันทึก</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap text-xs font-semibold" x-show="editingId !== <?= $a['id'] ?>">
                                            <button @click="editingId = <?= $a['id'] ?>; editText = `<?= htmlspecialchars(addslashes($a['agreement_text'])) ?>`"
                                                class="px-2.5 py-1.5 bg-indigo-500/10 text-indigo-400 hover:bg-indigo-500 hover:text-white rounded-lg transition mr-1">
                                                <i class="fa-solid fa-pen-to-square"></i> แก้ไข
                                            </button>
                                            <form action="/admin/agreements/delete/<?= $a['id'] ?>" method="POST" class="inline" onsubmit="return confirm('ยืนยันลบเงื่อนไขข้อตกลงนี้ออกจากระบบจองใช่หรือไม่?');">
                                                <button type="submit" class="px-2.5 py-1.5 bg-rose-500/10 text-rose-400 hover:bg-rose-500 hover:text-white rounded-lg transition">
                                                    <i class="fa-solid fa-trash"></i> ลบ
                                                </button>
                                            </form>
                                        </td>
                                        <!-- Keep column width when editing -->
                                        <td class="px-6 py-4 text-right whitespace-nowrap text-xs" x-show="editingId === <?= $a['id'] ?>" style="display: none;"></td>
                                    </tr>
                                    <?php $idx++; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
