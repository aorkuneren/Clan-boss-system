document.addEventListener('DOMContentLoaded', () => {
  const calendarEl = document.getElementById('calendar');
  const modalEl = document.getElementById('eventModal');
  const modal = new bootstrap.Modal(modalEl);
  const modalContent = document.getElementById('modalContent');

  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    locale: 'tr',
    headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
    buttonText: { today: 'Bugün', month: 'Ay' },
    events: '/load_events.php',
    dateClick: (info) => openDay(info.dateStr)
  });
  calendar.render();

  async function openDay(dateStr) {
    const res = await fetch(`/api/event_detail.php?date=${encodeURIComponent(dateStr)}`);
    const data = await res.json();
    modalContent.innerHTML = renderDetail(data);
    bindDetailHandlers(data);
    modal.show();
  }

  function renderDetail(data) {
    const ev = data.event;
    const isAdmin = window.App?.isAdmin;
    const userOptions = data.users.map(u => {
      const dot = (parseInt(u.is_active,10) === 1) ? '🟢' : '⚪';
      return `<option value="${u.id}" ${data.participants.some(p=>p.user_id==u.id)?'selected':''}>
                ${u.username}
              </option>`;
    }).join('');    
    const dropsRows = data.drops.map(d => dropRow(d)).join('');

    return `
      <div class="mb-2">
        <div class="list-mini">
          <span class="badge bg-${ev.status === 'Kesildi' ? 'success' : 'danger'}">${ev.status}</span>
          <span class="badge bg-info">Katılımcı: ${data.participants.length}</span>
          <span class="badge bg-primary">Toplam: ${data.total_sales_fmt}</span>
          <span class="badge bg-secondary">Kişi Başı: ${data.per_share_fmt}</span>
        </div>
      </div>

      ${isAdmin ? `
      <form id="formStatus" class="row g-2 align-items-end mb-2">
        <input type="hidden" name="date" value="${ev.event_date}">
        <div class="col-md-8">
          <label class="form-label">Durum</label>
          <select name="status" class="form-select form-select-sm">
            <option value="Kesildi" ${ev.status === 'Kesildi' ? 'selected' : ''}>Kesildi</option>
            <option value="Kesilmedi" ${ev.status === 'Kesilmedi' ? 'selected' : ''}>Kesilmedi</option>
          </select>
        </div>
        <div class="col-md-4">
          <button class="btn btn-success btn-sm w-100">Kaydet</button>
        </div>
      </form>

      <div class="section-title">Katılımcılar</div>
      <form id="formParticipants" class="row g-2 align-items-end mb-2">
        <input type="hidden" name="event_id" value="${ev.id ?? ''}">
        <div class="col-12">
          <select id="participantsSelect" name="participants[]" class="form-select" multiple size="6">
            ${userOptions}
          </select>
          <!--<div class="mt-2 d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnSelectAll">Tümünü Seç</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnDeselectAll">Tümünü Kaldır</button>
          </div>-->
        </div>
        <div class="col-12 mt-2">
          <button class="btn btn-primary btn-sm">Katılımcıları Kaydet</button>
        </div>
      </form>

      <div class="section-title">Droplar</div>
      <form id="formDrop" class="mb-2">
        <input type="hidden" name="event_id" value="${ev.id ?? ''}">
        <div id="dropsTable">${dropsRows || dropRow()}</div>
        <div class="d-flex gap-2 mt-2">
          <button type="button" class="btn btn-secondary btn-sm" id="btnAddDrop">Satır Ekle</button>
          <button class="btn btn-success btn-sm">Dropları Kaydet</button>
        </div>
      </form>
      ` : `<div class="alert alert-info">Sadece yöneticiler düzenleme yapabilir.</div>`}
    `;
  }

  function dropRow(d) {
    d = d || { id: '', item_name: '', status: 'Bekliyor', price: '', price_fmt: '' };
    return `
      <div class="row g-2 drop-row align-items-end mb-2" data-id="${d.id || ''}">
        <div class="col-md-5">
          <label class="form-label">Drop Adı</label>
          <input name="item_name[]" class="form-control form-control-sm" value="${d.item_name || ''}">
        </div>
        <div class="col-md-3">
          <label class="form-label">Durum</label>
          <select name="status[]" class="form-select form-select-sm">
            <option value="Bekliyor" ${d.status === 'Bekliyor' ? 'selected' : ''}>Bekliyor</option>
            <option value="Satıldı" ${d.status === 'Satıldı' ? 'selected' : ''}>Satıldı</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Satış Fiyatı</label>
          <input name="price[]" class="form-control form-control-sm price-input" value="${d.price_fmt||''}" placeholder="örn: 100.000.000">
        </div>
        <div class="col-md-1">
          ${d.id ? `<button type="button" class="btn btn-outline-danger btn-sm btnDelDrop" data-drop="${d.id}">Sil</button>` : ''}
        </div>
      </div>
    `;
    
  }
  
// Fiyat inputlarına mask
document.addEventListener('input', function(e) {
  if (e.target.classList.contains('price-input')) {
      let val = e.target.value.replace(/\D/g, ''); // sadece rakam
      val = val.replace(/\B(?=(\d{3})+(?!\d))/g, '.'); // binlik ayırıcı
      e.target.value = val;
  }
});
  function bindDetailHandlers(data) {
    const ev = data.event;
    if (!(window.App?.isAdmin)) return;
  
    // Gün durumu kaydet
    document.getElementById('formStatus')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      const r = await fetch('/api/event_save.php', { method: 'POST', body: fd });
      const j = await r.json();
      if (j.ok) { openDay(ev.event_date); calendar.refetchEvents(); } else { alert(j.msg || 'Hata'); }
    });
  
    // === KATILIMCI SEÇİMİ - Choices.js entegrasyonu ===
    // Select element
    const participantsSelect = document.getElementById('participantsSelect');
  
    // Choices örneği (aranabilir + çoklu + seçiliyi kaldır butonu)
    const choices = new Choices(participantsSelect, {
      removeItemButton: true,
      shouldSort: true,
      searchEnabled: true,
      placeholderValue: 'Katılımcı seçin',
      searchPlaceholderValue: 'Ara...',
      noChoicesText: 'Üye yok',
      noResultsText: 'Sonuç bulunamadı',
      itemSelectText: 'Seç',
      position: 'bottom'
    });
  
    // Tümünü seç
    document.getElementById('btnSelectAll')?.addEventListener('click', () => {
      const allValues = [...participantsSelect.options].map(o => o.value);
      choices.setChoiceByValue(allValues); // tüm değerleri seç
    });
  
    // Tümünü kaldır
    document.getElementById('btnDeselectAll')?.addEventListener('click', () => {
      choices.removeActiveItems(); // tüm seçili item'ları kaldır
    });
  
    // Katılımcılar kaydet
    document.getElementById('formParticipants')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      // Choices, seçimi SELECT üzerinde güncel tuttuğu için FormData yeterli
      const fd = new FormData(e.target);
      fd.append('date', ev.event_date);
      const r = await fetch('/api/participants_save.php', { method: 'POST', body: fd });
      const j = await r.json();
      if (j.ok) { openDay(ev.event_date); calendar.refetchEvents(); } else { alert(j.msg || 'Hata'); }
    });
  
    // === DROPLAR ===
    document.getElementById('btnAddDrop')?.addEventListener('click', () => {
      document.getElementById('dropsTable').insertAdjacentHTML('beforeend', dropRow());
    });
  
    document.getElementById('formDrop')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      fd.append('date', ev.event_date);
      const r = await fetch('/api/drop_save.php', { method: 'POST', body: fd });
      const j = await r.json();
      if (j.ok) { openDay(ev.event_date); calendar.refetchEvents(); } else { alert(j.msg || 'Hata'); }
    });
  
    // Drop sil
    modalContent.querySelectorAll('.btnDelDrop').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (!confirm('Silinsin mi?')) return;
        const fd = new FormData();
        fd.append('drop_id', btn.dataset.drop);
        const r = await fetch('/api/drop_delete.php', { method: 'POST', body: fd });
        const j = await r.json();
        if (j.ok) { openDay(ev.event_date); calendar.refetchEvents(); } else { alert(j.msg || 'Hata'); }
      });
    });
  }
  
});
