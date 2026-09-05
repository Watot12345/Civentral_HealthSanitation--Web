    <!-- ================================================================ -->
    <!--  SECTION 3: SAVED TEMPLATES                                      -->
    <!-- ================================================================ -->
    <div id="section-templates" class="report-section">
        <div class="report-card rounded-b-3xl rounded-tr-3xl p-5 sm:p-7 bg-white border border-[#B4D4FF]/30">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-base font-semibold text-[#176B87] flex items-center gap-2">
                        <i class="fa-regular fa-folder-open text-[#86B6F6]"></i>
                        Saved Report Templates
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Standardized templates and pre-configured report parameters</p>
                </div>
                <div class="flex items-center gap-2.5">
                    <div class="relative">
                        <input type="text" id="templateSearchInput" placeholder="Search templates..." oninput="filterTemplatesGrid()" class="pl-8 pr-3 py-1.5 rounded-xl text-xs border border-[#B4D4FF]/40 bg-white/70 focus:outline-none focus:border-[#176B87]" />
                        <i class="fa-solid fa-magnifying-glass text-slate-400 absolute left-2.5 top-2.5 text-xs"></i>
                    </div>
                    <?php if ($canCreateTpl): ?>
                    <button onclick="openCreateTemplateModal()" class="btn-primary px-3.5 py-1.5 rounded-xl text-xs font-semibold text-white flex items-center gap-1.5 shadow-xs">
                        <i class="fa-solid fa-plus"></i> Create Template
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Templates Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="templatesGrid">
                <p class="text-xs text-slate-400 col-span-full py-8 text-center">Loading templates...</p>
            </div>
        </div>
    </div>

