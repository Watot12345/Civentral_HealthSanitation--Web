<?php
/**
 * Civentral Accessibility System
 * Features:
 * - Brand-aligned floating toggle button (#176B87 / #86B6F6 / #EEF5FF)
 * - Compact bottom-right popover (short modal, non-centered)
 * - Interactive hover states on toggle rows and keyboard shortcut keys
 * - 3 fully functional checkmarks with precise visual targets & live On/Off status pills:
 *     1. High-Contrast Focus Rings  -> bold brand ring on mouse AND keyboard focus
 *     2. Enlarged Text Scale (+12%) -> root rem scale expanding tables, cards, & forms
 *     3. Reduce Motion & Effects    -> freezes CSS animations/transitions AND
 *        chart (ApexCharts) draw-in animation, which CSS alone cannot stop
 * - Interactive Quick Keyboard Keys (Data masking Ctrl+Shift+D with live status pill, Escape, Tab, etc.)
 * - Instant FOUC-free initialization and persistent preferences via localStorage
 */
?>
<!-- Immediate Initialization (prevents FOUC before DOMContentLoaded) -->
<script>
(function() {
  try {
    var f = localStorage.getItem('civentral_a11y_focus') === 'true';
    var t = localStorage.getItem('civentral_a11y_text') === 'true';
    var m = localStorage.getItem('civentral_a11y_motion') === 'true';
    var doc = document.documentElement;
    if (f) doc.classList.add('a11y-focus-enhanced');
    if (t) doc.classList.add('a11y-large-text');
    if (m) doc.classList.add('a11y-reduced-motion');
  } catch(e) {}
})();
</script>

<style>
/* ============================================================
   CIVENTRAL BRAND ACCESSIBILITY STYLES (#176B87 & #86B6F6)
   ============================================================ */

/* 1. Enhanced High-Contrast Focus Mode
   Targets interactive elements (:focus and :focus-visible) so the ring
   is unmistakably visible for BOTH mouse clicks and keyboard (Tab) navigation. */
html.a11y-focus-enhanced *:focus,
html.a11y-focus-enhanced *:focus-visible,
body.a11y-focus-enhanced *:focus,
body.a11y-focus-enhanced *:focus-visible {
  outline: 3px solid #176B87 !important;
  outline-offset: 2px !important;
  box-shadow: 0 0 0 5px rgba(134, 182, 246, 0.75) !important;
  transition: outline-offset 0.1s ease !important;
}

/* Focused text fields keep a readable brand tint */
html.a11y-focus-enhanced input:not([type="checkbox"]):not([type="radio"]):focus,
html.a11y-focus-enhanced select:focus,
html.a11y-focus-enhanced textarea:focus,
body.a11y-focus-enhanced input:not([type="checkbox"]):not([type="radio"]):focus,
body.a11y-focus-enhanced select:focus,
body.a11y-focus-enhanced textarea:focus {
  background-color: #EEF5FF !important;
  border-color: #176B87 !important;
}

/* Keep floating trigger button clean without heavy outer rings */
#a11yFloatingWidget button:focus,
#a11yFloatingWidget button:focus-visible {
  outline: 2px solid #86B6F6 !important;
  outline-offset: 2px !important;
  box-shadow: 0 0 0 4px rgba(23, 107, 135, 0.5) !important;
}

/* 2. Large Text Mode (+12.5% Root Scale)
   Scaling documentElement (root) reliably expands all rem-based typography
   in Tailwind CSS across tables, cards, badges, and form controls. */
html.a11y-large-text {
  font-size: 112.5% !important; /* 16px -> 18px! All Tailwind rem units expand! */
}
html.a11y-large-text body {
  font-size: 1.05rem !important;
}
/* Tables: expand headers, cell contents, and action buttons */
html.a11y-large-text table {
  font-size: 0.95rem !important;
}
html.a11y-large-text table th {
  font-size: 0.85rem !important;
  letter-spacing: 0.03em !important;
}
html.a11y-large-text table td {
  font-size: 0.95rem !important;
}
/* Forms & inputs: expand typography in inputs, selects, labels, and textareas */
html.a11y-large-text input,
html.a11y-large-text select,
html.a11y-large-text textarea {
  font-size: 0.95rem !important;
}
html.a11y-large-text label {
  font-size: 0.95rem !important;
}
/* Cards & headings */
html.a11y-large-text h1 { font-size: 1.85rem !important; }
html.a11y-large-text h2 { font-size: 1.5rem !important; }
html.a11y-large-text h3 { font-size: 1.25rem !important; }
html.a11y-large-text h4 { font-size: 1.05rem !important; }

/* Keep accessibility popover UI compact and contained so it doesn't push offscreen */
#accessibilityPopover {
  font-size: 12px !important;
}
#accessibilityPopover h3 {
  font-size: 12px !important;
}
#accessibilityPopover p {
  font-size: 11px !important;
}

/* 3. Reduced Motion Mode
   Duration-based (0.001ms instead of "none") on purpose: animations and
   transitions still fire their end events, so components that rely on
   animationend/transitionend are never left hanging. */
html.a11y-reduced-motion,
html.a11y-reduced-motion * {
  scroll-behavior: auto !important;
}
html.a11y-reduced-motion *,
html.a11y-reduced-motion *::before,
html.a11y-reduced-motion *::after,
body.a11y-reduced-motion *,
body.a11y-reduced-motion *::before,
body.a11y-reduced-motion *::after {
  animation-duration: 0.001ms !important;
  animation-delay: 0ms !important;
  animation-iteration-count: 1 !important;
  transition-duration: 0.001ms !important;
  transition-delay: 0ms !important;
  scroll-behavior: auto !important;
}
/* Instantly stops all Tailwind utility spinning/pulsing/bouncing animations */
html.a11y-reduced-motion .animate-spin,
html.a11y-reduced-motion .animate-pulse,
html.a11y-reduced-motion .animate-bounce,
html.a11y-reduced-motion .animate-ping,
body.a11y-reduced-motion .animate-spin,
body.a11y-reduced-motion .animate-pulse,
body.a11y-reduced-motion .animate-bounce,
body.a11y-reduced-motion .animate-ping {
  animation: none !important;
}

/* 4. Live On/Off Status Pill (option feedback inside the popover) */
.a11y-state-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 2px 9px;
  border-radius: 9999px;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  background-color: #f1f5f9;
  color: #64748b;
  border: 1px solid #cbd5e1;
  transition: all 0.2s ease;
}
.a11y-state-pill::before {
  content: '';
  width: 6px;
  height: 6px;
  border-radius: 9999px;
  background-color: #94a3b8;
}
.a11y-state-pill[data-on="true"] {
  background-color: #176B87;
  color: #ffffff;
  border-color: #176B87;
  box-shadow: 0 2px 6px rgba(23, 107, 135, 0.3);
}
.a11y-state-pill[data-on="true"]::before {
  background-color: #86B6F6;
}

/* Floating Accessibility Widget Positioning */
#a11yFloatingWidget {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 50;
}

/* Custom Interactive Key Badges */
.a11y-key-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 3px 8px;
  font-size: 11px;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-weight: 700;
  border-radius: 6px;
  background-color: #f1f5f9;
  color: #1e293b;
  border: 1px solid #cbd5e1;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
  user-select: none;
}
.a11y-key-badge:hover {
  background-color: #176B87;
  color: #ffffff;
  border-color: #176B87;
  transform: translateY(-1.5px) scale(1.05);
  box-shadow: 0 4px 10px rgba(23, 107, 135, 0.35);
}

/* Custom Styled Checkmarks & Card State Styling */
.a11y-card-row {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.a11y-card-row:hover {
  background-color: #EEF5FF !important;
  border-color: #86B6F6 !important;
  transform: translateY(-1px);
}
.a11y-card-row.is-checked {
  background-color: #EEF5FF !important;
  border-color: #86B6F6 !important;
  box-shadow: 0 2px 10px rgba(134, 182, 246, 0.25) !important;
}
.a11y-card-row.is-checked .a11y-check-indicator {
  background-color: #176B87 !important;
  border-color: #176B87 !important;
}
.a11y-card-row.is-checked .a11y-check-indicator i {
  display: inline-block !important;
}
.a11y-card-row:hover .a11y-check-indicator {
  border-color: #86B6F6 !important;
}
input[type="checkbox"]:focus-visible + .a11y-check-indicator {
  outline: 2px solid #176B87 !important;
  outline-offset: 2px !important;
  box-shadow: 0 0 0 3px rgba(134, 182, 246, 0.5) !important;
}
</style>

<!-- Floating Keyboard Accessibility Toggle Button (Full Size Icon, Borderless, Civentral Brand Theme) -->
<div id="a11yFloatingWidget">
  <button type="button"
          id="accessibilityToggleBtn"
          onclick="CiventralA11y.togglePanel(event)"
          class="relative w-12 h-12 bg-[#176B87] hover:bg-[#0d4f64] text-white rounded-full shadow-xl hover:shadow-2xl flex items-center justify-center focus:outline-none transition-all duration-200 cursor-pointer group"
          aria-haspopup="dialog"
          aria-expanded="false"
          aria-controls="accessibilityPopover"
          aria-label="Keyboard Controls (Shortcut: Alt + K)"
          title="Keyboard Controls (Alt + K)">
    <i class="fa-solid fa-universal-access text-2xl text-white group-hover:scale-110 group-hover:text-[#86B6F6] transition-all duration-200" aria-hidden="true"></i>
    <span id="a11yActiveBadge" class="hidden absolute top-0 right-0 w-3 h-3 bg-[#86B6F6] rounded-full shadow-xs"></span>
  </button>
</div>

<!-- Compact Bottom-Right Popover (Short Modal, Anchored, Non-Centered) -->
<div id="accessibilityPopover"
     class="hidden fixed bottom-20 right-6 z-[9999] w-[340px] sm:w-[380px] max-h-[80vh] bg-white rounded-2xl shadow-2xl border border-slate-200 flex flex-col overflow-hidden transition-all duration-200 origin-bottom-right"
     role="dialog"
     aria-modal="true"
     aria-labelledby="a11yPanelTitle">
  
  <!-- Popover Header (Civentral Brand Gradient) -->
  <div class="flex items-center justify-between px-5 py-3.5 bg-gradient-to-r from-[#176B87] to-[#0d4f64] text-white select-none">
    <div class="flex items-center gap-2.5">
      <div class="w-8 h-8 rounded-xl bg-white/15 text-[#86B6F6] flex items-center justify-center">
        <i class="fa-solid fa-universal-access text-base" aria-hidden="true"></i>
      </div>
      <div>
        <h3 id="a11yPanelTitle" class="font-bold text-xs text-white tracking-wide">Keyboard &amp; Display Controls</h3>
        <p class="text-[10px] text-[#EEF5FF]/80">Quick Controls &amp; Shortcuts (Alt + K)</p>
      </div>
    </div>
    <button type="button"
            onclick="CiventralA11y.closePanel()"
            class="w-7 h-7 rounded-lg hover:bg-white/20 text-white/80 hover:text-white flex items-center justify-center focus:outline-none focus-visible:ring-2 focus-visible:ring-[#86B6F6] transition cursor-pointer"
            aria-label="Close accessibility controls">
      <i class="fa-solid fa-xmark text-xs" aria-hidden="true"></i>
    </button>
  </div>

  <!-- Popover Scrollable Body -->
  <div class="p-4 space-y-3 text-slate-700 overflow-y-auto max-h-[calc(80vh-110px)] text-xs">
    
    <!-- Option 1: Enhanced Keyboard Focus Mode -->
    <div id="a11yCardFocus"
         onclick="CiventralA11y.handleRowClick(event, 'focus')"
         class="a11y-card-row flex items-start justify-between p-3 bg-slate-50/90 rounded-xl border border-slate-200 cursor-pointer group select-none transition-all">
      <div class="pr-3 flex-1">
        <div class="flex items-center gap-2 font-bold text-xs text-slate-900 group-hover:text-[#176B87] transition-colors">
          <i class="fa-solid fa-keyboard text-[#176B87] group-hover:scale-110 transition-transform" aria-hidden="true"></i>
          <span>High-Contrast Focus Rings</span>
        </div>
        <p class="text-[11px] text-slate-500 mt-1 leading-snug">
          Bold brand ring on every focused link, button, or field &mdash; with the mouse or the keyboard.
        </p>
        <!-- Live status feedback for this option + Interactive test button -->
        <div class="mt-2.5 flex items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <span class="a11y-state-pill" id="a11yStateFocus" data-on="false">OFF</span>
            <span class="text-[10px] font-semibold text-slate-400">Click any control to see the ring</span>
          </div>
          <button type="button"
                  id="a11yTestFocusBtn"
                  onclick="event.stopPropagation(); this.focus();"
                  class="text-[10px] px-2 py-0.5 bg-white hover:bg-[#EEF5FF] text-[#176B87] font-bold border border-slate-300 rounded shadow-2xs hover:border-[#86B6F6] transition cursor-pointer"
                  title="Click to test focus ring on this button">
            Test Ring
          </button>
        </div>
      </div>
      <div class="pt-0.5 relative flex items-center justify-center cursor-pointer"
           onclick="event.stopPropagation(); CiventralA11y.toggleOption('focus');">
        <input type="checkbox"
               id="a11yToggleFocus"
               onchange="CiventralA11y.toggleFocus(this.checked)"
               onclick="event.stopPropagation();"
               class="w-5 h-5 opacity-0 absolute inset-0 cursor-pointer z-10 m-0" />
        <div id="a11yCheckIndicatorFocus"
             class="a11y-check-indicator w-5 h-5 rounded border-2 border-slate-300 bg-white flex items-center justify-center transition-all duration-200 group-hover:border-[#86B6F6] pointer-events-none">
          <i class="fa-solid fa-check text-[10px] text-white hidden"></i>
        </div>
      </div>
    </div>

    <!-- Option 2: Large Text Mode -->
    <div id="a11yCardText"
         onclick="CiventralA11y.handleRowClick(event, 'text')"
         class="a11y-card-row flex items-start justify-between p-3 bg-slate-50/90 rounded-xl border border-slate-200 cursor-pointer group select-none transition-all">
      <div class="pr-3 flex-1">
        <div class="flex items-center gap-2 font-bold text-xs text-slate-900 group-hover:text-[#176B87] transition-colors">
          <i class="fa-solid fa-text-height text-[#176B87] group-hover:scale-110 transition-transform" aria-hidden="true"></i>
          <span>Enlarged Text Scale (+12%)</span>
        </div>
        <p class="text-[11px] text-slate-500 mt-1 leading-snug">
          Expands typography sizing across tables, cards, and forms.
        </p>
        <div class="mt-2.5 flex items-center gap-2">
          <span class="a11y-state-pill" id="a11yStateText" data-on="false">OFF</span>
          <span class="text-[10px] font-semibold text-slate-400">Scales the whole interface</span>
        </div>
      </div>
      <div class="pt-0.5 relative flex items-center justify-center cursor-pointer"
           onclick="event.stopPropagation(); CiventralA11y.toggleOption('text');">
        <input type="checkbox"
               id="a11yToggleText"
               onchange="CiventralA11y.toggleLargeText(this.checked)"
               onclick="event.stopPropagation();"
               class="w-5 h-5 opacity-0 absolute inset-0 cursor-pointer z-10 m-0" />
        <div id="a11yCheckIndicatorText"
             class="a11y-check-indicator w-5 h-5 rounded border-2 border-slate-300 bg-white flex items-center justify-center transition-all duration-200 group-hover:border-[#86B6F6] pointer-events-none">
          <i class="fa-solid fa-check text-[10px] text-white hidden"></i>
        </div>
      </div>
    </div>

    <!-- Option 3: Reduced Motion -->
    <div id="a11yCardMotion"
         onclick="CiventralA11y.handleRowClick(event, 'motion')"
         class="a11y-card-row flex items-start justify-between p-3 bg-slate-50/90 rounded-xl border border-slate-200 cursor-pointer group select-none transition-all">
      <div class="pr-3 flex-1">
        <div class="flex items-center gap-2 font-bold text-xs text-slate-900 group-hover:text-[#176B87] transition-colors">
          <i class="fa-solid fa-person-walking-dashed-line-arrow-right text-[#176B87] group-hover:scale-110 transition-transform" aria-hidden="true"></i>
          <span>Reduce Motion &amp; Effects</span>
        </div>
        <p class="text-[11px] text-slate-500 mt-1 leading-snug">
          Freezes CSS animations, transitions, and chart draw-in motion instantly.
        </p>
        <div class="mt-2.5 flex items-center gap-2">
          <span class="a11y-state-pill" id="a11yStateMotion" data-on="false">OFF</span>
          <span class="text-[10px] font-semibold text-slate-400">Spinners, pulses &amp; charts settle</span>
        </div>
      </div>
      <div class="pt-0.5 relative flex items-center justify-center cursor-pointer"
           onclick="event.stopPropagation(); CiventralA11y.toggleOption('motion');">
        <input type="checkbox"
               id="a11yToggleMotion"
               onchange="CiventralA11y.toggleReducedMotion(this.checked)"
               onclick="event.stopPropagation();"
               class="w-5 h-5 opacity-0 absolute inset-0 cursor-pointer z-10 m-0" />
        <div id="a11yCheckIndicatorMotion"
             class="a11y-check-indicator w-5 h-5 rounded border-2 border-slate-300 bg-white flex items-center justify-center transition-all duration-200 group-hover:border-[#86B6F6] pointer-events-none">
          <i class="fa-solid fa-check text-[10px] text-white hidden"></i>
        </div>
      </div>
    </div>

    <!-- Keyboard Shortcuts Interactive Section -->
    <div class="pt-2 border-t border-slate-100">
      <div class="flex items-center justify-between mb-2">
        <h4 class="font-extrabold uppercase text-[10px] tracking-wider text-slate-400 flex items-center gap-1.5">
          <i class="fa-solid fa-universal-access text-[#176B87]" aria-hidden="true"></i> Quick Keyboard Keys
        </h4>
        <span class="text-[9px] text-[#176B87] font-semibold">Hover keys to inspect</span>
      </div>

      <div class="grid grid-cols-1 gap-1.5">
        
        <div onclick="CiventralA11y.togglePanel(event)"
             class="flex items-center justify-between p-2 rounded-lg bg-slate-50 hover:bg-[#EEF5FF] border border-slate-100 transition-colors group cursor-pointer"
             title="Toggle keyboard & display controls panel">
          <span class="text-[11px] text-slate-600 font-medium group-hover:text-[#176B87]">Toggle this menu</span>
          <div class="flex items-center gap-1.5">
            <kbd class="a11y-key-badge" title="Primary shortcut to toggle controls">Alt + K</kbd>
            <span class="text-slate-400 text-[10px]">or</span>
            <kbd class="a11y-key-badge text-[10px] py-0.5 px-1.5" title="Alternative shortcut">Alt + A</kbd>
          </div>
        </div>

        <div onclick="CiventralA11y.toggleDataMask()"
             class="flex items-center justify-between p-2 rounded-lg bg-slate-50 hover:bg-[#EEF5FF] border border-slate-100 transition-colors group cursor-pointer"
             title="Click to toggle or press Ctrl + Shift + D">
          <div class="flex items-center gap-2">
            <span class="text-[11px] text-slate-600 font-medium group-hover:text-[#176B87]">Mask citizen data</span>
            <span id="a11yMaskStatePill" class="text-[9px] px-1.5 py-0.5 rounded font-bold uppercase bg-slate-200 text-slate-600">Off</span>
          </div>
          <kbd class="a11y-key-badge" title="Toggle PII confidentiality masking">Ctrl + Shift + D</kbd>
        </div>

        <div onclick="CiventralA11y.closePanel()"
             class="flex items-center justify-between p-2 rounded-lg bg-slate-50 hover:bg-[#EEF5FF] border border-slate-100 transition-colors group cursor-pointer"
             title="Close keyboard & display controls panel">
          <span class="text-[11px] text-slate-600 font-medium group-hover:text-[#176B87]">Close open dialogs</span>
          <kbd class="a11y-key-badge" title="Dismiss top-most active dialog or popover">Escape</kbd>
        </div>

        <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 hover:bg-[#EEF5FF] border border-slate-100 transition-colors group">
          <span class="text-[11px] text-slate-600 font-medium group-hover:text-[#176B87]">Next / Previous control</span>
          <div class="flex items-center gap-1">
            <kbd class="a11y-key-badge" title="Advance focus">Tab</kbd>
            <span class="text-slate-400 text-[10px]">/</span>
            <kbd class="a11y-key-badge" title="Previous focus">Shift+Tab</kbd>
          </div>
        </div>

        <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 hover:bg-[#EEF5FF] border border-slate-100 transition-colors group">
          <span class="text-[11px] text-slate-600 font-medium group-hover:text-[#176B87]">Activate item</span>
          <div class="flex items-center gap-1">
            <kbd class="a11y-key-badge" title="Execute primary action">Enter</kbd>
            <span class="text-slate-400 text-[10px]">/</span>
            <kbd class="a11y-key-badge" title="Toggle checkbox/button">Space</kbd>
          </div>
        </div>

      </div>
    </div>

  </div>

  <!-- Popover Footer -->
  <div class="flex justify-between items-center px-4 py-2.5 bg-slate-50 border-t border-slate-200 text-xs">
    <button type="button"
            onclick="CiventralA11y.resetDefaults()"
            class="text-[11px] text-slate-500 hover:text-[#176B87] font-semibold focus:outline-none focus-visible:underline cursor-pointer">
      Reset All
    </button>
    <button type="button"
            onclick="CiventralA11y.closePanel()"
            class="px-3.5 py-1.5 bg-[#176B87] hover:bg-[#0d4f64] text-white font-bold rounded-lg text-xs focus:outline-none focus-visible:ring-2 focus-visible:ring-[#86B6F6] transition shadow-xs cursor-pointer">
      Done
    </button>
  </div>

</div>

<!-- Global Screen Reader Live Region Announcer (WCAG 4.1.3 Status Messages) -->
<div id="a11yLiveAnnouncer" class="sr-only" role="status" aria-live="polite" aria-atomic="true"></div>

<script>
window.CiventralA11y = (function() {
  'use strict';

  var STORAGE_KEYS = {
    focus: 'civentral_a11y_focus',
    text: 'civentral_a11y_text',
    motion: 'civentral_a11y_motion'
  };

  /* Charts (ApexCharts) animate their draw-in with timers/SVG paths, which CSS
     cannot stop - so every chart instance is remembered and its resolved
     config is frozen while Reduce Motion is on. */
  var chartInstances = [];
  var chartHookInstalled = false;
  var chartHookAttempts = 0;

  function readSetting(key) {
    return localStorage.getItem(key) === 'true';
  }

  /* ===== Option feedback, chart motion & in-flight animation helpers ===== */

  function setStatePill(id, isOn) {
    var pill = document.getElementById(id);
    if (!pill) return;
    pill.setAttribute('data-on', isOn ? 'true' : 'false');
    pill.innerHTML = isOn
      ? '<i class="fa-solid fa-check text-[9px] mr-0.5"></i> ON'
      : 'OFF';
  }

  function updateCardState(cardId, isChecked) {
    var card = document.getElementById(cardId);
    if (!card) return;
    if (isChecked) {
      card.classList.add('is-checked');
      card.setAttribute('data-checked', 'true');
    } else {
      card.classList.remove('is-checked');
      card.setAttribute('data-checked', 'false');
    }

    // Direct styling fallback to ensure immediate visual response in all browsers
    var checkIcon = card.querySelector('.a11y-check-indicator i');
    if (checkIcon) {
      checkIcon.style.display = isChecked ? 'inline-block' : 'none';
    }
    var indicator = card.querySelector('.a11y-check-indicator');
    if (indicator) {
      indicator.style.backgroundColor = isChecked ? '#176B87' : '#ffffff';
      indicator.style.borderColor = isChecked ? '#176B87' : '#cbd5e1';
    }
  }

  function handleRowClick(e, type) {
    if (e.target.closest('button')) return;
    toggleOption(type);
  }

  function toggleOption(type) {
    if (type === 'focus') {
      var cur = readSetting(STORAGE_KEYS.focus);
      toggleFocus(!cur);
    } else if (type === 'text') {
      var cur = readSetting(STORAGE_KEYS.text);
      toggleLargeText(!cur);
    } else if (type === 'motion') {
      var cur = readSetting(STORAGE_KEYS.motion);
      toggleReducedMotion(!cur);
    }
  }

  function syncMaskState() {
    var pill = document.getElementById('a11yMaskStatePill');
    if (!pill) return;
    var isMasked = typeof window.isDataMasked === 'function'
      ? window.isDataMasked()
      : (localStorage.getItem('data_masking_enabled') === 'true');
    if (isMasked) {
      pill.textContent = 'Hidden';
      pill.className = 'text-[9px] px-1.5 py-0.5 rounded font-bold uppercase bg-amber-100 text-amber-800 border border-amber-300';
    } else {
      pill.textContent = 'Visible';
      pill.className = 'text-[9px] px-1.5 py-0.5 rounded font-bold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300';
    }
  }

  function toggleDataMask() {
    if (typeof window.toggleDataMask === 'function') {
      window.toggleDataMask();
    } else {
      var cur = localStorage.getItem('data_masking_enabled') === 'true';
      localStorage.setItem('data_masking_enabled', (!cur).toString());
    }
    syncMaskState();
  }

  /**
   * Freeze motion on a single ApexCharts instance.
   */
  function freezeChartMotion(instance) {
    var config = instance && instance.w && instance.w.config;
    if (!config || !config.chart) return;
    var anim = config.chart.animations || (config.chart.animations = {});
    anim.enabled = false;
    anim.animateGradually = Object.assign({}, anim.animateGradually, { enabled: false });
    anim.dynamicAnimation = Object.assign({}, anim.dynamicAnimation, { enabled: false });
    anim.chartTypeMorph = Object.assign({}, anim.chartTypeMorph, { enabled: false });
  }

  /** Register (and freeze, when needed) every chart as soon as it renders. */
  function hookChartLibrary() {
    if (chartHookInstalled) return true;
    if (typeof window.ApexCharts !== 'function' ||
        !window.ApexCharts.prototype ||
        typeof window.ApexCharts.prototype.render !== 'function') {
      return false;
    }
    var originalRender = window.ApexCharts.prototype.render;
    window.ApexCharts.prototype.render = function () {
      try {
        if (chartInstances.indexOf(this) === -1) chartInstances.push(this);
        if (readSetting(STORAGE_KEYS.motion)) freezeChartMotion(this);
      } catch (e) { /* never break chart rendering */ }
      return originalRender.apply(this, arguments);
    };
    chartHookInstalled = true;
    return true;
  }

  /**
   * Watch window.ApexCharts so the hook is installed the moment the chart
   * library lands on the page.
   */
  function watchForChartLibrary() {
    if (hookChartLibrary()) return;
    try {
      var libraryRef = window.ApexCharts;
      Object.defineProperty(window, 'ApexCharts', {
        configurable: true,
        enumerable: true,
        get: function () { return libraryRef; },
        set: function (value) {
          libraryRef = value;
          if (value) hookChartLibrary();
        }
      });
    } catch (e) { /* fall back to the polling retries below */ }
  }

  /** Retry briefly: page-level chart libraries load after this script. */
  function ensureChartHook() {
    watchForChartLibrary();
    if (chartHookInstalled) return;
    if (chartHookAttempts++ < 100) setTimeout(ensureChartHook, 50);
  }

  function applyChartMotionPreference(reducedMotion) {
    if (!reducedMotion) return;
    for (var i = 0; i < chartInstances.length; i++) {
      try { freezeChartMotion(chartInstances[i]); } catch (e) {}
    }
  }

  /**
   * Jump any animation running right now straight to its end state
   */
  function settleRunningAnimations() {
    if (typeof document.getAnimations !== 'function') return;
    var settle = function () {
      var running = document.getAnimations();
      for (var i = 0; i < running.length; i++) {
        try {
          if (running[i].playState === 'running') running[i].finish();
        } catch (e) { /* infinite animations cannot be finished - ignore */ }
      }
    };
    settle();
    setTimeout(settle, 150);
    setTimeout(settle, 600);
  }

  function applySettings() {
    ensureChartHook();

    var enhancedFocus = readSetting(STORAGE_KEYS.focus);
    var largeText = readSetting(STORAGE_KEYS.text);
    var reducedMotion = readSetting(STORAGE_KEYS.motion);

    // Apply classes to BOTH documentElement and body for universal specificity
    var doc = document.documentElement;
    var body = document.body;

    doc.classList.toggle('a11y-focus-enhanced', enhancedFocus);
    doc.classList.toggle('a11y-large-text', largeText);
    doc.classList.toggle('a11y-reduced-motion', reducedMotion);

    if (body) {
      body.classList.toggle('a11y-focus-enhanced', enhancedFocus);
      body.classList.toggle('a11y-large-text', largeText);
      body.classList.toggle('a11y-reduced-motion', reducedMotion);
    }

    // Synchronize checkmarks and card styling
    var focusCb = document.getElementById('a11yToggleFocus');
    if (focusCb) focusCb.checked = enhancedFocus;
    updateCardState('a11yCardFocus', enhancedFocus);

    var textCb = document.getElementById('a11yToggleText');
    if (textCb) textCb.checked = largeText;
    updateCardState('a11yCardText', largeText);

    var motionCb = document.getElementById('a11yToggleMotion');
    if (motionCb) motionCb.checked = reducedMotion;
    updateCardState('a11yCardMotion', reducedMotion);

    // Synchronize status pills
    setStatePill('a11yStateFocus', enhancedFocus);
    setStatePill('a11yStateText', largeText);
    setStatePill('a11yStateMotion', reducedMotion);
    syncMaskState();

    // Synchronize active indicator badge on floating button
    var badge = document.getElementById('a11yActiveBadge');
    if (badge) {
      if (enhancedFocus || largeText || reducedMotion) {
        badge.classList.remove('hidden');
      } else {
        badge.classList.add('hidden');
      }
    }

    // Motion controls outside CSS
    applyChartMotionPreference(reducedMotion);
    if (reducedMotion) settleRunningAnimations();
  }

  function toggleFocus(enable) {
    localStorage.setItem(STORAGE_KEYS.focus, enable ? 'true' : 'false');
    applySettings();
    notify(enable ? 'High-contrast focus rings enabled — click or Tab to see the ring' : 'Default focus rings restored');
  }

  function toggleLargeText(enable) {
    localStorage.setItem(STORAGE_KEYS.text, enable ? 'true' : 'false');
    applySettings();
    notify(enable ? 'Enlarged text scale enabled (+12%)' : 'Standard text size restored');
  }

  function toggleReducedMotion(enable) {
    localStorage.setItem(STORAGE_KEYS.motion, enable ? 'true' : 'false');
    applySettings();
    notify(enable ? 'Reduced motion enabled — animations and charts frozen' : 'Motion animations restored');
  }

  function resetDefaults() {
    localStorage.removeItem(STORAGE_KEYS.focus);
    localStorage.removeItem(STORAGE_KEYS.text);
    localStorage.removeItem(STORAGE_KEYS.motion);
    applySettings();
    notify('Accessibility settings reset to default');
  }

  function announce(msg, priority) {
    if (!msg) return;
    var el = document.getElementById('a11yLiveAnnouncer');
    if (!el) {
      el = document.createElement('div');
      el.id = 'a11yLiveAnnouncer';
      el.className = 'sr-only';
      el.setAttribute('role', priority === 'assertive' ? 'alert' : 'status');
      el.setAttribute('aria-live', priority === 'assertive' ? 'assertive' : 'polite');
      el.setAttribute('aria-atomic', 'true');
      document.body.appendChild(el);
    }
    if (priority) {
      el.setAttribute('aria-live', priority === 'assertive' ? 'assertive' : 'polite');
      el.setAttribute('role', priority === 'assertive' ? 'alert' : 'status');
    }
    el.textContent = '';
    setTimeout(function() {
      el.textContent = msg;
    }, 50);
  }

  function notify(msg) {
    announce(msg);
    try {
      if (typeof ModalSystem !== 'undefined' && ModalSystem.toast && typeof ModalSystem.toast.info === 'function') {
        ModalSystem.toast.info(msg);
      } else if (typeof toast !== 'undefined' && typeof toast.info === 'function') {
        toast.info(msg);
      }
    } catch(e) {
      // Toast notification error should never break settings toggle
    }
  }

  function openPanel() {
    var panel = document.getElementById('accessibilityPopover');
    var btn = document.getElementById('accessibilityToggleBtn');
    if (!panel) return;

    panel.classList.remove('hidden');
    if (btn) btn.setAttribute('aria-expanded', 'true');
    syncMaskState();
    announce('Accessibility settings opened');

    var firstCb = document.getElementById('a11yToggleFocus');
    if (firstCb) {
      setTimeout(function() { try { firstCb.focus(); } catch (e) {} }, 50);
    }
  }

  function closePanel() {
    var panel = document.getElementById('accessibilityPopover');
    var btn = document.getElementById('accessibilityToggleBtn');
    if (!panel) return;

    panel.classList.add('hidden');
    if (btn) {
      btn.setAttribute('aria-expanded', 'false');
      try { btn.focus(); } catch (e) {}
    }
    announce('Accessibility settings closed');
  }

  function togglePanel(e) {
    if (e && e.stopPropagation) e.stopPropagation();
    var panel = document.getElementById('accessibilityPopover');
    if (!panel || panel.classList.contains('hidden')) {
      openPanel();
    } else {
      closePanel();
    }
  }

  // Dismiss on outside click
  document.addEventListener('click', function(e) {
    var panel = document.getElementById('accessibilityPopover');
    var btn = document.getElementById('accessibilityToggleBtn');
    if (panel && !panel.classList.contains('hidden')) {
      if (!panel.contains(e.target) && (!btn || !btn.contains(e.target))) {
        closePanel();
      }
    }
  });

  // Global Keyboard Navigation
  document.addEventListener('keydown', function(e) {
    if (e.altKey && (e.key === 'k' || e.key === 'K' || e.key === 'a' || e.key === 'A')) {
      e.preventDefault();
      togglePanel();
    }
    if (e.key === 'Escape') {
      var panel = document.getElementById('accessibilityPopover');
      if (panel && !panel.classList.contains('hidden')) {
        closePanel();
      }
    }
    // Listen for data mask keypress to sync pill immediately
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'd' || e.key === 'D' || e.key === 'm' || e.key === 'M')) {
      setTimeout(syncMaskState, 100);
    }
  });

  // Storage sync across browser tabs
  window.addEventListener('storage', function(e) {
    if (e.key && (e.key.indexOf('civentral_a11y_') === 0 || e.key === 'data_masking_enabled')) {
      applySettings();
    }
  });

  // Early and delayed chart hook
  ensureChartHook();
  document.addEventListener('DOMContentLoaded', function() {
    ensureChartHook();
    applySettings();
  });
  window.addEventListener('load', function() {
    ensureChartHook();
    applySettings();
  });

  return {
    openPanel: openPanel,
    closePanel: closePanel,
    togglePanel: togglePanel,
    openModal: openPanel,
    closeModal: closePanel,
    toggleModal: togglePanel,
    toggleFocus: toggleFocus,
    toggleLargeText: toggleLargeText,
    toggleReducedMotion: toggleReducedMotion,
    toggleOption: toggleOption,
    handleRowClick: handleRowClick,
    toggleDataMask: toggleDataMask,
    syncMaskState: syncMaskState,
    isReducedMotion: function() { return readSetting(STORAGE_KEYS.motion); },
    settleAnimations: settleRunningAnimations,
    resetDefaults: resetDefaults,
    applySettings: applySettings,
    announce: announce
  };
})();
</script>