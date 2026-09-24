import { Head } from '@inertiajs/react';

export default function Receipt({ tps, batchCode, struk, waktu }) {
    return (
        <div className="min-h-screen bg-blue-900 flex items-center justify-center px-4 py-8">
            <Head title="Terima Kasih" />

            <div className="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full text-center space-y-6">
                <div className="text-green-600 text-5xl">✓</div>
                <h1 className="text-2xl font-bold">Suara Anda Tercatat</h1>
                <p className="text-gray-500 text-sm">
                    Terima kasih telah menggunakan hak pilih di {tps.nama_lokasi}.
                </p>

                {/* Struk untuk dicetak — di aplikasi Electron nanti bagian ini yang dikirim ke printer thermal */}
                <div className="border-2 border-dashed rounded-xl p-4 text-left font-mono text-sm space-y-1">
                    <p className="text-center font-semibold mb-2">STRUK AUDIT</p>
                    <p>Kode Batch: {batchCode}</p>
                    <p>Waktu: {waktu}</p>
                    <p>TPS: {tps.nama_lokasi}</p>
                    <hr className="my-2" />
                    {struk.map((s) => (
                        <p key={s.nomor_struk}>No. Struk: {s.nomor_struk}</p>
                    ))}
                    <hr className="my-2" />
                    <p className="text-xs text-gray-400">
                        Simpan/scan QR fisik untuk verifikasi independen jika diminta panitia audit.
                    </p>
                </div>

                <button
                    onClick={() => window.print()}
                    className="w-full py-3 bg-blue-700 text-white rounded-xl hover:bg-blue-800"
                >
                    Cetak Struk
                </button>

                <p className="text-xs text-gray-400">
                    Layar ini akan kembali ke halaman awal secara otomatis. Silakan tinggalkan bilik suara.
                </p>
            </div>
        </div>
    );
}
