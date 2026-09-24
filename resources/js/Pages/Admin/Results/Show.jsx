import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Show({ election, positions: initialPositions, ringkasanTps }) {
    const [positions, setPositions] = useState(initialPositions);

    // Polling ringan tiap 10 detik — cukup ambil jumlah suara, bukan reload seluruh halaman.
    useEffect(() => {
        const interval = setInterval(async () => {
            try {
                const res = await fetch(route('admin.elections.results.poll', election.id));
                const json = await res.json();

                setPositions((prev) =>
                    prev.map((pos) => {
                        const update = json.positions.find((p) => p.id === pos.id);
                        if (!update) return pos;

                        const totalSuara = update.kandidat.reduce((sum, k) => sum + k.suara, 0);

                        return {
                            ...pos,
                            total_suara: totalSuara,
                            kandidat: pos.kandidat.map((k) => {
                                const kUpdate = update.kandidat.find((u) => u.id === k.id);
                                const suara = kUpdate ? kUpdate.suara : k.suara;
                                return {
                                    ...k,
                                    suara,
                                    persentase: totalSuara > 0 ? Math.round((suara / totalSuara) * 1000) / 10 : 0,
                                };
                            }),
                        };
                    })
                );
            } catch (e) {
                // Diamkan saja — polling berikutnya akan coba lagi. Tidak perlu ganggu tampilan.
            }
        }, 10000);

        return () => clearInterval(interval);
    }, [election.id]);

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold">Hasil — {election.nama}</h2>}
        >
            <Head title={`Hasil — ${election.nama}`} />

            <div className="py-8 max-w-4xl mx-auto px-4 space-y-8">
                <div className="bg-white shadow rounded-lg p-4 flex items-center justify-between">
                    <p className="text-sm text-gray-500">
                        TPS sudah melapor:{' '}
                        <span className="font-semibold text-gray-800">
                            {ringkasanTps.sudah_lapor} / {ringkasanTps.total}
                        </span>
                    </p>
                    <p className="text-xs text-gray-400">Diperbarui otomatis tiap 10 detik</p>
                </div>

                {positions.map((position) => (
                    <div key={position.id} className="bg-white shadow rounded-lg p-6">
                        <div className="flex justify-between items-baseline mb-4">
                            <h3 className="text-lg font-semibold">{position.nama}</h3>
                            <span className="text-sm text-gray-400">
                                Total suara: {position.total_suara}
                            </span>
                        </div>

                        <ResponsiveContainer width="100%" height={220}>
                            <BarChart data={position.kandidat}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="nama" tick={{ fontSize: 12 }} />
                                <YAxis allowDecimals={false} />
                                <Tooltip formatter={(value) => [`${value} suara`, 'Suara']} />
                                <Bar dataKey="suara" fill="#2563eb" radius={[6, 6, 0, 0]} />
                            </BarChart>
                        </ResponsiveContainer>

                        <div className="mt-4 space-y-2">
                            {position.kandidat.map((k) => (
                                <div key={k.id} className="flex items-center gap-3 text-sm">
                                    <span className="w-40 truncate">
                                        No. {k.nomor_urut} — {k.nama}
                                    </span>
                                    <div className="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
                                        <div
                                            className="bg-blue-600 h-2"
                                            style={{ width: `${k.persentase}%` }}
                                        />
                                    </div>
                                    <span className="w-20 text-right text-gray-500">
                                        {k.suara} ({k.persentase}%)
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        </AuthenticatedLayout>
    );
}
