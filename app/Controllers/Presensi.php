<?php

namespace App\Controllers;

use App\Libraries\LaravelApiClient;

class Presensi extends BaseController
{
    private $client;

    public function __construct()
    {
        $this->client = new LaravelApiClient();
    }

    public function index()
    {
        $now = $this->jakartaNow();
        $today = $now->format('Y-m-d');
        $hariIni = $this->hariIndonesia($now->format('l'));
        $jamSekarang = $now->format('H:i');

        $jadwal = $this->safeList($this->client->get('jadwal'));
        $presensi = $this->buildReportRows($this->safeList($this->client->get('presensi')));
        $presensi = $this->scopeRowsForRole($presensi, (string) session()->get('role'));

        $presensiHariIni = array_values(array_filter($presensi, static fn (array $row): bool => $row['tanggal'] === $today));
        $presensiHariIni = $this->dedupeDailyRows($presensiHariIni);

        return view('presensi', [
            'hariIni' => $hariIni,
            'jamSekarang' => $jamSekarang,
            'jadwalHariIni' => $this->buildJadwalHariIni($jadwal, $hariIni, $jamSekarang),
            'presensiHariIni' => $presensiHariIni,
        ]);
    }

    public function simpan()
    {
        if ((string) session()->get('role') !== 'admin') {
            return redirect()->to('/presensi')->with('error', 'Akses ditolak.');
        }

        $id = (int) $this->request->getPost('id_presensi');
        if ($id <= 0) {
            return redirect()->to('/presensi')->with('error', 'ID presensi tidak valid.');
        }

        $payload = [
            'status' => trim((string) $this->request->getPost('status')),
            'jam' => trim((string) $this->request->getPost('jam')),
            'metode' => trim((string) $this->request->getPost('metode')),
            'catatan' => trim((string) $this->request->getPost('catatan')),
        ];
        $payload = array_filter($payload, static fn ($v): bool => $v !== '');

        $response = $this->client->put('presensi/' . $id, $payload);
        if (isset($response['message']) && $response['message'] === 'Updated') {
            return redirect()->to('/presensi')->with('success', 'Data presensi berhasil diperbarui.');
        }

        return redirect()->to('/presensi')->with('error', 'Gagal memperbarui data presensi.');
    }

    public function update(int $id)
    {
        if ((string) session()->get('role') !== 'admin') {
            return redirect()->to('/presensi')->with('error', 'Akses ditolak.');
        }
        $payload = [
            'status' => trim((string) $this->request->getPost('status')),
            'jam' => trim((string) $this->request->getPost('jam')),
            'metode' => trim((string) $this->request->getPost('metode')),
            'catatan' => trim((string) $this->request->getPost('catatan')),
        ];
        $payload = array_filter($payload, static fn ($v): bool => $v !== '');
        $response = $this->client->put('presensi/' . $id, $payload);
        if (isset($response['message']) && $response['message'] === 'Updated') {
            return redirect()->to('/presensi')->with('success', 'Data presensi berhasil diperbarui.');
        }
        return redirect()->to('/presensi')->with('error', 'Gagal memperbarui data presensi.');
    }

    public function hapus(int $id)
    {
        if ((string) session()->get('role') !== 'admin') {
            return redirect()->to('/presensi')->with('error', 'Akses ditolak.');
        }
        $response = $this->client->delete('presensi/' . $id);
        if (($response['message'] ?? '') === 'Deleted') {
            return redirect()->to('/presensi')->with('success', 'Data presensi berhasil dihapus.');
        }

        return redirect()->to('/presensi')->with('error', 'Gagal menghapus data presensi.');
    }

    public function riwayat()
    {
        return view('laporan_presensi', $this->buildReportContext());
    }

    public function cetak()
    {
        return view('laporan_presensi_cetak', $this->buildReportContext());
    }

    public function manualForm()
    {
        if ((string) session()->get('role') !== 'admin') {
            return redirect()->to('/presensi')->with('error', 'Akses ditolak.');
        }

        return view('absen_manual', $this->buildManualContext());
    }

    public function manual()
    {
        if ((string) session()->get('role') !== 'admin') {
            return redirect()->to('/presensi')->with('error', 'Akses ditolak.');
        }

        $payload = [
            'id_siswa' => (int) $this->request->getPost('id_siswa'),
            'tanggal' => trim((string) $this->request->getPost('tanggal')),
            'status' => strtolower(trim((string) $this->request->getPost('status'))),
            'catatan' => trim((string) $this->request->getPost('catatan')),
        ];

        if ($payload['id_siswa'] <= 0 || ! $this->validDate($payload['tanggal'])) {
            return redirect()->back()->withInput()->with('error', 'Pilih siswa dan tanggal yang valid.');
        }

        if (! in_array($payload['status'], ['sakit', 'izin', 'alpa'], true)) {
            return redirect()->back()->withInput()->with('error', 'Status manual hanya boleh Sakit, Izin, atau Alpa.');
        }

        if ($payload['catatan'] === '') {
            unset($payload['catatan']);
        }

        $response = $this->client->post('presensi', $payload);
        if (isset($response['message']) && ! isset($response['id_presensi'])) {
            return redirect()->back()->withInput()->with('error', $response['message']);
        }

        $kelasFilter = trim((string) $this->request->getPost('kelas_filter'));
        $redirectQuery = $kelasFilter !== '' ? '?' . http_build_query(['kelas' => $kelasFilter]) : '';

        return redirect()->to('/presensi/manual' . $redirectQuery)->with('success', 'Status laporan manual berhasil disimpan.');
    }

    private function buildManualContext(): array
    {
        $kelasFilter = trim((string) $this->request->getGet('kelas'));
        $students = $this->buildStudentRows($this->safeList($this->client->get('siswa')));

        $kelasFromStudents = [];
        foreach ($students as $student) {
            if (($student['kelas'] ?? '-') !== '-') {
                $kelasFromStudents[] = (string) $student['kelas'];
            }
        }

        $kelasOptions = $this->getMasterKelasList($kelasFromStudents);
        if ($kelasFilter !== '' && ! in_array($kelasFilter, $kelasOptions, true)) {
            $kelasOptions = $this->mergeKelasList($kelasOptions, [$kelasFilter]);
        }

        $students = array_values(array_filter($students, static function (array $student) use ($kelasFilter): bool {
            return $kelasFilter === '' || (string) ($student['kelas'] ?? '') === $kelasFilter;
        }));

        return [
            'kelasFilter' => $kelasFilter,
            'kelasOptions' => $kelasOptions,
            'students' => $students,
            'tanggalHariIni' => $this->jakartaNow()->format('Y-m-d'),
        ];
    }

    private function buildReportContext(): array
    {
        $mulai = $this->validDate((string) $this->request->getGet('mulai')) ?: date('Y-m-01');
        $akhir = $this->validDate((string) $this->request->getGet('akhir')) ?: date('Y-m-d');
        if ($mulai > $akhir) {
            [$mulai, $akhir] = [$akhir, $mulai];
        }

        $kelasFilter = trim((string) $this->request->getGet('kelas'));
        $shiftStatusFilter = $this->normalizeShiftStatusFilter($this->request->getGet('shift_status'));
        $role = (string) session()->get('role');
        $kelasWali = trim((string) session()->get('kelas_wali'));
        if ($role === 'guru') {
            $kelasFilter = $kelasWali !== '' ? $kelasWali : '';
        }

        $rows = $this->buildReportRows($this->safeList($this->client->get('presensi')));
        $rows = $this->scopeRowsForRole($rows, $role);
        $students = $this->buildStudentRows($this->safeList($this->client->get('siswa')));
        $students = $this->scopeStudentsForRole($students, $role);

        $kelasFromRows = [];
        foreach ($rows as $row) {
            if (($row['kelas'] ?? '-') !== '-') {
                $kelasFromRows[] = (string) $row['kelas'];
            }
        }
        foreach ($students as $student) {
            if (($student['kelas'] ?? '-') !== '-') {
                $kelasFromRows[] = (string) $student['kelas'];
            }
        }

        if ($role === 'guru') {
            $kelasOptions = $kelasWali !== '' ? [$kelasWali] : [];
        } else {
            $kelasOptions = $this->getMasterKelasList($kelasFromRows);
            if ($kelasFilter !== '' && ! in_array($kelasFilter, $kelasOptions, true)) {
                $kelasOptions = $this->mergeKelasList($kelasOptions, [$kelasFilter]);
            }
        }

        $rows = array_values(array_filter($rows, static function (array $row) use ($mulai, $akhir, $kelasFilter, $shiftStatusFilter): bool {
            if ($row['tanggal'] < $mulai || $row['tanggal'] > $akhir) {
                return false;
            }

            if ($kelasFilter !== '' && $row['kelas'] !== $kelasFilter) {
                return false;
            }

            return $shiftStatusFilter === [] || in_array((string) ($row['shift_status'] ?? ''), $shiftStatusFilter, true);
        }));
        $students = array_values(array_filter($students, static function (array $student) use ($kelasFilter): bool {
            return $kelasFilter === '' || (string) ($student['kelas'] ?? '') === $kelasFilter;
        }));
        $dateColumns = $this->buildDateColumns($mulai, $akhir);
        $matrixRows = $this->buildAttendanceMatrix($students, $rows, $dateColumns);

        $scopeInfo = $role === 'guru'
            ? 'Laporan dibatasi sesuai data presensi pada kelas wali guru.'
            : '';

        return [
            'role' => $role,
            'mulai' => $mulai,
            'akhir' => $akhir,
            'kelasFilter' => $kelasFilter,
            'kelasOptions' => $kelasOptions,
            'shiftStatusFilter' => $shiftStatusFilter,
            'shiftStatusOptions' => $this->shiftStatusOptions(),
            'cetakQuery' => $this->buildCetakQuery($mulai, $akhir, $kelasFilter, $shiftStatusFilter),
            'guruTanpaKelas' => $role === 'guru' && $kelasWali === '',
            'scopeInfo' => $scopeInfo,
            'rows' => $rows,
            'students' => $students,
            'dateColumns' => $dateColumns,
            'matrixRows' => $matrixRows,
            'summaryTotals' => $this->buildSummaryTotals($matrixRows),
        ];
    }

    private function buildReportRows(array $presensi): array
    {
        $rows = [];

        foreach ($presensi as $item) {
            if (! is_array($item)) {
                continue;
            }

            $siswa = is_array($item['siswa'] ?? null) ? $item['siswa'] : [];
            $guru = is_array($item['guru'] ?? null) ? $item['guru'] : [];
            $createdAt = (string) ($item['created_at'] ?? '');
            $tanggal = $this->validDate((string) ($item['tanggal'] ?? '')) ?: substr($createdAt, 0, 10);
            if (! $this->validDate($tanggal)) {
                $tanggal = date('Y-m-d');
            }

            $jam = trim((string) ($item['jam'] ?? ''));
            if ($jam === '' && strlen($createdAt) >= 16) {
                $jam = substr($createdAt, 11, 5);
            }

            $shiftStatus = trim((string) ($item['shift_status'] ?? ''));
            if ($shiftStatus === '') {
                $shiftStatus = 'in_shift';
            }
            $shiftName = trim((string) ($item['shift_name'] ?? ''));

            $rows[] = [
                'id_presensi' => (int) ($item['id_presensi'] ?? 0),
                'id_guru' => (int) ($item['id_guru'] ?? ($guru['id_guru'] ?? 0)),
                'id_siswa' => (int) ($item['id_siswa'] ?? ($siswa['id'] ?? 0)),
                'tanggal' => $tanggal,
                'kelas' => $this->fallback((string) ($item['kelas'] ?? ''), (string) ($siswa['kelas'] ?? ''), '-'),
                'nama_siswa' => $this->fallback((string) ($item['nama_siswa'] ?? ''), (string) ($siswa['nama'] ?? ''), '-'),
                'no_induk' => $this->fallback((string) ($item['no_induk'] ?? ''), (string) ($siswa['no_induk'] ?? ''), '-'),
                'status' => $this->fallback((string) ($item['status'] ?? ''), '', 'hadir'),
                'jam' => $jam !== '' ? $jam : '-',
                'metode' => $this->fallback((string) ($item['metode'] ?? ''), '', '-'),
                'shift_name' => $shiftName !== '' ? $shiftName : $this->shiftStatusLabel($shiftStatus),
                'shift_status' => $shiftStatus,
                'shift_status_label' => $this->shiftStatusLabel($shiftStatus),
                'nama_guru' => $this->fallback((string) ($item['nama_guru'] ?? ''), (string) ($guru['nama'] ?? ''), '-'),
                'catatan' => (string) ($item['catatan'] ?? ''),
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            return strcmp($b['tanggal'] . ' ' . $b['jam'], $a['tanggal'] . ' ' . $a['jam']);
        });

        return $rows;
    }

    private function buildStudentRows(array $siswa): array
    {
        $students = [];

        foreach ($siswa as $item) {
            if (! is_array($item)) {
                continue;
            }

            $students[] = [
                'id_siswa' => (int) ($item['id'] ?? 0),
                'nama_siswa' => $this->fallback((string) ($item['nama'] ?? ''), '', '-'),
                'no_induk' => $this->fallback((string) ($item['no_induk'] ?? ''), '', '-'),
                'kelas' => $this->fallback((string) ($item['kelas'] ?? ''), '', '-'),
            ];
        }

        usort($students, static function (array $a, array $b): int {
            $kelasCompare = strnatcasecmp((string) ($a['kelas'] ?? ''), (string) ($b['kelas'] ?? ''));
            if ($kelasCompare !== 0) {
                return $kelasCompare;
            }

            return strnatcasecmp((string) ($a['nama_siswa'] ?? ''), (string) ($b['nama_siswa'] ?? ''));
        });

        return $students;
    }

    private function scopeRowsForRole(array $rows, string $role): array
    {
        if ($role !== 'guru') {
            return $rows;
        }

        $kelasWali = trim((string) session()->get('kelas_wali'));

        if ($kelasWali === '') {
            return [];
        }

        return array_values(array_filter($rows, static function (array $row) use ($kelasWali): bool {
            return (string) ($row['kelas'] ?? '') === $kelasWali;
        }));
    }

    private function scopeStudentsForRole(array $students, string $role): array
    {
        if ($role !== 'guru') {
            return $students;
        }

        $kelasWali = trim((string) session()->get('kelas_wali'));

        if ($kelasWali === '') {
            return [];
        }

        return array_values(array_filter($students, static function (array $student) use ($kelasWali): bool {
            return (string) ($student['kelas'] ?? '') === $kelasWali;
        }));
    }

    private function buildDateColumns(string $mulai, string $akhir): array
    {
        $columns = [];
        $start = strtotime($mulai);
        $end = strtotime($akhir);

        if ($start === false || $end === false) {
            return $columns;
        }

        for ($date = $start; $date <= $end; $date = strtotime('+1 day', $date)) {
            if ($date === false) {
                break;
            }

            $columns[] = [
                'date' => date('Y-m-d', $date),
                'day' => date('j', $date),
                'label' => date('d', $date),
            ];
        }

        return $columns;
    }

    private function buildAttendanceMatrix(array $students, array $rows, array $dateColumns): array
    {
        $dateSet = [];
        foreach ($dateColumns as $column) {
            $dateSet[(string) ($column['date'] ?? '')] = true;
        }

        $matrix = [];
        foreach ($students as $student) {
            $key = $this->studentKey($student);
            if ($key === '') {
                continue;
            }

            $matrix[$key] = $this->emptyMatrixRow($student, $dateColumns);
        }

        foreach ($rows as $row) {
            $tanggal = (string) ($row['tanggal'] ?? '');
            if ($tanggal === '' || ! isset($dateSet[$tanggal])) {
                continue;
            }

            $key = $this->studentKey($row);
            if ($key === '') {
                continue;
            }

            if (! isset($matrix[$key])) {
                $matrix[$key] = $this->emptyMatrixRow([
                    'id_siswa' => (int) ($row['id_siswa'] ?? 0),
                    'nama_siswa' => (string) ($row['nama_siswa'] ?? '-'),
                    'no_induk' => (string) ($row['no_induk'] ?? '-'),
                    'kelas' => (string) ($row['kelas'] ?? '-'),
                ], $dateColumns);
            }

            $code = $this->statusCode((string) ($row['status'] ?? 'hadir'));
            if ($code === '') {
                continue;
            }

            $current = (string) ($matrix[$key]['cells'][$tanggal] ?? '-');
            $currentSource = (string) (($matrix[$key]['cell_sources'] ?? [])[$tanggal] ?? '');
            $source = (string) ($row['metode'] ?? '');

            if ($source === 'manual_report' || ($currentSource !== 'manual_report' && $this->statusPriority($code) > $this->statusPriority($current))) {
                $matrix[$key]['cells'][$tanggal] = $code;
                $matrix[$key]['cell_sources'][$tanggal] = $source;
            }
        }

        $matrixRows = array_values($matrix);
        foreach ($matrixRows as &$matrixRow) {
            $matrixRow['summary'] = ['H' => 0, 'I' => 0, 'S' => 0, 'A' => 0];
            foreach ($matrixRow['cells'] as $code) {
                if (isset($matrixRow['summary'][$code])) {
                    $matrixRow['summary'][$code]++;
                }
            }
        }
        unset($matrixRow);

        usort($matrixRows, static function (array $a, array $b): int {
            $kelasCompare = strnatcasecmp((string) ($a['kelas'] ?? ''), (string) ($b['kelas'] ?? ''));
            if ($kelasCompare !== 0) {
                return $kelasCompare;
            }

            return strnatcasecmp((string) ($a['nama_siswa'] ?? ''), (string) ($b['nama_siswa'] ?? ''));
        });

        return $matrixRows;
    }

    private function emptyMatrixRow(array $student, array $dateColumns): array
    {
        $cells = [];
        foreach ($dateColumns as $column) {
            $date = (string) ($column['date'] ?? '');
            if ($date !== '') {
                $cells[$date] = '-';
            }
        }

        return [
            'id_siswa' => (int) ($student['id_siswa'] ?? 0),
            'no_induk' => $this->fallback((string) ($student['no_induk'] ?? ''), '', '-'),
            'nama_siswa' => $this->fallback((string) ($student['nama_siswa'] ?? ''), '', '-'),
            'kelas' => $this->fallback((string) ($student['kelas'] ?? ''), '', '-'),
            'cells' => $cells,
            'cell_sources' => array_fill_keys(array_keys($cells), ''),
            'summary' => ['H' => 0, 'I' => 0, 'S' => 0, 'A' => 0],
        ];
    }

    private function dedupeDailyRows(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $key = $this->studentKey($row);
            if ($key === '') {
                $key = 'presensi:' . (int) ($row['id_presensi'] ?? 0);
            }
            $key .= '|' . (string) ($row['tanggal'] ?? '');

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'row' => $row,
                    'count' => 1,
                ];
                continue;
            }

            $groups[$key]['count']++;
            if ($this->dailyRowRank($row) > $this->dailyRowRank($groups[$key]['row'])) {
                $groups[$key]['row'] = $row;
            }
        }

        $deduped = [];
        foreach ($groups as $group) {
            $row = $group['row'];
            $count = (int) $group['count'];
            if ($count > 1) {
                $extra = $count - 1;
                $note = $extra . ' scan tambahan pada hari yang sama.';
                $row['catatan'] = trim((string) ($row['catatan'] ?? ''));
                $row['catatan'] = $row['catatan'] !== '' ? $row['catatan'] . ' ' . $note : $note;
                $row['scan_count'] = $count;
            }
            $deduped[] = $row;
        }

        usort($deduped, static function (array $a, array $b): int {
            return strcmp((string) ($b['tanggal'] ?? '') . ' ' . (string) ($b['jam'] ?? ''), (string) ($a['tanggal'] ?? '') . ' ' . (string) ($a['jam'] ?? ''));
        });

        return $deduped;
    }

    private function dailyRowRank(array $row): int
    {
        $method = (string) ($row['metode'] ?? '');
        $time = strtotime((string) ($row['tanggal'] ?? '') . ' ' . (string) ($row['jam'] ?? '00:00'));
        $timeRank = $time !== false ? min(86400, (int) date('G', $time) * 3600 + (int) date('i', $time) * 60 + (int) date('s', $time)) : 0;

        if ($method === 'manual_report') {
            return 200000 + $timeRank;
        }

        return 100000 + $timeRank;
    }

    private function buildSummaryTotals(array $matrixRows): array
    {
        $totals = ['H' => 0, 'I' => 0, 'S' => 0, 'A' => 0, 'siswa' => count($matrixRows)];

        foreach ($matrixRows as $row) {
            $summary = is_array($row['summary'] ?? null) ? $row['summary'] : [];
            foreach (['H', 'I', 'S', 'A'] as $code) {
                $totals[$code] += (int) ($summary[$code] ?? 0);
            }
        }

        return $totals;
    }

    private function studentKey(array $row): string
    {
        $id = (int) ($row['id_siswa'] ?? 0);
        if ($id > 0) {
            return 'id:' . $id;
        }

        $noInduk = trim((string) ($row['no_induk'] ?? ''));
        if ($noInduk !== '' && $noInduk !== '-') {
            return 'nis:' . strtolower($noInduk);
        }

        $nama = trim((string) ($row['nama_siswa'] ?? ''));
        $kelas = trim((string) ($row['kelas'] ?? ''));
        if ($nama !== '' && $nama !== '-') {
            return 'name:' . strtolower($kelas . '|' . $nama);
        }

        return '';
    }

    private function statusCode(string $status): string
    {
        $status = strtolower(trim($status));

        if (in_array($status, ['hadir', 'h'], true)) {
            return 'H';
        }
        if (in_array($status, ['izin', 'ijin', 'i'], true)) {
            return 'I';
        }
        if (in_array($status, ['sakit', 's'], true)) {
            return 'S';
        }
        if (in_array($status, ['alpa', 'alpha', 'absen', 'tidak hadir', 'a'], true)) {
            return 'A';
        }

        return '';
    }

    private function statusPriority(string $code): int
    {
        return [
            '-' => 0,
            'A' => 1,
            'I' => 2,
            'S' => 3,
            'H' => 4,
        ][$code] ?? 0;
    }

    private function buildJadwalHariIni(array $jadwal, string $hariIni, string $jamSekarang): array
    {
        $rows = [];

        foreach ($jadwal as $item) {
            if (! is_array($item) || ! $this->jadwalMatchesHari($item, $hariIni)) {
                continue;
            }

            $shifts = $item['shifts'] ?? [];
            if (is_string($shifts)) {
                $decoded = json_decode($shifts, true);
                $shifts = is_array($decoded) ? $decoded : [];
            }
            if (! is_array($shifts)) {
                $shifts = [];
            }

            foreach ($shifts as $idx => $shift) {
                if (! is_array($shift)) {
                    continue;
                }

                $masukAwal = (string) ($shift['masuk_awal'] ?? '');
                $masukAkhir = (string) ($shift['masuk_akhir'] ?? '');
                $pulangAwal = (string) ($shift['pulang_awal'] ?? '');
                $pulangAkhir = (string) ($shift['pulang_akhir'] ?? '');

                $shift['nama'] = trim((string) ($shift['nama'] ?? '')) ?: 'Jadwal ' . ($idx + 1);
                $shift['is_active'] = $this->timeInRange($jamSekarang, $masukAwal, $masukAkhir)
                    || $this->timeInRange($jamSekarang, $pulangAwal, $pulangAkhir);
                $shifts[$idx] = $shift;
            }

            $item['shifts'] = $shifts;
            $rows[] = $item;
        }

        return $rows;
    }

    private function timeInRange(string $time, string $start, string $end): bool
    {
        if ($start === '' || $end === '') {
            return false;
        }

        return $time >= substr($start, 0, 5) && $time <= substr($end, 0, 5);
    }

    private function normalizeShiftStatusFilter($raw): array
    {
        $allowed = array_keys($this->shiftStatusOptions());
        $items = is_array($raw) ? $raw : [$raw];
        $statuses = [];

        foreach ($items as $item) {
            $item = trim((string) $item);
            if (in_array($item, $allowed, true) && ! in_array($item, $statuses, true)) {
                $statuses[] = $item;
            }
        }

        return $statuses;
    }

    private function shiftStatusOptions(): array
    {
        return [
            'in_shift' => 'Dalam Jadwal',
            'outside_shift' => 'Di Luar Jadwal',
            'no_schedule' => 'Tanpa Jadwal',
        ];
    }

    private function shiftStatusLabel(string $status): string
    {
        $options = $this->shiftStatusOptions();
        return $options[$status] ?? 'Dalam Shift';
    }

    private function buildCetakQuery(string $mulai, string $akhir, string $kelasFilter, array $shiftStatusFilter): string
    {
        return http_build_query([
            'mulai' => $mulai,
            'akhir' => $akhir,
            'kelas' => $kelasFilter,
            'shift_status' => $shiftStatusFilter,
        ]);
    }

    private function jadwalMatchesHari(array $item, string $hariIni): bool
    {
        $hariList = $item['hari_list'] ?? null;
        if (is_array($hariList)) {
            return in_array($hariIni, $hariList, true);
        }

        $hari = (string) ($item['hari'] ?? '');
        $items = preg_split('/\s*,\s*/', $hari, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return in_array($hariIni, $items, true);
    }

    private function safeList($response): array
    {
        if (! is_array($response)) {
            return [];
        }

        if (array_key_exists('message', $response)) {
            return [];
        }

        return array_values($response);
    }

    private function jakartaNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('Asia/Jakarta'));
    }

    private function validDate(string $date): ?string
    {
        $date = trim($date);
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        return $date;
    }

    private function fallback(string $value, string $fallback, string $default): string
    {
        $value = trim($value);
        if ($value !== '') {
            return $value;
        }

        $fallback = trim($fallback);
        return $fallback !== '' ? $fallback : $default;
    }

    private function hariIndonesia(string $englishDay): string
    {
        $map = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
        ];

        return $map[$englishDay] ?? $englishDay;
    }
}
