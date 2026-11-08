
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Logbook PKL</title>

    <style>
        @page {
            margin: 25px;
        }

        body {
            font-family: sans-serif;
            font-size: 9px;
        }

        h2, p {
            text-align: center;
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #333;
            padding: 5px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #e5e5e5;
            text-align: center;
        }

        .empty {
            text-align: center;
            padding: 15px;
        }

        .description {
            max-width: 180px;
            word-wrap: break-word;
        }
    </style>
</head>
<body>

    <h2>LAPORAN LOGBOOK PKL</h2>
    <p>NEXORA - Internship Logbook Platform</p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>NIM</th>
                <th>Nama Mahasiswa</th>
                <th>Instansi</th>
                <th>Dosen Pembimbing</th>
                <th>Tanggal</th>
                <th>Deskripsi Kegiatan</th>
                <th>Status</th>
                <th>Catatan Dosen</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($logbooks as $index => $logbook)
                <tr>
                    <td>{{ $logbook->id }}</td>
                    <td>{{ $logbook->pkl->mahasiswa->nim ?? '-' }}</td>
                    <td>{{ $logbook->pkl->mahasiswa->name ?? '-' }}</td>
                    <td>{{ $logbook->pkl->nama_instansi ?? '-' }}</td>
                    <td>{{ $logbook->pkl->dosen->name ?? '-' }}</td>
                    <td>
                        {{ $logbook->tanggal_kegiatan?->format('d-m-Y') ?? '-' }}
                    </td>
                    <td class="description">
                        {{ $logbook->deskripsi_kegiatan ?? '-' }}
                    </td>
                    <td>{{ $logbook->status }}</td>
                    <td>{{ $logbook->catatan_dosen ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="empty">
                        Belum ada data logbook untuk ditampilkan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
