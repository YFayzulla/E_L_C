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
                                <h4><b>Parents name: </b>{{$student->parents_name}} </h4>
                                <h4><b>Parents tel: </b> {{$student->parents_tel}}</h4>
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

    @role('admin')
    @if($paymentCyclesAvailable ?? false)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header d-flex align-items-start justify-content-between flex-wrap gap-2">
                        <div>
                            <h5 class="mb-1">To‘lov sikllari</h5>
                            <div class="text-muted" style="font-size: .85rem;">
                                Guruh almashganda eski darslarni to‘langan siklga ehtiyotkor birlashtirish uchun.
                            </div>
                        </div>
                        <span class="badge bg-label-primary">12 dars = 1 sikl</span>
                    </div>

                    <div class="card-body pt-0">
                        @if($errors->has('source_cycle_id') || $errors->has('target_cycle_id'))
                            <div class="alert alert-warning mt-3 mb-3">
                                {{ $errors->first('source_cycle_id') ?: $errors->first('target_cycle_id') }}
                            </div>
                        @endif

                        <div class="alert alert-info d-flex gap-2 align-items-start mt-3" role="alert">
                            <i class="bx bx-info-circle fs-5 mt-1"></i>
                            <div>
                                Birlashtirish eski noto‘g‘ri qarzdor siklni o‘chirmaydi, faqat uning to‘lov ta’sirini nolga tushiradi.
                                Dars soni tanlangan to‘langan siklga qo‘shiladi.
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                <tr>
                                    <th>Guruh</th>
                                    <th>Sikl</th>
                                    <th>Dars</th>
                                    <th>Holat</th>
                                    <th>To‘lov</th>
                                    <th>Qoldiq</th>
                                    <th class="text-end">O‘zgartirish</th>
                                </tr>
                                </thead>
                                <tbody class="table-border-bottom-0">
                                @forelse($paymentCycles as $cycle)
                                    @php
                                        $lessonCount = max(0, min(12, (int) ($cycle->lesson_count ?? 0)));
                                        $amount = max(0, (int) ($cycle->amount ?? 0));
                                        $paid = max(0, (int) ($cycle->paid_amount ?? 0));
                                        $left = max(0, $amount - $paid);
                                        $percent = (int) round(($lessonCount / 12) * 100);
                                        $status = (int) ($cycle->status ?? 0);
                                        $isMerged = $amount === 0 && $paid === 0 && !empty($cycle->closed_at);
                                        $statusTone = $isMerged ? 'secondary' : ($status === 2 ? 'success' : ($paid > 0 ? 'warning' : 'danger'));
                                        $statusLabel = $isMerged ? 'Birlashtirilgan' : ($status === 2 ? 'To‘langan' : ($paid > 0 ? 'Qisman' : 'To‘lanmagan'));
                                        $mergeTargets = ($paidMergeTargets ?? collect())
                                            ->reject(fn($target) => (int) $target->id === (int) $cycle->id)
                                            ->values();
                                        $canMerge = $paid <= 0 && $lessonCount > 0 && $mergeTargets->isNotEmpty();
                                        $modalId = 'mergePaymentCycleModal' . $cycle->id;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $cycle->group_name ?? 'Guruhsiz sikl' }}</div>
                                            <div class="text-muted" style="font-size: .78rem;">
                                                Ochilgan: {{ $cycle->opened_at ? \Carbon\Carbon::parse($cycle->opened_at)->format('d.m.Y') : '—' }}
                                            </div>
                                        </td>
                                        <td>#{{ (int) ($cycle->cycle_number ?? 1) }}</td>
                                        <td style="min-width: 170px;">
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <span class="fw-semibold">{{ $lessonCount }}/12</span>
                                                <span class="text-muted" style="font-size: .78rem;">
                                                    {{ max(0, 12 - $lessonCount) }} dars qoldi
                                                </span>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-primary" style="width: {{ $percent }}%"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-label-{{ $statusTone }}">{{ $statusLabel }}</span>
                                            @if($cycle->paid_at)
                                                <div class="text-muted mt-1" style="font-size: .78rem;">
                                                    {{ \Carbon\Carbon::parse($cycle->paid_at)->format('d.m.Y') }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ number_format($paid, 0, '.', ' ') }}</div>
                                            <div class="text-muted" style="font-size: .78rem;">
                                                /{{ number_format($amount, 0, '.', ' ') }}
                                            </div>
                                        </td>
                                        <td class="fw-semibold">{{ number_format($left, 0, '.', ' ') }}</td>
                                        <td class="text-end">
                                            @if($canMerge)
                                                <button type="button"
                                                        class="btn btn-sm btn-warning"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#{{ $modalId }}">
                                                    Birlashtirish
                                                </button>

                                                <div class="modal fade text-start" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <form method="POST"
                                                              action="{{ route('student.payment-cycles.merge', $student->id) }}"
                                                              class="modal-content border-0 shadow">
                                                            @csrf
                                                            <input type="hidden" name="source_cycle_id" value="{{ $cycle->id }}">

                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Siklni birlashtirish</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>

                                                            <div class="modal-body">
                                                                <div class="alert alert-warning">
                                                                    <b>{{ $cycle->group_name ?? 'Guruhsiz sikl' }}</b> dagi
                                                                    {{ $lessonCount }} ta dars tanlangan to‘langan siklga qo‘shiladi.
                                                                    Bu amal noto‘g‘ri qarzdorlikni yopadi, lekin tarixni o‘chirmaydi.
                                                                </div>

                                                                <label class="form-label">Qaysi to‘langan siklga qo‘shilsin?</label>
                                                                <select name="target_cycle_id" class="form-select" required>
                                                                    @foreach($mergeTargets as $target)
                                                                        <option value="{{ $target->id }}">
                                                                            {{ $target->group_name ?? 'Guruhsiz sikl' }}
                                                                            — #{{ (int) ($target->cycle_number ?? 1) }}
                                                                            — {{ (int) ($target->lesson_count ?? 0) }}/12
                                                                            — {{ number_format((int) ($target->paid_amount ?? 0), 0, '.', ' ') }} so‘m
                                                                        </option>
                                                                    @endforeach
                                                                </select>

                                                                <p class="text-muted mt-3 mb-0" style="font-size: .85rem;">
                                                                    Xavfsizlik uchun to‘lovi bor manba siklni yoki 12 darsdan oshadigan birlashtirishni backend rad etadi.
                                                                </p>
                                                            </div>

                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Bekor qilish</button>
                                                                <button type="submit" class="btn btn-warning">Ha, birlashtirish</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">To‘lov sikli topilmadi.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @endrole

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
                <h5 class="card-header">Test Results</h5>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>No</th>
                            <th>Test Name</th>
                            <th>Group</th>
                            <th>Mark</th>
                            <th>Date</th>
                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                        @forelse($testResults as $result)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{$result->test_name}}</td>
                                <td>{{$result->group}}</td>
                                <td>
                                    <span class="badge bg-label-primary">{{$result->get_mark}}</span>
                                </td>
                                <td>{{$result->created_at->format('d M Y')}}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No test results found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
