(function(){
  const endpoint = '/api/colors.php';
  const tableBody = document.querySelector('#colorsTable tbody');
  const openBtn = document.getElementById('openColorModal');
  const modalEl = document.getElementById('colorModal');
  const form = document.getElementById('colorForm');
  const titleEl = document.getElementById('colorModalTitle');
  const idEl = document.getElementById('colorId');
  const nameEl = document.getElementById('colorName');
  const hexEl = document.getElementById('colorHex');
  const pickerEl = document.getElementById('colorPicker');
  const swatchEl = document.getElementById('colorSwatch');
  const activeEl = document.getElementById('colorActive');
  const errEl = document.getElementById('colorError');
  const okEl = document.getElementById('colorSuccess');
  const nameErr = document.getElementById('colorNameErr');
  const hexErr = document.getElementById('colorHexErr');
  let modal = null;

  function hideInlineErrors(){ nameErr?.classList.add('d-none'); hexErr?.classList.add('d-none'); }
  function resetAlerts(){ errEl.classList.add('d-none'); okEl.classList.add('d-none'); errEl.textContent=''; okEl.textContent=''; hideInlineErrors(); }

  function renderRows(items){
    tableBody.innerHTML='';
    if (!items.length){ tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No colors</td></tr>'; return; }
    items.forEach(c => {
      const tr = document.createElement('tr');
      const badge = c.is_active ? '<span class="badge light badge-success">Active</span>' : '<span class="badge light badge-secondary">Inactive</span>';
      const swatch = `<span class="color-swatch" style="background:${c.hex || '#eee'}"></span>`;
      tr.innerHTML = `
        <td>${swatch}${c.name}</td>
        <td>${c.hex || ''}</td>
        <td>${badge}</td>
        <td>${c.created_on || ''}</td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-primary" data-action="edit" data-id="${c.id}"><i class="las la-edit"></i></button>
          <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${c.id}"><i class="las la-trash"></i></button>
        </td>`;
      tableBody.appendChild(tr);
    });
  }

  async function load(){
    window.dispatchEvent(new Event('rewarity:data-loading'));
    tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Loading...</td></tr>';
    try{
      const res = await fetch(endpoint + '?all=1');
      if (!res.ok) throw new Error('Failed to load');
      const data = await res.json();
      renderRows(data.colors || []);
    }catch(e){
      tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">Unable to load colors.</td></tr>';
    } finally {
      window.dispatchEvent(new Event('rewarity:data-ready'));
    }
  }

  function openModal(edit=null){
    resetAlerts();
    if (typeof bootstrap !== 'undefined') { modal = bootstrap.Modal.getOrCreateInstance(modalEl); }
    if (edit){
      titleEl.textContent = 'Edit Color';
      idEl.value = edit.id;
      nameEl.value = edit.name || '';
      hexEl.value = edit.hex || '#000000';
      pickerEl.value = (edit.hex && /^#[0-9a-fA-F]{6}$/.test(edit.hex)) ? edit.hex : '#000000';
      swatchEl.style.background = pickerEl.value;
      activeEl.checked = !!edit.is_active;
    } else {
      titleEl.textContent = 'Add Color';
      form.reset();
      idEl.value = '';
      pickerEl.value = '#ff0000';
      hexEl.value = '#ff0000';
      swatchEl.style.background = '#ff0000';
      activeEl.checked = true;
    }
    modal?.show();
  }

  function validate(){
    hideInlineErrors();
    let ok = true;
    if (!nameEl.value.trim()) { nameErr.classList.remove('d-none'); ok = false; }
    const hex = hexEl.value.trim();
    if (!hex) { hexErr.classList.remove('d-none'); ok = false; }
    else if (!/^#[0-9a-fA-F]{6}$/.test(hex)) { hexErr.textContent = 'Invalid code. Use #RRGGBB.'; hexErr.classList.remove('d-none'); ok = false; }
    return ok;
  }

  async function save(ev){
    ev.preventDefault();
    resetAlerts();
    if (!validate()) return;
    const fd = new FormData(form);
    fd.set('is_active', activeEl.checked ? '1':'0');
    try{
      const res = await fetch(endpoint, { method:'POST', body: fd });
      const out = await res.json();
      if (!res.ok) throw new Error(out.error || 'Failed to save');
      okEl.textContent = out.message || 'Saved'; okEl.classList.remove('d-none');
      await load();
      setTimeout(()=> modal?.hide(), 600);
    }catch(e){ errEl.textContent = e.message; errEl.classList.remove('d-none'); }
  }

  async function onTableClick(e){
    const btn = e.target.closest('button[data-action]'); if (!btn) return;
    const id = parseInt(btn.dataset.id);
    const action = btn.dataset.action;
    if (action === 'edit'){
      const tr = btn.closest('tr');
      const name = tr.children[0].textContent.trim();
      const hex = tr.children[1].textContent.trim();
      const isActive = tr.children[2].textContent.includes('Active');
      openModal({ id, name, hex, is_active: isActive });
    } else if (action === 'delete'){
      if (!confirm('Delete this color?')) return;
      try{ const res = await fetch(`${endpoint}?id=${id}`, { method:'DELETE' }); if (!res.ok) throw new Error('Failed to delete'); await load(); }catch(e){ alert(e.message); }
    }
  }

  function syncFromPicker(){ hexEl.value = pickerEl.value; swatchEl.style.background = pickerEl.value; hideInlineErrors(); }
  function syncFromHex(){ const v = hexEl.value.trim(); if (/^#[0-9a-fA-F]{6}$/.test(v)) { pickerEl.value = v; swatchEl.style.background = v; hideInlineErrors(); } }

  document.addEventListener('DOMContentLoaded', ()=>{
    load();
    openBtn?.addEventListener('click', ()=> openModal());
    form?.addEventListener('submit', save);
    tableBody?.addEventListener('click', onTableClick);
    pickerEl?.addEventListener('input', syncFromPicker);
    hexEl?.addEventListener('input', syncFromHex);
  });
})();

