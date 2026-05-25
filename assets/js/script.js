/**
 * Samsung Inventory System — script.js
 */

document.addEventListener('DOMContentLoaded', () => {
  initSearch();
  initAlertDismiss();
  initTableHighlight();
  initColorDots();
  initDeleteConfirm();
  initFormValidation();
});

/* ── LIVE SEARCH ── */
function initSearch() {
  const input = document.getElementById('liveSearch');
  if (!input) return;

  input.addEventListener('input', () => {
    const query = input.value.toLowerCase().trim();
    const rows  = document.querySelectorAll('.data-table tbody tr');
    let visible = 0;

    rows.forEach(row => {
      const text = row.textContent.toLowerCase();
      const match = text.includes(query);
      row.style.display = match ? '' : 'none';
      if (match) visible++;
    });

    // show empty state
    let empty = document.getElementById('emptySearch');
    if (!empty) {
      empty = document.createElement('tr');
      empty.id = 'emptySearch';
      empty.innerHTML = `<td colspan="8" style="text-align:center;padding:3rem;color:var(--muted)">
        <div style="font-size:2rem;margin-bottom:.5rem">🔍</div>
        No products match "<strong id="emptyQuery"></strong>"
      </td>`;
      document.querySelector('.data-table tbody').appendChild(empty);
    }

    const qEl = document.getElementById('emptyQuery');
    if (qEl) qEl.textContent = input.value;
    empty.style.display = visible === 0 && query !== '' ? '' : 'none';
  });
}

/* ── AUTO-DISMISS ALERTS ── */
function initAlertDismiss() {
  document.querySelectorAll('.alert[data-dismiss]').forEach(alert => {
    const ms = parseInt(alert.dataset.dismiss) || 4000;
    setTimeout(() => {
      alert.style.transition = 'opacity .5s, transform .5s';
      alert.style.opacity = '0';
      alert.style.transform = 'translateY(-8px)';
      setTimeout(() => alert.remove(), 500);
    }, ms);
  });
}

/* ── ROW ANIMATION ── */
function initTableHighlight() {
  const rows = document.querySelectorAll('.data-table tbody tr');
  rows.forEach((row, i) => {
    row.style.animation = `fadeUp .35s ease ${i * 40}ms both`;
  });
}

/* ── DYNAMIC COLOR DOTS ── */
const COLOR_MAP = {
  black:   '#1a1a1a', white:  '#f5f5f5', silver: '#c0c0c0',
  gray:    '#808080', grey:   '#808080', blue:   '#1a6cff',
  red:     '#ff3b5c', green:  '#00e09a', gold:   '#ffb300',
  yellow:  '#ffe500', purple: '#a259ff', pink:   '#ff79c6',
  navy:    '#001f5b', brown:  '#8b5c3e', orange: '#ff8c00',
  violet:  '#7c3aed', teal:   '#00c8c8', cream:  '#f5e6c8',
  titanium:'#a8b0bc', coral:  '#ff6b6b', mint:   '#aaffd4',
  lavender:'#b39ddb', rose:   '#e91e8c',
};

function initColorDots() {
  document.querySelectorAll('.color-dot').forEach(dot => {
    const label = dot.nextSibling?.textContent?.trim().toLowerCase() ||
                  dot.parentElement?.textContent?.trim().toLowerCase() || '';
    const color = findColor(label);
    dot.style.background = color;
    if (color === '#f5f5f5' || color === '#c0c0c0') {
      dot.style.border = '1px solid #555';
    }
  });
}

function findColor(str) {
  for (const [name, hex] of Object.entries(COLOR_MAP)) {
    if (str.includes(name)) return hex;
  }
  return '#555';
}

/* ── DELETE CONFIRM ── */
function initDeleteConfirm() {
  document.querySelectorAll('.btn-delete-row').forEach(btn => {
    btn.addEventListener('click', e => {
      const model = btn.dataset.model || 'this product';
      if (!confirm(`Delete "${model}"? This cannot be undone.`)) {
        e.preventDefault();
      }
    });
  });
}

/* ── FORM VALIDATION ── */
function initFormValidation() {
  const form = document.getElementById('productForm');
  if (!form) return;

  form.addEventListener('submit', e => {
    let valid = true;

    form.querySelectorAll('[required]').forEach(field => {
      clearError(field);
      if (!field.value.trim()) {
        showError(field, 'This field is required.');
        valid = false;
      }
    });

    // Price: positive number
    const price = form.querySelector('[name="price"]');
    if (price && price.value !== '') {
      const val = parseFloat(price.value);
      if (isNaN(val) || val < 0) {
        showError(price, 'Enter a valid positive price.');
        valid = false;
      }
    }

    // Stocks: non-negative integer
    const stocks = form.querySelector('[name="Stocks"]');
    if (stocks && stocks.value !== '') {
      const val = parseInt(stocks.value);
      if (isNaN(val) || val < 0 || !Number.isInteger(val)) {
        showError(stocks, 'Enter a valid stock quantity (0 or more).');
        valid = false;
      }
    }

    if (!valid) e.preventDefault();
  });

  // Clear error on input
  form.querySelectorAll('.form-control').forEach(field => {
    field.addEventListener('input', () => clearError(field));
  });
}

function showError(field, msg) {
  field.style.borderColor = 'var(--danger)';
  field.style.boxShadow   = '0 0 0 3px rgba(255,59,92,.15)';
  let err = field.parentElement.querySelector('.field-error');
  if (!err) {
    err = document.createElement('span');
    err.className = 'field-error';
    err.style.cssText = 'font-size:.76rem;color:var(--danger);margin-top:.2rem;display:block';
    field.parentElement.appendChild(err);
  }
  err.textContent = msg;
}

function clearError(field) {
  field.style.borderColor = '';
  field.style.boxShadow   = '';
  const err = field.parentElement.querySelector('.field-error');
  if (err) err.remove();
}

/* ── STOCK BADGE COLORIZER ── */
function getStockClass(qty) {
  qty = parseInt(qty);
  if (qty > 20) return 'high';
  if (qty > 5)  return 'medium';
  return 'low';
}

/* ── UTILITY: format currency ── */
function formatPHP(amount) {
  return '₱' + parseFloat(amount).toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
}