@extends('template.master')

@section('title', 'Guruh talabalari')
@section('subtitle', 'Guruhga biriktirilgan talabalar ro‘yxati')

@section('content')

    <div class="page-head justify-content-end">
        <a href="{{ route('group.index') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Guruhlarga qaytish
        </a>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span>Jami: <strong>{{ $students->count() }}</strong> ta talaba</span>
            @if($students->isNotEmpty())
                <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#groupDiscountModal">
                    <i class="bx bx-purchase-tag-alt me-1"></i> Guruhga chegirma
                </button>
            @endif
        </div>

        @if($students->isNotEmpty())
            @include('partials.bulk-move-bar', ['groups' => $groups, 'fromGroupId' => $group->id])
        @endif

        @if($students->isEmpty())
            <div class="empty-state">
                <i class="bx bx-user-x"></i>
                <h6>Talaba yo‘q</h6>
                <p class="mb-0">Bu guruhga hali birorta talaba biriktirilmagan.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th style="width: 2.5rem;"></th>
                        <th style="width: 3rem;">#</th>
                        <th>Talaba</th>
                        <th>Telefon</th>
                        <th>Guruhlari</th>
                        <th class="text-end">Amallar</th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($students as $student)
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input js-move-check" form="bulkMoveForm"
                                       name="student_ids[]" value="{{ $student->id }}" aria-label="{{ $student->name }}">
                            </td>
                            <td class="text-muted">{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                    <div class="avatar avatar-sm">
                                        <span class="avatar-initial rounded-circle bg-label-primary">
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($student->name, 0, 2)) }}
                                        </span>
                                    </div>
                                    <a href="{{ route('student.show', $student->id) }}"
                                       class="fw-semibold text-decoration-none">{{ $student->name }}</a>
                                </div>
                            </td>
                            <td dir="ltr">{{ $student->phone ? '+' . $student->phone : '—' }}</td>
                            <td>
                                @forelse($student->groups as $group)
                                    <span class="badge bg-label-info me-1 mb-1 d-inline-block">{{ $group->name }}</span>
                                @empty
                                    <span class="text-muted">— guruhsiz</span>
                                @endforelse
                            </td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('student.show', $student->id) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Ma’lumotlari">
                                        <i class="bx bx-show-alt"></i>
                                    </a>
                                    <a href="{{ route('student.transfer.form', $student->id) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Guruhni o‘zgartirish">
                                        <i class="bx bx-transfer"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Guruhdagi barcha (hozirgi) talabaga bir xil chegirma. --}}
    @if($students->isNotEmpty())
        <div class="modal fade" id="groupDiscountModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">«{{ $group->name }}» — guruhga chegirma</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Yopish"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted" style="font-size: .85rem;">
                            Guruhdagi barcha {{ $students->count() }} ta talabaga beriladi va ularning shu guruh bo‘yicha
                            oylik summasi qayta hisoblanadi. Keyin har bir talabaning chegirmasini uning sahifasidan
                            alohida bekor qilish mumkin.
                        </p>
                        @include('partials.discount-form', [
                            'action' => route('discounts.group.store', $group->id),
                            'prefix' => 'group-discount',
                            'groups' => null,
                            'submitLabel' => 'Barchasiga chegirma berish',
                        ])
                    </div>
                </div>
            </div>
        </div>
    @endif

@endsection
