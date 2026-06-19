<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2.5">
                <i class="fa-brands fa-discord text-indigo-400 text-3xl"></i> ตั้งค่าการแจ้งเตือน Discord Webhook
            </h1>
            <p class="text-xs text-slate-400 font-light mt-1">กำหนดช่องทางเชื่อมต่อ Webhook URL เพื่อนำส่งข้อความแจ้งเตือนสถานะการทำงานขององค์กรร่วมกับช่องทาง Discord</p>
        </div>
    </div>

    <!-- Alert Messages -->
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

    <!-- Configuration Form -->
    <form action="/admin/discord-settings/save" method="POST" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= \App\Core\Csrf::generateToken() ?>">

        <!-- ========================================== -->
        <!-- CATEGORY: OPERATIONS (🟢 GREEN THEME)     -->
        <!-- ========================================== -->
        <div class="glass-panel p-6 rounded-2xl border border-emerald-500/20 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-500/5 rounded-full blur-2xl pointer-events-none"></div>
            
            <h2 class="text-sm font-bold text-emerald-400 flex items-center gap-2 mb-6">
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                [🟢 OPERATIONS] - สำหรับการจัดการรถและคำสั่งจอง
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Channel 1: #booking-alerts -->
                <div class="bg-slate-900/40 border border-slate-850 p-5 rounded-xl space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold text-slate-200 flex items-center gap-1.5">
                            <i class="fa-regular fa-calendar-check text-indigo-400"></i> แชแนล #booking-alerts
                        </h3>
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-400 mb-1.5">Discord Webhook URL</label>
                        <input type="url" name="channels[booking_alerts][webhook_url]" 
                            value="<?= htmlspecialchars($settings['channels']['booking_alerts']['webhook_url'] ?? '') ?>" 
                            placeholder="https://discord.com/api/webhooks/..."
                            class="block w-full px-3 py-2 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between border-t border-slate-850/60 pt-2 text-xs">
                            <span class="text-slate-400">เรื่องที่ 1: การจองรถใหม่ (New Bookings)</span>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="channels[booking_alerts][topics][new_booking]" value="1" <?= ($settings['channels']['booking_alerts']['topics']['new_booking'] ?? '0') === '1' ? 'checked' : '' ?> class="accent-emerald-500">
                                    <span class="text-[10px] text-slate-300">เปิด</span>
                                </label>
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="channels[booking_alerts][topics][new_booking]" value="0" <?= ($settings['channels']['booking_alerts']['topics']['new_booking'] ?? '0') === '0' ? 'checked' : '' ?> class="accent-slate-500">
                                    <span class="text-[10px] text-slate-500">ปิด</span>
                                </label>
                            </div>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-850/60 pt-2 text-xs">
                            <span class="text-slate-400">เรื่องที่ 2: การยกเลิกการจอง (Booking Cancellations)</span>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="channels[booking_alerts][topics][cancel_booking]" value="1" <?= ($settings['channels']['booking_alerts']['topics']['cancel_booking'] ?? '0') === '1' ? 'checked' : '' ?> class="accent-emerald-500">
                                    <span class="text-[10px] text-slate-300">เปิด</span>
                                </label>
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="channels[booking_alerts][topics][cancel_booking]" value="0" <?= ($settings['channels']['booking_alerts']['topics']['cancel_booking'] ?? '0') === '0' ? 'checked' : '' ?> class="accent-slate-500">
                                    <span class="text-[10px] text-slate-500">ปิด</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Channel 2: #vehicle-status -->
                <div class="bg-slate-900/40 border border-slate-850 p-5 rounded-xl space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold text-slate-200 flex items-center gap-1.5">
                            <i class="fa-solid fa-ban text-rose-450"></i> แชแนล #vehicle-status
                        </h3>
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-400 mb-1.5">Discord Webhook URL</label>
                        <input type="url" name="channels[vehicle_status][webhook_url]" 
                            value="<?= htmlspecialchars($settings['channels']['vehicle_status']['webhook_url'] ?? '') ?>" 
                            placeholder="https://discord.com/api/webhooks/..."
                            class="block w-full px-3 py-2 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between border-t border-slate-850/60 pt-2 text-xs">
                            <span class="text-slate-400">เรื่องที่ 3: สถานะระงับการใช้รถ (Suspensions)</span>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="channels[vehicle_status][topics][suspension]" value="1" <?= ($settings['channels']['vehicle_status']['topics']['suspension'] ?? '0') === '1' ? 'checked' : '' ?> class="accent-emerald-500">
                                    <span class="text-[10px] text-slate-300">เปิด</span>
                                </label>
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="channels[vehicle_status][topics][suspension]" value="0" <?= ($settings['channels']['vehicle_status']['topics']['suspension'] ?? '0') === '0' ? 'checked' : '' ?> class="accent-slate-500">
                                    <span class="text-[10px] text-slate-500">ปิด</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- CATEGORY: FUEL & FINANCE (🟡 YELLOW THEME) -->
        <!-- ========================================== -->
        <div class="glass-panel p-6 rounded-2xl border border-amber-500/20 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-amber-500/5 rounded-full blur-2xl pointer-events-none"></div>
            
            <h2 class="text-sm font-bold text-amber-400 flex items-center gap-2 mb-6">
                <span class="h-2.5 w-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                [🟡 FUEL & FINANCE] - สำหรับเรื่องโควต้าน้ำมันและค่าใช้จ่าย
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Channel 3: #fuel-quotas -->
                <div class="bg-slate-900/40 border border-slate-850 p-5 rounded-xl space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold text-slate-200 flex items-center gap-1.5">
                            <i class="fa-solid fa-gas-pump text-amber-450"></i> แชแนล #fuel-quotas
                        </h3>
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-400 mb-1.5">Discord Webhook URL</label>
                        <input type="url" name="channels[fuel_quotas][webhook_url]" 
                            value="<?= htmlspecialchars($settings['channels']['fuel_quotas']['webhook_url'] ?? '') ?>" 
                            placeholder="https://discord.com/api/webhooks/..."
                            class="block w-full px-3 py-2 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-amber-500">
                    </div>
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between border-t border-slate-850/60 pt-2 text-xs">
                            <span class="text-slate-400">เรื่องที่ 4: โควต้าน้ำมันใกล้หมด (Low Quotas)</span>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="channels[fuel_quotas][topics][quota_low]" value="1" <?= ($settings['channels']['fuel_quotas']['topics']['quota_low'] ?? '0') === '1' ? 'checked' : '' ?> class="accent-amber-500">
                                    <span class="text-[10px] text-slate-300">เปิด</span>
                                </label>
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="channels[fuel_quotas][topics][quota_low]" value="0" <?= ($settings['channels']['fuel_quotas']['topics']['quota_low'] ?? '0') === '0' ? 'checked' : '' ?> class="accent-slate-500">
                                    <span class="text-[10px] text-slate-500">ปิด</span>
                                </label>
                            </div>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-850/60 pt-2 text-xs">
                            <span class="text-slate-400">เรื่องที่ 5: โควต้าเต็ม/เกินกำหนด (Exceeded Quotas)</span>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="channels[fuel_quotas][topics][quota_over]" value="1" <?= ($settings['channels']['fuel_quotas']['topics']['quota_over'] ?? '0') === '1' ? 'checked' : '' ?> class="accent-amber-500">
                                    <span class="text-[10px] text-slate-300">เปิด</span>
                                </label>
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="channels[fuel_quotas][topics][quota_over]" value="0" <?= ($settings['channels']['fuel_quotas']['topics']['quota_over'] ?? '0') === '0' ? 'checked' : '' ?> class="accent-slate-500">
                                    <span class="text-[10px] text-slate-500">ปิด</span>
                                </label>
                            </div>
                        </div>
                        <div class="border-t border-slate-850/60 pt-3 space-y-1.5">
                            <label class="block text-[10px] font-semibold text-slate-400">รอบเวลาการแจ้งเตือนซ้ำ (สำหรับโควต้าน้ำมันใกล้หมด)</label>
                            <select name="channels[fuel_quotas][alert_cycle]" class="block w-full px-2 py-1.5 border border-slate-800 bg-slate-950/60 rounded-xl text-[10px] text-slate-300 focus:outline-none focus:ring-1 focus:ring-amber-500">
                                <option value="always" <?= ($settings['channels']['fuel_quotas']['alert_cycle'] ?? 'always') === 'always' ? 'selected' : '' ?>>แจ้งเตือนทุกครั้งที่มีการบันทึกใบเสร็จ</option>
                                <option value="1hour" <?= ($settings['channels']['fuel_quotas']['alert_cycle'] ?? 'always') === '1hour' ? 'selected' : '' ?>>แจ้งเตือนซ้ำห่างกันอย่างน้อย 1 ชั่วโมง</option>
                                <option value="6hours" <?= ($settings['channels']['fuel_quotas']['alert_cycle'] ?? 'always') === '6hours' ? 'selected' : '' ?>>แจ้งเตือนซ้ำห่างกันอย่างน้อย 6 ชั่วโมง</option>
                                <option value="12hours" <?= ($settings['channels']['fuel_quotas']['alert_cycle'] ?? 'always') === '12hours' ? 'selected' : '' ?>>แจ้งเตือนซ้ำห่างกันอย่างน้อย 12 ชั่วโมง</option>
                                <option value="24hours" <?= ($settings['channels']['fuel_quotas']['alert_cycle'] ?? 'always') === '24hours' ? 'selected' : '' ?>>แจ้งเตือนซ้ำห่างกันอย่างน้อย 24 ชั่วโมง (วันละครั้ง)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Channel 4: #receipt-approvals -->
                <div class="bg-slate-900/40 border border-slate-850 p-5 rounded-xl space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold text-slate-200 flex items-center gap-1.5">
                            <i class="fa-solid fa-file-invoice-dollar text-emerald-400"></i> แชแนล #receipt-approvals
                        </h3>
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-400 mb-1.5">Discord Webhook URL</label>
                        <input type="url" name="channels[receipt_approvals][webhook_url]" 
                            value="<?= htmlspecialchars($settings['channels']['receipt_approvals']['webhook_url'] ?? '') ?>" 
                            placeholder="https://discord.com/api/webhooks/..."
                            class="block w-full px-3 py-2 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-amber-500">
                    </div>
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between border-t border-slate-850/60 pt-2 text-xs">
                            <span class="text-slate-400">เรื่องที่ 6: มีใบเสร็จรอตรวจสอบ (Receipt Uploaded)</span>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="channels[receipt_approvals][topics][receipt_pending]" value="1" <?= ($settings['channels']['receipt_approvals']['topics']['receipt_pending'] ?? '0') === '1' ? 'checked' : '' ?> class="accent-amber-500">
                                    <span class="text-[10px] text-slate-300">เปิด</span>
                                </label>
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="channels[receipt_approvals][topics][receipt_pending]" value="0" <?= ($settings['channels']['receipt_approvals']['topics']['receipt_pending'] ?? '0') === '0' ? 'checked' : '' ?> class="accent-slate-500">
                                    <span class="text-[10px] text-slate-500">ปิด</span>
                                </label>
                            </div>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-850/60 pt-2 text-xs">
                            <span class="text-slate-400">เรื่องที่ 7: ผลการตรวจสอบใบเสร็จ (Approval Results)</span>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="channels[receipt_approvals][topics][receipt_status]" value="1" <?= ($settings['channels']['receipt_approvals']['topics']['receipt_status'] ?? '0') === '1' ? 'checked' : '' ?> class="accent-amber-500">
                                    <span class="text-[10px] text-slate-300">เปิด</span>
                                </label>
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="channels[receipt_approvals][topics][receipt_status]" value="0" <?= ($settings['channels']['receipt_approvals']['topics']['receipt_status'] ?? '0') === '0' ? 'checked' : '' ?> class="accent-slate-500">
                                    <span class="text-[10px] text-slate-500">ปิด</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- CATEGORY: SYSTEM & DEV (🔴 RED THEME)       -->
        <!-- ========================================== -->
        <div class="glass-panel p-6 rounded-2xl border border-rose-500/20 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-rose-500/5 rounded-full blur-2xl pointer-events-none"></div>
            
            <h2 class="text-sm font-bold text-rose-450 flex items-center gap-2 mb-6">
                <span class="h-2.5 w-2.5 rounded-full bg-rose-500 animate-pulse"></span>
                [🔴 SYSTEM & DEV] - สำหรับการติดตามการทำงานของระบบและโค้ด
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Channel 5: #system-logs -->
                <div class="bg-slate-900/40 border border-slate-850 p-5 rounded-xl space-y-4 md:col-span-2">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold text-slate-200 flex items-center gap-1.5">
                            <i class="fa-solid fa-triangle-exclamation text-rose-400"></i> แชแนล #system-logs
                        </h3>
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-400 mb-1.5">Discord Webhook URL</label>
                        <input type="url" name="channels[system_logs][webhook_url]" 
                            value="<?= htmlspecialchars($settings['channels']['system_logs']['webhook_url'] ?? '') ?>" 
                            placeholder="https://discord.com/api/webhooks/..."
                            class="block w-full px-3 py-2 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-rose-500">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                        <div class="flex items-center justify-between border-t border-slate-850/60 pt-2 text-xs">
                            <span class="text-slate-400">เรื่องที่ 8: Audit Logs & Security (การเคลื่อนไหวแอดมิน)</span>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="channels[system_logs][topics][audit_security]" value="1" <?= ($settings['channels']['system_logs']['topics']['audit_security'] ?? '0') === '1' ? 'checked' : '' ?> class="accent-rose-500">
                                    <span class="text-[10px] text-slate-300">เปิด</span>
                                </label>
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="channels[system_logs][topics][audit_security]" value="0" <?= ($settings['channels']['system_logs']['topics']['audit_security'] ?? '0') === '0' ? 'checked' : '' ?> class="accent-slate-500">
                                    <span class="text-[10px] text-slate-500">ปิด</span>
                                </label>
                            </div>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-850/60 pt-2 text-xs">
                            <span class="text-slate-400">เรื่องที่ 9: System Errors (ข้อผิดพลาดของโค้ด/โปรแกรม)</span>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="channels[system_logs][topics][system_errors]" value="1" <?= ($settings['channels']['system_logs']['topics']['system_errors'] ?? '0') === '1' ? 'checked' : '' ?> class="accent-rose-500">
                                    <span class="text-[10px] text-slate-300">เปิด</span>
                                </label>
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="channels[system_logs][topics][system_errors]" value="0" <?= ($settings['channels']['system_logs']['topics']['system_errors'] ?? '0') === '0' ? 'checked' : '' ?> class="accent-slate-500">
                                    <span class="text-[10px] text-slate-500">ปิด</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="pt-6 border-t border-slate-800/60 flex items-center justify-end">
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-xs font-semibold rounded-xl transition shadow-lg transform hover:-translate-y-0.5">
                <i class="fa-solid fa-save mr-1.5 text-sm"></i> บันทึกการตั้งค่า Discord Webhooks
            </button>
        </div>
    </form>
</div>
