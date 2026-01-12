<!DOCTYPE html>
<html>
<head>
    <title>Jurnal Mengajar</title>
    <style>
        body { font-family: sans-serif; font-size: 11pt; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px double black; padding-bottom: 10px; }
        .header h2, .header h3 { margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid black; padding: 5px; vertical-align: top; }
        th { background: #f0f0f0; }
        .text-center { text-align: center; }
        .signature-section { margin-top: 50px; page-break-inside: avoid; }
        .signature-table { width: 100%; border: none; }
        .signature-table td { border: none; text-align: center; vertical-align: top; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $school->school_name ?? 'NAMA MADRASAH BELUM DISET' }}</h2>
        <div>{{ $school->school_address ?? 'Alamat belum diset' }}</div>
    </div>

    <div class="text-center">
        <h3>JURNAL MENGAJAR GURU</h3>
        <p>Bulan: {{ $monthName }} {{ $year }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">NO</th>
                <th width="15%">HARI/TANGGAL</th>
                <th width="10%">KELAS</th>
                <th width="10%">JAM KE</th>
                <th width="35%">MATERI POKOK/BAHASAN</th>
                <th width="20%">KETERANGAN</th>
                <th width="5%">PARAF</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $no = 1; 
                $groupedActivities = $activities->groupBy(function($item) {
                    return \Carbon\Carbon::parse($item->activity_date)->format('Y-m-d');
                });
            @endphp

            @forelse($groupedActivities as $date => $dailyActivities)
                @foreach($dailyActivities as $index => $activity)
                    <tr>
                        @if($index === 0)
                            <td class="text-center" rowspan="{{ $dailyActivities->count() }}" style="vertical-align: middle;">{{ $no++ }}</td>
                            <td rowspan="{{ $dailyActivities->count() }}" style="vertical-align: middle;">
                                {{ \Carbon\Carbon::parse($activity->activity_date)->translatedFormat('l, j F Y') }}
                            </td>
                        @endif
                        
                        <td class="text-center">{{ $activity->class_name }}</td>
                        <td class="text-center">{{ $activity->period_start }} - {{ $activity->period_end }}</td>
                        <td>{{ $activity->topic }}</td>
                        <td>{{ $activity->student_outcome ?? '-' }}</td>
                        <td></td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada aktivitas mengajar bulan ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td width="50%">
                    Mengetahui,<br>
                    Kepala Madrasah<br><br><br><br>
                    <strong>{{ $school->headmaster_name ?? '.........................' }}</strong><br>
                    NIP. {{ $school->headmaster_nip ?? '................' }}
                </td>
                <td width="50%">
                    {{ $signatureDate }}<br>
                    Guru Mata Pelajaran<br><br><br><br>
                    <strong>{{ $user->name }}</strong><br>
                    NIP. {{ $user->nip }}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
