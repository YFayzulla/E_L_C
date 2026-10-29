@extends('template.master')
@section('content')

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Talaba ma’lumotlari</h5>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                {{-- SMS: qabul qiluvchi (ota / ona / vasiy / talaba) tanlanadi --}}
                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#smsModal">
                    <i class="bx bx-message-dots me-1"></i>
                    <span class="d-none d-sm-inline-block">SMS yuborish</span>
                </button>

                @role('admin')
                {{-- Talaba nimani ko'rayotganini aynan o'z ko'zi bilan ko'rish --}}
                <form method="POST" action="{{ route('impersonate.start', $student->id) }}"
                      onsubmit="return confirm('{{ $student->name }} hisobiga kirasizmi? Barcha amallar shu foydalanuvchi nomidan bajariladi.');">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="bx bx-log-in me-1"></i>
                        <span class="d-none d-sm-inline-block">Profiliga kirish</span>
                    </button>
                </form>
                @endrole

            @role('admin')
            <div class="dt-action-buttons text-end">
                <div class="dt-buttons btn-group flex-wrap">
                    <div class="btn-group">
                        <a class="btn buttons-collection dropdown-toggle btn-label-primary me-2" tabindex="0"
                           aria-controls="DataTables_Table_0" type="button" id="dropdownMenuButton"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <span><i class="bx bx-export me-sm-1"></i> <span
                                        class="d-none d-sm-inline-block">Export</span></span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <li><a class="dropdown-item" href="{{ URL::to('/student/pdf',$student->id) }}"><i
                                            class="bx bxs-file-pdf me-1"></i> Pdf</a></li>
                            <li><a class="dropdown-item" href="{{ route('student.export', $student->id) }}"><i
                                            class="bx bxs-file-export me-1"></i> Excel</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            @endrole
            </div>
        </div>

        @include('partials.student-sms-modal', ['student' => $student])

        <div class="row mb-5">
            <div class="col-md">
                <div class="card mb-3">
                    <div class="row g-0">
                        <div class="col-md-8">
                            <div class="card-body">
                                <h4><b>Full Name: </b>{{$student->name}}</h4>
                                <h4><b>Tel: </b>{{$student->phone}}</h4>
                                @role ('admin')
                                <h4><b>Location:</b> {{$student->location}}</h4>
                                <h4><b>Last Test Result: </b>{{$student->mark}}</h4>
                                <h4><b>Birth date </b>{{$student->date_born }}</h4>
                                <h4><b>Current Groups: </b> {{ $student->groups->pluck('name')->implode(', ') }}</h4>
                                @endrole
                                <div class="mt-4">
                                    <h5 class="text-primary mb-3"><i class="bx bx-note me-2"></i>Comments & Description
                                    </h5>
                                    <div class="bg-light p-3 rounded border">
                                        @if($student->description)
                                            @php
                                                // Split description by newline to separate comments
                                                $comments = explode("\n", $student->description);
                                            @endphp
                                            <ul class="list-unstyled mb-0">
                                                @foreach($comments as $comment)
                                                    @if(trim($comment) !== '')
                                                        <li class="mb-2 pb-2 border-bottom border-light-subtle last:border-0">
                                                            <i class="bx bx-chevron-right text-muted me-1"></i> {{ trim($comment) }}
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="text-muted mb-0">No description or comments available.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @role('admin')
                        <div class="col-md-4 d-flex justify-content-center align-items-center p-3">
                            @if($student->photo)
                                <img class="img-fluid rounded shadow-sm"
                                     src="{{asset( 'storage/'.$student->photo) }}"
                                     alt="Student Photo"
                                     style="max-width: 180px; height: auto;">
                            @else
                                <p class="text-muted">No Photo</p>
                            @endif
                        </div>
                        @endrole
                    </div>
                </div>
            </div>
        </div>
    </div>

    @role('admin')
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="mb-0">Ota-ona va vasiylar</h5>
                <small class="text-muted">Talabaga biriktirilgan aloqador shaxslar</small>
            </div>
            <a href="{{ route('student.edit', $student->id) }}" class="btn btn-sm btn-outline-primary">
                <i class="bx bx-edit-alt me-1"></i>Tahrirlash
            </a>
        </div>

        @if(empty($guardians))
            <div class="empty-state">
                <i class="bx bx-user-x"></i>
                <h6>Ota-ona ma’lumoti kiritilmagan</h6>
                <p class="mb-0">Tahrirlash sahifasida ota, ona yoki vasiyni qo‘shing.</p>
            </div>
        @else
            <div class="card-body">
                <div class="row g-3">
                    @foreach($guardians as $relation => $guardian)
                        @php
                            $label = \App\Services\ParentAccountService::LABELS[$relation] ?? 'Vasiy';
                            $icon = $relation === 'ota' ? 'bx-male' : ($relation === 'ona' ? 'bx-female' : 'bx-user');
                        @endphp
                        <div class="col-md-6 col-xl-4">
                            <div class="border rounded p-3 h-100" style="border-color: var(--app-border) !important;">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <span class="badge bg-label-primary"><i class="bx {{ $icon }} me-1"></i>{{ $label }}</span>
                                    @if(($guardian['id'] ?? 0) > 0)
                                        <a href="{{ route('parents.edit', $guardian['id']) }}" class="btn btn-sm btn-outline-secondary" title="Ota-ona hisobini tahrirlash">
                                            <i class="bx bx-edit-alt"></i>
                                        </a>
                                    @endif
                                </div>
                                <div class="fw-semibold mb-2">{{ $guardian['name'] ?: 'Ism kiritilmagan' }}</div>
                                <div class="small text-muted mb-1">Telefon</div>
                                @if(filled($guardian['phone']))
                                    <a dir="ltr" href="tel:{{ preg_replace('/[^0-9+]/', '', $guardian['phone']) }}">+{{ $guardian['phone'] }}</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                                <div class="small text-muted mt-3 mb-1">Pochta manzili</div>
                                @if(filled($guardian['email']))
                                    <a href="mailto:{{ $guardian['email'] }}" class="text-break">{{ $guardian['email'] }}</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                                @if(($guardian['id'] ?? 0) === 0)
                                    <div class="small text-warning mt-3">Hisob hali yaratilmagan. Tahrirlab saqlang.</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
    @endrole

    {{-- O'zlashtirish: davomat / uy vazifa / dars faoliyati / test + umumiy reyting.
         Ko'rsatkichlar talaba o'sha paytda qaysi guruhda bo'lgan bo'lsa o'shanga
         qarab hisoblanadi, shuning uchun guruhi o'zgargan talabaning tarixi ham
         shu yerda to'liq ko'rinadi. --}}
    @isset($progress)
        @php
            $tone = fn(?int $v) => $v === null ? 'secondary'
                : ($v >= config('grading.bands.good', 80) ? 'success'
                : ($v >= config('grading.bands.ok', 60) ? 'warning' : 'danger'));
            $labels = [
                'attendance' => ['Davomat', 'bx-calendar-check'],
                'homework'   => ['Uy vazifa', 'bx-task'],
                'skills'     => ['Dars faoliyati', 'bx-book-open'],
                'tests'      => ['Test', 'bx-clipboard'],
            ];
        @endphp

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <span>O‘zlashtirish dinamikasi</span>
                        <div class="d-flex align-items-center gap-2">
                            @if($progress['current'] !== null)
                                <span class="badge bg-label-{{ $tone($progress['current']) }}">
                                    Joriy: {{ $progress['current'] }}
                                </span>
                                @if($progress['delta'] !== null && $progress['delta'] !== 0)
                                    <span class="delta {{ $progress['delta'] > 0 ? 'is-up' : 'is-down' }}">
                                        <i class="bx bx-{{ $progress['delta'] > 0 ? 'up' : 'down' }}-arrow-alt"></i>
                                        {{ $progress['delta'] > 0 ? '+' : '' }}{{ $progress['delta'] }}
                                    </span>
                                @endif
                            @endif
                            <a href="{{ route('progress.student', $student->id) }}"
                               class="btn btn-sm btn-outline-secondary">Batafsil</a>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            @foreach($labels as $key => [$label, $icon])
                                @php $value = $progress['components'][$key] ?? null; @endphp
                                <div class="col-6 col-lg-3">
                                    <div class="metric-box">
                                        <div class="metric-value text-{{ $tone($value) }}">
                                            {{ $value ?? '—' }}
                                        </div>
                                        <div class="metric-label">
                                            <i class="bx {{ $icon }} me-1"></i>{{ $label }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if($progress['basis'] === 'attendance_only')
                            <p class="text-muted mb-3" style="font-size: .8rem;">
                                <i class="bx bx-info-circle me-1"></i>Hozircha faqat davomat asosida hisoblangan.
                            </p>
                        @endif

                        @include('partials.progress-chart', [
                            'chartId' => 'studentShowProgress',
                            'buckets' => $progress['buckets'],
                        ])
                    </div>
                </div>
            </div>
        </div>
    @endisset

    <div class="row">
        @role('admin')
        <div class="col-md-6 mt-4">
            <div class="card">
                <h5 class="card-header">Payment History</h5>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>No</th>
                            <th>Paid</th>
                            <th>Type</th>
                            <th>Desc</th>
                            <th>Date</th>
                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                        @forelse($student->studenthistory as $item)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{number_format($item->payment,0,'',' ')}}</td>
                                <td>{{$item->type_of_money}}</td>
                                <td>{{$item->description ?? '-'}}</td>
                                <td>{{ \Carbon\Carbon::parse($item->date ?? $item->created_at)->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No payment history found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endrole

        <div class="col-md-6 mt-4">
            <div class="card">
                <h5 class="card-header">Group History</h5>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>No</th>
                            <th>Group</th>
                            {{-- student_information now records departures too (action = 1),
                                 so this column can no longer be labelled "Date Joined". --}}
                            <th>Amal</th>
                            <th>Kim</th>
                            <th>Sana</th>
                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                        @forelse($groupHistory as $item)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{$item->group}}</td>
                                <td>
                                    <span class="badge bg-label-{{ $item->actionTone() }}">
                                        <i class="bx {{ $item->actionIcon() }} me-1"></i>{{ $item->actionLabel() }}
                                    </span>
                                </td>
                                <td>
                                    {{-- Aktyor ustuni keyinroq qo'shilgan, shuning uchun eski
                                         qatorlarda u bo'sh — buni yashirmasdan aytamiz. --}}
                                    @if($item->actor)
                                        <x-avatar :user="$item->actor" label />
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $item->created_at?->format('d M Y, H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No group history found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                {{-- Bitta imtihon — bitta qator: topshirilgan ko'nikmalar
                     ballari bilan va o'rtachasi. --}}
                <h5 class="card-header">Test natijalari</h5>
                @include('partials.test-results-table', ['tests' => $testResults])
            </div>
        </div>
    </div>

@endsection
