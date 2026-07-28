<?php
/**
 * Data Masking System - Reusable across entire application
 */
function maskName($name, $visibleChars = 1) {
    if (empty($name)) return '';
    $parts = explode(' ', $name);
    $masked = array_map(function($p) use ($visibleChars) {
        if (empty($p)) return '';
        if (strlen($p) <= $visibleChars) return $p;
        return substr($p, 0, $visibleChars) . str_repeat('*', strlen($p) - $visibleChars);
    }, $parts);
    return implode(' ', $masked);
}
function maskId($id, $visibleChars = 2) {
    if (empty($id)) return '';
    if (strlen($id) <= $visibleChars) return $id;
    return substr($id, 0, $visibleChars) . str_repeat('*', strlen($id) - $visibleChars);
}
function maskableHTML($real, $masked, $tag = 'span', $extraClasses = '') {
    return "<{$tag} class=\"maskable {$extraClasses}\" data-masked=\"" . htmlspecialchars($masked) . "\" data-real=\"" . htmlspecialchars($real) . "\">" . htmlspecialchars($masked) . "</{$tag}>";
}
function maskableInput($real, $masked, $id = '', $extraClasses = '') {
    $idAttr = $id ? "id=\"{$id}\"" : '';
    return "<input type=\"text\" {$idAttr} class=\"maskable input-maskable {$extraClasses}\" data-masked=\"" . htmlspecialchars($masked) . "\" data-real=\"" . htmlspecialchars($real) . "\" value=\"" . htmlspecialchars($masked) . "\" readonly>";
}
?>
<style>
.maskable{transition:all 0.2s ease;position:relative}
.maskable.masked{color:transparent!important;background:transparent!important;user-select:none;-webkit-user-select:none;text-shadow:none!important}
.maskable.masked *{color:transparent!important}
.maskable.masked::after{content:attr(data-masked);position:absolute;left:0;top:50%;transform:translateY(-50%);color:#475569;font-weight:600;white-space:nowrap;letter-spacing:.5px;font-size:inherit;font-family:inherit}
.maskable.masked.input-maskable{color:transparent!important;background:#f1f5f9!important;caret-color:transparent}
.maskable.masked.input-maskable::after{content:attr(data-masked)!important;position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#1e293b!important;font-weight:600;white-space:nowrap;letter-spacing:.5px;font-size:14px;font-family:inherit;pointer-events:none;z-index:10;width:calc(100% - 24px);overflow:hidden;text-overflow:ellipsis}
.mask-toast{position:fixed;bottom:20px;right:20px;padding:10px 20px;border-radius:10px;background:#0f172a;color:#fff;font-size:13px;font-weight:500;z-index:9999;opacity:0;transform:translateY(20px);transition:all .3s ease;pointer-events:none;box-shadow:0 10px 40px rgba(0,0,0,.2)}
.mask-toast.show{opacity:1;transform:translateY(0)}
</style>

<script>
(function(){
'use strict';
var STORAGE_KEY='data_masking_enabled';
var isMasked=localStorage.getItem(STORAGE_KEY);
var isApplying=false;
if(isMasked===null)isMasked=true;
else isMasked=isMasked==='true';

function maskText(text){
    if(!text)return'';
    return text.split(' ').map(function(p){
        if(!p)return'';
        return p.charAt(0).toUpperCase()+'*'.repeat(Math.max(0,p.length-1));
    }).join(' ');
}

function applyMasking(){
    if(isApplying)return;
    isApplying=true;
    try{
        document.querySelectorAll('.maskable').forEach(function(el){
            if(el.tagName==='INPUT'||el.tagName==='TEXTAREA'){
                if(isMasked){
                    if(!el.dataset.real&&el.value)el.dataset.real=el.value;
                    if(!el.dataset.masked&&el.dataset.real)el.dataset.masked=maskText(el.dataset.real);
                    el.value=el.dataset.masked||'';
                    el.classList.add('masked');
                    el.classList.add('input-maskable');
                }else{
                    el.value=el.dataset.real||'';
                    el.classList.remove('masked');
                    el.classList.remove('input-maskable');
                }
            }else{
                if(isMasked){
                    if(!el.dataset.real)el.dataset.real=el.textContent.trim();
                    if(!el.dataset.masked&&el.dataset.real)el.dataset.masked=maskText(el.dataset.real);
                    el.textContent=el.dataset.masked||el.dataset.real;
                    el.classList.add('masked');
                }else{
                    el.textContent=el.dataset.real||el.textContent;
                    el.classList.remove('masked');
                }
            }
        });
        
        // Update the toggle button icon to match current state
        updateMaskToggleButton();
        
        // Refresh any open modals
        document.querySelectorAll('.fixed.inset-0:not(.hidden)').forEach(function(modal){
            if(typeof ModalSystem!=='undefined'&&ModalSystem.applyMaskingToModal){
                ModalSystem.applyMaskingToModal(modal);
            }
        });
    }finally{isApplying=false}
}

// Update toggle button
function updateMaskToggleButton(){
    var icon=document.getElementById('maskToggleIcon');
    var label=document.getElementById('maskToggleLabel');
    if(icon){
        icon.className=isMasked?'fa-solid fa-eye-slash text-sm transition-all duration-300':'fa-solid fa-eye text-sm transition-all duration-300';
    }
    if(label){
        label.textContent=isMasked?'Hidden':'Visible';
    }
}

// FIXED: Toggle function with proper shortcut
window.toggleDataMask=function(){
    isMasked=!isMasked;
    localStorage.setItem('data_masking_enabled',isMasked);
    applyMasking();
    var msg=isMasked?'All Citizens confidential data is hidden':'All Citizens confidential data is visible';
    if(typeof toast!=='undefined'&&toast.info)toast.info(msg,{title:'Data Masking',duration:3000});
};

window.isDataMasked=function(){return isMasked;};
window.setDataMask=function(s){isMasked=s;localStorage.setItem(STORAGE_KEY,isMasked);applyMasking();};

// ============================================================
// FIXED: Keyboard shortcut - Changed from Ctrl+Shift+M to Ctrl+Shift+D
// ============================================================
document.addEventListener('keydown', function(e) {
    // Toggle Data Masking: Ctrl+Shift+D (or Ctrl+Shift+M as fallback)
    if ((e.ctrlKey || e.metaKey) && e.shiftKey) {
        // Check for D (primary) or M (fallback)
        if (e.key === 'd' || e.key === 'D' || e.key === 'm' || e.key === 'M') {
            e.preventDefault();
            e.stopPropagation();
            window.toggleDataMask();
            return;
        }
    }
});

// ============================================================
// Add visual shortcut indicator in console
// ============================================================
console.log('✅ Data Masking System loaded.');
console.log('📌 Press Ctrl+Shift+D to toggle data masking');

document.addEventListener('DOMContentLoaded',function(){
    applyMasking();
    // Update button on load
    updateMaskToggleButton();
});

// Watch for dynamic content
var observerTimeout=null;
var observer=new MutationObserver(function(mutations){
    if(isApplying)return;
    var found=false;
    for(var i=0;i<mutations.length;i++){
        if(mutations[i].addedNodes.length>0){
            for(var j=0;j<mutations[i].addedNodes.length;j++){
                var node=mutations[i].addedNodes[j];
                if(node.nodeType===1){
                    if(node.classList&&node.classList.contains('maskable'))found=true;
                    if(node.querySelectorAll&&node.querySelectorAll('.maskable').length>0)found=true;
                }
            }
        }
    }
    if(found){
        clearTimeout(observerTimeout);
        observerTimeout=setTimeout(applyMasking,150);
    }
});

document.addEventListener('DOMContentLoaded',function(){
    observer.observe(document.body,{childList:true,subtree:true});
});

// Watch for modal opens to capture input values
document.addEventListener('DOMContentLoaded',function(){
    document.querySelectorAll('[id$="Modal"]').forEach(function(modal){
        new MutationObserver(function(mutations){
            mutations.forEach(function(mutation){
                if(!mutation.target.classList.contains('hidden')){
                    setTimeout(function(){
                        modal.querySelectorAll('input.maskable, textarea.maskable').forEach(function(input){
                            if(input.value&&!input.dataset.real){
                                input.dataset.real=input.value;
                                input.dataset.masked=maskText(input.value);
                            }
                        });
                        applyMasking();
                    },200);
                }
            });
        }).observe(modal,{attributes:true,attributeFilter:['class']});
    });
});

// Also update ModalSystem's toggle function if it exists
document.addEventListener('DOMContentLoaded', function() {
    if (typeof ModalSystem !== 'undefined') {
        // Override ModalSystem toggle to use our toggle
        if (!ModalSystem._originalToggle) {
            ModalSystem._originalToggle = ModalSystem.toggleMasking;
        }
        ModalSystem.toggleMasking = function() {
            window.toggleDataMask();
        };
    }
});

})();
</script>