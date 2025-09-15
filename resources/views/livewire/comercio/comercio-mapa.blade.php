<div id="comercio-mapa-root"><!-- ÚNICO ROOT -->

  <section class="content">
    <div class="content-header">
      <div class="container-fluid">
        {{--<div class="row mb-2">
          <div class="col-sm-6"><h1 class="m-0">Mapa de comercios</h1></div>
          <div class="col-sm-6 text-right">
            <button id="btnAddMode" type="button" class="btn btn-sm btn-primary">
              <i class="fas fa-map-pin mr-1"></i> Agregar comercio
            </button>
          </div>
        </div>--}}

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

            {{-- Rubro (combo buscable como en el form) + Nomenclatura --}}
            <div class="mb-3">
              <div class="form-row ">
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
                         wire:model.live="selectedNomen" />
                  <datalist id="nomen-list">
                    @foreach($nomenOpts as $n)
                      <option value="{{ $n }}"></option>
                    @endforeach
                  </datalist>
                </div>

                <div class="form-group col-md-4 mb-2">
                  <label class="mb-1 d-block">Capas</label>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="toggleBarrios" checked>
                    <label class="form-check-label" for="toggleBarrios">Barrios</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="toggleCatastro" checked>
                    <label class="form-check-label" for="toggleCatastro">Catastro</label>
                  </div>
                </div>
              </div>
            </div>
          </div>{{--/card-body--}}
        </div>{{--/card filtros--}}

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
  <script>document.addEventListener('livewire:init', () => {Livewire.on('redirigir-a', ({url}) => { window.location.href = url; });});</script>
  <script>
    // --- Mapa básico + capas (resumido) ---
    mapboxgl.accessToken = 'pk.eyJ1IjoiYm9sc29uc2lzdGVtYXMiLCJhIjoiY2tpb3AzamM3MWYybzJ6dTYxZTR1cWJudCJ9.17kL4-zY3HQ16MGRHyuEkQ';
    const map = new mapboxgl.Map({ container:'map', style:'mapbox://styles/mapbox/streets-v12', center:[-71.53,-41.9645], zoom:14 });

    // Colapsable filtros
    (function () {
      const KEY='map.filters.collapsed'; const body=document.getElementById('filtros-body');
      const btn=document.getElementById('btnToggleFilters'); const ico=document.getElementById('icoToggleFilters');
      if (!body||!btn||!ico) return; let c=true; try{ if(localStorage.getItem(KEY)==='0') c=false; }catch{}
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

    // Capas geojson
    let GEO_CATASTRO=null, NOM_KEY=null;
    const detectKey=(gj)=>{ const p=gj?.features?.[0]?.properties||{}; const ks=Object.keys(p);
      const cands=['NOMEN','NOMENC','NOMENCLATURA','RefName','refname','nomenclatura']; for(const k of cands) if(ks.includes(k)) return k;
      const f=ks.find(k=>k.toLowerCase().includes('nomen')); return f||'NOMEN'; };

    map.on('load', async () => {
      (map.getStyle()?.layers||[]).forEach(l=>{ if((l.id||'').includes('poi')||(l.id||'').includes('place')){ try{map.setLayoutProperty(l.id,'visibility','none')}catch{}}});
      map.addSource('barrios-src',{type:'geojson',data:'/geo/BARRIOS1.json'});
      map.addLayer({id:'barrios-fill',type:'fill',source:'barrios-src',paint:{'fill-color':'#0080ff','fill-opacity':0.15}});
      map.addLayer({id:'barrios-line',type:'line',source:'barrios-src',paint:{'line-color':'#0080ff','line-width':1.2}});

      GEO_CATASTRO = await fetch('/geo/CATASTRO_GEO.json').then(r=>r.json());
      NOM_KEY = detectKey(GEO_CATASTRO);
      map.addSource('catastro-src',{type:'geojson',data:'/geo/CATASTRO_GEO.json'});
      map.addLayer({id:'catastro-fill',type:'fill',source:'catastro-src',paint:{'fill-color':'#ff8800','fill-opacity':0.08}});
      map.addLayer({id:'catastro-line',type:'line',source:'catastro-src',paint:{'line-color':'#ff8800','line-width':1}});

      map.addSource('catastro-hl-src',{type:'geojson',data:{type:'FeatureCollection',features:[]}});
      map.addLayer({id:'catastro-hl-fill',type:'fill',source:'catastro-hl-src',paint:{'fill-color':'#ff0000','fill-opacity':0.20}});
      map.addLayer({id:'catastro-hl-line',type:'line',source:'catastro-hl-src',paint:{'line-color':'#ff0000','line-width':2}});
    });

    // Toggles de capas
    function setVis(prefix,vis){ const v=vis?'visible':'none'; ['fill','line'].forEach(s=>{ const id=`${prefix}-${s}`; if(map.getLayer(id)) map.setLayoutProperty(id,'visibility',v); }); }
    function applyToggles(){ setVis('barrios',document.getElementById('toggleBarrios')?.checked!==false); const c=document.getElementById('toggleCatastro')?.checked!==false; setVis('catastro',c); setVis('catastro-hl',c); }
    document.getElementById('toggleBarrios')?.addEventListener('change',applyToggles);
    document.getElementById('toggleCatastro')?.addEventListener('change',applyToggles);

    // Puntos (desde PHP)
    function toGeo(list){ const feats=[]; for(const r of (list||[])){ const lat=parseFloat(r.lat),lng=parseFloat(r.lng); if(!Number.isFinite(lat)||!Number.isFinite(lng)) continue;
        feats.push({type:'Feature',geometry:{type:'Point',coordinates:[lng,lat]},properties:{id:r.id,nombre:r.nombre_comercial??r.razon_social??'',direccion:r.domicilio_comercio??'',barrio:r.barrio??'-',estado:r.estado??'-',rubro:r?.rubro?.subrubro??''}});}
      return {type:'FeatureCollection',features:feats}; }
    const popup = new mapboxgl.Popup({ closeButton:true, offset:16 });
    let srcReady=false;
    map.on('load',()=>{
      map.addSource('comercios-src',{type:'geojson',data:toGeo(@json($ubicaciones))});
      map.addLayer({id:'comercios-points',type:'circle',source:'comercios-src',paint:{'circle-color':'#1e90ff','circle-radius':7,'circle-stroke-color':'#fff','circle-stroke-width':2}});
      map.on('click','comercios-points',(e)=>{ if(addMode) return; const f=e.features[0]; popup.setLngLat(f.geometry.coordinates).setHTML(popHTML(f.properties)).addTo(map); });
      map.on('mouseenter','comercios-points',()=>map.getCanvas().style.cursor='pointer');
      map.on('mouseleave','comercios-points',()=>map.getCanvas().style.cursor='');
      srcReady=true; applyToggles();
    });
    function popHTML(p){ return `<div class="popup-card">
      <div class="popup-title"><i class="fas fa-store"></i><span>${esc(p.nombre||'')}</span></div>
      <div class="popup-row"><i class="fas fa-map-marker-alt"></i><div>${esc(p.direccion||'')}</div></div>
      <div class="popup-row"><i class="fas fa-tags"></i><div>${esc(p.rubro||'-')}</div></div>
      <div class="popup-row"><i class="fas fa-city"></i><div>${esc(p.barrio||'-')}</div></div>
      <div class="popup-row"><i class="fas fa-clipboard-check"></i><div>${esc(p.estado||'-')}</div></div></div>`; }
    function esc(s){return String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'","&#39;");}

    // Livewire → refrescar puntos y resaltar por nomenclatura
    window.addEventListener('ubicacionesUpdated',(ev)=>{
      const list=ev.detail?.ubicaciones??[]; const nom=ev.detail?.selectedNomen??'';
      if(srcReady){ const src=map.getSource('comercios-src'); if(src) src.setData(toGeo(list)); }
      const hl=map.getSource('catastro-hl-src');
      if(hl && NOM_KEY && nom && GEO_CATASTRO){
        const feats=(GEO_CATASTRO.features||[]).filter(f=> (f.properties?.[NOM_KEY]??'')===nom);
        hl.setData({type:'FeatureCollection',features:feats});
      } else if(hl){ hl.setData({type:'FeatureCollection',features:[]}); }
    });

    // Modo “agregar desde mapa”
    let addMode=false, addMarker=null;
    document.getElementById('btnAddMode')?.addEventListener('click',()=>{
      addMode=!addMode; const btn=document.getElementById('btnAddMode');
      btn.classList.toggle('btn-success',addMode); btn.classList.toggle('btn-primary',!addMode);
      btn.innerHTML=addMode?'<i class="fas fa-location-dot mr-1"></i> Click en el mapa para crear':'<i class="fas fa-map-pin mr-1"></i> Agregar comercio';
    });
    map.on('click',(e)=>{
      if(!addMode) return;
      const {lng,lat}=e.lngLat;
      if(addMarker) addMarker.remove();
      addMarker=new mapboxgl.Marker({color:'#d81b60'}).setLngLat([lng,lat]).addTo(map);
      new mapboxgl.Popup({offset:12}).setLngLat([lng,lat]).setHTML(`
        <div style="min-width:240px">
          <div class="mb-2"><strong>Crear comercio aquí</strong></div>
          <div class="text-muted mb-2">Lat: ${lat.toFixed(6)} · Lng: ${lng.toFixed(6)}</div>
          <button id="btnConfirmCreateHere" class="btn btn-sm btn-primary w-100">
            <i class="fas fa-plus mr-1"></i> Abrir formulario
          </button>
        </div>`).addTo(map);
      setTimeout(()=>{ const b=document.getElementById('btnConfirmCreateHere'); if(b){ b.onclick=()=>{ @this.call('crearDesdeMapa', lat, lng); }; }},0);
    });
  </script>
@endpush
