import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function DeviceToken({ tps, plainToken }) {
    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold">Device Token — {tps.nama_lokasi}</h2>}>
            <Head title="Device Token" />

            <div className="py-8 max-w-lg mx-auto px-4">
                <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4 text-sm text-yellow-800">
                    Token ini hanya ditampilkan <strong>sekali</strong>. Salin sekarang dan tempel ke
                    kolom "Device Token" di halaman Setup aplikasi Electron TPS ini. Kalau hilang,
                    generate ulang (token lama otomatis nonaktif).
                </div>

                <div className="bg-white shadow rounded-lg p-4">
                    <p className="text-xs text-gray-500 mb-1">Kode TPS</p>
                    <p className="font-mono mb-4">{tps.kode_tps}</p>

                    <p className="text-xs text-gray-500 mb-1">Device Token</p>
                    <textarea
                        readOnly
                        className="w-full border rounded-lg p-3 font-mono text-sm"
                        rows={3}
                        value={plainToken}
                        onClick={(e) => e.target.select()}
                    />
                </div>

                <Link
                    href={route('admin.elections.tps.index', tps.election_id)}
                    className="inline-block mt-4 text-blue-600 hover:underline text-sm"
                >
                    &larr; Kembali ke daftar TPS
                </Link>
            </div>
        </AuthenticatedLayout>
    );
}
