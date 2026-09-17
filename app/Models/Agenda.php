<?php

namespace App\Models;

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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($agenda) {
            if (empty($agenda->slug)) {
                $baseSlug = Str::slug($agenda->judul_rapat);
                $uniqueSlug = $baseSlug . '-' . Str::lower(Str::random(6));
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
        });
    }

    /**
     * Relationship to the user who created the agenda.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship to the designated meeting leader.
     */
    public function pimpinan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pimpinan_id');
    }

    /**
     * Relationship to the designated meeting minute taker.
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
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope for ongoing agendas.
     */
    public function scopeOngoing(Builder $query): Builder
    {
        return $query->where('status', 'ongoing');
    }

    /**
     * Scope for scheduled agendas (pure status check).
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope for upcoming scheduled agendas that have not passed yet.
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
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for agendas visible to a specific user based on unit & permissions.
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

        if (!$user->unit_id) {
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
                            $entityParts[$j] = preg_replace_callback('/([^\s]{' . $threshold . '})/u', function ($m) use ($zwsp) {
                                return $m[1] . $zwsp;
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
     * Get the formatted notulensi (rich HTML or safe nl2br for legacy text).
     */
    public function getFormattedNotulensiAttribute(): ?string
    {
        if (!$this->notulensi) {
            return null;
        }

        // If it already contains HTML tags, return as rich formatted content
        if ($this->notulensi !== strip_tags($this->notulensi)) {
            return self::wrapLongWordsWithZeroWidthSpace($this->notulensi);
        }

        // Otherwise legacy plain text, convert newlines safely
        return self::wrapLongWordsWithZeroWidthSpace(nl2br(e($this->notulensi)));
    }

    /**
     * Get the formatted kesimpulan (rich HTML or safe nl2br for legacy text).
     */
    public function getFormattedKesimpulanAttribute(): ?string
    {
        if (!$this->kesimpulan) {
            return null;
        }

        if ($this->kesimpulan !== strip_tags($this->kesimpulan)) {
            return self::wrapLongWordsWithZeroWidthSpace($this->kesimpulan);
        }

        return self::wrapLongWordsWithZeroWidthSpace(nl2br(e($this->kesimpulan)));
    }

    /**
     * Format rentang waktu kedinasan (contoh: "09:00 - 11:00 WIB" atau "09:00 WIB s.d. Selesai").
     */
    public function getRentangWaktuAttribute(): string
    {
        if (!$this->waktu_mulai) {
            return '-';
        }

        $mulai = $this->waktu_mulai->format('H:i');

        if ($this->waktu_selesai) {
            return $mulai . ' - ' . $this->waktu_selesai->format('H:i') . ' WIB';
        }

        return $mulai . ' WIB s.d. Selesai';
    }

    /**
     * Format jadwal lengkap dengan hari dan tanggal (contoh: "Selasa, 15 Sep 2026 • 09:00 - 11:00 WIB").
     */
    public function getJadwalLengkapAttribute(): string
    {
        if (!$this->waktu_mulai) {
            return '-';
        }

        $tanggal = $this->waktu_mulai->translatedFormat('l, d M Y');
        return $tanggal . ' • ' . $this->rentang_waktu;
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
        if (!$pimpinanId) {
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
        if (!$notulisId) {
            return null;
        }

        if ($this->relationLoaded('attendances')) {
            return $this->attendances->firstWhere('user_id', $notulisId);
        }

        return $this->attendances()->where('user_id', $notulisId)->first();
    }

    /**
     * Get system default report configuration for this agenda.
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
            'document_number' => 'BA-RAPAT/' . ($this->waktu_mulai ? $this->waktu_mulai->format('Y') : date('Y')) . '/' . str_pad($this->id, 4, '0', STR_PAD_LEFT),

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
            'show_footer_note' => true,
            'footer_note' => 'Dokumen ini diterbitkan secara resmi melalui Sistem Informasi Presensi Rapat (SIPERAPAT) LLDIKTI Wilayah X',
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
}
