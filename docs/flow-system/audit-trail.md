# Audit trail

Dua mekanisme, satu tabel (`audit_logs`, append-only).

| Mekanisme                              | Mencatat                            | Nama event                                          |
| -------------------------------------- | ----------------------------------- | --------------------------------------------------- |
| `HasAuditLog` (trait pada model)       | created, updated, deleted, restored | otomatis: `{model_snake}.{action}` (`user.created`) |
| `AuditLogger::record()` (dari service) | kejadian domain, mis. ganti role    | enum `AuditEvent`                                   |
| `RecordAuthActivity` (listener)        | login, logout, login gagal          | `auth.login`, `auth.logout`, `auth.login_failed`    |

## Menambah audit ke modul baru

1. Daftarkan model di morph map (`AppServiceProvider::configureMorphMap`).
2. Tambahkan trait ke model:

    ```php
    use HasAuditLog, SoftDeletes, UserStamp;

    /** @var list<string> */
    protected array $auditExcept = ['internal_notes'];   // opsional
    ```

3. Untuk kejadian domain, tambahkan case ke `AuditEvent` lalu panggil dari service:

    ```php
    $this->audit->record(AuditEvent::ApprovalGranted, $document, ['status' => 'draft'], ['status' => 'approved']);
    ```

    Periksa `AuditEvent` dahulu agar nama tidak ganda.

4. Tambahkan label di `resources/js/lib/audit.ts` (tanpa label, nama event mentah yang tampil).
5. Tampilkan riwayat di halaman edit: kirim prop `history` dari controller dan render `<AuditTimeline>`:

    ```php
    $history = AuditLog::query()->forModel($model)->with('actor:id,name')->latest('id')->limit(20)->get();
    'history' => AuditLogResource::collection($history)->resolve(),
    ```

6. Test: lihat `tests/Feature/AuditTrailTest.php`.

## Perilaku

- Atribut `hidden` model disimpan sebagai `[redacted]`: perubahan password terlihat, nilainya tidak.
- Primary key dan kolom stamp/timestamp tidak dicatat. Perubahan yang hanya menyentuh kolom
  yang dikecualikan (mis. `remember_token`) tidak membuat log.
- Pelaku (`actor_id`) dan IP/user agent terisi dari request; kosong untuk console/queue.
- Log tidak bisa diubah atau dihapus lewat model (`LogicException`).
- Semua operasi yang melewati model Eloquent tercatat. `Model::query()->update()`/`delete()`
  massal tidak memicu event model, jadi tidak tercatat. Gunakan model instance untuk data yang perlu diaudit.
- Class `Spatie\Permission\Models\Role` langsung tidak diaudit. Selalu pakai `App\Models\Role`.
