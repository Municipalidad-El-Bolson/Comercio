<!-- Main content -->
<section class="content">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0">Mapa de comercios</h1>
        </div>
      </div>
      <div>
        <div>
          <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
          <link rel="stylesheet"
              href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />

          <div class="card mb-3" id="filtros-card">
          <div class="card-header d-flex align-items-center justify-content-between py-2">
            <strong class="mb-0">Filtros</strong>
            <button id="btnToggleFilters" type="button" class="btn btn-sm btn-outline-secondary">
              <i id="icoToggleFilters" class="fas fa-chevron-up"></i>
            </button>
          </div>

          <div class="card-body py-2" id="filtros-body">
            {{-- ================= Filtros adicionales ================= --}}
            <div class="mb-2">
              <div class="form-row">
                <div class="form-group col-md-3 mb-2">
                  <label class="mb-1">Barrio</label>
                  <select class="form-control form-control-sm" wire:model.live="selectedBarrio">
                    <option value="">-- Todos --</option>
                    @foreach($barrios as $b)
                      <option value="{{ $b }}">{{ $b }}</option>
                    @endforeach
                  </select>
                </div>

                <div class="form-group col-md-3 mb-2">
                  <label class="mb-1">Estado</label>
                  <select class="form-control form-control-sm" wire:model.live="selectedEstado">
                    <option value="">-- Todos --</option>
                    @foreach($estados as $value => $label)
                      <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                  </select>
                </div>

                <div class="form-group col-md-6 mb-2 position-relative">
                  <label class="mb-1">Nombre de fantasía</label>
                  <input type="text" class="form-control form-control-sm"
                        placeholder="Escribí para buscar (mín. 2 letras)"
                        wire:model.live.debounce.300ms="fantasiaQuery" />

                  {{-- Sugerencias --}}
                  @if(!empty($fantasiaQuery) && count($fantasiaSuggestions) > 0)
                    <ul class="list-group position-absolute w-50" style="z-index:1000;">
                      @foreach($fantasiaSuggestions as $sug)
                        <li class="list-group-item list-group-item-action p-1"
                            style="cursor:pointer"
                            wire:click="$set('fantasiaQuery','{{ addslashes($sug) }}')">
                          {{ $sug }}
                        </li>
                      @endforeach
                    </ul>
                  @endif
                </div>
              </div>
            </div>

              {{-- Filtros de rubro --}}
            <div class="mb-3">
              <div class="form-row">
                <div class="form-group col-md-4 mb-2">
                  <label class="mb-1">Mega rubro</label>
                  <select id="f-mega" class="form-control form-control-sm" wire:model.live="selectedMega">
                    <option value="">-- Seleccione Mega rubro --</option>
                    @foreach ($megas as $mega)
                      <option value="{{ $mega }}">{{ $mega }}</option>
                    @endforeach
                  </select>
                </div>

                <div class="form-group col-md-4 mb-2">
                  <label class="mb-1">Rubro madre</label>
                  <select id="f-madre" class="form-control form-control-sm"
                          wire:model.live="selectedMadre" @disabled(empty($selectedMega))>
                    <option value="">-- Seleccione Rubro madre --</option>
                    @foreach ($madres as $madre)
                      <option value="{{ $madre }}">{{ $madre }}</option>
                    @endforeach
                  </select>
                </div>

                <div class="form-group col-md-4 mb-2">
                  <label class="mb-1">Subrubro</label>
                  <select id="f-sub" class="form-control form-control-sm"
                          wire:model.live="selectedSubId" @disabled(empty($selectedMadre))>
                    <option value="">-- Seleccione Subrubro --</option>
                    @foreach ($subs as $op)
                      <option value="{{ $op['id'] }}">{{ $op['sub'] }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
            </div>

            <script>
              (function () {
                const KEY = 'map.filters.collapsed';

                function setCollapsed({ bodyEl, iconEl }, collapsed) {
                  if (!bodyEl || !iconEl) return;
                  bodyEl.style.display = collapsed ? 'none' : '';
                  iconEl.classList.toggle('fa-chevron-up', !collapsed);
                  iconEl.classList.toggle('fa-chevron-down', collapsed);
                  try { localStorage.setItem(KEY, collapsed ? '1' : '0'); } catch {}
                  // si cambia el alto, avisamos al mapa
                  setTimeout(() => { try { map.resize(); } catch {} }, 150);
                }

                function bindHandlers(root) {
                  const cardEl = root.querySelector('#filtros-card');
                  const bodyEl = root.querySelector('#filtros-body');
                  const btnEl  = root.querySelector('#btnToggleFilters');
                  const iconEl = root.querySelector('#icoToggleFilters');
                  const floEl  = root.querySelector('#btnToggleFiltersFloating');

                  if (!cardEl || !bodyEl || !btnEl || !iconEl) return;

                  // DEFAULT = COLAPSADO (si no hay valor guardado)
                  let collapsed = true;
                  try {
                    const saved = localStorage.getItem(KEY);
                    if (saved === '0') collapsed = false; // respetar lo guardado
                  } catch {}

                  // aplicar estado inicial
                  setCollapsed({ bodyEl, iconEl }, collapsed);

                  // evitar duplicar listeners si Livewire re-renderiza
                  btnEl.onclick = () => {
                    collapsed = !collapsed;
                    setCollapsed({ bodyEl, iconEl }, collapsed);
                  };

                  if (floEl) {
                    floEl.onclick = () => {
                      collapsed = !collapsed;
                      setCollapsed({ bodyEl, iconEl }, collapsed);
                    };
                  }
                }

                // 1) Bind inicial cuando el DOM está listo
                if (document.readyState === 'loading') {
                  document.addEventListener('DOMContentLoaded', () => bindHandlers(document));
                } else {
                  bindHandlers(document);
                }

                // 2) Re-bind cuando Livewire actualiza el DOM (v3)
                window.addEventListener('livewire:init', () => {
                  // en v3 “morph” reemplaza nodos; nos re-enlazamos post-actualización
                  document.addEventListener('livewire:navigated', () => bindHandlers(document));
                });

                // 3) Fallback universal: si el card se re-crea por cualquier causa
                const mo = new MutationObserver((muts) => {
                  for (const m of muts) {
                    if (m.addedNodes?.length) {
                      const added = Array.from(m.addedNodes);
                      if (added.some(n => (n.id === 'filtros-card') || (n.querySelector && n.querySelector('#filtros-card')))) {
                        bindHandlers(document);
                        break;
                      }
                    }
                  }
                });
                mo.observe(document.body, { childList: true, subtree: true });
              })();
            </script>


              {{-- CAPAS: TOGGLES --}}
            <div class="mb-2">
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="toggleBarrios" checked>
                <label class="form-check-label" for="toggleBarrios">Capa Barrios</label>
              </div>
              {{--<div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="toggleCatastro" checked>
                <label class="form-check-label" for="toggleCatastro">Capa Catastro</label>
              </div>--}}
            </div>
          </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div id="map" wire:ignore style="height: 500px; width: 100%; min-width: 200px;"></div>
            </div>
        </div> 
      </div>
    </div>
  </div>
</section>

<script src='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js'></script>
<script>
  const googleApiKey = "AIzaSyAyL3dQW5_PKAJLxYhs7EuzN3KGfbF7Ang";

  mapboxgl.accessToken = 'pk.eyJ1IjoiYm9sc29uc2lzdGVtYXMiLCJhIjoiY2tpb3AzamM3MWYybzJ6dTYxZTR1cWJudCJ9.17kL4-zY3HQ16MGRHyuEkQ';
  const map = new mapboxgl.Map({
    container: 'map',
    style: 'mapbox://styles/mapbox/streets-v12',
    center: [-71.53, -41.9645],
    zoom: 14
  });

  // ===== CARGA INICIAL DEL MAPA + CAPAS =====
    // Cache local para calcular bounds de polígonos
    let GEO_BARRIOS = null;
    let GEO_CATASTRO = null;

    // Cargar GeoJSON en memoria (además de usarlos como sources)
    fetch('/geo/BARRIOS1.json').then(r=>r.json()).then(j=>{ GEO_BARRIOS=j; });
    fetch('/geo/CATASTRO_GEO.json').then(r=>r.json()).then(j=>{ GEO_CATASTRO=j; });

    map.on('load', async function() {
      // 1) Limpiar POIs/places
      const layers = map.getStyle()?.layers ?? [];
      layers.forEach((layer) => {
        if (layer.id?.includes('poi') || layer.id?.includes('place')) {
          try { map.setLayoutProperty(layer.id, 'visibility', 'none'); } catch {}
        }
    });

    // 2) FUENTES (GeoJSON servidos por tu app)
    //    Asegurate de que existan estos archivos en /public/geo/...
    map.addSource('barrios-src', {
      type: 'geojson',
      data: '/geo/BARRIOS1.json'
    });
    map.addSource('catastro-hl-src', {
      type:'geojson', 
      data: { "type":"FeatureCollection", "features":[] }});

    // 3) CAPAS: BARRIOS
    map.addLayer({
      id: 'barrios-fill',
      type: 'fill',
      source: 'barrios-src',
      paint: { 'fill-color': '#0080ff', 'fill-opacity': 0.15 }
    });
    map.addLayer({
      id: 'barrios-line',
      type: 'line',
      source: 'barrios-src',
      paint: { 'line-color': '#0080ff', 'line-width': 1.5 }
    });

    map.addLayer({
      id: 'catastro-hl-fill',
      type: 'fill',
      source: 'catastro-hl-src',
      paint: { 'fill-color': '#ff0000', 'fill-opacity': 0.25 }
    });
    map.addLayer({
      id: 'catastro-hl-line',
      type: 'line',
      source: 'catastro-hl-src',
      paint: { 'line-color': '#ff0000', 'line-width': 2 }
    });

    // 5) Aplicar filtros iniciales de resaltado de polígonos (si hay selects en Livewire)
    applyPolygonFilters();
  });

    // Acumula posiciones de pines de esta "pintada"
    let currentMarkerLngLats = [];

    // Agregar un punto a la caja
    function extendBounds(bounds, lng, lat) {
    if (!bounds) return new mapboxgl.LngLatBounds([lng, lat], [lng, lat]);
    return bounds.extend([lng, lat]);
    }

    // Hace fit a bounds si existen; si no, centra a El Bolsón
    function fitToBounds(bounds) {
    if (bounds && typeof bounds.getNorthEast === 'function') {
        map.fitBounds(bounds, { padding: 40, maxZoom: 17, duration: 600 });
    } else {
        map.easeTo({ center: [-71.53, -41.9645], zoom: 14, duration: 600 });
    }
    }

    // Devuelve bounds del polígono por propiedad exacta
    function boundsOfFeatureByProp(featureCollection, propName, value) {
    if (!featureCollection) return null;
    const feats = featureCollection.features || [];
    // buscar primer match exacto
    const f = feats.find(fe => (fe.properties?.[propName] ?? '') === value);
    if (!f) return null;

    // recorrer todas las coords del (Multi)Polygon
    const type = f.geometry?.type;
    const coords = f.geometry?.coordinates || [];
    let b = null;

    const addCoord = ([lng,lat]) => { b = extendBounds(b, lng, lat); };

    if (type === 'Polygon') {
        (coords[0] || []).forEach(addCoord);
    } else if (type === 'MultiPolygon') {
        coords.forEach(poly => (poly[0] || []).forEach(addCoord));
    }
    return b;
    }


  // ===== Helpers para toggles y filtros de capas =====
  function setLayerVisibility(prefix, visible) {
    const v = visible ? 'visible' : 'none';
    ['fill','line'].forEach(sfx => {
      const id = `${prefix}-${sfx}`;
      if (map.getLayer(id)) map.setLayoutProperty(id, 'visibility', v);
    });
  }

  // Lee valores actuales de selects Livewire (si existen) y filtra polígonos
  function applyPolygonFilters() {
    const barrio = document.querySelector('[wire\\:model\\.live="selectedBarrio"]')?.value || '';
    const nomen  = document.querySelector('[wire\\:model\\.live="selectedNomen"]')?.value || '';

    if (map.getLayer('barrios-fill')) {
      const filter = barrio ? ['==', ['get','BARRIO'], barrio] : true;
      map.setFilter('barrios-fill', filter);
      map.setFilter('barrios-line', filter);
    }

    if (map.getLayer('catastro-fill')) {
      // El archivo usa RefName (ej. "J749 052F000")  → filtrar por esa propiedad
      const f = nomen ? ['==', ['get','RefName'], nomen] : true;
      map.setFilter('catastro-fill', f);
      map.setFilter('catastro-line', f);
    }
  }

  // ===== Listener para toggles de capas =====
  document.getElementById('toggleBarrios')?.addEventListener('change', (e) => {
    setLayerVisibility('barrios', e.target.checked);
  });
  document.getElementById('toggleCpu')?.addEventListener('change', (e) => {
    setLayerVisibility('cpu', e.target.checked);
  });

  let ubicaciones = @json($ubicaciones);
  // --- util ---
  const sleep = (ms) => new Promise(r => setTimeout(r, ms));

  // Convierte tus ubicaciones en GeoJSON
  function ubicacionesToGeoJSON(list) {
    const feats = [];
    for (const rec of (list || [])) {
      const lat = parseFloat(rec.lat ?? rec.latitud);
      const lng = parseFloat(rec.lng ?? rec.longitud);
      if (!Number.isFinite(lat) || !Number.isFinite(lng)) continue;

      feats.push({
        type: 'Feature',
        geometry: { type: 'Point', coordinates: [lng, lat] },
        properties: {
          nombre: rec.nombre_comercial ?? rec.razon_social ?? '',
          direccion: rec.domicilio_comercio ?? '',
          barrio: rec.barrio ?? '-',
          estado: rec.estado ?? '-',
          rubro: rec?.rubro?.subrubro ?? ''
        }
      });
    }
    return { type: 'FeatureCollection', features: feats };
  }

  // Geocodifica sólo los que no traen lat/lng (rápido y sencillo)
  async function geocodeMissingCoords(list, {maxToGeocode=50, delayMs=120} = {}) {
    if (!googleApiKey) return list;

    const BBOX = { minLat: -42.10, maxLat: -41.85, minLng: -71.65, maxLng: -71.35 };
    const inBbox = (lat,lng) => lat>=BBOX.minLat && lat<=BBOX.maxLat && lng>=BBOX.minLng && lng<=BBOX.maxLng;

    let count = 0;
    for (const rec of list) {
      const hasLatLng = Number.isFinite(parseFloat(rec.lat ?? rec.latitud))
                    && Number.isFinite(parseFloat(rec.lng ?? rec.longitud));
      if (hasLatLng) continue;
      if (!rec.domicilio_comercio) continue;
      if (count >= maxToGeocode) break;

      try {
        const addr = rec.domicilio_comercio + ', El Bolsón, Río Negro, Argentina';
        const url = `https://maps.googleapis.com/maps/api/geocode/json?address=${encodeURIComponent(addr)}&key=${googleApiKey}&language=es-AR&region=ar&components=country:AR|administrative_area:Rio%20Negro|locality:El%20Bolson`;
        const res = await fetch(url);
        const data = await res.json();
        const loc = data?.results?.[0]?.geometry?.location;
        if (loc && Number.isFinite(loc.lat) && Number.isFinite(loc.lng) && inBbox(loc.lat, loc.lng)) {
          rec.lat = loc.lat;
          rec.lng = loc.lng;
          count++;
          await sleep(delayMs);
        }
      } catch (e) {
        // silencioso
      }
    }
    console.log(`[Mapa] Geocodificados en front: ${count}`);
    return list;
  }


  // Popup
  function popupHTML(p) {
    return `
      <div class="popup-card">
        <div class="popup-title">
          <i class="fas fa-store"></i>
          <span>${escapeHtml(p.nombre || '')}</span>
        </div>
        <div class="popup-row">
          <i class="fas fa-map-marker-alt"></i>
          <div>${escapeHtml(p.direccion || '')}</div>
        </div>
        <div class="popup-row">
          <i class="fas fa-tags"></i>
          <div>${escapeHtml(p.rubro || '-')}</div>
        </div>
        <div class="popup-row">
          <i class="fas fa-city"></i>
          <div>${escapeHtml(p.barrio || '-')}</div>
        </div>
        <div class="popup-row">
          <i class="fas fa-clipboard-check"></i>
          <div>${escapeHtml(p.estado || '-')}</div>
        </div>
      </div>
    `;
  }
  function escapeHtml(s) {
    return String(s)
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'",'&#39;');
  }

  // ====== BLOQUE PRINCIPAL ======
  map.on('load', async () => {
    // 1) Rellenar coords faltantes (temporal, mejor hacerlo en backend)
    try {
      ubicaciones = await geocodeMissingCoords(ubicaciones, { maxToGeocode: 200, delayMs: 110 });
    } catch {}

    // 2) Crear source con clustering
    map.addSource('comercios-src', {
      type: 'geojson',
      data: ubicacionesToGeoJSON(ubicaciones),
      cluster: true,
      clusterMaxZoom: 16,
      clusterRadius: 40
    });

    // 3) Capas de clusters y puntos
    map.addLayer({
      id: 'comercios-clusters',
      type: 'circle',
      source: 'comercios-src',
      filter: ['has', 'point_count'],
      paint: {
        'circle-color': [
          'step', ['get', 'point_count'],
          '#5b8def', 5,
          '#3fa06f', 15,
          '#e67e22'
        ],
        'circle-radius': [
          'step', ['get', 'point_count'],
          14, 5,
          18, 15,
          24
        ],
        'circle-stroke-color': '#ffffff',
        'circle-stroke-width': 2
      }
    });

    map.addLayer({
      id: 'comercios-cluster-count',
      type: 'symbol',
      source: 'comercios-src',
      filter: ['has', 'point_count'],
      layout: {
        'text-field': ['get', 'point_count_abbreviated'],
        'text-size': 12,
        'text-font': ['Open Sans Bold', 'Arial Unicode MS Bold']
      },
      paint: { 'text-color': '#ffffff' }
    });

    map.addLayer({
      id: 'comercios-unclustered',
      type: 'circle',
      source: 'comercios-src',
      filter: ['!', ['has', 'point_count']],
      paint: {
        'circle-color': '#1e90ff',
        'circle-radius': 7,
        'circle-stroke-color': '#ffffff',
        'circle-stroke-width': 2
      }
    });

    // 4) Interacciones
    map.on('click', 'comercios-clusters', (e) => {
      const features = map.queryRenderedFeatures(e.point, { layers: ['comercios-clusters'] });
      const clusterId = features[0].properties.cluster_id;
      map.getSource('comercios-src').getClusterExpansionZoom(clusterId, (err, zoom) => {
        if (err) return;
        map.easeTo({ center: features[0].geometry.coordinates, zoom });
      });
    });

    const popup = new mapboxgl.Popup({ closeButton: true, offset: 16 });
    map.on('click', 'comercios-unclustered', (e) => {
      const f = e.features[0];
      popup.setLngLat(f.geometry.coordinates).setHTML(popupHTML(f.properties)).addTo(map);
    });

    ['comercios-clusters','comercios-unclustered'].forEach(id => {
      map.on('mouseenter', id, () => map.getCanvas().style.cursor = 'pointer');
      map.on('mouseleave', id, () => map.getCanvas().style.cursor = '');
    });

    // 5) Fit a todo lo visible (si querés)
    try {
      const data = map.getSource('comercios-src')._data || map.getSource('comercios-src')._options.data;
      const feats = data?.features || [];
      if (feats.length > 1) {
        const bounds = new mapboxgl.LngLatBounds();
        feats.forEach(f => bounds.extend(f.geometry.coordinates));
        map.fitBounds(bounds, { padding: 40, maxZoom: 15, duration: 600 });
      }
    } catch {}
  });

  // Livewire → refrescar source cuando cambien ubicaciones
  window.addEventListener('ubicacionesUpdated', async (ev) => {
    ubicaciones = ev.detail?.ubicaciones ?? [];
    // Intentamos completar coords de nuevos registros sin lat/lng
    try { ubicaciones = await geocodeMissingCoords(ubicaciones, { maxToGeocode: 80, delayMs: 110 }); } catch {}
    const src = map.getSource('comercios-src');
    const data = ubicacionesToGeoJSON(ubicaciones);
    if (src) src.setData(data);
    const feats = data?.features || [];
    if (feats.length > 0) {
      const bounds = new mapboxgl.LngLatBounds();
      feats.forEach(f => bounds.extend(f.geometry.coordinates));
      map.fitBounds(bounds, { padding: 40, maxZoom: 15, duration: 600 });
    }
  });
</script>
<style>
  #filtros-body {
    transition: height .18s ease, opacity .18s ease;
  }
</style>

<style> .mapboxgl-popup-content { padding: 0 !important; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 24px rgba(0,0,0,.18); min-width: 260px; } .popup-card { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; } .popup-title { display: flex; font-size: 18px; align-items: center; gap: .5rem; background: #0d6efd; color: #fff; padding: .6rem .8rem; font-weight: 600; } .popup-row { display: grid; grid-template-columns: 20px 1fr; gap: .6rem; padding: .55rem .8rem; border-top: 1px solid #f0f1f3; font-size: .95rem; align-items: start; } .popup-row i { opacity: .7; margin-top: .15rem; } </style>