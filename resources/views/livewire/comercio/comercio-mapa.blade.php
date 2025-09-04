<!-- Main content -->
<section class="content">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0">Habilitaciones Comerciales - Mapa</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item active"><a href="/">Home</a></li>
                <li class="breadcrumb-item">Mapa</li>
            </ol>
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

  // ======= TU LÓGICA EXISTENTE (marcadores, eventos Livewire, etc.) =======
  let ubicaciones = @json($ubicaciones);
  const markers = [];
  const markerIconUrl = "https://maps.gstatic.com/mapfiles/api-3/images/spotlight-poi2_hdpi.png";

  function clearMarkers() {
    while (markers.length) {
      const m = markers.pop();
      try { m.remove(); } catch {}
    }
  }

  async function placeMarker(record) {
  let lat = parseFloat(record.lat ?? record.latitud);
  let lng = parseFloat(record.lng ?? record.longitud);

  if (!(Number.isFinite(lat) && Number.isFinite(lng))) {
    try {
      const geocodeURL = `https://maps.googleapis.com/maps/api/geocode/json?address=${encodeURIComponent(record.domicilio_comercio || '')}&key=${googleApiKey}`;
      const res = await fetch(geocodeURL);
      const data = await res.json();
      const loc = data?.results?.[0]?.geometry?.location;
      if (loc) { lat = loc.lat; lng = loc.lng; }
    } catch(e) {}
  }

  if (!(Number.isFinite(lat) && Number.isFinite(lng))) return;

  const el = document.createElement('div');
  el.style.backgroundImage = `url('${markerIconUrl}')`;
  el.style.width = '30px';
  el.style.height = '30px';
  el.style.backgroundSize = 'contain';
  el.style.backgroundRepeat = 'no-repeat';

  const marker = new mapboxgl.Marker(el)
    .setLngLat([lng, lat])
    .setPopup(new mapboxgl.Popup({ offset: 25 }).setHTML(`
      <h3 class="mb-1">${record.nombre_comercial ?? record.razon_social ?? ''}</h3>
      <div><strong>Dirección:</strong> ${record.domicilio_comercio ?? ''}</div>
      <div><strong>Rubro:</strong> ${record.rubro?.subrubro ?? ''}</div>
      <div><strong>Barrio:</strong> ${record.barrio ?? '-'}</div>
      <div><strong>Estado:</strong> ${record.estado ?? '-'}</div>
    `))
    .addTo(map);

  markers.push(marker);
  currentMarkerLngLats.push([lng, lat]);
}

async function updateMarkers() {
  clearMarkers();
  currentMarkerLngLats = [];

  const checked = Array.from(document.querySelectorAll('input.rubro-checkbox:checked'))
    .map(cb => (cb.value || '').toLowerCase().trim());
  const filterFn = checked.length
    ? (rec) => checked.includes((rec.rubro?.subrubro || '').toLowerCase().trim())
    : () => true;

  for (const record of (ubicaciones || []).filter(filterFn)) {
    await placeMarker(record);
  }

  // aplicar filtros visuales a polígonos
  applyPolygonFilters();

  // === FIT A LO QUE SE VE ===
  // 1) si hay pines, ajustamos a pines
  let bounds = null;
  for (const [lng, lat] of currentMarkerLngLats) {
    bounds = extendBounds(bounds, lng, lat);
  }

  // 2) si NO hay pines, pero hay barrio/CPU seleccionado, ajustamos al polígono
  if (!bounds) {
    const barrio = document.querySelector('[wire\\:model\\.live="selectedBarrio"]')?.value || '';
    const nomen  = document.querySelector('[wire\\:model\\.live="selectedNomen"]')?.value || '';

    if (barrio && GEO_BARRIOS) {
      bounds = boundsOfFeatureByProp(GEO_BARRIOS, 'BARRIO', barrio);
    }
    if (!bounds && nomen && GEO_CATASTRO) {
      bounds = boundsOfFeatureByProp(GEO_CATASTRO, 'RefName', nomen);
    }
  }

  // 3) fallback: centro por defecto
  fitToBounds(bounds);
}


  // Livewire -> actualizar ubicaciones y refrescar mapa
  window.addEventListener('ubicacionesUpdated', (ev) => {
    ubicaciones = ev.detail?.ubicaciones ?? [];
    updateMarkers();
  });

  // Si usás selects de barrio/CPU con wire:model.live, reaccioná a cambios del DOM
  // (esto cubre cuando el usuario cambia los selects y Livewire re-renderiza)
  const observer = new MutationObserver(() => {
    // Reaplicar filtros de polígonos si cambió algo en la UI
    applyPolygonFilters();
  });
  observer.observe(document.body, { childList: true, subtree: true });

  // Primera pinta
  updateMarkers();


  // === Toggle de filtros con persistencia ===
  (function(){
    const KEY = 'map.filters.collapsed';
    const card  = document.getElementById('filtros-card');
    const body  = document.getElementById('filtros-body');
    const btn   = document.getElementById('btnToggleFilters');
    const ico   = document.getElementById('icoToggleFilters');

    if (!card || !body || !btn || !ico) return;

    function setCollapsed(flag){
      body.style.display = flag ? 'none' : '';
      ico.classList.toggle('fa-chevron-up', !flag);
      ico.classList.toggle('fa-chevron-down', flag);
      try { localStorage.setItem(KEY, flag ? '1' : '0'); } catch {}
      // Ajustar el mapa por si cambió el alto disponible
      setTimeout(() => { try { map.resize(); } catch {} }, 150);
    }

    let collapsed = false;
    try { collapsed = localStorage.getItem(KEY) === '1'; } catch {}
    setCollapsed(collapsed);

    btn.addEventListener('click', () => {
      collapsed = !collapsed;
      setCollapsed(collapsed);
    });

    // Si Livewire re-renderiza el bloque, re-aplicamos estado
    document.addEventListener('livewire:init', () => {
      Livewire.hook('message.processed', () => setCollapsed(
        (localStorage.getItem(KEY) === '1')
      ));
    });
  })();
</script>
<style>
  @media (max-width: 576px) {
    #btnToggleFiltersFloating{
      position: fixed;
      right: 12px;
      bottom: 12px;
      z-index: 1100;
      border-radius: 999px;
      box-shadow: 0 4px 12px rgba(0,0,0,.2);
    }
  }
</style>

<button id="btnToggleFiltersFloating" type="button"
        class="btn btn-primary d-sm-none">
  <i class="fas fa-sliders-h"></i>
</button>

<script>
  // Click del botón flotante = mismo comportamiento del botón del card
  (function(){
    const flo = document.getElementById('btnToggleFiltersFloating');
    const btn = document.getElementById('btnToggleFilters');
    if (!flo || !btn) return;
    flo.addEventListener('click', () => btn.click());
  })();
</script>



