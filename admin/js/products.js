(function () {
  const productsEndpoint = '/api/products.php';

  const filters = {
    search: document.getElementById('filterSearch'),
    status: document.getElementById('filterStatus'),
    minStock: document.getElementById('filterMinStock'),
  };

  const summaryEls = {
    totalProducts: document.querySelector('.gradient-1 h3'),
    totalStock: document.querySelector('.gradient-3 h3'),
    inventoryValue: document.querySelector('.gradient-2 h3'),
    averagePrice: document.querySelector('.gradient-4 h3'),
  };

  const tableBody = document.querySelector('#productsTable tbody');
  const searchBtn = document.getElementById('searchProducts');
  const resetBtn = document.getElementById('resetProducts');
  const exportBtn = document.getElementById('exportProducts');
  const openModalBtn = document.getElementById('openProductModal');
  const productForm = document.getElementById('productForm');
  const productModalEl = document.getElementById('productModal');
  const errorAlert = document.getElementById('productError');
  const successAlert = document.getElementById('productSuccess');
  const startDateInput = document.getElementById('productStartDate');
  const createdOnInput = document.getElementById('productCreatedOn');
  const categorySelect = document.getElementById('productCategory');
  const imageInput = document.getElementById('productImage');
  const imagePreview = document.getElementById('productImagePreview');
  const hasColorCheckbox = document.getElementById('productHasColor');
  const colorSelectWrap = document.getElementById('colorSelectWrap');
  const colorSelect = document.getElementById('productColorSelect');

  let cachedProducts = [];
  let modalInstance = null;

  function formatCurrency(value) {
    return new Intl.NumberFormat('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value || 0);
  }

  function updateSummary(summary) {
    if (!summary) return;
    summaryEls.totalProducts.textContent = Number(summary.total_products || 0).toLocaleString();
    summaryEls.totalStock.textContent = Number(summary.total_stock || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    summaryEls.inventoryValue.textContent = `₹${formatCurrency(summary.inventory_value || 0)}`;
    summaryEls.averagePrice.textContent = `₹${formatCurrency(summary.average_price || 0)}`;
  }

  function renderProducts(products) {
    cachedProducts = products || [];
    tableBody.innerHTML = '';

    if (!cachedProducts.length) {
      const tr = document.createElement('tr');
      const td = document.createElement('td');
      td.colSpan = 6;
      td.className = 'text-center text-muted py-4';
      td.textContent = 'No products found for the selected filters.';
      tr.appendChild(td);
      tableBody.appendChild(tr);
      return;
    }

    cachedProducts.forEach(product => {
      const tr = document.createElement('tr');
      const statusBadge = product.is_active ? '<span class="badge light badge-success">Active</span>' : '<span class="badge light badge-secondary">Inactive</span>';
      tr.innerHTML = `
        <td>${product.product_code}</td>
        <td>${product.product_name}</td>
        <td>₹${formatCurrency(product.selling_price)}</td>
        <td>${parseFloat(product.current_stock).toFixed(2)}</td>
        <td>${statusBadge}</td>
        <td>${product.created_on || ''}</td>
        <td>${product.updated_on || ''}</td>`;
      tableBody.appendChild(tr);
    });
  }

  function buildQuery() {
    const params = new URLSearchParams();
    if (filters.search.value) params.append('search', filters.search.value);
    if (filters.status.value) params.append('status', filters.status.value);
    if (filters.minStock.value) params.append('min_stock', filters.minStock.value);
    return params.toString();
  }

  async function loadProducts() {
    const query = buildQuery();
    const url = query ? `${productsEndpoint}?${query}` : productsEndpoint;
    try {
      tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Loading products...</td></tr>';
      const response = await fetch(url);
      if (!response.ok) throw new Error('Failed to load products');
      const data = await response.json();
      updateSummary(data.summary);
      renderProducts(data.products);
    } catch (error) {
      console.error(error);
      tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">Unable to load products.</td></tr>';
    }
  }

  function resetFilters() {
    Object.values(filters).forEach(input => {
      if (input) input.value = '';
    });
    loadProducts();
  }

  function exportCsv() {
    if (!cachedProducts.length) {
      alert('No products to export.');
      return;
    }
    const headers = ['Code','Name','Selling Price','Stock','Status','Start Date'];
    const rows = cachedProducts.map(p => [
      p.product_code,
      p.product_name,
      p.selling_price,
      p.current_stock,
      p.is_active ? 'Active' : 'Inactive',
      p.start_date || ''
    ]);
    const csv = [headers.join(','), ...rows.map(r => r.map(v => `"${String(v ?? '').replace(/"/g,'""')}"`).join(','))].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `products-${Date.now()}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  function resetFormMessages() {
    errorAlert.classList.add('d-none');
    successAlert.classList.add('d-none');
    errorAlert.textContent = '';
    successAlert.textContent = '';
  }

  function initModal() {
    if (typeof bootstrap !== 'undefined') {
      modalInstance = bootstrap.Modal.getOrCreateInstance(productModalEl);
    }
    resetFormMessages();
    productForm.reset();
    startDateInput.valueAsDate = new Date();
    createdOnInput.valueAsDate = new Date();
    // Clear preview
    if (imagePreview) { imagePreview.src = ''; imagePreview.style.display = 'none'; }
    // Load categories into select
    loadCategories();
    // Load colors
    loadColors();
    // Reset color UI
    if (colorSelectWrap) colorSelectWrap.style.display = 'none';
    if (hasColorCheckbox) hasColorCheckbox.checked = false;
  }

  async function loadCategories() {
    if (!categorySelect) return;
    try {
      const res = await fetch('/api/categories.php');
      if (!res.ok) throw new Error('Failed to load categories');
      const data = await res.json();
      const cats = data.categories || [];
      categorySelect.innerHTML = '<option value="">Select category</option>' + cats.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    } catch (e) {
      // keep select minimal
      categorySelect.innerHTML = '<option value="">Select category</option>';
    }
  }

  async function loadColors() {
    if (!colorSelect) return;
    try {
      const res = await fetch('/api/colors.php');
      if (!res.ok) throw new Error('Failed to load colors');
      const data = await res.json();
      const cols = data.colors || [];
      colorSelect.innerHTML = '<option value="">Select color</option>' + cols.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    } catch (e) {
      colorSelect.innerHTML = '<option value="">Select color</option>';
    }
  }

  async function submitProduct(event) {
    event.preventDefault();
    resetFormMessages();

    const formData = new FormData(productForm);
    // Normalize checkbox to explicit numeric value
    const isActiveEl = document.getElementById('productIsActive');
    if (isActiveEl) {
      formData.set('is_active', isActiveEl.checked ? '1' : '0');
    }
    // Created on
    if (createdOnInput && createdOnInput.value) {
      formData.set('created_on', createdOnInput.value);
    }
    // Color handling: include only if enabled and selected
    if (hasColorCheckbox?.checked) {
      const val = colorSelect?.value || '';
      if (val) formData.set('color_id', val);
    } else {
      formData.delete('color_id');
    }
    // Optional: image validation client-side
    const file = imageInput?.files?.[0];
    if (file) {
      const okTypes = ['image/jpeg','image/png','image/webp'];
      if (!okTypes.includes(file.type)) {
        errorAlert.textContent = 'Unsupported image type. Use JPG, PNG, or WEBP.';
        errorAlert.classList.remove('d-none');
        return;
      }
      if (file.size > 2 * 1024 * 1024) {
        errorAlert.textContent = 'Image too large. Max size is 2 MB.';
        errorAlert.classList.remove('d-none');
        return;
      }
    }
    try {
      const response = await fetch(productsEndpoint, { method: 'POST', body: formData });
      const result = await response.json();
      if (!response.ok) {
        throw new Error(result.error || 'Failed to create product');
      }
      successAlert.textContent = result.message || 'Product saved successfully.';
      successAlert.classList.remove('d-none');
      await loadProducts();
      setTimeout(() => {
        modalInstance?.hide();
      }, 800);
    } catch (error) {
      errorAlert.textContent = error.message;
      errorAlert.classList.remove('d-none');
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
    searchBtn?.addEventListener('click', loadProducts);
    resetBtn?.addEventListener('click', resetFilters);
    exportBtn?.addEventListener('click', exportCsv);
    openModalBtn?.addEventListener('click', () => {
      initModal();
      modalInstance?.show();
    });
    productForm?.addEventListener('submit', submitProduct);
    imageInput?.addEventListener('change', () => {
      const file = imageInput.files && imageInput.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (e) => { imagePreview.src = e.target.result; imagePreview.style.display = 'inline-block'; };
        reader.readAsDataURL(file);
      } else {
        imagePreview.src = ''; imagePreview.style.display = 'none';
      }
    });
    hasColorCheckbox?.addEventListener('change', () => {
      if (!colorSelectWrap) return;
      colorSelectWrap.style.display = hasColorCheckbox.checked ? 'block' : 'none';
    });
  });
})();
