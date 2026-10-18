<?php
// app/Models/Barangay.php
// Complete 193 Barangays & 16 Zones across South Caloocan (13.63 km²) and North Caloocan (39.71 km²)

require_once __DIR__ . '/../../config/database.php';

class Barangay
{
    private Database $db;
    private string $table = 'barangays';

    /**
     * Caloocan City Geographic Profile:
     * - Total Land Area: 53.33 - 55.80 km² (5,333.40 hectares)
     * - Non-contiguous dual geographic layout:
     *   1. South Caloocan (Urban Center): ~13.63 km² (7 km E-W × 6 km N-S) - Zones 1 to 15 (Barangays 1 to 164)
     *   2. North Caloocan (Subdivisions): ~39.71 km² (10 km E-W × 8 km N-S) - Zone 16 (Barangays 165 to 193 / 176-A-F)
     * - Total Barangays: 193 (Originally 188; Barangay 176 Bagong Silang was split into 6: 176-A to 176-F)
     * - Total Zones: 16 administrative clusters
     */
    private static ?array $defaultBarangays = null;

    private static function initDefaultBarangays(): array
    {
        if (self::$defaultBarangays !== null) {
            return self::$defaultBarangays;
        }

        $b = [];

        $add = function(string|int $no, string $name, string $zone, int $district, float $lat, float $lng, string $landmark, int $pop, string $region) use (&$b) {
            $b[$no] = [
                'name'       => $name,
                'zone'       => $zone,
                'district'   => $district,
                'region'     => $region,
                'lat'        => round($lat, 4),
                'lng'        => round($lng, 4),
                'landmark'   => $landmark,
                'population' => $pop
            ];
        };

        // ═════════════════════════════════════════════════════════════
        // SOUTH CALOOCAN (Districts 1 & 2 - Zones 1 to 15 - 164 Barangays)
        // ═════════════════════════════════════════════════════════════

        // Zone 1 (District 1) - Barangays 1 to 4
        $add(1, 'Barangay 1', 'Zone 1', 1, 14.6589, 120.9732, 'Near University of the East Caloocan', 9200, 'South Caloocan');
        $add(2, 'Barangay 2', 'Zone 1', 1, 14.6595, 120.9715, 'Dagat-Dagatan Avenue', 8800, 'South Caloocan');
        $add(3, 'Barangay 3', 'Zone 1', 1, 14.6572, 120.9708, 'Near Tanigue Street', 10500, 'South Caloocan');
        $add(4, 'Barangay 4', 'Zone 1', 1, 14.6560, 120.9721, 'Near Lapu-Lapu Street', 11200, 'South Caloocan');

        // Zone 2 (District 2) - Barangays 5 to 24 (Maypajo / Kaunlaran)
        $z2 = [
            5  => ['Maypajo Market / J.P. Rizal', 14.6432, 120.9750, 7800],
            6  => ['A. Mabini St near C-3', 14.6441, 120.9738, 8200],
            7  => ['Near Maypajo Elementary School', 14.6425, 120.9725, 6900],
            8  => ['Near P. Burgos Street', 14.6415, 120.9742, 7400],
            9  => ['Near M. Hizon Street', 14.6405, 120.9755, 6800],
            10 => ['Kaunlaran Village Phase 1', 14.6448, 120.9712, 8500],
            11 => ['Kaunlaran Village Phase 2', 14.6455, 120.9698, 7900],
            12 => ['Near C-3 Road Bridge', 14.6462, 120.9728, 9100],
            13 => ['Near Dagat-Dagatan boundary', 14.6470, 120.9705, 8300],
            14 => ['Near Sabalo Street', 14.6478, 120.9692, 7600],
            15 => ['Kaunlaran Waterfront edge', 14.6485, 120.9680, 8100],
            16 => ['Near Pampano Street', 14.6492, 120.9700, 7200],
            17 => ['Near Hasa-Hasa Street', 14.6499, 120.9715, 6800],
            18 => ['Near Dalagang Bukid St', 14.6508, 120.9725, 7500],
            19 => ['Near Pag-asa Street', 14.6420, 120.9765, 8400],
            20 => ['Near Heroes del 96 St', 14.6410, 120.9775, 7100],
            21 => ['Near Manila City boundary (R. Papa)', 14.6395, 120.9782, 8900],
            22 => ['Near J. Teodoro Street', 14.6402, 120.9795, 7300],
            23 => ['Near 2nd Avenue South', 14.6415, 120.9805, 8000],
            24 => ['Near 3rd Avenue South', 14.6425, 120.9815, 7700],
        ];
        foreach ($z2 as $num => $inf) {
            $add($num, "Barangay {$num}", 'Zone 2', 2, $inf[1], $inf[2], $inf[0], $inf[3], 'South Caloocan');
        }

        // Zone 3 (District 2) - Barangays 25 to 35 (Grace Park West 1st to 3rd Ave)
        for ($num = 25; $num <= 35; $num++) {
            $add($num, "Barangay {$num}", 'Zone 3', 2, 14.6430 + (($num - 25) * 0.0012), 120.9810 + (($num - 25) * 0.0008), "Grace Park West 1st-3rd Ave (Brgy {$num})", 6500 + ($num * 50), 'South Caloocan');
        }

        // Zone 4 (District 2) - Barangays 36 to 48 (Grace Park West 4th to 7th Ave)
        for ($num = 36; $num <= 48; $num++) {
            $add($num, "Barangay {$num}", 'Zone 4', 2, 14.6465 + (($num - 36) * 0.0011), 120.9805 + (($num - 36) * 0.0007), "Grace Park West 4th-7th Ave (Brgy {$num})", 7000 + ($num * 40), 'South Caloocan');
        }

        // Zone 5 (District 2) - Barangays 49 to 58 (Grace Park West 8th to 10th Ave)
        for ($num = 49; $num <= 58; $num++) {
            $add($num, "Barangay {$num}", 'Zone 5', 2, 14.6510 + (($num - 49) * 0.0012), 120.9790 + (($num - 49) * 0.0008), "Grace Park West 8th-10th Ave (Brgy {$num})", 7500 + ($num * 45), 'South Caloocan');
        }

        // Zone 6 (District 2) - Barangays 59 to 76 (Grace Park West 10th to 12th Ave)
        for ($num = 59; $num <= 76; $num++) {
            $add($num, "Barangay {$num}", 'Zone 6', 2, 14.6535 + (($num - 59) * 0.0008), 120.9780 + (($num - 59) * 0.0006), "Grace Park West 10th-12th Ave (Brgy {$num})", 7200 + ($num * 35), 'South Caloocan');
        }

        // Zone 7 (District 1) - Barangays 77 to 81
        $add(77, 'Barangay 77', 'Zone 7', 1, 14.6568, 120.9754, 'Near Caloocan High School / 10th Ave', 14500, 'South Caloocan');
        $add(78, 'Barangay 78', 'Zone 7', 1, 14.6579, 120.9760, 'Near Phase 1, Sangandaan', 13200, 'South Caloocan');
        $add(79, 'Barangay 79', 'Zone 7', 1, 14.6592, 120.9772, 'Near Samson Road intersection', 12800, 'South Caloocan');
        $add(80, 'Barangay 80', 'Zone 7', 1, 14.6610, 120.9761, 'Near General San Miguel Street', 15000, 'South Caloocan');
        $add(81, 'Barangay 81', 'Zone 7', 1, 14.6601, 120.9790, 'Near Monumento Circle access', 9800, 'South Caloocan');

        // Zone 8 (District 1) - Barangays 82 to 85
        $add(82, 'Barangay 82', 'Zone 8', 1, 14.6615, 120.9798, 'Near Morning Breeze Subdivision', 10400, 'South Caloocan');
        $add(83, 'Barangay 83', 'Zone 8', 1, 14.6628, 120.9785, 'Morning Breeze Area', 11600, 'South Caloocan');
        $add(84, 'Barangay 84', 'Zone 8', 1, 14.6635, 120.9769, 'Near Asuncion Street', 12100, 'South Caloocan');
        $add(85, 'Barangay 85', 'Zone 8', 1, 14.6648, 120.9751, 'Bordering Tullahan River / Malabon', 13000, 'South Caloocan');

        // Zone 9 (District 2) - Barangays 86 to 98 (Grace Park East 1st to 5th Ave)
        for ($num = 86; $num <= 98; $num++) {
            $add($num, "Barangay {$num}", 'Zone 9', 2, 14.6450 + (($num - 86) * 0.0012), 120.9860 + (($num - 86) * 0.0006), "Grace Park East 1st-5th Ave (Brgy {$num})", 6800 + ($num * 40), 'South Caloocan');
        }

        // Zone 10 (District 2) - Barangays 99 to 116 (Grace Park East 6th to 10th Ave)
        for ($num = 99; $num <= 116; $num++) {
            $add($num, "Barangay {$num}", 'Zone 10', 2, 14.6500 + (($num - 99) * 0.0010), 120.9870 + (($num - 99) * 0.0005), "Grace Park East 6th-10th Ave (Brgy {$num})", 7100 + ($num * 30), 'South Caloocan');
        }

        // Zone 11 (District 2) - Barangays 117 to 131 (Grace Park East 11th to 12th Ave)
        for ($num = 117; $num <= 131; $num++) {
            $add($num, "Barangay {$num}", 'Zone 11', 2, 14.6560 + (($num - 117) * 0.0008), 120.9880 + (($num - 117) * 0.0005), "Grace Park East EDSA Corridor (Brgy {$num})", 7400 + ($num * 25), 'South Caloocan');
        }

        // Zone 12 (District 1) - Barangays 132 to 140
        $add(132, 'Barangay 132', 'Zone 12', 1, 14.6612, 120.9912, 'Bagong Barrio West (Near EDSA)', 11000, 'South Caloocan');
        $add(133, 'Barangay 133', 'Zone 12', 1, 14.6621, 120.9901, 'Malanting Street', 10500, 'South Caloocan');
        $add(134, 'Barangay 134', 'Zone 12', 1, 14.6630, 120.9892, 'Near Progreso Street', 12400, 'South Caloocan');
        $add(135, 'Barangay 135', 'Zone 12', 1, 14.6639, 120.9885, 'Near Reparo Road', 11800, 'South Caloocan');
        $add(136, 'Barangay 136', 'Zone 12', 1, 14.6645, 120.9899, 'General Malvar Street', 13100, 'South Caloocan');
        $add(137, 'Barangay 137', 'Zone 12', 1, 14.6651, 120.9915, 'Near Selya Street', 10900, 'South Caloocan');
        $add(138, 'Barangay 138', 'Zone 12', 1, 14.6640, 120.9928, 'Near boundary with EDSA Northbound', 11500, 'South Caloocan');
        $add(139, 'Barangay 139', 'Zone 12', 1, 14.6629, 120.9934, 'Near Old Samson Road', 12000, 'South Caloocan');
        $add(140, 'Barangay 140', 'Zone 12', 1, 14.6618, 120.9945, 'Near Cloverleaf Area border', 9700, 'South Caloocan');

        // Zone 13 (District 1) - Barangays 141 to 150
        $add(141, 'Barangay 141', 'Zone 13', 1, 14.6631, 120.9959, 'Bagong Barrio East', 10200, 'South Caloocan');
        $add(142, 'Barangay 142', 'Zone 13', 1, 14.6642, 120.9950, 'Near Plymouth Street', 11300, 'South Caloocan');
        $add(143, 'Barangay 143', 'Zone 13', 1, 14.6653, 120.9941, 'Near Dorotea Street', 10800, 'South Caloocan');
        $add(144, 'Barangay 144', 'Zone 13', 1, 14.6664, 120.9930, 'Near United Street', 11900, 'South Caloocan');
        $add(145, 'Barangay 145', 'Zone 13', 1, 14.6672, 120.9919, 'Near Industrial Road', 12600, 'South Caloocan');
        $add(146, 'Barangay 146', 'Zone 13', 1, 14.6681, 120.9932, 'Near NLEX Lane', 13400, 'South Caloocan');
        $add(147, 'Barangay 147', 'Zone 13', 1, 14.6670, 120.9948, 'Near Sheridan Street', 14000, 'South Caloocan');
        $add(148, 'Barangay 148', 'Zone 13', 1, 14.6659, 120.9961, 'Near Violeta Street', 15200, 'South Caloocan');
        $add(149, 'Barangay 149', 'Zone 13', 1, 14.6648, 120.9972, 'Near boundary with Balintawak', 14800, 'South Caloocan');
        $add(150, 'Barangay 150', 'Zone 13', 1, 14.6662, 120.9985, 'Near Skyway Stage 3 off-ramp area', 13900, 'South Caloocan');

        // Zone 14 (District 1) - Barangays 151 to 160
        $add(151, 'Barangay 151', 'Zone 14', 1, 14.6675, 120.9971, 'Near Libis Gozon', 14200, 'South Caloocan');
        $add(152, 'Barangay 152', 'Zone 14', 1, 14.6686, 120.9960, 'Near neighborhood public market', 13500, 'South Caloocan');
        $add(153, 'Barangay 153', 'Zone 14', 1, 14.6698, 120.9949, 'Near Baesa Road junction', 12700, 'South Caloocan');
        $add(154, 'Barangay 154', 'Zone 14', 1, 14.6709, 120.9938, 'Near Eternal Gardens Memorial Park', 11400, 'South Caloocan');
        $add(155, 'Barangay 155', 'Zone 14', 1, 14.6721, 120.9925, 'T. Santiago Street area', 10900, 'South Caloocan');
        $add(156, 'Barangay 156', 'Zone 14', 1, 14.6730, 120.9941, 'Near Libis Baesa', 11800, 'South Caloocan');
        $add(157, 'Barangay 157', 'Zone 14', 1, 14.6718, 120.9955, 'Near Mendez Street', 12200, 'South Caloocan');
        $add(158, 'Barangay 158', 'Zone 14', 1, 14.6705, 120.9969, 'Quirino Highway transitional pocket', 10600, 'South Caloocan');
        $add(159, 'Barangay 159', 'Zone 14', 1, 14.6692, 120.9982, 'Baesa Border Zone', 9900, 'South Caloocan');
        $add(160, 'Barangay 160', 'Zone 14', 1, 14.6704, 120.9998, 'Along Quezon City Boundary', 13100, 'South Caloocan');

        // Zone 15 (District 1) - Barangays 161 to 164
        $add(161, 'Barangay 161', 'Zone 15', 1, 14.6719, 121.0011, 'Near Santa Quiteria Road', 12500, 'South Caloocan');
        $add(162, 'Barangay 162', 'Zone 15', 1, 14.6735, 120.9995, 'Santa Quiteria Area', 11900, 'South Caloocan');
        $add(163, 'Barangay 163', 'Zone 15', 1, 14.6750, 120.9980, 'Near Tullahan River bend', 10800, 'South Caloocan');
        $add(164, 'Barangay 164', 'Zone 15', 1, 14.6762, 120.9965, 'Northernmost point of South Caloocan', 11500, 'South Caloocan');

        // ═════════════════════════════════════════════════════════════
        // NORTH CALOOCAN (District 3 - Zone 16 - 29 Barangays / 193 Total)
        // ═════════════════════════════════════════════════════════════
        $z16 = [
            165 => ['Barangay 165 (Bagbaguin)', 14.7185, 121.0112, 'Bagbaguin / General Luis St', 26500],
            166 => ['Barangay 166 (Kaybiga)', 14.7225, 121.0205, 'Kaybiga Public Market', 28900],
            167 => ['Barangay 167 (Llano)', 14.7335, 121.0255, 'Llano Road / Caloocan Sports Complex', 31200],
            168 => ['Barangay 168 (Deparo)', 14.7432, 121.0315, 'Deparo Road / El Pueblo', 35400],
            169 => ['Barangay 169 (BF Homes)', 14.7482, 121.0375, 'BF Homes Caloocan Phase 1', 19800],
            170 => ['Barangay 170 (Deparo 2)', 14.7525, 121.0335, 'Deparo 2 / Congressional Rd', 22100],
            171 => ['Barangay 171 (Bagumbong)', 14.7558, 121.0285, 'Bagumbong High School / Rainbow Village', 42000],
            172 => ['Barangay 172 (Urduja)', 14.7455, 121.0425, 'Urduja Village / Zabarte Rd', 33500],
            173 => ['Barangay 173 (Congress)', 14.7495, 121.0485, 'Congress Village / Camarin Road', 27800],
            174 => ['Barangay 174 (Camarin)', 14.7575, 121.0505, 'Camarin Public Market', 38200],
            175 => ['Barangay 175 (Camarin North)', 14.7625, 121.0545, 'Camarin D-Site / University of Caloocan North', 44500],
            
            // Historic 2024 Plebiscite Split of Bagong Silang (Barangays 176-A to 176-F / 176, 189-193)
            176 => ['Barangay 176-A (Bagong Silang Phase 1 & 2)', 14.7685, 121.0465, 'Bagong Silang Phase 1 / Silang Ave', 45000],
            189 => ['Barangay 176-B (Bagong Silang Phase 3 & 4)', 14.7725, 121.0515, 'Bagong Silang Phase 3 Plaza', 43500],
            190 => ['Barangay 176-C (Bagong Silang Phase 7 & 8)', 14.7765, 121.0555, 'Bagong Silang Phase 7 Commercial Area', 41200],
            191 => ['Barangay 176-D (Bagong Silang Phase 9)', 14.7805, 121.0585, 'Bagong Silang Phase 9 Terminal', 46000],
            192 => ['Barangay 176-E (Bagong Silang Phase 10)', 14.7845, 121.0625, 'Bagong Silang Phase 10 Boundary', 42800],
            193 => ['Barangay 176-F (Bagong Silang Phase 5)', 14.7705, 121.0595, 'Bagong Silang Health Center Phase 5', 41500],

            177 => ['Barangay 177 (Camarin Cielito)', 14.7665, 121.0575, 'Cielito Homes / Zabarte Junction', 34200],
            178 => ['Barangay 178 (Camarin Kiko)', 14.7695, 121.0605, 'Kiko Camarin / St. Vincent Church', 37600],
            179 => ['Barangay 179 (Amparo)', 14.7515, 121.0685, 'Amparo Subdivision / Quirino Highway', 31500],
            180 => ['Barangay 180 (Tala / Malaria)', 14.7615, 121.0725, 'Tala General Hospital / Malaria Area', 29800],
            181 => ['Barangay 181 (Pangarap Village)', 14.7685, 121.0775, 'Pangarap Village / Quirino Highway', 24600],
            182 => ['Barangay 182 (Pangarap North)', 14.7725, 121.0805, 'Pangarap Village Phase 2', 21800],
            183 => ['Barangay 183 (Bankers Village)', 14.7585, 121.0625, 'Bankers Village / Quirino Highway', 19400],
            184 => ['Barangay 184 (Tala Sanitarium)', 14.7645, 121.0695, 'San Jose Clinic / Tala Leprosarium', 18200],
            185 => ['Barangay 185 (Malaria Quirino)', 14.7595, 121.0785, 'Quirino Highway / North Bound Bus Terminal', 25400],
            186 => ['Barangay 186 (Barracks Tala)', 14.7675, 121.0735, 'Barracks Area / Tala Sports Complex', 22700],
            187 => ['Barangay 187 (Bagong Silang Tala)', 14.7755, 121.0625, 'Tala Boundary / Holy Cross', 24100],
            188 => ['Barangay 188 (Tala North Santo Niño)', 14.7785, 121.0705, 'Northernmost Point of Caloocan City', 27300],
        ];

        foreach ($z16 as $num => $inf) {
            $add($num, $inf[0], 'Zone 16', 3, $inf[1], $inf[2], $inf[3], $inf[4], 'North Caloocan');
        }

        self::$defaultBarangays = $b;
        return $b;
    }

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    private static ?array $cachedList = null;

    /**
     * Retrieve all 193 Barangays across South and North Caloocan.
     * Optionally filtered by region ('South Caloocan' or 'North Caloocan').
     */
    public function allForSurveillance(?string $region = null): array
    {
        if (self::$cachedList === null) {
            $dict = self::initDefaultBarangays();

            try {
                $dbRows = $this->db->select($this->table, [], ['order' => 'barangay_no.asc', 'limit' => 300]);
                if (!empty($dbRows)) {
                    foreach ($dbRows as $row) {
                        $num = (int)($row['barangay_no'] ?? 0);
                        if (isset($dict[$num])) {
                            if (!empty($row['latitude']))   $dict[$num]['lat'] = (float)$row['latitude'];
                            if (!empty($row['longitude']))  $dict[$num]['lng'] = (float)$row['longitude'];
                            if (!empty($row['zone']))       $dict[$num]['zone'] = $row['zone'];
                            if (!empty($row['landmark']))   $dict[$num]['landmark'] = $row['landmark'];
                            if (!empty($row['population'])) $dict[$num]['population'] = (int)$row['population'];
                            if (!empty($row['name']))       $dict[$num]['name'] = $row['name'];
                            if (!empty($row['district']))   $dict[$num]['district'] = (int)$row['district'];
                        }
                    }
                }
            } catch (Throwable $e) {}

            $list = [];
            foreach ($dict as $num => $spec) {
                $list[] = [
                    'name'          => $spec['name'] ?? "Barangay {$num}",
                    'barangay_no'   => $num,
                    'zone'          => $spec['zone'],
                    'district'      => $spec['district'] ?? 1,
                    'region'        => $spec['region'] ?? 'South Caloocan',
                    'landmark'      => $spec['landmark'] ?? '',
                    'lat'           => (float)$spec['lat'],
                    'lng'           => (float)$spec['lng'],
                    'population'    => (int)($spec['population'] ?? 10000),
                    'dengue'        => 0,
                    'influenza'     => 0,
                    'leptospirosis' => 0,
                    'total'         => 0,
                    'risk'          => 'Low',
                    'case_rate'     => 0,
                ];
            }
            self::$cachedList = $list;
        }

        if ($region) {
            return array_values(array_filter(self::$cachedList, fn($item) => strcasecmp($item['region'], $region) === 0));
        }

        return self::$cachedList;
    }

    /**
     * Caloocan City Regional Geographics Reference
     */
    public static function getRegions(): array
    {
        return [
            'South Caloocan' => [
                'name'         => 'South Caloocan',
                'description'  => 'Urban Center / Commercial Hub',
                'area_km2'     => 13.63,
                'width_km'     => 7.0, // 7 km East-West
                'length_km'    => 6.0, // 6 km North-South
                'districts'    => [1, 2],
                'zones'        => ['Zone 1', 'Zone 2', 'Zone 3', 'Zone 4', 'Zone 5', 'Zone 6', 'Zone 7', 'Zone 8', 'Zone 9', 'Zone 10', 'Zone 11', 'Zone 12', 'Zone 13', 'Zone 14', 'Zone 15'],
                'barangay_count' => 164,
                'center'       => [120.9845, 14.6560],
                'zoom'         => 13.5
            ],
            'North Caloocan' => [
                'name'         => 'North Caloocan',
                'description'  => 'Residential Subdivisions & Rolling Hills',
                'area_km2'     => 39.71,
                'width_km'     => 10.0, // 10 km East-West
                'length_km'    => 8.0,  // 8 km North-South
                'districts'    => [3],
                'zones'        => ['Zone 16'],
                'barangay_count' => 29, // Barangays 165 to 193 (includes Bagong Silang 176-A to 176-F)
                'center'       => [121.0450, 14.7580],
                'zoom'         => 12.8
            ],
            'Whole City' => [
                'name'         => 'City of Caloocan',
                'description'  => 'Non-Contiguous Dual Administrative Territory',
                'area_km2'     => 53.33,
                'total_barangays' => 193,
                'total_zones'  => 16,
                'center'       => [121.0150, 14.7100],
                'zoom'         => 11.5
            ]
        ];
    }
}
