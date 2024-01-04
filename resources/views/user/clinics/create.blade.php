@extends('user.master')
@section('content')
  <!-- Basic with Icons -->
  <div class="col-xxl">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Klinika qo'shish</h5>
        <small class="text-muted float-end">Malumotlarni to'ldirishingiz mumkin.</small>
      </div>
      <div class="card-body demo-vertical-spacing demo-only-element">
        <form method="POST" action="{{ route('clinic.store') }}" enctype="multipart/form-data">
          @csrf
          <div class="row mb-3">
            <label class="col-sm-2 col-form-label" for="basic-icon-default-fullname">Klinika nomi</label>
            <div class="col-sm-10">
              <div class="input-group input-group-merge">
                <span id="basic-icon-default-fullname2" class="input-group-text"
                  ><i class="bx bx-clinic"></i
                ></span>
                <input
                  name="name"
                  type="text"
                  class="form-control"
                  id="basic-icon-default-fullname"
                  placeholder="Yulduz ko'z klinikasi"
                  aria-label="Yulduz ko'z klinikasi"
                  aria-describedby="basic-icon-default-fullname2"
                  value="{{old('name')}}"
                />
              </div>
              @error('name')
                <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
              @enderror
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-2 col-form-label" for="basic-icon-default-fullname">Klinika manzili</label>
            <div class="col-sm-10">
              <div class="input-group input-group-merge">
                <span id="basic-icon-default-fullname2" class="input-group-text"
                  ><i class="bx bx-map"></i
                ></span>
                <input
                  name="address"
                  type="text"
                  class="form-control"
                  id="basic-icon-default-fullname"
                  placeholder="Gastronom orqa tomoni"
                  aria-label="Gastronom orqa tomoni"
                  aria-describedby="basic-icon-default-fullname2"
                  value="{{old('address')}}"
                />
              </div>
              @error('address')
                <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
              @enderror
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-2 col-form-label" for="basic-icon-default-fullname">Klinika telefon raqami</label>
            <div class="col-sm-10">
              <div class="input-group input-group-merge">
                <span id="basic-icon-default-fullname2" class="input-group-text"
                  ><i class="bx bx-phone"></i
                ></span>
                <input
                  name="phone"
                  type="text"
                  class="form-control"
                  id="basic-icon-default-fullname"
                  placeholder="+998 91 277 96 93"
                  aria-label="+998 91 277 96 93"
                  aria-describedby="basic-icon-default-fullname2"
                  value="{{old('phone')}}"
                />
              </div>
              @error('phone')
                <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
              @enderror
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-2 col-form-label" for="basic-icon-default-fullname">URL</label>
            <div class="col-sm-10">
              <div class="input-group input-group-merge">
                <span id="basic-icon-default-fullname2" class="input-group-text"
                  ><i class="bx bx-link"></i
                ></span>
                <input
                  name="url"
                  type="text"
                  class="form-control"
                  id="basic-icon-default-fullname"
                  placeholder="http://amusoft.uz"
                  aria-label="http://amusoft.uz"
                  aria-describedby="basic-icon-default-fullname2"
                  value="{{old('url')}}"
                />
              </div>
              @error('url')
                <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
              @enderror
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-2 col-form-label" for="basic-icon-default-fullname">Manzilni xaritadan tanlang</label>
            <div class="col-sm-10">
              <div class="input-group input-group-merge">
                <a 
                class="btn btn-info" 
                onclick="initModal()"
                data-bs-toggle="modal"
                data-bs-target="#modalMap"
                >
                    <i class="bx bx-map"></i>
                    Manzilni tanlash
                </a>
              </div>
              @error('url')
                <div class="alert alert-danger" role="alert">Ushbu maydon bo'sh bo'lishi mumkin emas!</div>
              @enderror
            </div>
          </div>
          <div class="row justify-content-end mb-5">
            <div class="col-sm-10">
              <button type="submit" class="btn btn-primary">Qo'shish</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
    <!-- Modal -->
  <div class="modal fade" id="modalMap" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalCenterTitle">Manzilni tanlang</h5>
        </div>
        <div class="modal-body">
            <div id="map"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                Yopish
            </button>
            <button type="button" class="btn btn-primary">Saqlash</button>
        </div>
      </div>
    </div>
  </div>
  <script>
  (g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
    key: "AIzaSyBp7rFounUr9QYz248DkbcakZMBzJIBeSw",
    v: "weekly",
    // Use the 'v' parameter to indicate the version to use (weekly, beta, alpha, etc.).
    // Add other bootstrap parameters as needed, using camel case.
  });
</script>
<script async
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBp7rFounUr9QYz248DkbcakZMBzJIBeSw">
</script>

  <script>
let map;
        let marker;

        function initMap() {
            const initialPosition = { lat: -34.397, lng: 150.644 };

            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 8,
                center: initialPosition
            });

            marker = new google.maps.Marker({
                position: initialPosition,
                map: map,
                draggable: true
            });

            map.addListener('click', function(e) {
                marker.setPosition(e.latLng);
            });
        }  </script>
@endsection
