<?php

namespace App\Models;

use App\Support\Html;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Agenda extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'pimpinan_id',
        'notulis_id',
        'judul_rapat',
        'slug',
        'jenis_rapat',
        'tipe_rapat',
        'lokasi_ruang',
        'link_meeting',
        'waktu_mulai',
        'waktu_selesai',
        'is_all_units',
        'surat_edaran_path',
        'notulensi',
        'kesimpulan',
        'report_config',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'waktu_mulai' => 'datetime',
            'waktu_selesai' => 'datetime',
            'is_all_units' => 'boolean',
            'report_config' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($agenda) {
            if (empty($agenda->slug)) {
                $baseSlug = Str::slug($agenda->judul_rapat);
                $uniqueSlug = $baseSlug.'-'.Str::lower(Str::random(6));
                $agenda->slug = $uniqueSlug;
            }
        });

        static::saving(function (Agenda $agenda) {
            if ($agenda->isDirty(['pimpinan_id', 'notulis_id']) && is_array($agenda->report_config)) {
                $config = $agenda->report_config;

                if ($agenda->isDirty('pimpinan_id')) {
                    $newPimpinan = $agenda->pimpinan_id ? User::find($agenda->pimpinan_id) : $agenda->creator;
                    $config['signer1_name'] = $newPimpinan?->name ?? 'Pemimpin Rapat';
                    $config['signer1_nip'] = ($newPimpinan?->nip && $newPimpinan->nip !== '-') ? $newPimpinan->nip : '-';
                }

                if ($agenda->isDirty('notulis_id')) {
                    $newNotulis = $agenda->notulis_id ? User::find($agenda->notulis_id) : $agenda->creator;
                    $config['signer2_name'] = $newNotulis?->name ?? 'Notulis Rapat';
                    $config['signer2_nip'] = ($newNotulis?->nip && $newNotulis->nip !== '-') ? $newNotulis->nip : '-';
                }

                $agenda->report_config = $config;
            }

            // Keep the sargable room key in sync whenever the room actually changes.
            // Intentionally not fillable: this is derived server-side and must never
            // be settable through mass assignment.
            if ($agenda->isDirty('lokasi_ruang')) {
                $agenda->lokasi_ruang_normalized = self::normalizeRoom($agenda->lokasi_ruang);
            }
        });
    }

    /**
     * Canonical form of a physical room name, used for conflict matching.
     *
     * Kept as a single definition so AgendaConflictService and the write path agree.
     * The migration that backfills lokasi_ruang_normalized mirrors this logic on
     * purpose — migrations must not depend on mutable application code.
     */
    public static function normalizeRoom(?string $room): ?string
    {
        if ($room === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($room));

        return $normalized === '' ? null : mb_substr($normalized, 0, 150);
    }

    /**
     * Relationship to the user who created the agenda.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship to the designated meeting leader.
     *
     * @return BelongsTo<User, $this>
     */
    public function pimpinan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pimpinan_id');
    }

    /**
     * Relationship to the designated meeting minute taker.
     *
     * @return BelongsTo<User, $this>
     */
    public function notulis(): BelongsTo
    {
        return $this->belongsTo(User::class, 'notulis_id');
    }

    /**
     * Relationship to units invited to this agenda.
     */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'agenda_units')
            ->withTimestamps();
    }

    /**
     * Relationship to attendances recorded for this agenda.
     *
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Relationship to photo documentations of this agenda.
     */
    public function documentations(): HasMany
    {
        return $this->hasMany(AgendaDocumentation::class)->orderBy('sort_order');
    }

    /**
     * Scope for draft/concept agendas.
     *
     * @param  Builder<Agenda>  $query
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope for ongoing agendas.
     *
     * @param  Builder<Agenda>  $query
     */
    public function scopeOngoing(Builder $query): Builder
    {
        return $query->where('status', 'ongoing');
    }

    /**
     * Scope for scheduled agendas (pure status check).
     *
     * @param  Builder<Agenda>  $query
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope for upcoming scheduled agendas that have not passed yet.
     *
     * @param  Builder<Agenda>  $query
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', 'scheduled')
            ->where(function (Builder $q) {
                $q->where(function (Builder $sub) {
                    $sub->whereNotNull('waktu_selesai')
                        ->where('waktu_selesai', '>=', now());
                })->orWhere(function (Builder $sub) {
                    $sub->whereNull('waktu_selesai')
                        ->where('waktu_mulai', '>=', now()->startOfDay());
                });
            });
    }

    /**
     * Scope for active/relevant agendas on the dashboard (ongoing right now OR upcoming).
     *
     * @param  Builder<Agenda>  $query
     */
    public function scopeRelevantForDashboard(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('status', 'ongoing')
                ->orWhere(function (Builder $sub) {
                    $sub->upcoming();
                });
        });
    }

    /**
     * Scope for completed agendas.
     *
     * @param  Builder<Agenda>  $query
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for agendas visible to a specific user based on unit & permissions.
     *
     * @param  Builder<Agenda>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdministrator()) {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($user) {
            $sub->where('is_all_units', true);

            if ($user->unit_id) {
                $sub->orWhereHas('units', function (Builder $uQuery) use ($user) {
                    $uQuery->where('units.id', $user->unit_id);
                });
            }
        });
    }

    /**
     * Unit kerja milik pembuat agenda, atau null bila tidak diketahui.
     *
     * Policies need this on every authorisation check, and reading $agenda->creator
     * there would lazy-load the relation — one extra query per check, plus a
     * lazy-loading violation once the strict guard is enabled. This mirrors the
     * relationLoaded() pattern already used by isUserEligible(): use the loaded
     * relation when it is available, otherwise ask the database directly.
     */
    public function creatorUnitId(): ?int
    {
        if ($this->relationLoaded('creator')) {
            return $this->creator?->unit_id;
        }

        $unitId = $this->creator()->value('unit_id');

        return $unitId === null ? null : (int) $unitId;
    }

    /**
     * Check if a specific user is eligible to attend this agenda.
     */
    public function isUserEligible(User $user): bool
    {
        // Administrator has global oversight and can attend any agenda across all units
        if ($user->isAdministrator()) {
            return true;
        }

        if ($this->is_all_units) {
            return true;
        }

        if (! $user->unit_id) {
            return false;
        }

        if ($this->relationLoaded('units')) {
            return $this->units->contains('id', $user->unit_id);
        }

        return $this->units()->where('units.id', $user->unit_id)->exists();
    }

    /**
     * Check if a user has already checked in.
     */
    public function hasUserAttended(User $user): bool
    {
        if ($this->relationLoaded('attendances')) {
            return $this->attendances->contains('user_id', $user->id);
        }

        return $this->attendances()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the agenda already has recorded attendee attendances.
     */
    public function hasAttendances(): bool
    {
        if (isset($this->attendances_count)) {
            return $this->attendances_count > 0;
        }

        if ($this->relationLoaded('attendances')) {
            return $this->attendances->isNotEmpty();
        }

        return $this->attendances()->exists();
    }

    /**
     * Insert zero-width space (\u{200B}) into unbroken words longer than threshold (e.g., URLs, hashes)
     * to ensure proper wrapping and prevent margin overflow in Web Preview, PDF, and Word exports.
     * Safely preserves HTML tags, tag attributes, and entities.
     */
    public static function wrapLongWordsWithZeroWidthSpace(?string $html, int $threshold = 30): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        $zwsp = "\u{200B}";

        // Split HTML by tags to isolate text nodes from tags/attributes
        $parts = preg_split('/(<[^>]+>)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $html;
        }

        foreach ($parts as $i => $part) {
            // Even indices are text nodes; odd indices are HTML tags
            if ($i % 2 === 0 && $part !== '') {
                // Split text node by HTML entities so entities like &nbsp; are not corrupted
                $entityParts = preg_split('/(&[a-zA-Z0-9#]+;)/u', $part, -1, PREG_SPLIT_DELIM_CAPTURE);
                if ($entityParts !== false) {
                    foreach ($entityParts as $j => $entityPart) {
                        // Even indices are raw text; odd indices are entities
                        if ($j % 2 === 0 && $entityPart !== '') {
                            $entityParts[$j] = preg_replace_callback('/([^\s]{'.$threshold.'})/u', function ($m) use ($zwsp) {
                                return $m[1].$zwsp;
                            }, $entityPart);
                        }
                    }
                    $parts[$i] = implode('', $entityParts);
                }
            }
        }

        return implode('', $parts);
    }

    /**
     * Get the formatted notulensi, sanitised for safe raw rendering.
     *
     * Sanitising lives here, in the accessor, rather than in a FormRequest. An
     * accessor is the choke point every read path goes through — a seeder, an
     * import, a future endpoint, or a row that predates the current validation
     * rules. Sanitising on write meant any of those could store markup that the
     * template then emitted through {!! !!} unfiltered.
     */
    public function getFormattedNotulensiAttribute(): ?string
    {
        return self::renderSafeRichText($this->notulensi);
    }

    /**
     * Get the formatted kesimpulan, sanitised for safe raw rendering.
     */
    public function getFormattedKesimpulanAttribute(): ?string
    {
        return self::renderSafeRichText($this->kesimpulan);
    }

    /**
     * Shared render path for both rich-text fields.
     */
    private static function renderSafeRichText(?string $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        // Legacy rows may hold plain text rather than markup.
        if ($raw === strip_tags($raw)) {
            return self::wrapLongWordsWithZeroWidthSpace(nl2br(e($raw)));
        }

        $safe = Html::sanitize($raw);

        return $safe === null ? null : self::wrapLongWordsWithZeroWidthSpace($safe);
    }

    /**
     * Label dan warna badge untuk status agenda.
     *
     * consolidated here after the same match() block was duplicated across five
     * templates with three different results. staff_show.blade.php in particular
     * had no 'cancelled' case, so a cancelled meeting rendered in neutral grey —
     * the most safety-critical status to communicate was the one hardest to spot.
     *
     * @return array{label: string, class: string}
     */
    public function getStatusMetaAttribute(): array
    {
        return match ($this->status) {
            'ongoing' => [
                'label' => 'Sedang Berlangsung (Presensi Dibuka)',
                'class' => 'bg-amber-400 text-slate-950 font-bold',
            ],
            'completed' => [
                'label' => 'Selesai (Presensi Ditutup)',
                'class' => 'bg-emerald-400 text-slate-950 font-bold',
            ],
            'draft' => [
                'label' => 'Konsep',
                'class' => 'bg-slate-800 text-slate-200 border border-slate-700',
            ],
            'cancelled' => [
                'label' => 'Dibatalkan',
                'class' => 'bg-rose-400 text-slate-950 font-bold',
            ],
            default => [
                'label' => 'Terjadwal',
                'class' => 'bg-slate-800 text-white border border-slate-700 font-bold',
            ],
        };
    }

    /**
     * Status badge in the light card palette used by list and dashboard views.
     *
     * Kept alongside status_meta so both surfaces stay in step. The card variant
     * previously had a 'cancelled' label with no matching colour arm, which meant
     * a cancelled meeting showed the word "Dibatalkan" on a neutral grey chip.
     *
     * @return array{label: string, class: string}
     */
    public function getStatusMetaSoftAttribute(): array
    {
        return match ($this->status) {
            'ongoing' => [
                'label' => 'Sedang Berlangsung',
                'class' => 'bg-amber-100 text-amber-900 border-amber-300 font-bold',
            ],
            'completed' => [
                'label' => 'Selesai',
                'class' => 'bg-emerald-100 text-emerald-900 border-emerald-300 font-bold',
            ],
            'draft' => [
                'label' => 'Konsep',
                'class' => 'bg-slate-100 text-slate-800 border-slate-300 font-semibold',
            ],
            'cancelled' => [
                'label' => 'Dibatalkan',
                'class' => 'bg-rose-100 text-rose-900 border-rose-300 font-bold',
            ],
            default => [
                'label' => 'Terjadwal',
                'class' => 'bg-slate-100 text-slate-900 border-slate-300 font-bold',
            ],
        };
    }

    /**
     * Format rentang waktu kedinasan (contoh: "09:00 - 11:00 WIB" atau "09:00 WIB s.d. Selesai").
     */
    public function getRentangWaktuAttribute(): string
    {
        if (! $this->waktu_mulai) {
            return '-';
        }

        $mulai = $this->waktu_mulai->format('H:i');

        if ($this->waktu_selesai) {
            return $mulai.' - '.$this->waktu_selesai->format('H:i').' WIB';
        }

        return $mulai.' WIB s.d. Selesai';
    }

    /**
     * Format jadwal lengkap dengan hari dan tanggal (contoh: "Selasa, 15 Sep 2026 • 09:00 - 11:00 WIB").
     */
    public function getJadwalLengkapAttribute(): string
    {
        if (! $this->waktu_mulai) {
            return '-';
        }

        $tanggal = $this->waktu_mulai->translatedFormat('l, d M Y');

        return $tanggal.' • '.$this->rentang_waktu;
    }

    /**
     * Effective meeting leader (designated pimpinan or fallback to creator).
     */
    public function getEffectivePimpinanAttribute(): ?User
    {
        return $this->pimpinan ?? $this->creator;
    }

    /**
     * Effective minute taker (designated notulis or fallback to creator).
     */
    public function getEffectiveNotulisAttribute(): ?User
    {
        return $this->notulis ?? $this->creator;
    }

    /**
     * Leader's full name.
     */
    public function getNamaPimpinanAttribute(): string
    {
        return $this->effective_pimpinan?->name ?? 'Pimpinan Rapat';
    }

    /**
     * Leader's NIP.
     */
    public function getNipPimpinanAttribute(): string
    {
        return $this->effective_pimpinan?->nip ?? '-';
    }

    /**
     * Minute taker's full name.
     */
    public function getNamaNotulisAttribute(): string
    {
        return $this->effective_notulis?->name ?? 'Notulis Rapat';
    }

    /**
     * Minute taker's NIP.
     */
    public function getNipNotulisAttribute(): string
    {
        return $this->effective_notulis?->nip ?? '-';
    }

    /**
     * Attendance record of the effective pimpinan.
     */
    public function getPimpinanAttendanceAttribute(): ?Attendance
    {
        $pimpinanId = $this->effective_pimpinan?->id;
        if (! $pimpinanId) {
            return null;
        }

        if ($this->relationLoaded('attendances')) {
            return $this->attendances->firstWhere('user_id', $pimpinanId);
        }

        return $this->attendances()->where('user_id', $pimpinanId)->first();
    }

    /**
     * Attendance record of the effective notulis.
     */
    public function getNotulisAttendanceAttribute(): ?Attendance
    {
        $notulisId = $this->effective_notulis?->id;
        if (! $notulisId) {
            return null;
        }

        if ($this->relationLoaded('attendances')) {
            return $this->attendances->firstWhere('user_id', $notulisId);
        }

        return $this->attendances()->where('user_id', $notulisId)->first();
    }

    /**
     * Get system default report configuration for this agenda.
     *
     * @return array<string,mixed>
     */
    public function getDefaultReportConfig(): array
    {
        return [
            // Header
            'show_kop' => true,
            'show_logo' => true,
            'custom_logo_path' => null,
            'instansi_induk' => 'KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI',
            'instansi_pelaksana' => 'LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X',
            'alamat_kontak' => 'Jalan Khatib Sulaiman, Padang, Sumatera Barat • Laman: lldikti10.kemdikbud.go.id',
            'document_title' => 'BERITA ACARA DAN DAFTAR HADIR RAPAT',
            'show_document_number' => true,
            'document_number' => 'BA-RAPAT/'.($this->waktu_mulai ? $this->waktu_mulai->format('Y') : date('Y')).'/'.str_pad($this->id, 4, '0', STR_PAD_LEFT),

            // Content
            'show_meeting_info' => true,
            'custom_agenda_title' => $this->judul_rapat,
            'custom_location' => $this->lokasi_ruang ?? 'Daring (Online Meeting)',
            'show_attendees' => true,
            'show_nip' => true,
            'show_unit' => true,
            'show_attendance_time' => true,
            'show_selfie_photos' => true,
            'show_attendee_signatures' => true,
            'show_notulensi' => true,
            'show_kesimpulan' => true,
            'show_documentation' => true,

            // Footer
            'signing_city' => 'Padang',
            'signing_date' => $this->waktu_mulai ? $this->waktu_mulai->translatedFormat('d F Y') : now()->translatedFormat('d F Y'),
            'signer1_role' => 'Pemimpin Rapat',
            'signer1_name' => $this->nama_pimpinan,
            'signer1_nip' => ($this->nip_pimpinan && $this->nip_pimpinan !== '-') ? $this->nip_pimpinan : '-',
            'show_signer1_signature' => true,
            'signer2_role' => 'Notulis Rapat',
            'signer2_name' => $this->nama_notulis,
            'signer2_nip' => ($this->nip_notulis && $this->nip_notulis !== '-') ? $this->nip_notulis : '-',
            'show_signer2_signature' => true,
            'show_signer3' => false,
            'signer3_role' => 'Kepala Lembaga Layanan Pendidikan Tinggi Wilayah X',
            'signer3_name' => '',
            'signer3_nip' => '-',
            'show_footer_note' => false,
            // Kosong secara sengaja. Dokumen ini tidak lagi mencantumkan
            // kalimat resmi SIPERAPAT maupun stempel waktu cetak;-petugas
            // menuliskan catatannya sendiri bila memang diperlukan.
            'footer_note' => '',
        ];
    }

    /**
     * Get resolved report configuration, merging stored config with defaults.
     */
    public function getResolvedReportConfigAttribute(): array
    {
        $defaults = $this->getDefaultReportConfig();
        $stored = is_array($this->report_config) ? $this->report_config : [];

        $resolved = array_replace($defaults, $stored);

        // Dynamically track active assigned leader and minute taker if roles were assigned
        if ($this->pimpinan_id && isset($stored['signer1_name'])) {
            if ($stored['signer1_name'] === $this->creator?->name || empty($stored['signer1_name'])) {
                $resolved['signer1_name'] = $this->nama_pimpinan;
                $resolved['signer1_nip'] = ($this->nip_pimpinan && $this->nip_pimpinan !== '-') ? $this->nip_pimpinan : '-';
            }
        }

        if ($this->notulis_id && isset($stored['signer2_name'])) {
            if ($stored['signer2_name'] === $this->creator?->name || empty($stored['signer2_name'])) {
                $resolved['signer2_name'] = $this->nama_notulis;
                $resolved['signer2_nip'] = ($this->nip_notulis && $this->nip_notulis !== '-') ? $this->nip_notulis : '-';
            }
        }

        return $resolved;
    }

    /**
     * Get public root-relative URL for surat edaran file if present.
     * Using root-relative path prevents CSP origin blocks caused by APP_URL / port differences.
     */
    public function getSuratEdaranUrlAttribute(): ?string
    {
        if (! $this->surat_edaran_path) {
            return null;
        }

        return '/storage/'.ltrim($this->surat_edaran_path, '/');
    }

    /**
     * Get lowercase extension of surat edaran file.
     */
    public function getSuratEdaranExtensionAttribute(): ?string
    {
        return $this->surat_edaran_path ? strtolower(pathinfo($this->surat_edaran_path, PATHINFO_EXTENSION)) : null;
    }

    /**
     * Determine if the attached surat edaran is a PDF document.
     */
    public function getIsSuratEdaranPdfAttribute(): bool
    {
        return $this->surat_edaran_extension === 'pdf';
    }

    /**
     * Determine if the attached surat edaran is an image format.
     */
    public function getIsSuratEdaranImageAttribute(): bool
    {
        return in_array($this->surat_edaran_extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'], true);
    }
}
