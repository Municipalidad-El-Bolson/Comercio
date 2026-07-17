(function () {
    'use strict';

    const PREFIX = 'comercio:draft:v2:';
    const timers = new Map();
    let notice;
    let pendingRoot;

    const modelName = (el) => {
        const attr = Array.from(el.attributes).find((item) => item.name.startsWith('wire:model'));
        return attr ? attr.value : null;
    };
    const componentName = (root) => {
        try { return JSON.parse(root.getAttribute('wire:snapshot') || '{}').memo?.name || 'component'; }
        catch (_) { return 'component'; }
    };
    const roots = () => Array.from(document.querySelectorAll('[wire\\:id]')).filter((root) =>
        root.querySelector('input[wire\\:model], input[wire\\:model\\.defer], input[wire\\:model\\.live], input[wire\\:model\\.blur], textarea[wire\\:model], textarea[wire\\:model\\.defer], select[wire\\:model], select[wire\\:model\\.live]')
    );
    const keyFor = (root) => PREFIX + location.pathname + ':' + componentName(root);
    const eligible = (el) => {
        if (!modelName(el) || el.disabled || el.type === 'password' || el.type === 'file' || el.type === 'hidden' || el.type === 'search') return false;
        return !el.closest('[data-autosave="off"]');
    };

    function valuesFor(root) {
        const result = {};
        const controls = Array.from(root.querySelectorAll('input, textarea, select'))
            .filter((el) => eligible(el) && el.closest('[wire\\:id]') === root);
        [...new Set(controls.map(modelName))].forEach((model) => {
            const group = controls.filter((el) => modelName(el) === model);
            if (!group.length) return;
            if (group[0].type === 'checkbox') {
                result[model] = group.length > 1 || group[0].value !== 'on'
                    ? group.filter((el) => el.checked).map((el) => el.value)
                    : group[0].checked;
            } else if (group[0].type === 'radio') {
                result[model] = group.find((el) => el.checked)?.value ?? null;
            } else {
                result[model] = group[0].value;
            }
        });
        return result;
    }

    function show(message, actions = false) {
        if (!notice) {
            notice = document.createElement('div');
            notice.className = 'autosave-notice';
            notice.innerHTML = '<span class="autosave-text"></span><div class="autosave-actions"><button type="button" class="autosave-recover">Recuperar borrador</button><button type="button" class="autosave-discard">Descartar</button></div>';
            notice.querySelector('.autosave-recover').addEventListener('click', () => restore(pendingRoot));
            notice.querySelector('.autosave-discard').addEventListener('click', () => discard(pendingRoot));
            document.body.appendChild(notice);
        }
        notice.querySelector('.autosave-text').textContent = message;
        notice.querySelector('.autosave-actions').hidden = !actions;
        notice.classList.add('visible');
        clearTimeout(notice.hideTimer);
        if (!actions) notice.hideTimer = setTimeout(() => notice.classList.remove('visible'), 2200);
    }

    function save(root) {
        const values = valuesFor(root);
        if (!Object.keys(values).length) return;
        localStorage.setItem(keyFor(root), JSON.stringify({ savedAt: new Date().toISOString(), values }));
        show('Borrador guardado en este equipo', false);
    }
    function schedule(root) {
        clearTimeout(timers.get(root));
        timers.set(root, setTimeout(() => save(root), 500));
    }
    function offerRestore(root) {
        if (!root || root.dataset.autosaveOffered || root.dataset.autosaveRestored) return;
        const raw = localStorage.getItem(keyFor(root));
        if (!raw) return;
        let draft;
        try { draft = JSON.parse(raw); } catch (_) { localStorage.removeItem(keyFor(root)); return; }
        if (!draft.values || !Object.keys(draft.values).length) return;
        root.dataset.autosaveOffered = '1';
        pendingRoot = root;
        show('Hay un borrador guardado. Podés recuperarlo cuando lo necesites.', true);
    }
    function restore(root) {
        if (!root) return;
        const raw = localStorage.getItem(keyFor(root));
        if (!raw) return;
        let draft;
        try { draft = JSON.parse(raw); } catch (_) { localStorage.removeItem(keyFor(root)); return; }
        const wire = window.Livewire?.find(root.getAttribute('wire:id'));
        if (!wire || !draft.values) return;
        root.dataset.autosaveRestored = '1';
        const allowedModels = new Set(Object.keys(valuesFor(root)));
        Object.entries(draft.values).forEach(([model, value]) => {
            if (!allowedModels.has(model)) return;
            try { wire.set(model, value, false); } catch (_) {}
        });
        pendingRoot = null;
        show('Borrador recuperado', false);
    }
    function clearRoot(root) {
        if (!root) return;
        localStorage.removeItem(keyFor(root));
        delete root.dataset.autosaveRestored;
        delete root.dataset.autosaveOffered;
    }
    function discard(root) {
        clearRoot(root);
        pendingRoot = null;
        if (notice) notice.classList.remove('visible');
    }

    document.addEventListener('input', (event) => { if (eligible(event.target)) schedule(event.target.closest('[wire\\:id]')); }, true);
    document.addEventListener('change', (event) => { if (eligible(event.target)) schedule(event.target.closest('[wire\\:id]')); }, true);
    document.addEventListener('autosave-clear', (event) => clearRoot(event.target.closest?.('[wire\\:id]')));
    document.addEventListener('livewire:init', () => {
        Object.keys(localStorage).filter((key) => key.startsWith('comercio:draft:v1:')).forEach((key) => localStorage.removeItem(key));
        setTimeout(() => roots().forEach(offerRestore), 350);
        Livewire.hook('morph.updated', ({ el }) => {
            const root = el.closest?.('[wire\\:id]') || (el.matches?.('[wire\\:id]') ? el : null);
            if (root) setTimeout(() => offerRestore(root), 50);
        });
    });
})();
