<div class="modal fade" id="modalMapa" tabindex="-1" aria-labelledby="modalMapaLabel" aria-hidden="true" wire:ignore>
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content shadow-sm border-0">
      <div class="modal-header bg-light py-2">
        <h5 class="modal-title fw-bold" id="modalMapaLabel">
          <i class="fas fa-map-marker-alt me-1 text-primary"></i> Ubicación de Comercio
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body pt-3">
        <div class="row">
          <div class="col-md-5 mb-3">
            <ul class="list-unstyled mb-2 small" id="map-info"></ul>
            <div id="map-loading" class="text-muted small d-none">
              Cargando ubicación…
              <span class="spinner-border spinner-border-sm align-middle ms-1"></span>
            </div>
          </div>
          <div class="col-md-7">
            <div id="map-modal" style="height:380px;width:100%;min-width:200px;border-radius:8px;overflow:hidden;"></div>
          </div>
        </div>
      </div>

      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" data-dismiss="modal">
          <i class="fas fa-times me-1"></i> Cerrar
        </button>
      </div>
    </div>
  </div>
</div>

@once
  <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
  <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>

  <style>
    /* Popup prolijo y consistente con el resto */
    .mapboxgl-popup-content{
      padding:0 !important; border-radius:12px; overflow:hidden;
      box-shadow:0 8px 24px rgba(0,0,0,.18);
      font-family:system-ui, -apple-system, Segoe UI, Roboto, Arial;
    }
    .popup-card .popup-title{
      display:flex; align-items:center; gap:.5rem; font-weight:600;
      background:#0d6efd; color:#fff; padding:.6rem .8rem; font-size:16px;
    }
    .popup-card .popup-row{
      display:grid; grid-template-columns:20px 1fr; gap:.6rem;
      padding:.55rem .8rem; border-top:1px solid #f0f1f3; font-size:.95rem; align-items:start;
    }
    .popup-card .popup-row i{ opacity:.75; margin-top:.15rem; }
    #map-info li{ padding:.25rem 0; border-bottom:1px dashed #e9ecef; }
    #map-info li:last-child{ border-bottom:none; }
  </style>

  <script>
    // ===== Config =====
    mapboxgl.accessToken = 'pk.eyJ1IjoiYm9sc29uc2lzdGVtYXMiLCJhIjoiY2tpb3AzamM3MWYybzJ6dTYxZTR1cWJudCJ9.17kL4-zY3HQ16MGRHyuEkQ';
    const DEFAULT_CENTER = [-71.53, -41.9645]; // El Bolsón (lng,lat)

    // ===== Helpers Modal (BS5 todas / BS4) =====
    function getModalInst(el){
      if (window.bootstrap && bootstrap.Modal){
        // BS 5.2+ trae getOrCreateInstance
        if (bootstrap.Modal.getOrCreateInstance) return bootstrap.Modal.getOrCreateInstance(el);
        // BS 5.0/5.1: no métodos estáticos
        if (!el._bsModal) el._bsModal = new bootstrap.Modal(el);
        return el._bsModal;
      }
      return null;
    }
    function showModalById(id){
      const el = document.getElementById(id);
      if (!el) return;
      // tiny delay por si Livewire acaba de inyectar el DOM del modal
      setTimeout(() => {
        const inst = getModalInst(el);
        if (inst){
          el.addEventListener('shown.bs.modal', () => {
            try { window._comercioMapa?.map?.resize(); } catch {}
          }, { once:true });
          inst.show();
          return;
        }
        if (window.jQuery && typeof jQuery.fn.modal === 'function'){ // BS4
          jQuery(el).one('shown.bs.modal', () => { try { window._comercioMapa?.map?.resize(); } catch {} });
          jQuery(el).modal('show');
          return;
        }
        // Fallback sin Bootstrap
        el.style.display='block'; el.classList.add('show'); el.removeAttribute('aria-hidden');
        try { window._comercioMapa?.map?.resize(); } catch {}
      }, 0);
    }
    function hideModalById(id){
      const el = document.getElementById(id);
      if (!el) return;
      const inst = getModalInst(el);
      if (inst){ inst.hide(); return; }
      if (window.jQuery && typeof jQuery.fn.modal === 'function'){ jQuery(el).modal('hide'); return; }
      el.style.display='none'; el.classList.remove('show'); el.setAttribute('aria-hidden','true');
    }

    // ===== Namespace único =====
    if (!window._comercioMapa){
      window._comercioMapa = {
        map: null,
        marker: null,

        ensureMap(){
          if (this.map) return this.map;
          this.map = new mapboxgl.Map({
            container: 'map-modal',
            style: 'mapbox://styles/mapbox/streets-v12',
            center: DEFAULT_CENTER,
            zoom: 14
          });
          this.map.on('load', () => {
            const layers = this.map.getStyle()?.layers ?? [];
            layers.forEach(l => {
              if (l.id.includes('poi') || l.id.includes('place')){
                try { this.map.setLayoutProperty(l.id, 'visibility', 'none'); } catch {}
              }
            });
          });
          return this.map;
        },

        setMarker({lng,lat, razon, domicilio, subrubro}){
          this.marker?.remove();
          this.marker = new mapboxgl.Marker()
            .setLngLat([lng, lat])
            .setPopup(new mapboxgl.Popup({ offset:16 }).setHTML(`
              <div class="popup-card">
                <div class="popup-title"><i class="fas fa-store"></i><span>${this.escape(razon||'')}</span></div>
                <div class="popup-row"><i class="fas fa-map-marker-alt"></i><div>${this.escape(domicilio||'')}</div></div>
                <div class="popup-row"><i class="fas fa-tags"></i><div>${this.escape(subrubro||'-')}</div></div>
              </div>
            `))
            .addTo(this.map);
        },

        renderInfo(payload){
          const info = document.getElementById('map-info');
          if (!info) return;
          info.innerHTML = `
            <li><strong>Razón social:</strong> ${this.escape(payload.razon ?? '')}</li>
            <li><strong>DNI/CUIT:</strong> ${this.escape(payload.dni_cuit ?? '')}</li>
            <li><strong>Persona:</strong> ${this.escape(payload.persona ?? '')}</li>
            <li><strong>Estado:</strong> ${this.escape(payload.estado ?? '')}</li>
            <li><strong>Situación:</strong> ${this.escape(payload.situacion ?? '')}</li>
            <li><strong>Domicilio:</strong> ${this.escape(payload.domicilio ?? '')}</li>
            <li><strong>Subrubro:</strong> ${this.escape(payload.subrubro ?? '')}</li>
          `;
        },

        escape(s){
          return String(s)
            .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
            .replaceAll('"','&quot;').replaceAll("'",'&#39;');
        }
      };
    }

    if (!window.renderInfo) {
      window.renderInfo = function () {
        return window._comercioMapa?.renderInfo?.apply(window._comercioMapa, arguments);
      };
    }

    if (!window.initOrUpdateMap) {
      window.initOrUpdateMap = function (opts) {
        const ns = window._comercioMapa;
        if (!ns) return;
        const map = ns.ensureMap?.();
        if (!map) return;
        // Si te llamaban con {lat,lng, razon, domicilio, subrubro}
        if (opts && typeof opts === 'object') {
          const lng = opts.lng ?? -71.53;
          const lat = opts.lat ?? -41.9645;
          try { map.setCenter([lng, lat]); map.setZoom(15); } catch {}
          ns.setMarker?.({
            lng, lat,
            razon: opts.razon,
            domicilio: opts.domicilio,
            subrubro: opts.subrubro
          });
        }
      };
    }

    // ===== Utilidades =====
    function setLoading(v){
      const el = document.getElementById('map-loading');
      if (el) el.classList.toggle('d-none', !v);
    }

    // ===== Enlace a eventos (BrowserEvent + Livewire.on) =====
    function handleShowMapaEvent(payload){
      // 1) abrir modal ya
      showModalById('modalMapa');
      // 2) info textual
      window._comercioMapa.renderInfo(payload || {});
      // 3) mapa
      const map = window._comercioMapa.ensureMap();
      setLoading(false);

      const hasCoords = payload && payload.lat != null && payload.lng != null;
      const lng = hasCoords ? payload.lng : DEFAULT_CENTER[0];
      const lat = hasCoords ? payload.lat : DEFAULT_CENTER[1];

      map.setCenter([lng, lat]);
      map.setZoom(hasCoords ? 15 : 14);
      window._comercioMapa.setMarker({
        lng, lat,
        razon: payload?.razon,
        domicilio: payload?.domicilio,
        subrubro: payload?.subrubro
      });

      // por si el modal terminó de animar luego
      setTimeout(() => { try { map.resize(); } catch {} }, 200);
    }

    // Browser Event (cuando usás $this->dispatch('mostrar-modal-mapa', payload: [...] ))
    if (!window._mapModalBoundWin){
      window.addEventListener('mostrar-modal-mapa', (e) => {
        const payload = e?.detail?.payload ?? e?.detail ?? {};
        handleShowMapaEvent(payload);
      });
      window._mapModalBoundWin = true;
    }

    // Livewire Event (cuando usás Livewire.emit/dispatch desde JS o blade con $dispatch)
    if (!window._mapModalBoundLW){
      window.addEventListener('livewire:init', () => {
        if (window.Livewire?.on){
          Livewire.on('mostrar-modal-mapa', ({ payload }) => handleShowMapaEvent(payload || {}));
        }
      });
      window._mapModalBoundLW = true;
    }

    // Cerrar modal si tenés botones BS4 con data-dismiss
    document.addEventListener('click', (ev) => {
      if (ev.target.matches('[data-dismiss="modal"]')) hideModalById('modalMapa');
    });
  </script>
@endonce
