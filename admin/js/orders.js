(function () {
  const supportEndpoint = '/api/order_support.php';
  const ordersEndpoint = '/api/orders.php';

  const filters = {
    product: document.getElementById('filterProduct'),
    dealer: document.getElementById('filterDealer'),
    distributor: document.getElementById('filterDistributor'),
    salesperson: document.getElementById('filterSalesperson'),
    startDate: document.getElementById('filterStartDate'),
    endDate: document.getElementById('filterEndDate'),
  };

  const summaryEls = {
    totalOrders: document.querySelector('.gradient-1 h3'),
    totalQuantity: document.querySelector('.gradient-3 h3'),
    totalAmount: document.querySelector('.gradient-2 h3'),
    avgAmount: document.querySelector('.gradient-4 h3'),
  };

  const tableBody = document.querySelector('#ordersTable tbody');
  const searchBtn = document.getElementById('searchOrders');
  const resetBtn = document.getElementById('resetOrders');
  const exportBtn = document.getElementById('exportOrders');
  const openModalBtn = document.getElementById('openOrderModal');
  const modalEl = document.getElementById('orderModal');
  const orderForm = document.getElementById('orderForm');
  const dealerSelect = document.getElementById('orderDealer');
  const distributorSelect = document.getElementById('orderDistributor');
  const salespersonSelect = document.getElementById('orderSalesperson');
  const productSelect = document.getElementById('orderProduct');
  const productStockInfo = document.getElementById('orderProductStock');
  const quantityInput = document.getElementById('orderQuantity');
  const unitPriceInput = document.getElementById('orderUnitPrice');
  const totalInput = document.getElementById('orderTotal');
  const orderDateInput = document.getElementById('orderDate');
  const errorAlert = document.getElementById('orderError');
  const successAlert = document.getElementById('orderSuccess');

  let cachedOrders = [];
  let supportData = { dealers: [], distributors: [], salespersons: [], products: [] };
  let modalInstance = null;

  function formatCurrency(value) {
    return new Intl.NumberFormat('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value || 0);
  }

  function populateSelect(select, data, placeholder) {
    const currentValue = select.value;
    select.innerHTML = '';
    if (placeholder !== undefined) {
      const option = document.createElement('option');
      option.value = '';
      option.textContent = placeholder;
      select.appendChild(option);
    }
    data.forEach(item => {
      const option = document.createElement('option');
      option.value = item.id;
      option.textContent = item.name;
      if (item.unit_price !== undefined) {
        option.dataset.unitPrice = item.unit_price;
      }
      if (item.current_stock !== undefined) {
        option.dataset.currentStock = item.current_stock;
      }
      select.appendChild(option);
    });
    if (currentValue) {
      select.value = currentValue;
    }
  }

  async function loadSupportData() {
    try {
      const response = await fetch(supportEndpoint);
      if (!response.ok) throw new Error('Failed to load lists');
      supportData = await response.json();

      populateSelect(filters.product, supportData.products, 'All Products');
      populateSelect(filters.dealer, supportData.dealers, 'All Dealers');
      populateSelect(filters.distributor, supportData.distributors, 'All Distributors');
      populateSelect(filters.salesperson, supportData.salespersons, 'All Salespersons');

      populateSelect(dealerSelect, supportData.dealers, 'Select Dealer');
      populateSelect(distributorSelect, supportData.distributors, 'Select Distributor');
      populateSelect(salespersonSelect, supportData.salespersons, 'Select Salesperson');
      populateSelect(productSelect, supportData.products, 'Select Product');
      handleProductChange();
    } catch (error) {
      console.error(error);
    }
  }

  function buildQuery() {
    const params = new URLSearchParams();
    if (filters.product.value) params.append('product_id', filters.product.value);
    if (filters.dealer.value) params.append('dealer_id', filters.dealer.value);
    if (filters.distributor.value) params.append('distributor_id', filters.distributor.value);
    if (filters.salesperson.value) params.append('salesperson_id', filters.salesperson.value);
    if (filters.startDate.value) params.append('start_date', filters.startDate.value);
    if (filters.endDate.value) params.append('end_date', filters.endDate.value);
    return params.toString();
  }

  function renderOrders(orders) {
    cachedOrders = orders || [];
    tableBody.innerHTML = '';

    if (!cachedOrders.length) {
      const tr = document.createElement('tr');
      const td = document.createElement('td');
      td.colSpan = 11;
      td.className = 'text-center text-muted py-4';
      td.textContent = 'No orders found for the selected filters.';
      tr.appendChild(td);
      tableBody.appendChild(tr);
      return;
    }

    cachedOrders.forEach(order => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${order.order_number}</td>
        <td>${order.order_date}</td>
        <td>${order.dealer_name}</td>
        <td>${order.distributor_name}</td>
        <td>${order.salesperson_name}</td>
        <td>${order.product_name}</td>
        <td>${parseFloat(order.quantity).toFixed(2)}</td>
        <td>₹${formatCurrency(order.unit_price)}</td>
        <td>₹${formatCurrency(order.total_amount)}</td>
        <td>${order.notes ? order.notes : ''}</td>
        <td class="text-nowrap">
          <button class="btn btn-sm btn-outline-primary me-1" title="View" data-attachment="${order.attachment || ''}"><i class="las la-eye"></i></button>
          <button class="btn btn-sm btn-outline-danger" title="Delete" data-order="${order.id}" disabled><i class="las la-trash"></i></button>
        </td>`;
      const viewBtn = tr.querySelector('button');
      viewBtn.addEventListener('click', () => {
        if (order.attachment_url) {
          window.open(order.attachment_url, '_blank');
        } else {
          alert('No attachment available for this order.');
        }
      });
      tableBody.appendChild(tr);
    });
  }

  function updateSummary(summary) {
    if (!summary) return;
    summaryEls.totalOrders.textContent = Number(summary.total_orders || 0).toLocaleString();
    summaryEls.totalQuantity.textContent = Number(summary.total_quantity || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    summaryEls.totalAmount.textContent = `₹${formatCurrency(summary.total_amount || 0)}`;
    summaryEls.avgAmount.textContent = `₹${formatCurrency(summary.average_amount || 0)}`;
  }

  async function loadOrders() {
    const query = buildQuery();
    const url = query ? `${ordersEndpoint}?${query}` : ordersEndpoint;
    try {
      tableBody.innerHTML = '<tr><td colspan="11" class="text-center text-muted py-4">Loading orders...</td></tr>';
      const response = await fetch(url);
      if (!response.ok) throw new Error('Failed to load orders');
      const data = await response.json();
      updateSummary(data.summary);
      renderOrders(data.orders);
    } catch (error) {
      console.error(error);
      tableBody.innerHTML = '<tr><td colspan="11" class="text-center text-danger py-4">Unable to load orders.</td></tr>';
    }
  }

  function resetFilters() {
    Object.values(filters).forEach(input => {
      if (input) input.value = '';
    });
    loadOrders();
  }

  function exportToCsv() {
    if (!cachedOrders.length) {
      alert('No orders to export.');
      return;
    }
    const headers = ['Order Number','Date','Dealer','Distributor','Salesperson','Product','Quantity','Unit Price','Total Amount','Notes'];
    const rows = cachedOrders.map(o => [
      o.order_number,
      o.order_date,
      o.dealer_name,
      o.distributor_name,
      o.salesperson_name,
      o.product_name,
      o.quantity,
      o.unit_price,
      o.total_amount,
      o.notes ? o.notes.replace(/\n/g, ' ') : ''
    ]);
    const csv = [headers.join(','), ...rows.map(r => r.map(value => `"${String(value ?? '').replace(/"/g,'""')}"`).join(','))].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `orders-${Date.now()}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  function computeTotal() {
    const qty = parseFloat(quantityInput.value) || 0;
    const price = parseFloat(unitPriceInput.value) || 0;
    totalInput.value = (qty * price).toFixed(2);
  }

  function handleProductChange() {
    const selectedOption = productSelect ? productSelect.options[productSelect.selectedIndex] : null;
    if (!selectedOption) {
      if (productStockInfo) productStockInfo.textContent = 'Current Stock: 0';
      return;
    }
    const price = selectedOption.dataset.unitPrice ? parseFloat(selectedOption.dataset.unitPrice) : null;
    const stock = selectedOption.dataset.currentStock ? parseFloat(selectedOption.dataset.currentStock) : null;
    if (price !== null && unitPriceInput) {
      unitPriceInput.value = price.toFixed(2);
    }
    if (productStockInfo) {
      productStockInfo.textContent = `Current Stock: ${stock !== null ? stock.toFixed(2) : '0'}`;
    }
    computeTotal();
  }

  function resetFormMessages() {
    errorAlert.classList.add('d-none');
    successAlert.classList.add('d-none');
    errorAlert.textContent = '';
    successAlert.textContent = '';
  }

  async function submitOrder(event) {
    event.preventDefault();
    resetFormMessages();

    const formData = new FormData(orderForm);
    try {
      const response = await fetch(ordersEndpoint, {
        method: 'POST',
        body: formData
      });
      const result = await response.json();
      if (!response.ok) {
        throw new Error(result.error || 'Failed to save order');
      }
      successAlert.textContent = result.message || 'Order saved successfully.';
      successAlert.classList.remove('d-none');
      orderForm.reset();
      computeTotal();
      orderDateInput.valueAsDate = new Date();
      await loadOrders();
      setTimeout(() => {
        if (modalInstance) {
          modalInstance.hide();
        }
      }, 800);
    } catch (error) {
      errorAlert.textContent = error.message;
      errorAlert.classList.remove('d-none');
    }
  }

  function initModal() {
    if (typeof bootstrap !== 'undefined') {
      modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
    }
    orderDateInput.valueAsDate = new Date();
    computeTotal();
    handleProductChange();
  }

  document.addEventListener('DOMContentLoaded', () => {
    loadSupportData().then(loadOrders);

    searchBtn?.addEventListener('click', loadOrders);
    resetBtn?.addEventListener('click', resetFilters);
    exportBtn?.addEventListener('click', exportToCsv);
    openModalBtn?.addEventListener('click', () => {
      resetFormMessages();
      orderForm.reset();
      initModal();
      modalInstance?.show();
    });
    productSelect?.addEventListener('change', handleProductChange);
    quantityInput?.addEventListener('input', computeTotal);
    unitPriceInput?.addEventListener('input', computeTotal);
    orderForm?.addEventListener('submit', submitOrder);
  });
})();
