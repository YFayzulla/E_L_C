@extends('template.master')

@section('title', 'Ota-onalar')
@section('subtitle', 'Ota-ona hisoblari va ularga biriktirilgan farzandlar')

@section('content')

    <div class="page-head">
        <div class="page-sub">Jami {{ $parents->count() }} ta hisob</div>
        <div class="d-flex gap-2 flex-wrap">
            <form action="{{ route('parents.backfill') }}" method="post"
                  onsubmit="return confirm('Talabalar kartochkasidagi ota-ona raqamlari asosida hisoblar yaratilsin va bog‘lansinmi?');">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="bx bx-import me-1"></i> Talabalardan import
                </button>
            </form>
            <a href="{{ route('parents.create') }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i> Yangi ota-ona
            </a>
        </div>
    </div>

    <div class="card">
        @if($parents->isEmpty())
            <div class="empty-state">
                <i class="bx bx-user-plus"></i>
                <h6>Ota-ona hisoblari yo‘q</h6>
                <p class="mb-3">Qo‘lda qo‘shing yoki mavjud talabalar ma’lumotidan import qiling.</p>
                <a href="{{ route('parents.create') }}" class="btn btn-primary btn-sm">
                    <i class="bx bx-plus me-1"></i> Yangi ota-ona
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th style="width: 3rem;">#</th>
                        <th>Ota-ona</th>
                        <th>Telefon</th>
                        <th>Farzandlar</th>
                        <th class="text-end">Amallar</th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($parents as $parent)
                        <tr>
                            <td class="text-muted">{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar avatar-sm">
                                        <span class="avatar-initial rounded-circle bg-label-primary">
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($parent->name, 0, 2)) }}
                                        </span>
                                    </div>
                                    <span class="fw-semibold">{{ $parent->name }}</span>
                                </div>
                            </td>
                            <td dir="ltr">+{{ $parent->phone }}</td>
                            <td>
                                @forelse($parent->children as $child)
                                    <a href="{{ route('student.show', $child->id) }}"
                                       class="badge bg-label-info me-1 mb-1 d-inline-block">{{ $child->name }}</a>
                                @empty
                                    <span class="text-muted">— biriktirilmagan</span>
                                @endforelse
                            </td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('parents.edit', $parent->id) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Tahrirlash">
                                        <i class="bx bx-edit-alt"></i>
                                    </a>
                                    <form action="{{ route('parents.destroy', $parent->id) }}" method="post"
                                          onsubmit="return confirm('{{ $parent->name }} hisobi o‘chirilsinmi?');">
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
        @endif
    </div>

@endsection
