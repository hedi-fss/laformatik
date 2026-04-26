/**
 * LAFORMATIK — Frontend Script
 * Theme toggle, image preview, sidebar toggle, flash dismiss,
 * category edit modal, emoji picker modal.
 */

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initSidebarToggle();
    initFlashAutoDismiss();
    initDeleteButtons();
});

// ═══════ DELETE BUTTON FIX ═══════
// Prevent clicks on category action buttons from bubbling to the parent <a> link
function initDeleteButtons() {
    // Stop propagation on ALL category action areas
    document.querySelectorAll('.cat-actions').forEach(el => {
        el.addEventListener('click', e => e.stopPropagation());
        el.addEventListener('mousedown', e => e.stopPropagation());
    });

    // Ensure all inline delete forms work with explicit confirm
    document.querySelectorAll('.inline-form').forEach(form => {
        form.addEventListener('click', e => e.stopPropagation());
        form.addEventListener('submit', e => {
            e.stopPropagation();
            if (!confirm('Are you sure you want to delete this?')) {
                e.preventDefault();
            }
        });
    });

    // Category edit buttons — stop propagation
    document.querySelectorAll('.cat-act-btn').forEach(btn => {
        btn.addEventListener('click', e => e.stopPropagation());
    });
}

// ═══════ THEME TOGGLE ═══════
function initTheme() {
    const toggle = document.getElementById('themeToggle');
    const icon   = document.getElementById('themeIcon');
    const label  = document.getElementById('themeLabel');
    if (!toggle) return;
    const saved = localStorage.getItem('laformatik-theme') || 'dark';
    applyTheme(saved);
    toggle.addEventListener('click', () => {
        const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        applyTheme(next);
        localStorage.setItem('laformatik-theme', next);
    });
    function applyTheme(t) {
        document.documentElement.setAttribute('data-theme', t);
        if (icon)  icon.textContent  = t === 'dark' ? '🌙' : '☀️';
        if (label) label.textContent = t === 'dark' ? 'Dark Mode' : 'Light Mode';
    }
}

// ═══════ SIDEBAR TOGGLE ═══════
function initSidebarToggle() {
    const btn = document.getElementById('sidebarToggle');
    const sb  = document.getElementById('sidebar');
    if (!btn || !sb) return;
    btn.addEventListener('click', () => sb.classList.toggle('open'));
}

// ═══════ IMAGE PREVIEW ═══════
function previewImage(input) {
    const prev = document.getElementById('imgPreview');
    if (!prev) return;
    if (input.files && input.files[0]) {
        const r = new FileReader();
        r.onload = e => {
            prev.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
            prev.classList.add('has-image');
        };
        r.readAsDataURL(input.files[0]);
    } else {
        prev.innerHTML = '<span class="preview-text">Image preview</span>';
        prev.classList.remove('has-image');
    }
}

// ═══════ FLASH AUTO-DISMISS ═══════
function initFlashAutoDismiss() {
    document.querySelectorAll('.flash').forEach(f => {
        setTimeout(() => {
            f.style.transition = 'opacity .4s, transform .4s';
            f.style.opacity = '0';
            f.style.transform = 'translateY(-10px)';
            setTimeout(() => f.remove(), 400);
        }, 4000);
    });
}

// ═══════ EDIT CATEGORY MODAL ═══════
function openEditCatModal(id, name, emoji) {
    document.getElementById('editCatId').value = id;
    document.getElementById('editCatName').value = name;
    document.getElementById('editCatEmojiVal').value = emoji;
    document.getElementById('editCatEmojiBtn').textContent = emoji;
    document.getElementById('editCatModal').classList.add('open');
}
function closeEditCatModal() {
    document.getElementById('editCatModal').classList.remove('open');
}

// ═══════ EMOJI PICKER ═══════
const EMOJIS = [
    '💻','🖥️','🔧','🖱️','🌐','💾','🎒','⌨️','🖨️','📡',
    '📱','🔌','💡','🔋','📦','🛒','📊','🧰','🎮','🎧',
    '📷','🔒','💿','🖲️','🔊','📁','🗂️','⚙️','🛠️','💽',
    '🧲','📐','🔍','📈','🏷️','⚡','🌟','🔆','📎','🧪',
    '🖊️','📌','🗃️','💳','🏆','🎯','✅','📋','🔑','🗄️',
    '📞','💬','📝','🧮','🚀','🪙','📮','🏪','🔥','💎',
    '🤖','👾','🕹️','📀','🧊','🧬','📺','🎬','🎤','🎵'
];

let _emojiInputId = null;
let _emojiBtnId = null;

function openEmojiPicker(inputId, btnId) {
    _emojiInputId = inputId;
    _emojiBtnId = btnId;
    const grid = document.getElementById('emojiGrid');
    grid.innerHTML = '';
    EMOJIS.forEach(e => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = e;
        btn.addEventListener('click', () => selectEmoji(e));
        grid.appendChild(btn);
    });
    document.getElementById('emojiPickerModal').classList.add('open');
}

function selectEmoji(emoji) {
    if (_emojiInputId) document.getElementById(_emojiInputId).value = emoji;
    if (_emojiBtnId)   document.getElementById(_emojiBtnId).textContent = emoji;
    closeEmojiPicker();
}

function closeEmojiPicker() {
    document.getElementById('emojiPickerModal').classList.remove('open');
    _emojiInputId = null;
    _emojiBtnId = null;
}

// Close modals on overlay click
document.addEventListener('click', e => {
    if (e.target.id === 'editCatModal') closeEditCatModal();
    if (e.target.id === 'emojiPickerModal') closeEmojiPicker();
});
