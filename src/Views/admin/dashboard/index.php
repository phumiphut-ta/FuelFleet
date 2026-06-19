<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-chart-line text-indigo-400"></i> แผงวิเคราะห์และแดชบอร์ดบริหาร (Executive Analytics)
            </h1>
            <p class="text-xs text-slate-400 font-light mt-1">สรุปข้อมูลเชิงสถิติเพื่อเพิ่มความโปร่งใส คุมงบประมาณเชื้อเพลิง และเฝ้าระวังอัตราการใช้เกินโควต้า</p>
        </div>
    </div>

    <!-- Quick Stats Cards (Module 8) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- 1. Total & Available Cars -->
        <div class="glass-panel p-5 rounded-2xl border border-slate-800 flex items-center justify-between glow-indigo relative overflow-hidden">
            <div>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">ยานพาหนะพร้อมใช้ / ทั้งหมด</p>
                <h2 class="text-2xl font-extrabold text-white mt-1"><?= $availableVehicles ?> / <?= $totalVehicles ?> คัน</h2>
            </div>
            <div class="h-10 w-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-400"><i class="fa-solid fa-car text-lg"></i></div>
        </div>

        <!-- 2. Today's bookings -->
        <div class="glass-panel p-5 rounded-2xl border border-slate-800 flex items-center justify-between glow-indigo relative overflow-hidden">
            <div>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">คิวจองรถออกราชการวันนี้</p>
                <h2 class="text-2xl font-extrabold text-white mt-1"><?= $todayBookings ?> รายการ</h2>
            </div>
            <div class="h-10 w-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-400"><i class="fa-solid fa-calendar-day text-lg"></i></div>
        </div>

        <!-- 3. Monthly Fuel Usage Liters -->
        <div class="glass-panel p-5 rounded-2xl border border-slate-800 flex items-center justify-between glow-indigo relative overflow-hidden">
            <div>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">เติมน้ำมันเดือนนี้ (ลิตร)</p>
                <h2 class="text-2xl font-extrabold text-white mt-1"><?= number_format($monthlyFuelLiters, 2) ?> ลิตร</h2>
            </div>
            <div class="h-10 w-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-400"><i class="fa-solid fa-gas-pump text-lg"></i></div>
        </div>

        <!-- 4. Over quota cars count & alert -->
        <div class="glass-panel p-5 rounded-2xl border border-slate-800 flex items-center justify-between glow-indigo relative overflow-hidden">
            <div>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">รถใช้เกินโควต้าเดือนนี้</p>
                <h2 class="text-2xl font-extrabold mt-1 <?= $overQuotaCount > 0 ? 'text-rose-400' : 'text-white' ?>"><?= $overQuotaCount ?> คัน</h2>
            </div>
            <div class="h-10 w-10 rounded-xl <?= $overQuotaCount > 0 ? 'bg-rose-500/10 text-rose-450' : 'bg-slate-500/10 text-slate-400' ?> flex items-center justify-center"><i class="fa-solid fa-triangle-exclamation text-lg"></i></div>
        </div>
    </div>

    <!-- Over-quota vehicles notice -->
    <?php if ($overQuotaCount > 0): ?>
        <div class="bg-rose-500/15 border border-rose-500/30 text-rose-300 p-4 rounded-xl text-xs flex flex-col space-y-1.5 glow-rose">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-triangle-exclamation text-sm text-rose-400"></i>
                <span class="font-bold">ตรวจพบรถใช้พลังงานเกินปริมาณโควต้าเดือนปัจจุบัน!</span>
            </div>
            <div class="pl-6 space-y-1 text-slate-300 font-light leading-relaxed">
                <?php foreach ($overQuotaList as $c): ?>
                    <p>&bull; ทะเบียน <span class="font-semibold text-white"><?= htmlspecialchars($c['license_plate']) ?></span> (<?= htmlspecialchars($c['fuel_type']) ?>) - เติมสะสมไปแล้ว <span class="font-bold text-rose-400"><?= number_format($c['liters_used'], 2) ?> ลิตร</span> (โควต้ากำหนด <?= number_format($c['quota_liters'], 2) ?> ลิตร)</p>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Row 1: Fuel Trend Chart + Province Pie Chart -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- 1. Monthly Fuel Usage Trend -->
        <div class="lg:col-span-2 glass-card p-6 rounded-2xl glow-indigo">
            <h3 class="text-sm font-semibold text-white border-b border-slate-800 pb-3 mb-4"><i class="fa-solid fa-chart-area text-indigo-400 mr-1.5"></i>แนวโน้มยอดการใช้น้ำมันย้อนหลัง (ลิตร)</h3>
            <div class="h-[280px]">
                <canvas id="fuelTrendChart"></canvas>
            </div>
        </div>

        <!-- 2. Province Pie Chart (Top 5 + อื่นๆ) -->
        <div class="glass-card p-6 rounded-2xl glow-indigo">
            <h3 class="text-sm font-semibold text-white border-b border-slate-800 pb-3 mb-4"><i class="fa-solid fa-chart-pie text-indigo-400 mr-1.5"></i>สถิติจังหวัดจุดหมายยอดนิยม</h3>
            <div class="h-[240px] flex items-center justify-center">
                <canvas id="provinceTravelChart"></canvas>
            </div>
        </div>

    </div>

    <!-- Row 2: Quota Remaining Table -->
    <div class="glass-card p-6 rounded-2xl glow-indigo">
        <h3 class="text-sm font-semibold text-white border-b border-slate-800 pb-3 mb-4">
            <i class="fa-solid fa-gauge-high text-amber-400 mr-1.5"></i>โควต้าน้ำมันคงเหลือรายคัน (เดือนปัจจุบัน)
        </h3>
        <?php if (empty($quotaRemaining)): ?>
            <p class="text-slate-500 text-xs text-center py-6">ไม่พบข้อมูลยานพาหนะ</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-slate-300">
                    <thead>
                        <tr class="text-[10px] text-slate-500 uppercase tracking-wider border-b border-slate-800">
                            <th class="text-left py-2 pr-4">ทะเบียนรถ</th>
                            <th class="text-left py-2 pr-4">ประเภทน้ำมัน</th>
                            <th class="text-right py-2 pr-4">การใช้น้ำมัน (ใช้ไป / โควต้า)</th>
                            <th class="text-left py-2 pl-4">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        <?php foreach ($quotaRemaining as $q):
                            $pct = $q['quota_liters'] > 0 ? ($q['remaining_liters'] / $q['quota_liters']) * 100 : 0;
                            $barColor = $pct <= 20 ? 'bg-rose-500' : ($pct <= 50 ? 'bg-amber-400' : 'bg-emerald-500');
                            $textColor = $pct <= 20 ? 'text-rose-400' : ($pct <= 50 ? 'text-amber-400' : 'text-emerald-400');
                            $label = $pct <= 20 ? 'วิกฤต' : ($pct <= 50 ? 'ระวัง' : 'ปกติ');
                        ?>
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-2.5 pr-4 font-semibold text-white"><?= htmlspecialchars($q['license_plate']) ?></td>
                            <td class="py-2.5 pr-4"><?= htmlspecialchars($q['fuel_type']) ?></td>
                            <td class="py-2.5 pr-4 text-right">
                                <span class="text-slate-200 font-semibold"><?= number_format($q['used_liters'], 2) ?></span>
                                <span class="text-slate-500">/</span>
                                <span class="text-slate-400"><?= number_format($q['quota_liters'], 2) ?> ลิตร</span>
                                <span class="text-[10px] text-slate-500 font-normal ml-1.5">(คงเหลือ <span class="font-bold <?= $textColor ?>"><?= number_format($q['remaining_liters'], 2) ?></span> ลิตร)</span>
                            </td>
                            <td class="py-2.5 pl-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-1.5 bg-slate-700 rounded-full overflow-hidden min-w-[60px]">
                                        <div class="h-full <?= $barColor ?> rounded-full" style="width: <?= max(0, min(100, $pct)) ?>%"></div>
                                    </div>
                                    <span class="<?= $textColor ?> text-[10px] font-semibold"><?= $label ?></span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Row 3: Cancelled Bookings + Top Bookers -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Cancelled Bookings (latest 5) -->
        <div class="glass-card p-6 rounded-2xl glow-indigo">
            <h3 class="text-sm font-semibold text-white border-b border-slate-800 pb-3 mb-4">
                <i class="fa-solid fa-ban text-rose-400 mr-1.5"></i>รายการยกเลิกการจองรถล่าสุด
            </h3>
            <?php if (empty($cancelledBookings)): ?>
                <p class="text-slate-500 text-xs text-center py-6">ไม่มีรายการยกเลิก</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($cancelledBookings as $i => $cb): ?>
                    <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-800/30 hover:bg-slate-800/50 transition">
                        <div class="h-7 w-7 rounded-lg bg-rose-500/10 flex items-center justify-center text-rose-400 text-[11px] font-bold flex-shrink-0 mt-0.5">
                            <?= $i + 1 ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-white text-xs font-semibold truncate"><?= htmlspecialchars($cb['booker_name'] ?? 'ไม่ระบุ') ?></p>
                            <p class="text-slate-400 text-[11px] mt-0.5">
                                <span class="text-indigo-300 font-medium"><?= htmlspecialchars($cb['license_plate'] ?? '-') ?></span>
                                &nbsp;·&nbsp; จอง: <?= $cb['booking_date'] ? date('d/m/Y', strtotime($cb['booking_date'])) : '-' ?>
                            </p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-[10px] text-slate-500">ยกเลิกเมื่อ</p>
                            <p class="text-rose-400 text-[11px] font-semibold"><?= $cb['cancelled_at'] ? date('d/m/Y', strtotime($cb['cancelled_at'])) : '-' ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Top 5 Bookers (current fiscal year) -->
        <div class="glass-card p-6 rounded-2xl glow-indigo">
            <h3 class="text-sm font-semibold text-white border-b border-slate-800 pb-3 mb-4">
                <i class="fa-solid fa-ranking-star text-amber-400 mr-1.5"></i>อันดับผู้ใช้บริการจองรถสูงสุด (ปีงบประมาณปัจจุบัน)
            </h3>
            <?php if (empty($topBookers)): ?>
                <p class="text-slate-500 text-xs text-center py-6">ไม่มีข้อมูล</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php
                    $medalColors = ['text-amber-400', 'text-slate-300', 'text-amber-600', 'text-slate-400', 'text-slate-400'];
                    $medalBg     = ['bg-amber-400/10', 'bg-slate-300/10', 'bg-amber-700/10', 'bg-slate-500/10', 'bg-slate-500/10'];
                    $medalIcons  = ['fa-trophy', 'fa-medal', 'fa-medal', 'fa-circle', 'fa-circle'];
                    foreach ($topBookers as $rank => $tb):
                        $mc = $medalColors[$rank] ?? 'text-slate-500';
                        $mb = $medalBg[$rank]     ?? 'bg-slate-500/10';
                        $mi = $medalIcons[$rank]  ?? 'fa-circle';
                        $maxCount = $topBookers[0]['booking_count'] ?: 1;
                        $barPct   = round(($tb['booking_count'] / $maxCount) * 100);
                    ?>
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-800/30 hover:bg-slate-800/50 transition">
                        <div class="h-7 w-7 rounded-lg <?= $mb ?> flex items-center justify-center flex-shrink-0">
                            <i class="fa-solid <?= $mi ?> text-xs <?= $mc ?>"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-white text-xs font-semibold truncate"><?= htmlspecialchars($tb['full_name'] ?? 'ไม่ระบุ') ?></p>
                                <span class="text-xs font-bold <?= $mc ?> ml-2 flex-shrink-0"><?= number_format($tb['booking_count']) ?> ครั้ง</span>
                            </div>
                            <div class="h-1.5 bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full rounded-full <?= $rank === 0 ? 'bg-amber-400' : 'bg-indigo-500' ?>" style="width: <?= $barPct ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Charts Rendering Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Fuel usage trend data
        const fuelTrendCtx = document.getElementById('fuelTrendChart').getContext('2d');
        const monthlyLabels = <?= json_encode($chartMonthLabels) ?>;
        const monthlyDatasets = <?= json_encode($chartDatasets) ?>;

        new Chart(fuelTrendCtx, {
            type: 'line',
            data: {
                labels: monthlyLabels.length > 0 ? monthlyLabels : ['ไม่มีข้อมูล'],
                datasets: monthlyDatasets.length > 0 ? monthlyDatasets : []
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: '#94a3b8',
                            font: { size: 10 },
                            boxWidth: 12,
                            padding: 8
                        }
                    }
                },
                scales: {
                    x: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#94a3b8' } },
                    y: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#94a3b8' } }
                }
            }
        });

        // 2. Province Pie Chart (Top 5 + อื่นๆ)
        const provinceCtx    = document.getElementById('provinceTravelChart').getContext('2d');
        const provinceNames  = <?= json_encode(array_column($provinceTravelStats, 'province_name')) ?>;
        const provinceCounts = <?= json_encode(array_column($provinceTravelStats, 'travel_count')) ?>;
        const pieColors      = ['#6366f1', '#a78bfa', '#fbbf24', '#34d399', '#22d3ee', '#94a3b8'];

        new Chart(provinceCtx, {
            type: 'doughnut',
            data: {
                labels: provinceNames.length > 0 ? provinceNames : ['ไม่มีข้อมูล'],
                datasets: [{
                    data: provinceCounts.length > 0 ? provinceCounts : [1],
                    backgroundColor: pieColors,
                    borderColor: 'rgba(15, 23, 42, 0.8)',
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        display: true,
                        position: 'right',
                        labels: {
                            color: '#94a3b8',
                            font: { size: 10 },
                            boxWidth: 10,
                            padding: 8
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct   = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                                return ` ${ctx.label}: ${ctx.parsed} ทริป (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
