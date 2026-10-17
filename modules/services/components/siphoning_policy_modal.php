<?php
// modules/services/components/siphoning_policy_modal.php
/**
 * Component: Septic Tank Siphoning Policy & Standards Modal
 *
 * Implements clean view architecture by decoupling the statutory guidance
 * presentation from the main procedural page. Driven by SepticSiphoningPolicy.
 */
require_once __DIR__ . '/../../../app/Policies/SepticSiphoningPolicy.php';
$policy = \App\Policies\SepticSiphoningPolicy::getPolicyDetails();
$s1 = $policy['section_1_schedule'];
$s2 = $policy['section_2_warning_signs'];
$s3 = $policy['section_3_legal_standards'];
?>

<!-- ============================================================ -->
<!-- SIPHONING POLICY & REGULATORY STANDARDS MODAL                -->
<!-- Architecture: View Component bound to SepticSiphoningPolicy  -->
<!-- ============================================================ -->
<div id="siphoningPolicyModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl max-h-[92vh] overflow-y-auto border border-slate-100 flex flex-col">
        
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200 sticky top-0 bg-white/95 backdrop-blur z-10 rounded-t-3xl">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-600 to-emerald-700 flex items-center justify-center text-white shadow-md shadow-emerald-200">
                    <i class="fa-solid fa-truck-droplet text-lg"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-lg leading-tight"><?php echo htmlspecialchars($policy['title']); ?></h3>
                    <p class="text-xs text-slate-500 font-medium"><?php echo htmlspecialchars($policy['subtitle']); ?></p>
                </div>
            </div>
            <button type="button" onclick="closeModal('siphoningPolicyModal')" class="w-9 h-9 rounded-xl hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-700 transition" aria-label="Close dialog">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="p-6 md:p-8 space-y-6 text-slate-700">
            <!-- Statutory Legal Authority Banner -->
            <div class="bg-gradient-to-r from-teal-50 via-emerald-50 to-cyan-50 border border-teal-200/80 rounded-2xl p-5 flex items-start gap-4 shadow-xs">
                <div class="w-10 h-10 rounded-xl bg-teal-700 text-white flex items-center justify-center flex-shrink-0 mt-0.5 shadow-sm">
                    <i class="fa-solid fa-scale-balanced text-base"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h4 class="font-bold text-slate-900 text-sm">Official Environmental Sanitation Standard</h4>
                        <span class="px-2 py-0.5 bg-teal-200/80 text-teal-900 text-[10px] font-black uppercase rounded tracking-wider">Philippine Law</span>
                    </div>
                    <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                        Enacted in strict compliance with <strong>Presidential Decree No. 856 (Sanitation Code of the Philippines - Chapter XVII)</strong>,
                        <strong>Republic Act No. 9275 (Clean Water Act of 2004)</strong>, and <strong>DOH Administrative Order No. 2019-0047</strong>.
                    </p>
                </div>
            </div>

            <!-- SECTION 1: WHEN TO SIPHON AGAIN (SCHEDULE REQUIREMENTS) -->
            <div class="border border-slate-200 rounded-2xl p-5 bg-white shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-teal-100 text-teal-800 font-black text-xs flex items-center justify-center">1</span>
                        <h4 class="font-bold text-slate-900 text-base"><?php echo htmlspecialchars($s1['title']); ?></h4>
                    </div>
                    <span class="px-2.5 py-0.5 bg-teal-50 border border-teal-200 text-teal-800 rounded-full text-xs font-bold">
                        Standard Schedule: <?php echo htmlspecialchars($s1['standard_schedule']); ?>
                    </span>
                </div>

                <div class="space-y-3">
                    <!-- Household Size Rules -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/50">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-bold text-emerald-900 flex items-center gap-1.5">
                                    <i class="fa-solid fa-users text-emerald-600"></i> 5+ People in the House
                                </span>
                                <span class="text-xs px-2.5 py-0.5 bg-emerald-200 text-emerald-900 rounded-full font-black">Every 3 Years</span>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed"><?php echo htmlspecialchars($s1['large_household']); ?> Higher bio-load accelerates sludge buildup and reduces retention efficiency.</p>
                        </div>
                        <div class="p-4 rounded-xl border border-blue-200 bg-blue-50/50">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-bold text-blue-900 flex items-center gap-1.5">
                                    <i class="fa-solid fa-user-group text-blue-600"></i> 2–4 People in the House
                                </span>
                                <span class="text-xs px-2.5 py-0.5 bg-blue-200 text-blue-900 rounded-full font-black">Every 5 Years</span>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed"><?php echo htmlspecialchars($s1['small_household']); ?> Standard domestic usage maintains stable anaerobic digestion for up to 5 years.</p>
                        </div>
                    </div>

                    <!-- QC Free Concessionaire Card -->
                    <div class="p-4 rounded-xl border border-amber-200 bg-amber-50/70">
                        <div class="flex items-center gap-2 text-amber-900 font-bold text-sm mb-1">
                            <i class="fa-solid fa-droplet text-amber-600"></i>
                            <span>Quezon City & Metro Manila Free Desludging Cycle</span>
                        </div>
                        <p class="text-xs text-slate-700 leading-relaxed">
                            <?php echo htmlspecialchars($s1['quezon_city_free']); ?>
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2.5">
                            <a href="tel:1626" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-amber-300 rounded-lg text-xs font-bold text-slate-800 hover:bg-amber-100 transition shadow-2xs">
                                <i class="fa-solid fa-phone text-teal-700 text-[11px]"></i> Maynilad Hotline: <strong>1626</strong> (West Zone)
                            </a>
                            <a href="tel:1627" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-amber-300 rounded-lg text-xs font-bold text-slate-800 hover:bg-amber-100 transition shadow-2xs">
                                <i class="fa-solid fa-phone text-blue-700 text-[11px]"></i> Manila Water Hotline: <strong>1627</strong> (East Zone)
                            </a>
                        </div>
                    </div>

                    <!-- Interactive Siphoning Schedule Calculator Widget (Full State Machine Lifecycle) -->
                    <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/90 shadow-2xs">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-1.5">
                                <i class="fa-solid fa-arrows-spin text-teal-700 text-sm"></i> Siphoning Lifecycle & State Machine
                            </p>
                            <span class="text-[10px] px-2 py-0.5 bg-teal-100 text-teal-900 rounded font-bold uppercase tracking-wider">Automated Workflow</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">1. Household Size:</label>
                                <select id="policyCalculatorOccupants" onchange="runSiphoningCalculator()" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white font-medium focus:ring-2 focus:ring-teal-500 outline-none">
                                    <option value="5" selected>5+ people living in house (Siphon every 3 yrs)</option>
                                    <option value="4">2–4 people living in house (Siphon every 5 yrs)</option>
                                    <option value="1">1 person living in house (Siphon every 5 yrs)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">2. Last Siphoned Date:</label>
                                <input type="date" id="policyCalculatorLastDate" onchange="runSiphoningCalculator()" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white font-medium focus:ring-2 focus:ring-teal-500 outline-none">
                            </div>
                        </div>

                        <!-- Live State Machine Output Card -->
                        <div id="calculatorResult" class="p-4 bg-white rounded-xl border border-slate-200 text-xs text-slate-700 shadow-2xs transition-all">
                            <!-- Populated dynamically by runSiphoningCalculator() -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: WARNING SIGNS ("SIPHON NOW") -->
            <div class="border border-rose-200 rounded-2xl p-5 bg-rose-50/30 shadow-xs">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-rose-100 text-rose-800 font-black text-xs flex items-center justify-center">2</span>
                        <h4 class="font-bold text-slate-900 text-base"><?php echo htmlspecialchars($s2['title']); ?></h4>
                    </div>
                    <span class="px-2.5 py-0.5 bg-rose-600 text-white rounded-full text-[10px] font-black uppercase tracking-wider">Hazard Override</span>
                </div>
                <p class="text-xs text-slate-600 mb-3 leading-relaxed">
                    <?php echo htmlspecialchars($s2['directive']); ?> If any of these diagnostic symptoms occur, immediate action is required:
                </p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <?php foreach ($s2['signs'] as $sign): ?>
                    <label class="p-3.5 bg-white rounded-xl border border-rose-200 shadow-2xs flex items-start gap-3 cursor-pointer hover:border-rose-400 transition select-none">
                        <input type="checkbox" name="warning_sign_check" value="<?php echo htmlspecialchars($sign['key']); ?>" onchange="evaluateWarningSignsTriage()" class="mt-1 w-4 h-4 text-rose-600 rounded border-slate-300 focus:ring-rose-500">
                        <div>
                            <p class="text-xs font-bold text-slate-900"><?php echo htmlspecialchars($sign['title']); ?></p>
                            <p class="text-[11px] text-slate-500 mt-0.5"><?php echo htmlspecialchars($sign['description']); ?></p>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div id="diagnosticTriageAlert" class="hidden mt-3 p-3 bg-rose-100 border border-rose-300 rounded-xl text-xs text-rose-900 flex items-center gap-2.5">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600 text-lg"></i>
                    <div>
                        <strong class="font-black">CRITICAL ACTION: SIPHON NOW!</strong>
                        <p class="text-[11px] text-rose-800 mt-0.5">One or more active warning signs detected. Tank requires emergency desludging to avert biohazard overflow.</p>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: LEGAL & ENVIRONMENTAL STANDARDS -->
            <div class="border border-slate-200 rounded-2xl p-5 bg-white shadow-xs">
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-800 font-black text-xs flex items-center justify-center">3</span>
                    <h4 class="font-bold text-slate-900 text-base"><?php echo htmlspecialchars($s3['title']); ?></h4>
                </div>
                
                <div class="space-y-3">
                    <div class="p-3.5 bg-slate-900 text-white rounded-xl flex items-center justify-between">
                        <span class="font-bold text-sm">Direct Statutory Answer:</span>
                        <span class="px-2.5 py-1 bg-emerald-500 text-white text-xs font-bold rounded-lg"><?php echo htmlspecialchars($s3['answer']); ?></span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                        <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl space-y-1.5">
                            <p class="font-bold text-rose-800 flex items-center gap-1.5">
                                <i class="fa-solid fa-ban text-rose-600"></i> Prohibitions
                            </p>
                            <ul class="list-disc list-inside space-y-1 text-slate-700 leading-relaxed">
                                <li><strong><?php echo htmlspecialchars($s3['prohibition']); ?></strong> Direct handling exposes individuals to toxic H₂S and methane.</li>
                                <li><strong><?php echo htmlspecialchars($s3['prohibited_dumping']); ?></strong> Violators face criminal prosecution under RA 9275 Section 27.</li>
                            </ul>
                        </div>
                        <div class="p-4 bg-teal-50 border border-teal-200 rounded-xl space-y-1.5">
                            <p class="font-bold text-teal-900 flex items-center gap-1.5">
                                <i class="fa-solid fa-certificate text-teal-600"></i> Mandatory Compliance
                            </p>
                            <ul class="list-disc list-inside space-y-1 text-slate-700 leading-relaxed">
                                <li><strong><?php echo htmlspecialchars($s3['accreditation']); ?></strong></li>
                                <li>Must obtain an official <strong>Certificate of Desludging & Treatment Manifest</strong>.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-lg">
                                ₱
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800">Commercial Benchmark Rate</p>
                                <p class="text-xs text-slate-500"><?php echo htmlspecialchars($s3['cost_range']['basis']); ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-base font-black text-slate-900">₱<?php echo number_format($s3['cost_range']['min_php']); ?> to ₱<?php echo number_format($s3['cost_range']['max_php']); ?></p>
                            <p class="text-[10px] text-slate-400 font-semibold uppercase"><?php echo htmlspecialchars($s3['cost_range']['unit']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-3xl flex flex-col sm:flex-row justify-between items-center gap-3">
            <p class="text-xs text-slate-500 font-medium flex items-center gap-1.5">
                <i class="fa-solid fa-shield-halved text-teal-700"></i>
                <span>CivCentral Wastewater Services Policy Engine</span>
            </p>
            <div class="flex items-center gap-2">
                <button type="button" onclick="closeModal('siphoningPolicyModal')" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-semibold transition">
                    Close Policy
                </button>
            </div>
        </div>
    </div>
</div>
