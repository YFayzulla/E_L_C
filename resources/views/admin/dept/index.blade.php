@extends('template.master')
@section('content')

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0">Payment</h5>
        </div>

        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Group</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody class="table-border-bottom-0" id="myTable">
                @forelse($students as $student)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $student->name }}</strong></td>
                        <td>{{ $student->groups->isNotEmpty() ? $student->groups->pluck('name')->implode(', ') : 'No group' }}</td>
                        <td>
                            @if($student->deptStudent && $student->deptStudent->payed > 0)
                                <span class="badge bg-label-warning">Partially Paid</span>
                            @elseif($student->status === null)
                                <span class="badge bg-label-info">Disabled</span>
                            @elseif( $student->status <= 0 )
                                <span class="badge bg-label-danger">Debtor</span>
                            @else
                                <span class="badge bg-label-success">Paid</span>
                            @endif
                        </td>
                        <td>
                            {{-- View Button --}}
                            <a class="btn btn-sm btn-outline-primary m-1"
                               href="{{ route('student.show',$student->id) }}">
                                <i class="bx bx-show-alt"></i>
                            </a>

                            {{-- Payment Modal Button --}}
                            <button type="button" class="btn btn-sm btn-outline-success m-1" data-bs-toggle="modal"
                                    data-bs-target="#paymentModal{{$student->id}}">
                                <i class="bx bx-dollar-circle"></i>
                            </button>

                            {{-- Refresh Button --}}
                            <a class="btn btn-sm btn-outline-info m-1"
                               onclick="return confirm('Do you want to refresh the student\'s dept?')"
                               href="{{ route('refresh.update', $student->id) }}">
                                <i class="bx bx-refresh"></i>
                            </a>

                            {{-- MODAL --}}
                            <div class="modal fade" id="paymentModal{{$student->id}}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Make Payment for {{ $student->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="{{ route('dept.update', $student->id) }}" method="post"
                                                  class="submit-payment-form">
                                                @csrf
                                                @method('PUT')

                                                <p>Monthly Payment:
                                                    <strong>{{ number_format($student->should_pay, 0, '.', ' ') }}</strong>
                                                </p>

                                                @if($student->deptStudent?->payed > 0)
                                                    <div class="alert alert-warning p-2 mb-2">
                                                        Paid
                                                        partially: {{ number_format($student->deptStudent->payed, 0, '.', ' ') }}
                                                        <br>
                                                        Last date: {{ $student->deptStudent->date }}
                                                    </div>
                                                @endif

                                                @if($student->status < 0)
                                                    <div class="alert alert-danger p-2 mb-2">
                                                        Debt: {{ abs($student->status) }} month(s)
                                                    </div>
                                                @endif

                                                <label class="form-label">Payment Amount</label>
                                                <div class="input-group mb-3">
                                                    <input type="text" class="form-control payment-input"
                                                           value="{{ $student->deptStudent?->payed > 0 ? number_format($student->deptStudent->dept - $student->deptStudent->payed, 0, '', ' ') : '' }}"
                                                           name="payment" required
                                                           oninput="formatNumber(this)">

                                                    <select name="money_type" class="form-select"
                                                            style="max-width: 120px;">
                                                        <option value="cash">Cash</option>
                                                        <option value="electronic">Card</option>
                                                    </select>
                                                </div>

                                                <label class="form-label">Date (Optional)</label>
                                                <div class="input-group">
                                                    <input type="date" class="form-control" name="date_paid"
                                                           value="{{ date('Y-m-d') }}">
                                                    <button type="submit" class="btn btn-primary">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{-- End Modal --}}

                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">No students found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Bu skript sahifa yuklanganda ishga tushadi
        document.addEventListener('DOMContentLoaded', function () {
            // Raqamlarni formatlash (1 000 000)
            function formatNumber(input) {
                let value = input.value.replace(/\s+/g, '').replace(/,/g, '');
                if (!isNaN(value) && value.length > 0) {
                    input.value = parseInt(value, 10).toLocaleString('en-US').replace(/,/g, ' ');
                }
            }

            // To'lov inputiga formatlash funksiyasini qo'shish
            // Bu qismni global scope'ga chiqarish kerak, chunki oninput atributi uni chaqiradi
            window.formatNumber = formatNumber;

            // Form submit bo'lganda bo'sh joylarni olib tashlash
            document.querySelectorAll('.submit-payment-form').forEach(form => {
                form.addEventListener('submit', function (e) {
                    const input = this.querySelector('.payment-input');
                    input.value = input.value.replace(/\s+/g, '');
                });
            });

            // Session'da chek ID'si borligini tekshirish va yangi oynada ochish
            @if(session('payment_receipt_id'))
            (function(){
                var baseUrl = "{{ route('payment.receipt', session('payment_receipt_id')) }}";
                var receiptUrl = baseUrl + '?embed=1';
                try {
                    var iframe = document.createElement('iframe');
                    iframe.style.position = 'fixed';
                    iframe.style.width = '0';
                    iframe.style.height = '0';
                    iframe.style.border = '0';
                    iframe.style.opacity = '0';
                    iframe.onload = function(){
                        try {
                            var cw = iframe.contentWindow || iframe;
                            var cleanup = function(){
                                // Remove iframe shortly after printing finishes
                                setTimeout(function(){
                                    if (iframe && iframe.parentNode) iframe.parentNode.removeChild(iframe);
                                }, 50);
                            };
                            // Listen inside the iframe for afterprint if supported
                            if (cw && 'onafterprint' in cw) {
                                cw.addEventListener('afterprint', cleanup);
                            } else {
                                // Fallback: cleanup after a short delay
                                setTimeout(cleanup, 2000);
                            }
                            // Trigger print
                            cw.focus();
                            cw.print();
                        } catch (e) {
                            // If printing is blocked, fallback to opening a new tab
                            window.open(baseUrl, '_blank');
                            if (iframe && iframe.parentNode) iframe.parentNode.removeChild(iframe);
                        }
                    };
                    document.body.appendChild(iframe);
                    iframe.src = receiptUrl;
                } catch (err) {
                    // Last-resort fallback
                    window.open(baseUrl, '_blank');
                }
            })();
            @endif
        });
    </script>

@endsection
