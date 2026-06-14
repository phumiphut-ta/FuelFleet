<div class="space-y-6" x-data="{ 
    modalOpen: false, 
    cancelFormOpen: false,
    eventDetails: { id: '', title: '', type: '', start: '', end: '', booker: '', vehicle: '', purpose: '', reason: '' },
    openEvent(info) {
        const props = info.event.extendedProps;
        this.eventDetails.id = props.booking_id || props.suspension_id || '';
        this.eventDetails.title = info.event.title;
        this.eventDetails.type = props.type;
        this.eventDetails.booker = props.booker || '-';
        this.eventDetails.vehicle = props.vehicle;
        this.eventDetails.purpose = props.purpose || '-';
        this.eventDetails.reason = props.reason || '-';
        this.eventDetails.start = info.event.start.toLocaleString('th-TH');
        this.eventDetails.end = info.event.end ? info.event.end.toLocaleString('th-TH') : '-';
        
        this.cancelFormOpen = false;
        this.modalOpen = true;
    }
}">

    <!-- Banner -->
    <div class="glass-panel p-6 rounded-2xl border border-slate-800 flex flex-col md:flex-row items-center justify-between gap-6 glow-indigo relative overflow-hidden">
        <div class="absolute -top-10 -right-10 w-24 h-24 bg-indigo-500/5 rounded-full blur-2xl pointer-events-none"></div>
        <div>
            <h2 class="text-2xl font-extrabold text-white tracking-tight">ปฏิทินจองยานพาหนะส่วนกลาง</h2>
            <p class="text-xs text-slate-400 font-light mt-1.5 leading-relaxed max-w-xl">ตรวจสอบสถานะคิวการใช้รถส่วนราชการ หรือยื่นคำขอจองคิวรถยนต์ออนไลน์ได้ทันที โดยไม่มีขั้นตอนอนุมัติยุ่งยาก ป้องกันปัญหาเวลาทับซ้อนและงดใช้รถชำรุดชั่วคราว</p>
        </div>
        <a href="/booking/new" class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-500/20 flex items-center gap-2 transition duration-300 transform hover:-translate-y-0.5 shrink-0">
            <i class="fa-solid fa-plus-circle text-base"></i> จองรถยนต์ออนไลน์
        </a>
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

    <!-- Calendar View -->
    <div class="glass-panel p-6 rounded-2xl border border-slate-850 bg-slate-900/10">
        <!-- Legend indicators -->
        <div class="flex items-center space-x-4 text-xs font-light text-slate-400 mb-6 border-b border-slate-800/80 pb-4">
            <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-[#6366f1] inline-block"></span> การจองของพนักงาน</span>
            <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-[#f43f5e] inline-block"></span> รถยนต์ปิดปรับปรุง (Suspension)</span>
        </div>

        <!-- Target element for FullCalendar -->
        <div id="calendar" class="text-slate-200"></div>
    </div>

    <!-- Event Detail Popup Modal (Alpine.js) -->
    <div x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" style="display: none;" x-transition>
        <div class="max-w-md w-full glass-panel border border-slate-800 rounded-2xl glow-indigo p-6 relative overflow-hidden" @click.away="modalOpen = false">
            <button @click="modalOpen = false" class="absolute top-4 right-4 text-slate-500 hover:text-white transition"><i class="fa-solid fa-xmark text-lg"></i></button>
            
            <div class="flex items-center space-x-2.5 mb-4">
                <span :class="eventDetails.type === 'booking' ? 'bg-indigo-500/15 text-indigo-400 border-indigo-500/20' : 'bg-rose-500/15 text-rose-400 border-rose-500/20'"
                    class="px-2 py-0.5 rounded-full border text-[10px] font-semibold" x-text="eventDetails.type === 'booking' ? 'การจองใช้งาน' : 'การระงับใช้รถชั่วคราว'"></span>
            </div>

            <h3 class="text-base font-bold text-white mb-4" x-text="eventDetails.title"></h3>

            <!-- Details list -->
            <div class="space-y-3 text-xs font-light text-slate-300 mb-6 bg-slate-950/20 border border-slate-900 p-4 rounded-xl">
                <div>
                    <span class="text-slate-500 font-medium block">ยานพาหนะ (License Plate):</span>
                    <span x-text="eventDetails.vehicle" class="font-semibold text-white"></span>
                </div>
                
                <!-- If Booking -->
                <template x-if="eventDetails.type === 'booking'">
                    <div class="space-y-3">
                        <div>
                            <span class="text-slate-500 font-medium block">ผู้จองใช้งาน (Booker):</span>
                            <span x-text="eventDetails.booker" class="text-slate-200"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 font-medium block">จุดประสงค์ในการเดินทาง (Purpose):</span>
                            <span x-text="eventDetails.purpose" class="text-slate-200"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 font-medium block">เวลาเดินทาง (Period):</span>
                            <span class="text-slate-200 font-semibold" x-text="eventDetails.start + ' น. - ' + eventDetails.end + ' น.'"></span>
                        </div>
                    </div>
                </template>

                <!-- If Suspension -->
                <template x-if="eventDetails.type === 'suspension'">
                    <div class="space-y-3">
                        <div>
                            <span class="text-slate-500 font-medium block">เหตุผลการปิดปรับปรุง (Reason):</span>
                            <span x-text="eventDetails.reason" class="text-slate-200"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 font-medium block">ระยะเวลาสั่งแบน (Period):</span>
                            <span class="text-slate-200 font-semibold" x-text="eventDetails.start + ' - ' + eventDetails.end"></span>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Cancel booking workflow -->
            <template x-if="eventDetails.type === 'booking'">
                <div>
                    <div x-show="!cancelFormOpen">
                        <button @click="cancelFormOpen = true" class="w-full py-2.5 bg-rose-950/40 border border-rose-900/50 hover:bg-rose-500 hover:text-white text-rose-400 text-xs font-semibold rounded-xl transition duration-200">
                            <i class="fa-solid fa-trash-can mr-1"></i> ขอยกเลิกรายการจองนี้
                        </button>
                    </div>

                    <!-- Password Check form -->
                    <div x-show="cancelFormOpen" class="space-y-4 pt-4 border-t border-slate-800/80" x-transition>
                        <form action="/booking/cancel" method="POST" class="space-y-3">
                            <input type="hidden" name="booking_id" :value="eventDetails.id">
                            <div>
                                <label for="cancellation_password" class="block text-[11px] font-semibold text-slate-400 mb-2">ระบุรหัสผ่านขอยกเลิกการจอง</label>
                                <input type="password" id="cancellation_password" name="cancellation_password" required placeholder="ป้อนรหัสผ่านที่ตั้งตอนลงจอง"
                                    class="block w-full px-3 py-2 border border-slate-800 bg-slate-950/60 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-1 focus:ring-rose-500 focus:border-rose-500 transition duration-200">
                            </div>
                            <div class="flex gap-2">
                                <button type="button" @click="cancelFormOpen = false" class="flex-grow py-2 border border-slate-800 hover:bg-slate-900 text-slate-400 text-xs font-semibold rounded-xl transition">
                                    ย้อนกลับ
                                </button>
                                <button type="submit" class="flex-grow py-2 bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold rounded-xl transition shadow-lg shadow-rose-500/10">
                                    ยืนยันยกเลิกทันที
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </template>
            
            <template x-if="eventDetails.type === 'suspension'">
                <button @click="modalOpen = false" class="w-full py-2.5 bg-slate-850 hover:bg-slate-800 text-slate-300 text-xs font-semibold rounded-xl transition">
                    ปิดหน้าต่าง
                </button>
            </template>

        </div>
    </div>

</div>

<!-- Calendar Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;

        // Obtain Alpine data object reference
        const alpineData = Alpine.$data(calendarEl.closest('[x-data]'));

        const calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'th',
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: ''
            },
            buttonText: {
                today: 'วันนี้'
            },
            displayEventTime: false,
            events: '/api/calendar/events',
            eventClick: function(info) {
                alpineData.openEvent(info);
            },
            height: 'auto',
            editable: false
        });

        calendar.render();
    });
</script>
