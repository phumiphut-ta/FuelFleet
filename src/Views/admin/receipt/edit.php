<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-file-pen text-indigo-400"></i> แก้ไขรายละเอียดใบเสร็จรับเงินค่าน้ำมัน
            </h1>
            <p class="text-xs text-slate-400 font-light mt-1">ปรับปรุงข้อมูลใบเสร็จ ตรวจประเภทน้ำมัน หรืออัปโหลดไฟล์หลักฐานใบเสร็จเพิ่มเติมย้อนหลัง</p>
        </div>
        <a href="/admin/receipts" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/80 text-xs rounded-xl font-medium flex items-center gap-1.5 transition">
            <i class="fa-solid fa-arrow-left"></i> กลับหน้าประวัติใบเสร็จ
        </a>
    </div>

    <!-- Alert / Validation messages -->
    <?php if (!empty($error)): ?>
        <div class="bg-rose-500/15 border border-rose-500/30 text-rose-300 px-4 py-3 rounded-xl text-xs flex items-center space-x-2 animate-bounce">
            <i class="fa-solid fa-circle-exclamation text-sm text-rose-400"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Form Panel -->
    <div class="glass-panel p-8 rounded-2xl border border-slate-800/80 max-w-3xl mx-auto relative overflow-hidden"
        x-data="{ 
            amount: <?= (float)$receipt['amount'] ?>, 
            liters: <?= (float)$receipt['liters'] ?>,
            fileName: '<?= $receipt['file_path'] ? basename($receipt['file_path']) : '' ?>',
            fileSize: '',
            isPdf: <?= $receipt['file_path'] && str_ends_with(strtolower($receipt['file_path']), '.pdf') ? 'true' : 'false' ?>,
            get pricePerLiter() {
                if (this.liters > 0) {
                    return (this.amount / this.liters).toFixed(2);
                }
                return '0.00';
            },
            handleFileChange(event) {
                const file = event.target.files[0];
                if (file) {
                    this.fileName = file.name;
                    const sizeInMb = (file.size / (1024 * 1024)).toFixed(2);
                    this.fileSize = sizeInMb + ' MB';
                    this.isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
                } else {
                    this.fileName = '<?= $receipt['file_path'] ? basename($receipt['file_path']) : '' ?>';
                    this.fileSize = '';
                    this.isPdf = <?= $receipt['file_path'] && str_ends_with(strtolower($receipt['file_path']), '.pdf') ? 'true' : 'false' ?>;
                }
            }
        }">

        <form action="/admin/receipts/update/<?= $receipt['id'] ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Receipt Number -->
                <div>
                    <label for="receipt_number" class="block text-xs font-semibold text-slate-400 mb-2">เลขที่ใบเสร็จรับเงิน (ภาษี) <span class="text-rose-500">*</span></label>
                    <input id="receipt_number" name="receipt_number" type="text" required 
                        value="<?= htmlspecialchars($receipt['receipt_number']) ?>"
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200">
                </div>

                <!-- Receipt Date -->
                <div>
                    <label for="receipt_date" class="block text-xs font-semibold text-slate-400 mb-2">วันที่ระบุในใบเสร็จ <span class="text-rose-500">*</span></label>
                    <input id="receipt_date" name="receipt_date" type="date" required 
                        value="<?= htmlspecialchars($receipt['receipt_date']) ?>"
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>

                <!-- Employee (who filled it) -->
                <div>
                    <label for="employee_id" class="block text-xs font-semibold text-slate-400 mb-2">พนักงานที่ยื่นเติมน้ำมัน <span class="text-rose-500">*</span></label>
                    <?php
                    $groupedEmployees = [];
                    foreach ($employees as $emp) {
                        $divName = $emp['division_name'] ?: 'ส่วนกลาง / ไม่ระบุกอง';
                        $groupedEmployees[$divName][] = $emp;
                    }
                    ?>
                    <select id="employee_id" name="employee_id" required 
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <?php foreach ($groupedEmployees as $divName => $emps): ?>
                            <optgroup label="<?= htmlspecialchars($divName) ?>" class="text-[10px] font-semibold text-indigo-400 bg-slate-950">
                                <?php foreach ($emps as $emp): ?>
                                    <option value="<?= $emp['id'] ?>" <?= $emp['id'] == $receipt['employee_id'] ? 'selected' : '' ?> class="text-xs text-slate-300 bg-slate-950">
                                        <?= htmlspecialchars($emp['full_name']) ?> (<?= htmlspecialchars($emp['position_name'] ?? 'ไม่ระบุ') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Vehicle -->
                <div>
                    <label for="car_id" class="block text-xs font-semibold text-slate-400 mb-2">รถยนต์หลวงคันที่เติม <span class="text-rose-500">*</span></label>
                    <select id="car_id" name="car_id" required 
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <?php foreach ($cars as $car): ?>
                            <option value="<?= $car['id'] ?>" <?= $car['id'] == $receipt['car_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($car['license_plate']) ?> (รองรับ: <?= htmlspecialchars($car['fuel_type']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Amount in Baht -->
                <div>
                    <label for="amount" class="block text-xs font-semibold text-slate-400 mb-2">ยอดเงินรวมในใบเสร็จ (บาท) <span class="text-rose-500">*</span></label>
                    <input id="amount" name="amount" type="number" step="0.01" required placeholder="0.00" x-model.number="amount"
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>

                <!-- Liters -->
                <div>
                    <label for="liters" class="block text-xs font-semibold text-slate-400 mb-2">ปริมาณน้ำมันที่เติม (ลิตร) <span class="text-rose-500">*</span></label>
                    <input id="liters" name="liters" type="number" step="0.01" required placeholder="0.00" x-model.number="liters"
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>

                <!-- Optional Mileage -->
                <div>
                    <label for="mileage" class="block text-xs font-semibold text-slate-400 mb-2">เลขไมล์รถขณะเติมน้ำมัน (กิโลเมตร - ไม่บังคับ)</label>
                    <input id="mileage" name="mileage" type="number" placeholder="เช่น 24500" 
                        value="<?= htmlspecialchars($receipt['mileage'] ?? '') ?>"
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>

                <!-- Status Select -->
                <div>
                    <label for="status" class="block text-xs font-semibold text-slate-400 mb-2">สถานะใบเสร็จ <span class="text-rose-500">*</span></label>
                    <select id="status" name="status" required 
                        class="block w-full px-3.5 py-2.5 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <option value="Pending verification" <?= $receipt['status'] === 'Pending verification' ? 'selected' : '' ?>>รอตรวจอนุมัติ</option>
                        <option value="Verified" <?= $receipt['status'] === 'Verified' ? 'selected' : '' ?>>อนุมัติแล้ว</option>
                        <option value="Cancelled" <?= $receipt['status'] === 'Cancelled' ? 'selected' : '' ?>>ยกเลิกใบเสร็จ</option>
                    </select>
                </div>

                <!-- Auto calculated price per liter display -->
                <div class="md:col-span-2 bg-slate-900/30 border border-slate-850 p-4 rounded-xl flex items-center justify-between text-xs">
                    <span class="text-slate-400 font-light"><i class="fa-solid fa-calculator mr-1"></i> คำนวณราคาเฉลี่ยต่อลิตร (อัตโนมัติ):</span>
                    <strong class="text-indigo-400 font-bold text-sm"><span x-text="pricePerLiter"></span> บาท / ลิตร</strong>
                </div>

                <!-- Attachment image/document file -->
                <div class="md:col-span-2">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-semibold text-slate-400">
                            อัปเดต/สลับไฟล์หลักฐานสลิปใบเสร็จ (แนะนำไฟล์ PDF หรือไฟล์ภาพ JPG, PNG, WEBP)
                        </label>
                        <?php if ($receipt['file_path']): ?>
                            <span class="text-[10px] bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 px-2 py-0.5 rounded-full font-light">
                                มีหลักฐานเดิมอยู่แล้ว: <a href="<?= htmlspecialchars($receipt['file_path']) ?>" target="_blank" class="font-bold underline hover:text-indigo-300">คลิกเพื่อเปิดดู</a>
                            </span>
                        <?php else: ?>
                            <span class="text-[10px] bg-rose-500/10 border border-rose-500/20 text-rose-400 px-2 py-0.5 rounded-full font-light italic">
                                ยังไม่มีหลักฐานแนบในระบบ
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="relative group cursor-pointer">
                        <!-- Hidden file input but covers the entire area -->
                        <input id="receipt_image" name="receipt_image" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf"
                            @change="handleFileChange"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                        
                        <!-- Premium UI container -->
                        <div class="border-2 border-dashed rounded-2xl p-6 text-center transition-all duration-200"
                            :class="fileName ? (isPdf ? 'border-rose-500/40 bg-rose-950/5' : 'border-indigo-500/40 bg-indigo-950/5') : 'border-slate-800 bg-slate-950/40 hover:border-slate-700 hover:bg-slate-900/20'">
                            
                            <!-- Default State (No new file selected) -->
                            <div x-show="!fileName" class="space-y-3">
                                <div class="inline-flex items-center justify-center h-12 w-12 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 group-hover:text-indigo-400 group-hover:border-indigo-500/30 transition-all duration-200">
                                    <i class="fa-solid fa-cloud-arrow-up text-2xl text-slate-450 mr-0.5"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-slate-300">คลิก หรือลากไฟล์รูป/PDF ชุดใหม่มาวางเพื่อเปลี่ยนไฟล์แนบเดิม</p>
                                    <p class="text-[10px] text-slate-500 mt-1">หากไม่ต้องการแก้ไขหรือเปลี่ยนรูปภาพเดิม ให้ปล่อยฟิลด์นี้ว่างไว้ได้เลย</p>
                                </div>
                            </div>
                            
                            <!-- File Selected State (Or existing display) -->
                            <div x-show="fileName" class="space-y-3">
                                <div class="inline-flex items-center justify-center h-12 w-12 rounded-xl border transition-all duration-200"
                                    :class="isPdf ? 'bg-rose-500/10 border-rose-500/30 text-rose-400' : 'bg-indigo-500/10 border-indigo-500/30 text-indigo-400'">
                                    <i class="text-2xl" :class="isPdf ? 'fa-solid fa-file-pdf' : 'fa-regular fa-image'"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-200 truncate max-w-md mx-auto" x-text="fileName"></p>
                                    <p class="text-[10px] text-slate-550 mt-1" x-show="fileSize" x-text="'ขนาดไฟล์อัปเดต: ' + fileSize"></p>
                                </div>
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9px] font-semibold tracking-wider uppercase"
                                    :class="isPdf ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20'">
                                    <i class="fa-solid fa-circle-check text-[10px]"></i>
                                    <span x-text="isPdf ? 'เอกสาร PDF พร้อมเขียนทับ' : 'รูปภาพหลักฐานพร้อมเขียนทับ'"></span>
                                </div>
                                <p class="text-[10px] text-slate-500 hover:text-slate-400 transition underline cursor-pointer pt-1" @click.prevent="document.getElementById('receipt_image').value = ''; fileName = '<?= $receipt['file_path'] ? basename($receipt['file_path']) : '' ?>'; fileSize = ''; isPdf = <?= $receipt['file_path'] && str_ends_with(strtolower($receipt['file_path']), '.pdf') ? 'true' : 'false' ?>;">
                                    ยกเลิกการเลือกไฟล์ใหม่ (ใช้รูปเดิม)
                                </p>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="pt-6 border-t border-slate-800 flex items-center justify-end gap-3">
                <a href="/admin/receipts" class="px-5 py-2.5 border border-slate-850 hover:bg-slate-900 text-slate-400 hover:text-slate-200 text-xs font-semibold rounded-xl transition">
                    ยกเลิก
                </a>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-xs font-semibold rounded-xl transition shadow-lg transform hover:-translate-y-0.5">
                    <i class="fa-solid fa-save mr-1.5 text-xs"></i> บันทึกการแก้ไข
                </button>
            </div>
        </form>
    </div>
</div>
