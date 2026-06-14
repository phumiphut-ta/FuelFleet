<div class="space-y-6">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Leaflet Map CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Leaflet Custom Glassmorphism Theme Overrides -->
    <style>
        #map-container {
            position: relative;
        }
        #map {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: transparent !important;
        }
        .leaflet-container {
            background: transparent !important;
        }
        .leaflet-bar {
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            background: rgba(15, 23, 42, 0.85) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5) !important;
            border-radius: 8px !important;
            overflow: hidden;
        }
        .leaflet-bar a {
            background: transparent !important;
            color: #94a3b8 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            transition: all 0.2s;
        }
        .leaflet-bar a:hover {
            color: #fff !important;
            background: rgba(99, 102, 241, 0.25) !important;
        }
        .leaflet-bar a.leaflet-disabled {
            background: transparent !important;
            color: #475569 !important;
        }
        .leaflet-tooltip-pane {
            display: none !important;
        }
    </style>

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
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left Column: Map of Thailand Choropleth -->
        <div class="lg:col-span-7 glass-panel p-6 rounded-2xl border border-slate-850 flex flex-col h-[650px]">
            <div class="border-b border-slate-800 pb-3 mb-6">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-map-location-dot text-indigo-400"></i> แผนที่ความถี่การเดินทางรายจังหวัด
                </h3>
                <p class="text-[10px] text-slate-500 mt-0.5">แผนที่ประเทศไทยแสดงระดับความเข้มของสีตามจำนวนครั้งการเดินทางจริงของปีงบประมาณนี้</p>
            </div>
            
            <div id="map-container" class="flex-grow rounded-xl overflow-hidden relative border border-slate-800/60 bg-slate-950/20">
                <div id="map"></div>
                
                <!-- Tooltip -->
                <div id="map-tooltip" class="absolute hidden z-[1000] pointer-events-none bg-slate-950/90 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 shadow-xl backdrop-blur-md">
                    <div class="font-bold text-white mb-0.5" id="tooltip-province"></div>
                    <div class="text-[11px] text-indigo-400 font-semibold" id="tooltip-count"></div>
                </div>
                
                <!-- Legend -->
                <div class="absolute bottom-4 left-4 z-[500] bg-slate-950/80 border border-slate-800/80 px-3 py-2 rounded-xl text-[10px] text-slate-400 font-light flex flex-col gap-1.5 shadow-lg backdrop-blur-md">
                    <div class="font-semibold text-slate-300">ระดับความถี่การเดินทาง</div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded bg-slate-850/50 border border-slate-800"></span>
                        <span>ไม่มีการเดินทาง (0 ครั้ง)</span>
                    </div>
                    <?php 
                    $maxCount = !empty($stats) ? (int)$stats[0]['travel_count'] : 0;
                    if ($maxCount > 0): 
                    ?>
                        <div class="flex items-center gap-2">
                            <div class="w-24 h-2 rounded bg-gradient-to-r from-[#6366f1] to-[#ec4899]"></div>
                            <span>1 - <?= number_format($maxCount) ?> ครั้ง</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Table & Pie Chart -->
        <div class="lg:col-span-5 flex flex-col gap-6">
            
            <!-- Province Travel Ranking Table -->
            <div class="glass-panel p-5 rounded-2xl border border-slate-850 flex flex-col h-[312px]">
                <div class="border-b border-slate-800 pb-2 mb-4">
                    <h3 class="text-xs font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-trophy text-amber-400"></i> จังหวัดปลายทางที่มีการเดินทางมากที่สุด
                    </h3>
                    <p class="text-[9px] text-slate-500 mt-0.5">เรียงลำดับความถี่ของจังหวัดปลายทางที่มีการเดินทางจริง (เฉพาะจังหวัดที่มีสถิติ)</p>
                </div>
                
                <div class="flex-grow overflow-y-auto pr-1">
                    <?php if (empty($stats)): ?>
                        <div class="flex flex-col items-center justify-center py-12 text-slate-550 space-y-2">
                            <i class="fa-solid fa-folder-open text-3xl text-slate-700"></i>
                            <p class="text-xs font-light">ไม่พบประวัติจุดหมายเดินทางในรอบปีงบประมาณนี้</p>
                        </div>
                    <?php else: ?>
                        <table class="min-w-full divide-y divide-slate-800/40 text-left text-xs">
                            <thead>
                                <tr class="text-[10px] text-slate-500 font-semibold uppercase tracking-wider">
                                    <th class="pb-2 text-center w-12">อันดับ</th>
                                    <th class="pb-2 pl-2">จังหวัดปลายทาง</th>
                                    <th class="pb-2 text-right pr-4">จำนวนทริป</th>
                                    <th class="pb-2 w-32">สัดส่วนสถิติ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-850/30 text-slate-300 font-light">
                                <?php 
                                $rank = 1;
                                foreach ($stats as $row):
                                    $percentage = $maxCount > 0 ? ($row['travel_count'] / $maxCount) * 100 : 0;
                                ?>
                                    <tr class="hover:bg-slate-800/10 transition">
                                        <td class="py-2.5 text-center">
                                            <?php if ($rank === 1): ?>
                                                <span class="inline-flex items-center justify-center h-4 w-4 rounded-full bg-amber-500/20 text-amber-400 font-bold text-[9px]">1</span>
                                            <?php elseif ($rank === 2): ?>
                                                <span class="inline-flex items-center justify-center h-4 w-4 rounded-full bg-slate-300/20 text-slate-300 font-bold text-[9px]">2</span>
                                            <?php elseif ($rank === 3): ?>
                                                <span class="inline-flex items-center justify-center h-4 w-4 rounded-full bg-amber-700/20 text-amber-600 font-bold text-[9px]">3</span>
                                            <?php else: ?>
                                                <span class="text-slate-500 font-semibold text-[10px]"><?= $rank ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-2.5 pl-2 font-semibold text-slate-200 text-[11px]">
                                            <?= htmlspecialchars($row['province_name']) ?>
                                        </td>
                                        <td class="py-2.5 text-right font-bold text-indigo-400 pr-4 text-[11px]">
                                            <?= number_format($row['travel_count']) ?> รอบ
                                        </td>
                                        <td class="py-2.5">
                                            <!-- Progress micro-bar -->
                                            <div class="h-1.5 w-full bg-slate-950 rounded-full overflow-hidden border border-slate-850/50">
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

            <!-- Top Bookers Pie Chart -->
            <div class="glass-panel p-5 rounded-2xl border border-slate-850 flex flex-col h-[312px]">
                <div class="border-b border-slate-800 pb-2 mb-4">
                    <h3 class="text-xs font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-chart-pie text-purple-400"></i> สัดส่วนพนักงานที่จองใช้รถสูงสุด 5 อันดับแรก
                    </h3>
                    <p class="text-[9px] text-slate-500 mt-0.5">พนักงานที่มีสถิติการได้รับการอนุมัติเดินทางสูงสุด และพนักงานที่เหลือรวบรวมเป็นกลุ่ม "อื่นๆ"</p>
                </div>
                
                <div class="flex-grow flex flex-col items-center justify-center relative overflow-hidden">
                    <?php if (empty($bookers)): ?>
                        <div class="flex flex-col items-center justify-center py-12 text-slate-550 space-y-2">
                            <i class="fa-solid fa-chart-line text-3xl text-slate-700"></i>
                            <p class="text-xs font-light">ไม่พบประวัติการจองพาหนะในรอบปีงบประมาณนี้</p>
                        </div>
                    <?php else: ?>
                        <!-- Side-by-Side Wrapper -->
                        <div class="flex flex-row items-center justify-between gap-4 w-full h-full">
                            <!-- Left: Chart -->
                            <div class="w-[42%] h-[160px] flex items-center justify-center">
                                <canvas id="bookerPieChart"></canvas>
                            </div>
                            <!-- Right: Customized Elegant Legend Table -->
                            <div class="w-[58%] overflow-y-auto max-h-[170px] rounded-xl border border-slate-800 bg-slate-950/40 text-[10px] text-slate-400">
                                <div class="grid grid-cols-3 bg-slate-900/60 font-semibold px-3 py-1.5 border-b border-slate-800 text-slate-300 sticky top-0 z-10">
                                    <div>พนักงาน</div>
                                    <div class="text-center">จอง (ครั้ง)</div>
                                    <div class="text-right">สัดส่วน</div>
                                </div>
                                <div class="divide-y divide-slate-800/40">
                                    <?php 
                                    $totalBookings = array_sum(array_column($bookers, 'count'));
                                    $colors = ['#6366f1', '#a855f7', '#ec4899', '#f43f5e', '#eab308', '#64748b'];
                                    foreach ($bookers as $idx => $b):
                                        $percent = $totalBookings > 0 ? ($b['count'] / $totalBookings) * 100 : 0;
                                        $color = $colors[$idx] ?? '#64748b';
                                    ?>
                                        <div class="grid grid-cols-3 px-3 py-1.5 hover:bg-slate-800/10 transition">
                                            <div class="flex items-center gap-1.5 font-medium text-slate-200 truncate font-light">
                                                <span class="h-1.5 w-1.5 rounded-full inline-block flex-shrink-0" style="background-color: <?= $color ?>;"></span>
                                                <span class="truncate" title="<?= htmlspecialchars($b['name']) ?>"><?= htmlspecialchars($b['name']) ?></span>
                                            </div>
                                            <div class="text-center font-bold text-slate-300"><?= $b['count'] ?></div>
                                            <div class="text-right font-semibold text-slate-400"><?= number_format($percent, 1) ?>%</div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
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
        // ----------------------------------------------------
        // 1. Thailand Choropleth Map initialization
        // ----------------------------------------------------
        const map = L.map('map', {
            zoomControl: true,
            attributionControl: false,
            scrollWheelZoom: false,
            doubleClickZoom: false
        });
        
        // Reposition Zoom controls to top-right to prevent overlap
        map.zoomControl.setPosition('topright');
        
        // 77 Thai provinces Mapping (Thai name -> English name in GeoJSON properties)
        const provinceThToEn = {
            "กรุงเทพมหานคร": "Bangkok Metropolis",
            "กระบี่": "Krabi",
            "กาญจนบุรี": "Kanchanaburi",
            "กาฬสินธุ์": "Kalasin",
            "กำแพงเพชร": "Kamphaeng Phet",
            "ขอนแก่น": "Khon Kaen",
            "จันทบุรี": "Chanthaburi",
            "ฉะเชิงเทรา": "Chachoengsao",
            "ชลบุรี": "Chon Buri",
            "ชัยนาท": "Chai Nat",
            "ชัยภูมิ": "Chaiyaphum",
            "ชุมพร": "Chumphon",
            "เชียงราย": "Chiang Rai",
            "เชียงใหม่": "Chiang Mai",
            "ตรัง": "Trang",
            "ตราด": "Trat",
            "ตาก": "Tak",
            "นครนายก": "Nakhon Nayok",
            "นครปฐม": "Nakhon Pathom",
            "นครพนม": "Nakhon Phanom",
            "นครราชสีมา": "Nakhon Ratchasima",
            "นครศรีธรรมราช": "Nakhon Si Thammarat",
            "นครสวรรค์": "Nakhon Sawan",
            "นนทบุรี": "Nonthaburi",
            "นราธิวาส": "Narathiwat",
            "น่าน": "Nan",
            "บึงกาฬ": "Bueng Kan",
            "บุรีรัมย์": "Buri Ram",
            "ปทุมธานี": "Pathum Thani",
            "ประจวบคีรีขันธ์": "Prachuap Khiri Khan",
            "ปราจีนบุรี": "Prachin Buri",
            "ปัตตานี": "Pattani",
            "พระนครศรีอยุธยา": "Phra Nakhon Si Ayutthaya",
            "พะเยา": "Phayao",
            "พังงา": "Phangnga",
            "พัทลุง": "Phatthalung",
            "พิจิตร": "Phichit",
            "พิษณุโลก": "Phitsanulok",
            "เพชรบุรี": "Phetchaburi",
            "เพชรบูรณ์": "Phetchabun",
            "แพร่": "Phrae",
            "ภูเก็ต": "Phuket",
            "มหาสารคาม": "Maha Sarakham",
            "มุกดาหาร": "Mukdahan",
            "แม่ฮ่องสอน": "Mae Hong Son",
            "ยะลา": "Yala",
            "ยโสธร": "Yasothon",
            "ร้อยเอ็ด": "Roi Et",
            "ระนอง": "Ranong",
            "ระยอง": "Rayong",
            "ราชบุรี": "Ratchaburi",
            "ลพบุรี": "Lop Buri",
            "ลำปาง": "Lampang",
            "ลำพูน": "Lamphun",
            "เลย": "Loei",
            "ศรีสะเกษ": "Si Sa Ket",
            "สกลนคร": "Sakon Nakhon",
            "สงขลา": "Songkhla",
            "สตูล": "Satun",
            "สมุทรปราการ": "Samut Prakan",
            "สมุทรสงคราม": "Samut Songkhram",
            "สมุทรสาคร": "Samut Sakhon",
            "สระแก้ว": "Sa Kaeo",
            "สระบุรี": "Saraburi",
            "สิงห์บุรี": "Sing Buri",
            "สุโขทัย": "Sukhothai",
            "สุพรรณบุรี": "Suphan Buri",
            "สุราษฎร์ธานี": "Surat Thani",
            "สุรินทร์": "Surin",
            "หนองคาย": "Nong Khai",
            "หนองบัวลำภู": "Nong Bua Lam Phu",
            "อ่างทอง": "Ang Thong",
            "อุดรธานี": "Udon Thani",
            "อุทัยธานี": "Uthai Thani",
            "อุตรดิตถ์": "Uttaradit",
            "อุบลราชธานี": "Ubon Ratchathani",
            "อำนาจเจริญ": "Amnat Charoen"
        };
        
        // Reverse mapping to parse GeoJSON property name into Thai database keys
        const provinceEnToTh = {};
        for (const [th, en] of Object.entries(provinceThToEn)) {
            provinceEnToTh[en] = th;
        }
        
        // Fetch PHP travel statistics injection
        const statsData = <?= json_encode($stats, JSON_UNESCAPED_UNICODE) ?>;
        const travelCounts = {};
        statsData.forEach(row => {
            travelCounts[row.province_name] = parseInt(row.travel_count);
        });
        
        const maxCount = <?= $maxCount ?>;
        
        // Dynamic continuous color function mapping travel count to modern indigo-pink gradient HSL values
        function getChoroplethColor(count) {
            if (count === 0) return 'rgba(30, 41, 59, 0.15)'; // Slate base for unvisited
            
            const ratio = maxCount > 0 ? count / maxCount : 0;
            const hue = 240 + (80 * ratio); // 240 (Indigo) to 320 (Pink)
            const lightness = 60 - (10 * ratio); // 60% to 50%
            const opacity = 0.25 + (0.65 * ratio); // 0.25 to 0.90
            
            return `hsla(${hue}, 85%, ${lightness}%, ${opacity})`;
        }
        
        function styleFeature(feature) {
            const enName = feature.properties.name;
            const thName = provinceEnToTh[enName] || enName;
            const count = travelCounts[thName] || 0;
            
            return {
                fillColor: getChoroplethColor(count),
                weight: 1,
                opacity: 0.8,
                color: 'rgba(255, 255, 255, 0.12)', // Thin boundaries
                fillOpacity: 1
            };
        }
        
        // Custom Floating Tooltip positioning relative to #map-container
        const tooltip = document.getElementById('map-tooltip');
        const tooltipProvince = document.getElementById('tooltip-province');
        const tooltipCount = document.getElementById('tooltip-count');
        const mapContainer = document.getElementById('map-container');
        
        function showTooltip(e, provinceName, count) {
            tooltipProvince.textContent = provinceName;
            tooltipCount.textContent = `จำนวนเดินทาง: ${count} ครั้ง`;
            tooltip.classList.remove('hidden');
            positionTooltip(e);
        }
        
        function positionTooltip(e) {
            const rect = mapContainer.getBoundingClientRect();
            const x = e.originalEvent.clientX - rect.left + 15;
            const y = e.originalEvent.clientY - rect.top + 15;
            
            tooltip.style.left = `${x}px`;
            tooltip.style.top = `${y}px`;
        }
        
        function hideTooltip() {
            tooltip.classList.add('hidden');
        }
        
        let geojsonLayer;
        
        function highlightFeature(e) {
            const layer = e.target;
            const enName = layer.feature.properties.name;
            const thName = provinceEnToTh[enName] || enName;
            const count = travelCounts[thName] || 0;
            
            layer.setStyle({
                weight: 2,
                color: '#818cf8', // High-end indigo highlight boundary
                fillOpacity: 0.95
            });
            
            if (!L.Browser.ie && !L.Browser.opera && !L.Browser.edge) {
                layer.bringToFront();
            }
            
            showTooltip(e, thName, count);
        }
        
        function resetHighlight(e) {
            geojsonLayer.resetStyle(e.target);
            hideTooltip();
        }
        
        function onEachFeature(feature, layer) {
            layer.on({
                mouseover: highlightFeature,
                mouseout: resetHighlight,
                mousemove: positionTooltip
            });
        }
        
        // Load GeoJSON dynamically
        fetch('<?= \App\Core\Request::getBasePath() ?>/data/thailand_apisit.json')
            .then(res => {
                if (!res.ok) throw new Error('Failed to load map data');
                return res.json();
            })
            .then(geojsonData => {
                geojsonLayer = L.geoJSON(geojsonData, {
                    style: styleFeature,
                    onEachFeature: onEachFeature
                }).addTo(map);
                
                // Automatically bounds and zooms to fit Thailand perfectly
                map.fitBounds(geojsonLayer.getBounds(), {
                    padding: [10, 10]
                });
            })
            .catch(err => {
                console.error(err);
            });

        // ----------------------------------------------------
        // 2. Chart.js Pie Chart Initialization
        // ----------------------------------------------------
        const ctx = document.getElementById('bookerPieChart').getContext('2d');
        const labels = <?= json_encode($bookerNames, JSON_UNESCAPED_UNICODE) ?>;
        const dataValues = <?= json_encode($bookerCounts) ?>;
        
        const backgroundColors = ['#6366f1', '#a855f7', '#ec4899', '#f43f5e', '#eab308', '#64748b'];
        
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: dataValues,
                    backgroundColor: backgroundColors,
                    borderColor: '#1e293b',
                    borderWidth: 2,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#fff',
                        bodyColor: '#cbd5e1',
                        borderColor: '#334155',
                        borderWidth: 1,
                        padding: 8,
                        boxPadding: 4,
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
