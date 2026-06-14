<div class="space-y-6">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-map-location-dot text-indigo-400"></i> สถิติและการเดินทางส่วนกลาง (Fleet Travel Analytics)
            </h1>
            <p class="text-xs text-slate-400 font-light mt-1">วิเคราะห์จุดหมายการเดินทางยอดนิยมและพนักงานผู้ใช้งานรถยนต์หลวงสูงสุดประจำปีงบประมาณ</p>
        </div>
        
        <!-- Fiscal Year Selector Form -->
        <form action="/heatmap" method="GET">
            <select id="fy" name="fy" onchange="this.form.submit()"
                class="bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-200 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <?php foreach ($fiscalYears as $fyOption): ?>
                    <option value="<?= $fyOption ?>" <?= $fyOption === $selectedFY ? 'selected' : '' ?>>
                        ปีงบประมาณ <?= $fyOption ?> (1 ต.ค. <?= $fyOption - 1 ?> - 30 ก.ย. <?= $fyOption ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Main Grid Dashboard -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Left Column: Province Travel Ranking Table -->
        <div class="glass-panel p-6 rounded-2xl border border-slate-850 flex flex-col h-full">
            <div class="border-b border-slate-800 pb-3 mb-6">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-trophy text-amber-400"></i> จังหวัดปลายทางที่มีการเดินทางมากที่สุด
                </h3>
                <p class="text-[10px] text-slate-500 mt-0.5">เรียงลำดับความถี่ของจังหวัดปลายทางที่มีการเดินทางจริง (เฉพาะจังหวัดที่มีสถิติ)</p>
            </div>
            
            <div class="flex-grow overflow-x-auto">
                <?php if (empty($stats)): ?>
                    <div class="flex flex-col items-center justify-center py-16 text-slate-550 space-y-2">
                        <i class="fa-solid fa-folder-open text-3xl text-slate-700"></i>
                        <p class="text-xs font-light">ไม่พบประวัติจุดหมายเดินทางในรอบปีงบประมาณนี้</p>
                    </div>
                <?php else: ?>
                    <table class="min-w-full divide-y divide-slate-800/40 text-left text-xs">
                        <thead>
                            <tr class="text-[10px] text-slate-500 font-semibold uppercase tracking-wider">
                                <th class="pb-3 text-center w-12">อันดับ</th>
                                <th class="pb-3 pl-2">จังหวัดปลายทาง</th>
                                <th class="pb-3 text-right pr-6">จำนวนทริป</th>
                                <th class="pb-3 w-40">สัดส่วนสถิติ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-850/30 text-slate-300 font-light">
                            <?php 
                            $maxCount = (int)$stats[0]['travel_count'];
                            $rank = 1;
                            foreach ($stats as $row):
                                $percentage = $maxCount > 0 ? ($row['travel_count'] / $maxCount) * 100 : 0;
                            ?>
                                <tr class="hover:bg-slate-800/10 transition">
                                    <td class="py-3.5 text-center">
                                        <?php if ($rank === 1): ?>
                                            <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-amber-500/20 text-amber-400 font-bold text-[10px]">1</span>
                                        <?php elseif ($rank === 2): ?>
                                            <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-slate-300/20 text-slate-300 font-bold text-[10px]">2</span>
                                        <?php elseif ($rank === 3): ?>
                                            <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-amber-700/20 text-amber-600 font-bold text-[10px]">3</span>
                                        <?php else: ?>
                                            <span class="text-slate-500 font-semibold"><?= $rank ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 pl-2 font-semibold text-slate-200">
                                        <?= htmlspecialchars($row['province_name']) ?>
                                    </td>
                                    <td class="py-3.5 text-right font-bold text-indigo-400 pr-6">
                                        <?= number_format($row['travel_count']) ?> รอบ
                                    </td>
                                    <td class="py-3.5">
                                        <!-- Progress micro-bar -->
                                        <div class="h-2 w-full bg-slate-950 rounded-full overflow-hidden border border-slate-850/50">
                                            <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full" 
                                                 style="width: <?= $percentage ?>%;"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php 
                                $rank++;
                            endforeach; 
                            ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Pie Chart for Top Bookers -->
        <div class="glass-panel p-6 rounded-2xl border border-slate-850 flex flex-col h-full">
            <div class="border-b border-slate-800 pb-3 mb-6">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-chart-pie text-purple-400"></i> สัดส่วนพนักงานที่จองใช้รถสูงสุด 5 อันดับแรก
                </h3>
                <p class="text-[10px] text-slate-500 mt-0.5">พนักงานที่มีสถิติการได้รับการอนุมัติเดินทางสูงสุด และพนักงานที่เหลือรวบรวมเป็นกลุ่ม "อื่นๆ"</p>
            </div>
            
            <div class="flex-grow flex flex-col items-center justify-center relative">
                <?php if (empty($bookers)): ?>
                    <div class="flex flex-col items-center justify-center py-16 text-slate-550 space-y-2">
                        <i class="fa-solid fa-chart-line text-3xl text-slate-700"></i>
                        <p class="text-xs font-light">ไม่พบประวัติการจองพาหนะในรอบปีงบประมาณนี้</p>
                    </div>
                <?php else: ?>
                    <!-- Pie Chart Wrapper -->
                    <div class="w-full max-w-[280px] mx-auto mb-4">
                        <canvas id="bookerPieChart"></canvas>
                    </div>
                    
                    <!-- Customized Elegant Legend Table -->
                    <div class="w-full mt-4 overflow-hidden rounded-xl border border-slate-800 bg-slate-950/40 text-[11px] text-slate-400">
                        <div class="grid grid-cols-3 bg-slate-900/60 font-semibold px-4 py-2 border-b border-slate-800 text-slate-300">
                            <div>พนักงาน</div>
                            <div class="text-center">จำนวนการจอง</div>
                            <div class="text-right">สัดส่วน %</div>
                        </div>
                        <div class="divide-y divide-slate-800/40">
                            <?php 
                            $totalBookings = array_sum(array_column($bookers, 'count'));
                            // Standard high-end hex colors for UI mapping representation
                            $colors = ['#6366f1', '#a855f7', '#ec4899', '#f43f5e', '#eab308', '#64748b'];
                            foreach ($bookers as $idx => $b):
                                $percent = $totalBookings > 0 ? ($b['count'] / $totalBookings) * 100 : 0;
                                $color = $colors[$idx] ?? '#64748b';
                            ?>
                                <div class="grid grid-cols-3 px-4 py-2 hover:bg-slate-800/10 transition">
                                    <div class="flex items-center gap-2 font-medium text-slate-200">
                                        <span class="h-2 w-2 rounded-full inline-block" style="background-color: <?= $color ?>;"></span>
                                        <?= htmlspecialchars($b['name']) ?>
                                    </div>
                                    <div class="text-center font-bold text-slate-300"><?= $b['count'] ?> ครั้ง</div>
                                    <div class="text-right font-semibold text-slate-400"><?= number_format($percent, 1) ?>%</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Monthly Fuel Quota Table -->
    <div class="glass-panel p-6 rounded-2xl border border-slate-850">
        <div class="border-b border-slate-800 pb-3 mb-5 flex flex-col md:flex-row md:items-center md:justify-between gap-1">
            <div>
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-gas-pump text-amber-400"></i> ปริมาณการใช้น้ำมันรายคัน (เดือนปัจจุบัน)
                </h3>
                <p class="text-[10px] text-slate-500 mt-0.5">เปรียบเทียบยอดน้ำมันที่เติมจริง vs โควต้ารายเดือนของแต่ละยานพาหนะ</p>
            </div>
            <span class="text-[10px] font-semibold text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 px-2.5 py-1 rounded-full">
                <?= date('F Y') ?>
            </span>
        </div>

        <?php if (empty($quotaStats)): ?>
            <div class="flex flex-col items-center justify-center py-12 text-slate-600 space-y-2">
                <i class="fa-solid fa-folder-open text-3xl text-slate-700"></i>
                <p class="text-xs font-light">ไม่พบข้อมูลยานพาหนะ</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-slate-300">
                    <thead>
                        <tr class="text-[10px] text-slate-500 uppercase tracking-wider border-b border-slate-800">
                            <th class="text-left py-2.5 pr-4 font-semibold">ทะเบียนรถ</th>
                            <th class="text-left py-2.5 pr-4 font-semibold">ประเภทน้ำมัน</th>
                            <th class="text-right py-2.5 pr-4 font-semibold">โควต้า (ลิตร)</th>
                            <th class="text-right py-2.5 pr-4 font-semibold">ใช้ไป (ลิตร)</th>
                            <th class="text-right py-2.5 pr-4 font-semibold">คงเหลือ (ลิตร)</th>
                            <th class="py-2.5 font-semibold">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        <?php foreach ($quotaStats as $q):
                            $pct = $q['quota_liters'] > 0
                                ? ($q['remaining_liters'] / $q['quota_liters']) * 100
                                : 0;
                            $pctCapped = max(0, min(100, $pct));

                            if ($pct <= 0) {
                                $barColor  = 'bg-rose-600';
                                $textColor = 'text-rose-400';
                                $badge     = '<span class="px-2 py-0.5 rounded-full bg-rose-500/15 text-rose-400 border border-rose-500/20 text-[10px] font-bold">เกินโควต้า</span>';
                            } elseif ($pct <= 20) {
                                $barColor  = 'bg-rose-500';
                                $textColor = 'text-rose-400';
                                $badge     = '<span class="px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20 text-[10px] font-bold">วิกฤต</span>';
                            } elseif ($pct <= 50) {
                                $barColor  = 'bg-amber-400';
                                $textColor = 'text-amber-400';
                                $badge     = '<span class="px-2 py-0.5 rounded-full bg-amber-400/10 text-amber-400 border border-amber-400/20 text-[10px] font-bold">ระวัง</span>';
                            } else {
                                $barColor  = 'bg-emerald-500';
                                $textColor = 'text-emerald-400';
                                $badge     = '<span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-bold">ปกติ</span>';
                            }
                        ?>
                        <tr class="hover:bg-slate-800/20 transition">
                            <td class="py-3 pr-4 font-bold text-white"><?= htmlspecialchars($q['license_plate']) ?></td>
                            <td class="py-3 pr-4 text-slate-400"><?= htmlspecialchars($q['fuel_type']) ?></td>
                            <td class="py-3 pr-4 text-right"><?= number_format($q['quota_liters'], 2) ?></td>
                            <td class="py-3 pr-4 text-right font-semibold text-slate-200"><?= number_format($q['used_liters'], 2) ?></td>
                            <td class="py-3 pr-4 text-right font-bold <?= $textColor ?>"><?= number_format($q['remaining_liters'], 2) ?></td>
                            <td class="py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex-1 h-2 bg-slate-800 rounded-full overflow-hidden min-w-[80px]">
                                        <div class="h-full rounded-full <?= $barColor ?> transition-all" style="width: <?= $pctCapped ?>%"></div>
                                    </div>
                                    <?= $badge ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-slate-700 font-bold text-white bg-slate-800/20">
                            <td class="py-3 pr-4" colspan="2">รวมทั้งหมด</td>
                            <td class="py-3 pr-4 text-right"><?= number_format(array_sum(array_column($quotaStats, 'quota_liters')), 2) ?></td>
                            <td class="py-3 pr-4 text-right"><?= number_format(array_sum(array_column($quotaStats, 'used_liters')), 2) ?></td>
                            <td class="py-3 pr-4 text-right text-emerald-400"><?= number_format(array_sum(array_column($quotaStats, 'remaining_liters')), 2) ?></td>
                            <td class="py-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php if (!empty($bookers)): 
    $bookerNames = array_column($bookers, 'name');
    $bookerCounts = array_column($bookers, 'count');
?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('bookerPieChart').getContext('2d');
        
        const labels = <?= json_encode($bookerNames, JSON_UNESCAPED_UNICODE) ?>;
        const dataValues = <?= json_encode($bookerCounts) ?>;
        
        // Premium curated color palette
        const backgroundColors = [
            '#6366f1', // indigo-500
            '#a855f7', // purple-500
            '#ec4899', // pink-500
            '#f43f5e', // rose-500
            '#eab308', // yellow-500
            '#64748b'  // slate-500 (for others)
        ];
        
        const borderColors = [
            'rgba(99, 102, 241, 0.4)',
            'rgba(168, 85, 247, 0.4)',
            'rgba(236, 72, 153, 0.4)',
            'rgba(244, 63, 94, 0.4)',
            'rgba(234, 179, 8, 0.4)',
            'rgba(100, 116, 139, 0.4)'
        ];

        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: dataValues,
                    backgroundColor: backgroundColors,
                    borderColor: '#1e293b', // slate-800 matching glassmorphism card
                    borderWidth: 2,
                    hoverOffset: 12
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false // We render our own premium table legend below instead
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#fff',
                        bodyColor: '#cbd5e1',
                        borderColor: '#334155',
                        borderWidth: 1,
                        padding: 10,
                        boxPadding: 6,
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return ` จอง ${value} ครั้ง (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
<?php endif; ?>
