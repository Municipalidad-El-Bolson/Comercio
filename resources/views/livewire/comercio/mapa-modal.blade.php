<div class="modal fade" id="modalMapa" tabindex="-1" aria-labelledby="modalMapaLabel" aria-hidden="true" wire:ignore>
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title" id="modalMapaLabel">Ubicación de Comercio</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body pt-2">
        <div class="row">
          <div class="col-md-5 mb-3">
            <ul class="list-unstyled mb-0" id="map-info"></ul>
          </div>
          <div class="col-md-7">
            <div id="map-modal" style="height:380px;width:100%;min-width:200px;"></div>
          </div>
        </div>
      </div>

        <div class="modal-footer py-2">
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
        </div>
    </div>
  </div>
</div>


@once
  <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
  <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>

  <script>
    // ===== Helpers Modal (BS5, BS4+jQuery, o fallback) =====
    function showModalById(id) {
      const el = document.getElementById(id);
      if (!el) return;

      // Bootstrap 5.x (sin jQuery)
      if (window.bootstrap && bootstrap.Modal) {
        // 5.2+ tiene getOrCreateInstance; 5.0/5.1 no
        let inst = (bootstrap.Modal.getInstance ? bootstrap.Modal.getInstance(el) : null);
        if (!inst) inst = new bootstrap.Modal(el);
        inst.show();

        el.addEventListener('shown.bs.modal', () => {
          try { window._comercioMapa?.mapInstance?.resize(); } catch {}
        }, { once: true });
        return;
      }

      // Bootstrap 4 (con jQuery)
      if (window.jQuery && typeof jQuery.fn.modal === 'function') {
        jQuery(el).one('shown.bs.modal', () => {
          try { window._comercioMapa?.mapInstance?.resize(); } catch {}
        });
        jQuery(el).modal('show');
        return;
      }

      // Fallback sin Bootstrap
      el.style.display = 'block';
      el.classList.add('show');
      el.removeAttribute('aria-hidden');
      try { window._comercioMapa?.mapInstance?.resize(); } catch {}
    }

    function hideModalById(id) {
      const el = document.getElementById(id);
      if (!el) return;

      if (window.bootstrap && bootstrap.Modal) {
        let inst = (bootstrap.Modal.getInstance ? bootstrap.Modal.getInstance(el) : null);
        if (!inst) inst = new bootstrap.Modal(el);
        inst.hide();
        return;
      }

      if (window.jQuery && typeof jQuery.fn.modal === 'function') {
        jQuery(el).modal('hide');
        return;
      }

      el.style.display = 'none';
      el.classList.remove('show');
      el.setAttribute('aria-hidden', 'true');
    }

    // ===== Mapbox =====
    mapboxgl.accessToken = 'pk.eyJ1IjoiYm9sc29uc2lzdGVtYXMiLCJhIjoiY2tpb3AzamM3MWYybzJ6dTYxZTR1cWJudCJ9.17kL4-zY3HQ16MGRHyuEkQ';
    const googleApiKey = "AIzaSyAyL3dQW5_PKAJLxYhs7EuzN3KGfbF7Ang"; // usar solo si geocodificás

    // Namespace único y persistente
    if (!window._comercioMapa) {
      window._comercioMapa = {
        mapInstance: null,
        markerInstance: null,

        renderInfo(payload) {
          const info = document.getElementById('map-info');
          if (!info) return;
          info.innerHTML = `
            <li><strong>Razón social:</strong> ${payload.razon ?? ''}</li>
            <li><strong>DNI/CUIT:</strong> ${payload.dni_cuit ?? ''}</li>
            <li><strong>Persona:</strong> ${payload.persona ?? ''}</li>
            <li><strong>Estado:</strong> ${payload.estado ?? ''}</li>
            <li><strong>Situación:</strong> ${payload.situacion ?? ''}</li>
            <li><strong>Domicilio:</strong> ${payload.domicilio ?? ''}</li>
            <li><strong>Subrubro:</strong> ${payload.subrubro ?? ''}</li>
          `;
        },

        initOrUpdateMap({ lat, lng, razon, domicilio, subrubro }) {
          const center = [ (lng ?? -71.53), (lat ?? -41.9645) ]; // El Bolsón fallback

          if (!this.mapInstance) {
            this.mapInstance = new mapboxgl.Map({
              container: 'map-modal',
              style: 'mapbox://styles/mapbox/streets-v12',
              center,
              zoom: 15
            });

            this.mapInstance.on('load', () => {
              const layers = this.mapInstance.getStyle().layers || [];
              layers.forEach((layer) => {
                if (layer.id.includes('poi') || layer.id.includes('place')) {
                  this.mapInstance.setLayoutProperty(layer.id, 'visibility', 'none');
                }
              });
            });
          } else {
            this.mapInstance.setCenter(center);
            this.mapInstance.setZoom(15);
          }

          if (this.markerInstance) this.markerInstance.remove();

          this.markerInstance = new mapboxgl.Marker()
            .setLngLat(center)
            .setPopup(new mapboxgl.Popup({ offset: 25 }).setHTML(`
              <h6 class="mb-1">${razon ?? ''}</h6>
              <div><strong>Dirección:</strong> ${domicilio ?? ''}</div>
              <div><strong>Subrubro:</strong> ${subrubro ?? ''}</div>
            `))
            .addTo(this.mapInstance);
        }
      };
    }

    // —— Shim para llamadas antiguas "renderInfo(...)" que hayan quedado sueltas ——
    if (!window.renderInfo) {
      window.renderInfo = function(payload) {
        return window._comercioMapa?.renderInfo?.(payload);
      };
    }

    // Registrar listener UNA sola vez
    if (!window._comercioMapaBound) {
      window.addEventListener('mostrar-modal-mapa', async (e) => {
        const payload = e.detail?.payload ?? e.detail ?? {};

        // SIEMPRE vía namespace (y el shim cubre cualquier renderInfo suelto)
        window._comercioMapa.renderInfo(payload);

        if (payload.lat != null && payload.lng != null) {
          window._comercioMapa.initOrUpdateMap({
            lat: payload.lat, lng: payload.lng,
            razon: payload.razon, domicilio: payload.domicilio, subrubro: payload.subrubro
          });
          showModalById('modalMapa');
          return;
        }

        // Fallback: geocodificar domicilio
        try {
          const url = `https://maps.googleapis.com/maps/api/geocode/json?address=${encodeURIComponent(payload.domicilio || '')}&key=${googleApiKey}`;
          const res = await fetch(url);
          const data = await res.json();
          const loc = data.results?.[0]?.geometry?.location ?? { lat: -41.9645, lng: -71.53 };

          window._comercioMapa.initOrUpdateMap({
            lat: loc.lat, lng: loc.lng,
            razon: payload.razon, domicilio: payload.domicilio, subrubro: payload.subrubro
          });
        } catch {
          window._comercioMapa.initOrUpdateMap({
            lat: -41.9645, lng: -71.53,
            razon: payload.razon, domicilio: payload.domicilio, subrubro: payload.subrubro
          });
        }

        showModalById('modalMapa');
      });

      // Cerrar modal sin jQuery (por si tenés data-dismiss="modal")
      document.addEventListener('click', (ev) => {
        if (ev.target.matches('[data-dismiss="modal"]')) {
          hideModalById('modalMapa');
        }
      });

      window._comercioMapaBound = true;
    }
  </script>
@endonce
