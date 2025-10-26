(function(){
  const endpoint = '/api/categories.php';
  const tableBody = document.querySelector('#categoriesTable tbody');
  const openBtn = document.getElementById('openCategoryModal');
  const modalEl = document.getElementById('categoryModal');
  const form = document.getElementById('categoryForm');
  const titleEl = document.getElementById('categoryModalTitle');
  const idEl = document.getElementById('categoryId');
  const nameEl = document.getElementById('categoryName');
  const descEl = document.getElementById('categoryDescription');
  const activeEl = document.getElementById('categoryActive');
  const errEl = document.getElementById('categoryError');
  const okEl = document.getElementById('categorySuccess');
  let modal = null;

  function resetAlerts(){ errEl.classList.add('d-none'); okEl.classList.add('d-none'); errEl.textContent=''; okEl.textContent=''; }

  function renderRows(items){
    tableBody.innerHTML='';
    if (!items.length){ tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No categories</td></tr>'; return; }
    items.forEach(c => {
      const tr = document.createElement('tr');
      const badge = c.is_active ? '<span class="badge light badge-success">Active</span>' : '<span class="badge light badge-secondary">Inactive</span>';
      tr.innerHTML = `
        <td>${c.name}</td>
        <td>${c.description || ''}</td>
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
    tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Loading...</td></tr>';
    try{
      const res = await fetch(endpoint);
      if (!res.ok) throw new Error('Failed to load');
      const data = await res.json();
      renderRows(data.categories || []);
    }catch(e){
      tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">Unable to load categories.</td></tr>';
    }
  }

  function openModal(edit=null){
    resetAlerts();
    if (typeof bootstrap !== 'undefined') { modal = bootstrap.Modal.getOrCreateInstance(modalEl); }
    if (edit){
      titleEl.textContent = 'Edit Category';
      idEl.value = edit.id;
      nameEl.value = edit.name || '';
      descEl.value = edit.description || '';
      activeEl.checked = !!edit.is_active;
    } else {
      titleEl.textContent = 'Add Category';
      form.reset();
      idEl.value = '';
      activeEl.checked = true;
    }
    modal?.show();
  }

  async function save(ev){
    ev.preventDefault();
    resetAlerts();
    const fd = new FormData(form);
    if (activeEl) fd.set('is_active', activeEl.checked ? '1':'0');
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
      // find row data from table DOM
      const tr = btn.closest('tr');
      openModal({ id, name: tr.children[0].textContent, description: tr.children[1].textContent, is_active: tr.children[2].textContent.includes('Active') });
    } else if (action === 'delete'){
      if (!confirm('Delete this category?')) return;
      try{ const res = await fetch(`${endpoint}?id=${id}`, { method:'DELETE' }); if (!res.ok) throw new Error('Failed to delete'); await load(); }catch(e){ alert(e.message); }
    }
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    load();
    openBtn?.addEventListener('click', ()=> openModal());
    form?.addEventListener('submit', save);
    tableBody?.addEventListener('click', onTableClick);
  });
})();

