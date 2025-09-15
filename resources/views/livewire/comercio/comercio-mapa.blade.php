<div id="comercio-mapa-root"><!-- ÚNICO ROOT -->

  <section class="content">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6"><h1 class="m-0">Mapa de comercios</h1></div>
          {{-- <div class="col-sm-6 text-right">
            <button id="btnAddMode" type="button" class="btn btn-sm btn-primary">
              <i class="fas fa-map-pin mr-1"></i> Agregar comercio
            </button>
          </div>--}}
        </div>

        {{-- Filtros --}}
        <div class="card mb-3" id="filtros-card">
          <div class="card-header d-flex align-items-center justify-content-between py-2">
            <strong class="mb-0">Filtros</strong>
            <button id="btnToggleFilters" type="button" class="btn btn-sm btn-outline-secondary">
              <i id="icoToggleFilters" class="fas fa-chevron-up"></i>
            </button>
          </div>

          <div class="card-body py-2" id="filtros-body">
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

            {{-- Rubro (combo buscable) + Nomenclatura --}}
            <div class="mb-3">
              <div class="form-row">
                <div class="form-group col-md-4 mb-2" wire:ignore>
                  <label class="mb-1">Rubro</label>
                  <select id="select-map-rubro" class="form-control form-control-sm border rounded p-2">
                    <option value="">-- Todos --</option>
                    @foreach($rubroOpts as $op)
                      <option value="{{ $op['id'] }}">{{ $op['subrubro'] }}</option>
                    @endforeach
                  </select>
                </div>

                <div class="form-group col-md-4 mb-2">
                  <label class="mb-1">Nomenclatura catastral</label>
                  <input class="form-control form-control-sm"
                         list="nomen-list"
                         placeholder="Escribí la nomenclatura…"
                         wire:model.live.debounce.300ms="selectedNomen" />
                  <datalist id="nomen-list">
                    @foreach($nomenOpts as $n)
                      <option value="{{ $n }}"></option>
                    @endforeach
                  </datalist>
                </div>

                <div class="form-group col-md-4 mb-2">
                  <label class="mb-1 d-block">Capas</label>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="toggleBarrios">
                    <label class="form-check-label" for="toggleBarrios">Barrios</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="toggleCatastro" checked>
                    <label class="form-check-label" for="toggleCatastro">Catastro</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="toggleCpu">
                    <label class="form-check-label" for="toggleCpu">CPU</label>
                  </div>
                </div>
              </div>
            </div>
          </div> {{-- /card-body --}}
        </div> {{-- /card filtros --}}

        <div class="card">
          <div class="card-body">
            <div id="map" wire:ignore style="height:520px;width:100%;min-width:200px;"></div>
          </div>
        </div>

      </div>
    </div>
  </section>

</div>

@push('styles')
  <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
  <style>
    #filtros-body { transition: height .18s ease, opacity .18s ease; }
    .mapboxgl-popup-content { padding:0!important;border-radius:12px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,.18);min-width:260px;}
    .popup-card { font-family: system-ui,-apple-system,Segoe UI,Roboto,Arial; }
    .popup-title { display:flex;font-size:18px;align-items:center;gap:.5rem;background:#0d6efd;color:#fff;padding:.6rem .8rem;font-weight:600;}
    .popup-row { display:grid;grid-template-columns:20px 1fr;gap:.6rem;padding:.55rem .8rem;border-top:1px solid #f0f1f3;font-size:.95rem;align-items:start;}
    .popup-row i { opacity:.7;margin-top:.15rem;}
  </style>
@endpush

@push('scripts')
  <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
  <script>
    // Redirect helper (el componente emite 'redirigir-a')
    document.addEventListener('livewire:init', () => {
      Livewire.on('redirigir-a', ({url}) => { window.location.href = url; });
    });
  </script>
  <script>
    const googleApiKey = "{{ config('services.google.maps_key') }}";

    mapboxgl.accessToken = 'pk.eyJ1IjoiYm9sc29uc2lzdGVtYXMiLCJhIjoiY2tpb3AzamM3MWYybzJ6dTYxZTR1cWJudCJ9.17kL4-zY3HQ16MGRHyuEkQ';
    const map = new mapboxgl.Map({
      container:'map',
      style:'mapbox://styles/mapbox/streets-v12',
      center:[-71.53,-41.9645],
      zoom:14
    });

    // Colapsable filtros
    (function () {
      const KEY='map.filters.collapsed';
      const body=document.getElementById('filtros-body');
      const btn=document.getElementById('btnToggleFilters');
      const ico=document.getElementById('icoToggleFilters');
      if (!body||!btn||!ico) return;
      let c=true; try{ if(localStorage.getItem(KEY)==='0') c=false; }catch{}
      const setC=(v)=>{ body.style.display=v?'none':''; ico.classList.toggle('fa-chevron-up',!v); ico.classList.toggle('fa-chevron-down',v); try{localStorage.setItem(KEY,v?'1':'0')}catch{}; setTimeout(()=>{try{map.resize()}catch{}},120); };
      setC(c); btn.onclick=()=>{ c=!c; setC(c); };
    })();

    // TomSelect en rubro (escribible)
    function initRubroOnce(){
      const el=document.getElementById('select-map-rubro'); if(!el||el.tomselect) return;
      new TomSelect(el,{allowEmptyOption:true,maxOptions:8000,plugins:['dropdown_input']});
      el.addEventListener('change',(e)=>{ const v=e.target.value||null; @this.set('selectedRubroId', v?parseInt(v):null); });
    }
    document.addEventListener('livewire:init',()=>{ Livewire.hook('message.processed',()=>initRubroOnce()); initRubroOnce(); });

    // ====== GeoJSON locales para bounds/búsquedas
    let GEO_BARRIOS=null, GEO_CATASTRO=null, GEO_CPU=null, NOM_KEY=null;
    const fetchJson = (u)=>fetch(u).then(r=>r.json());
    const detectNomKey=(gj)=>{ const p=gj?.features?.[0]?.properties||{}; const ks=Object.keys(p);
      const cands=['RefName','NOMEN','NOMENC','NOMENCLATURA','refname','nomenclatura']; for(const k of cands) if(ks.includes(k)) return k;
      const f=ks.find(k=>k.toLowerCase().includes('nomen')); return f||'RefName'; };

    // ====== Helpers de bounds / geometrías
    const extendB = (b,lng,lat)=> b ? b.extend([lng,lat]) : new mapboxgl.LngLatBounds([lng,lat],[lng,lat]);
    function boundsOfFeature(feat){
      const g=feat?.geometry,p=g?.type,c=g?.coordinates; if(!p||!c) return null; let b=null;
      const each=([lng,lat])=>{ b = extendB(b,lng,lat); };
      if(p==='Polygon'){ (c[0]||[]).forEach(each); }
      else if(p==='MultiPolygon'){ c.forEach(poly => (poly[0]||[]).forEach(each)); }
      return b;
    }
    function fitToFC(fc){ const feats=fc?.features||[]; let b=null; feats.forEach(f=>{ const bb=boundsOfFeature(f); if(bb){ b = b? b.union(bb): bb; }});
      if(b) map.fitBounds(b, {padding:40,maxZoom:17,duration:600}); }
    function fitToPoints(features){
      if(!features.length) return;
      const b = features.reduce((acc,f)=>extendB(acc,f.geometry.coordinates[0],f.geometry.coordinates[1]), null);
      if(b) map.fitBounds(b, {padding:40,maxZoom:16,duration:600});
    }

    // ====== Cargar mapa
    map.on('load', async () => {
      // Ocultar POIs/lugares
      (map.getStyle()?.layers||[]).forEach(l=>{ const id=l.id||''; if(id.includes('poi')||id.includes('place')){ try{map.setLayoutProperty(id,'visibility','none')}catch{}}});

      GEO_BARRIOS   = await fetchJson('/geo/BARRIOS1.json');
      GEO_CATASTRO  = await fetchJson('/geo/CATASTRO_GEO.json');
      GEO_CPU       = await fetchJson('/geo/CPU_MEB.json');
      NOM_KEY       = detectNomKey(GEO_CATASTRO);

      // Fuentes
      map.addSource('barrios-src',{type:'geojson',data:'/geo/BARRIOS1.json'});
      map.addSource('catastro-src',{type:'geojson',data:'/geo/CATASTRO_GEO.json'});
      map.addSource('catastro-hl-src',{type:'geojson',data:{type:'FeatureCollection',features:[]}});
      map.addSource('cpu-src',{type:'geojson',data:'/geo/CPU_MEB.json'});

      // Barrios (fill/line/labels)
      map.addLayer({id:'barrios-fill',type:'fill',source:'barrios-src',paint:{'fill-color':'#0080ff','fill-opacity':0.12}});
      map.addLayer({id:'barrios-line',type:'line',source:'barrios-src',paint:{'line-color':'#0080ff','line-width':1.2}});
      map.addLayer({
        id:'barrios-labels', type:'symbol', source:'barrios-src',
        layout:{ 'text-field':['get','BARRIO'], 'text-size':['interpolate',['linear'],['zoom'],10,11,14,15], 'text-anchor':'center'},
        paint:{ 'text-color':'#004b9a','text-halo-color':'#ffffff','text-halo-width':1.1}
      });

      // Catastro (fill/line/highlight/labels)
      map.addLayer({id:'catastro-fill',type:'fill',source:'catastro-src',paint:{'fill-color':'#ff8800','fill-opacity':0.06}});
      map.addLayer({id:'catastro-line',type:'line',source:'catastro-src',paint:{'line-color':'#ff8800','line-width':1}});
      map.addLayer({id:'catastro-hl-fill',type:'fill',source:'catastro-hl-src',paint:{'fill-color':'#ff0000','fill-opacity':0.20}});
      map.addLayer({id:'catastro-hl-line',type:'line',source:'catastro-hl-src',paint:{'line-color':'#ff0000','line-width':2}});
      map.addLayer({
        id:'catastro-labels', type:'symbol', source:'catastro-src', minzoom:14,
        layout:{ 'text-field':['get',NOM_KEY], 'text-size':['interpolate',['linear'],['zoom'],14,11,18,16] },
        paint:{ 'text-color':'#8a4d00','text-halo-color':'#ffffff','text-halo-width':1}
      });

      // CPU (fill/line/labels)  -> /geo/CPU_MEB.json
      map.addLayer({id:'cpu-fill',type:'fill',source:'cpu-src',paint:{'fill-color':'#39c16c','fill-opacity':0.10}});
      map.addLayer({id:'cpu-line',type:'line',source:'cpu-src',paint:{'line-color':'#39c16c','line-width':1.2}});
      map.addLayer({
        id:'cpu-labels', type:'symbol', source:'cpu-src',
        layout:{ 'text-field':['coalesce',['get','CPU_NOMBRE'],['get','CPU_COD']], 'text-size':['interpolate',['linear'],['zoom'],10,11,14,15]},
        paint:{ 'text-color':'#1d5134','text-halo-color':'#ffffff','text-halo-width':1.1}
      });

      // Puntos de comercios
      map.addSource('comercios-src',{type:'geojson',data:toGeo(@json($ubicaciones))});
      map.addLayer({id:'comercios-points',type:'circle',source:'comercios-src',
        paint:{'circle-color':'#1e90ff','circle-radius':7,'circle-stroke-color':'#fff','circle-stroke-width':2}});

      const popup = new mapboxgl.Popup({ closeButton:true, offset:16 });
      map.on('click','comercios-points',(e)=>{ if(addMode) return; const f=e.features[0]; popup.setLngLat(f.geometry.coordinates).setHTML(popHTML(f.properties)).addTo(map); });
      map.on('mouseenter','comercios-points',()=>map.getCanvas().style.cursor='pointer');
      map.on('mouseleave','comercios-points',()=>map.getCanvas().style.cursor='');

      // Visibilidad inicial (solo Catastro)
      applyToggles();
      // Ajustar a puntos iniciales
      try{ const feats = map.getSource('comercios-src')._data.features||[]; if(feats.length) fitToPoints(feats); }catch{}
    });

    // ======= Visibilidad por toggles
    function setVisGroup(prefix, visible){
      const v = visible ? 'visible' : 'none';
      ['fill','line','labels'].forEach(sfx=>{
        const id=`${prefix}-${sfx}`;
        if(map.getLayer(id)) map.setLayoutProperty(id,'visibility',v);
      });
      // highlight de catastro depende de catastro
      if(prefix==='catastro'){ ['catastro-hl-fill','catastro-hl-line'].forEach(id=>{ if(map.getLayer(id)) map.setLayoutProperty(id,'visibility', v); }); }
    }
    function applyToggles(){
      setVisGroup('barrios', !!document.getElementById('toggleBarrios')?.checked);
      setVisGroup('catastro', !!document.getElementById('toggleCatastro')?.checked);
      setVisGroup('cpu',      !!document.getElementById('toggleCpu')?.checked);
    }
    document.getElementById('toggleBarrios')?.addEventListener('change',applyToggles);
    document.getElementById('toggleCatastro')?.addEventListener('change',applyToggles);
    document.getElementById('toggleCpu')?.addEventListener('change',applyToggles);

    // ====== Puntos → GeoJSON y popups
    function toGeo(list){
      const feats=[]; for(const r of (list||[])){
        const lat=parseFloat(r.lat),lng=parseFloat(r.lng);
        if(!Number.isFinite(lat)||!Number.isFinite(lng)) continue;
        feats.push({type:'Feature',geometry:{type:'Point',coordinates:[lng,lat]},
          properties:{id:r.id,nombre:r.nombre_comercial??r.razon_social??'',direccion:r.domicilio_comercio??'',barrio:r.barrio??'-',estado:r.estado??'-',rubro:r?.rubro?.subrubro??''}});
      }
      return {type:'FeatureCollection',features:feats};
    }
    function popHTML(p){ return `<div class="popup-card">
      <div class="popup-title"><i class="fas fa-store"></i><span>${esc(p.nombre||'')}</span></div>
      <div class="popup-row"><i class="fas fa-map-marker-alt"></i><div>${esc(p.direccion||'')}</div></div>
      <div class="popup-row"><i class="fas fa-tags"></i><div>${esc(p.rubro||'-')}</div></div>
      <div class="popup-row"><i class="fas fa-city"></i><div>${esc(p.barrio||'-')}</div></div>
      <div class="popup-row"><i class="fas fa-clipboard-check"></i><div>${esc(p.estado||'-')}</div></div></div>`; }
    function esc(s){return String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'","&#39;");}

    // ====== Actualizaciones desde Livewire
    window.addEventListener('ubicacionesUpdated',(ev)=>{
      const list=ev.detail?.ubicaciones??[];
      const src=map.getSource('comercios-src');
      if(src) src.setData(toGeo(list));

      // Zoom a los puntos resultantes
      try{ const feats = (src._data?.features)||[]; if(feats.length) fitToPoints(feats); }catch{}

      // Resaltar nomen seleccionada (si llega en el evento)
      const nom = ev.detail?.selectedNomen ?? document.querySelector('[list="nomen-list"]')?.value ?? '';
      highlightNomen(nom);
    });

    // ====== Resaltado + zoom por Nomenclatura / Barrio
    function highlightNomen(nom){
      const hl=map.getSource('catastro-hl-src');
      if(!hl || !GEO_CATASTRO || !NOM_KEY) return;
      if(nom){
        const feats=(GEO_CATASTRO.features||[]).filter(f=> (f.properties?.[NOM_KEY]??'')===nom);
        const fc = {type:'FeatureCollection',features:feats};
        hl.setData(fc);
        if(feats.length) fitToFC(fc);
      }else{
        hl.setData({type:'FeatureCollection',features:[]});
      }
    }
    // Cuando cambia el input de nomen (por si Livewire no emite detalle)
    document.querySelector('[list="nomen-list"]')?.addEventListener('change', (e)=> highlightNomen(e.target.value||''));

    // ====== Agregar comercio desde el mapa (dirección + barrio + nomen)
    let addMode=false, addMarker=null;
    document.getElementById('btnAddMode')?.addEventListener('click',()=>{
      addMode=!addMode; const btn=document.getElementById('btnAddMode');
      btn.classList.toggle('btn-success',addMode); btn.classList.toggle('btn-primary',!addMode);
      btn.innerHTML=addMode?'<i class="fas fa-location-dot mr-1"></i> Click en el mapa':'<i class="fas fa-map-pin mr-1"></i> Agregar comercio';
    });

    // point-in-polygon rápido (ray casting); ring = [[lng,lat],...]
    function pointInRing(lat,lng,ring){
      let inside=false; for(let i=0,j=ring.length-1;i<ring.length;j=i++){
        const xi=ring[i][0], yi=ring[i][1], xj=ring[j][0], yj=ring[j][1];
        const intersect=((yi>lat)!==(yj>lat)) && (lng < (xj-xi)*(lat-yi)/((yj-yi)||1e-12) + xi);
        if(intersect) inside=!inside;
      }
      return inside;
    }
    function featureContainsLatLng(feat,lat,lng){
      const g=feat?.geometry; if(!g) return false;
      if(g.type==='Polygon'){ return pointInRing(lat,lng, g.coordinates[0]||[]); }
      if(g.type==='MultiPolygon'){ return (g.coordinates||[]).some(poly => pointInRing(lat,lng, poly[0]||[])); }
      return false;
    }
    function findByPropAtLatLng(fc, prop, lat,lng){
      const feats = fc?.features||[];
      return feats.find(f => featureContainsLatLng(f,lat,lng) && (f.properties?.[prop]??'')!=='') || null;
    }

    async function reverseGeocode(lat,lng){
      if(!googleApiKey) return null;
      try{
        const url=`https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=${googleApiKey}&language=es-AR&region=ar`;
        const res=await fetch(url); const j=await res.json();
        return j?.results?.[0]?.formatted_address || null;
      }catch{ return null; }
    }

    map.on('click', async (e)=>{
      if(!addMode) return;
      const {lng,lat}=e.lngLat;
      if(addMarker) addMarker.remove();
      addMarker=new mapboxgl.Marker({color:'#d81b60'}).setLngLat([lng,lat]).addTo(map);

      // Barrio (BARRIOS1.json)
      let barrio='—';
      try{
        const f = (GEO_BARRIOS?.features||[]).find(fe => featureContainsLatLng(fe,lat,lng));
        if(f) barrio = f.properties?.BARRIO || '—';
      }catch{}

      // Nomenclatura (CATASTRO_GEO.json)
      let nomen='—';
      try{
        const f = findByPropAtLatLng(GEO_CATASTRO, NOM_KEY, lat, lng);
        if(f) nomen = f.properties?.[NOM_KEY] || '—';
      }catch{}

      // Dirección (reverse geocode)
      const dir = await reverseGeocode(lat,lng) || '—';

      const html = `
        <div style="min-width:260px">
          <div class="mb-2"><strong>Nueva ubicación</strong></div>
          <div class="text-muted small mb-1"><i class="fas fa-map-marker-alt mr-1"></i><b>Dirección:</b> ${esc(dir)}</div>
          <div class="text-muted small mb-1"><i class="fas fa-city mr-1"></i><b>Barrio:</b> ${esc(barrio)}</div>
          <div class="text-muted small mb-2"><i class="fas fa-vector-square mr-1"></i><b>Nomenclatura:</b> ${esc(nomen)}</div>
          <button id="btnConfirmCreateHere" class="btn btn-sm btn-primary w-100">
            <i class="fas fa-plus mr-1"></i> Abrir formulario
          </button>
        </div>`;
      new mapboxgl.Popup({offset:12}).setLngLat([lng,lat]).setHTML(html).addTo(map);

      setTimeout(()=>{
        const b=document.getElementById('btnConfirmCreateHere');
        if(b){ b.onclick=()=>{ @this.call('crearDesdeMapaConDatos', dir, barrio, nomen); }; }
      },0);
    });
  </script>
@endpush
