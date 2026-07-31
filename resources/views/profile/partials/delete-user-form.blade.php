<p class="text-muted mb-3" style="font-size: .875rem;">
    Hisob o‘chirilgach, unga tegishli barcha ma’lumotlar butunlay yo‘qoladi va tiklab bo‘lmaydi.
    Davom etishdan oldin kerakli ma’lumotlarni saqlab oling.
</p>

<button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmUserDeletion">
    <i class="bx bx-trash-alt me-1"></i> Hisobni o‘chirish
</button>

<div class="modal fade" id="confirmUserDeletion" tabindex="-1" aria-hidden="true"
     aria-labelledby="confirmUserDeletionLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')

                <div class="modal-header">
                    <h5 class="modal-title" id="confirmUserDeletionLabel">Hisobni o‘chirishni tasdiqlang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Yopish"></button>
                </div>

                <div class="modal-body">
                    <p class="text-muted" style="font-size: .875rem;">
                        Bu amalni orqaga qaytarib bo‘lmaydi. Tasdiqlash uchun parolingizni kiriting.
                    </p>

                    <label class="form-label" for="delete_password">Parol</label>
                    <input type="password" id="delete_password" name="password"
                           class="form-control @if($errors->userDeletion->has('password')) is-invalid @endif"
                           placeholder="••••••••" autocomplete="current-password">
                    <x-input-error class="mt-1" :messages="$errors->userDeletion->get('password')"/>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Bekor qilish</button>
                    <button type="submit" class="btn btn-danger">Butunlay o‘chirish</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->userDeletion->isNotEmpty())
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('confirmUserDeletion')).show();
            });
        </script>
    @endpush
@endif
