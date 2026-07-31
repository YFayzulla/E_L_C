@extends('template.master')

@section('title', 'Sertifikatlar')
@section('subtitle', 'Bitiruvchilarga berilgan hujjatlar reyestri')

@section('content')

    <div class="page-head justify-content-end">
        <a href="{{ route('certificates.create') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i> Sertifikat berish
        </a>
    </div>

    <div class="filter-card">
        <form method="GET" action="{{ route('certificates.index') }}" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label" for="q">Qidirish</label>
                <input type="text" id="q" name="q" class="form-control"
                       value="{{ request('q') }}" placeholder="Talaba ismi yoki seriya raqami">
            </div>

            <div class="col-md-4">
                <label class="form-label" for="group_id">Guruh</label>
                <select id="group_id" name="group_id" class="form-select">
                    <option value="">Barchasi</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}" @selected(request('group_id') == $group->id)>
                            {{ $group->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bx bx-search-alt me-1"></i> Qidirish
                </button>
                <a href="{{ route('certificates.index') }}" class="btn btn-outline-secondary">Tozalash</a>
            </div>
        </form>
    </div>

    <div class="card">
        @if($certificates->isEmpty())
            <div class="empty-state">
                <i class="bx bx-award"></i>
                <h6>Sertifikat yo‘q</h6>
                <p class="mb-3">
                    {{ request()->hasAny(['q', 'group_id']) ? 'Filtrga mos sertifikat topilmadi.' : 'Hali birorta sertifikat berilmagan.' }}
                </p>
                <a href="{{ route('certificates.create') }}" class="btn btn-sm btn-primary">
                    <i class="bx bx-plus me-1"></i> Sertifikat berish
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th style="width: 9rem;">Seriya</th>
                        <th>Talaba</th>
                        <th>Sertifikat</th>
                        <th>Guruh</th>
                        <th class="text-center" style="width: 6rem;">Ball</th>
                        <th style="width: 7rem;">Sana</th>
                        <th class="text-end" style="width: 8rem;">Amallar</th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($certificates as $certificate)
                        <tr>
                            <td>
                                <span class="text-muted" style="font-size: .8rem;" dir="ltr">
                                    {{ $certificate->serial }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('student.show', $certificate->user_id) }}" class="fw-semibold">
                                    {{ $certificate->student->name ?? '—' }}
                                </a>
                            </td>
                            <td>
                                {{ $certificate->title }}
                                @if($certificate->level)
                                    <span class="badge bg-label-primary ms-1">{{ $certificate->level }}</span>
                                @endif
                                @if(! $certificate->hasFile())
                                    <div class="text-muted" style="font-size: .72rem;">
                                        <i class="bx bx-file-blank me-1"></i>shablondan yaratiladi
                                    </div>
                                @endif
                            </td>
                            <td class="text-muted">{{ $certificate->group->name ?? '—' }}</td>
                            <td class="text-center">
                                {{ $certificate->final_score !== null ? $certificate->final_score : '—' }}
                            </td>
                            <td class="text-muted">{{ optional($certificate->issued_at)->format('d.m.Y') }}</td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('certificates.download', $certificate->id) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Yuklab olish">
                                        <i class="bx bx-download"></i>
                                    </a>
                                    <form action="{{ route('certificates.destroy', $certificate->id) }}" method="post"
                                          onsubmit="return confirm('{{ $certificate->serial }} sertifikati o‘chirilsinmi? Bu amalni qaytarib bo‘lmaydi.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="O‘chirish">
                                            <i class="bx bx-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-footer d-flex justify-content-end">
                {{ $certificates->links() }}
            </div>
        @endif
    </div>

@endsection
