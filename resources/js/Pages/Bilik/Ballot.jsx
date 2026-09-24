import { Head, useForm } from '@inertiajs/react';
import { useMemo } from 'react';

export default function Ballot({ tps, positions }) {
    const { data, setData, post, processing } = useForm({
        pilihan: positions.map((p) => ({ position_id: p.id, candidate_id: null })),
    });

    const semuaTerisi = useMemo(
        () => data.pilihan.every((p) => p.candidate_id !== null),
        [data.pilihan]
    );

    const pilih = (positionId, candidateId) => {
        setData(
            'pilihan',
            data.pilihan.map((p) =>
                p.position_id === positionId ? { ...p, candidate_id: candidateId } : p
            )
        );
    };

    const submit = () => {
        if (!semuaTerisi) return;
        if (!confirm('Pilihan tidak bisa diubah setelah dikirim. Kirim sekarang?')) return;
        post(route('bilik.submit', tps.kode_tps));
    };

    return (
        <div className="min-h-screen bg-blue-900 py-8 px-4">
            <Head title="Pilih Kandidat" />

            <div className="max-w-3xl mx-auto space-y-8">
                <h1 className="text-white text-2xl font-bold text-center">
                    Surat Suara Digital — {tps.election.nama}
                </h1>

                {positions.map((position) => {
                    const dipilih = data.pilihan.find((p) => p.position_id === position.id)?.candidate_id;

                    return (
                        <div key={position.id} className="bg-white rounded-2xl shadow-xl p-6">
                            <h2 className="text-lg font-semibold mb-4">{position.nama}</h2>
                            <div className="grid grid-cols-2 gap-4">
                                {position.candidates.map((c) => (
                                    <button
                                        key={c.id}
                                        onClick={() => pilih(position.id, c.id)}
                                        className={`border-2 rounded-xl p-4 text-left transition ${
                                            dipilih === c.id
                                                ? 'border-blue-600 bg-blue-50'
                                                : 'border-gray-200 hover:border-blue-300'
                                        }`}
                                    >
                                        {c.foto && (
                                            <img
                                                src={`/storage/${c.foto}`}
                                                alt={c.nama}
                                                className="w-full h-32 object-cover rounded-lg mb-2"
                                            />
                                        )}
                                        <p className="font-medium">
                                            No. {c.nomor_urut} — {c.nama}
                                        </p>
                                    </button>
                                ))}
                            </div>
                        </div>
                    );
                })}

                <button
                    onClick={submit}
                    disabled={!semuaTerisi || processing}
                    className="w-full py-4 bg-green-600 text-white text-lg font-semibold rounded-xl hover:bg-green-700 disabled:opacity-40"
                >
                    {semuaTerisi ? 'Kirim Pilihan' : 'Pilih kandidat di semua posisi dulu'}
                </button>
            </div>
        </div>
    );
}
