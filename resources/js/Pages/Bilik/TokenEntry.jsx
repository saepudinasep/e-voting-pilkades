import { Head, useForm, usePage } from '@inertiajs/react';

export default function TokenEntry({ tps }) {
    const { props } = usePage();
    const { data, setData, post, processing } = useForm({ token: '' });

    const submit = (e) => {
        e.preventDefault();
        post(route('bilik.masuk.submit', tps.kode_tps));
    };

    return (
        <div className="min-h-screen bg-blue-900 flex items-center justify-center px-4">
            <Head title="Bilik Suara Digital" />

            <div className="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full text-center space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-blue-900">Bilik Suara Digital</h1>
                    <p className="text-gray-500">{tps.nama_lokasi}</p>
                    <p className="text-sm text-gray-400">{tps.election.nama}</p>
                </div>

                {props.flash?.error && (
                    <div className="bg-red-50 text-red-700 rounded-lg p-3 text-sm">
                        {props.flash.error}
                    </div>
                )}

                <form onSubmit={submit} className="space-y-4">
                    <input
                        type="text"
                        autoFocus
                        className="w-full border-2 rounded-xl px-4 py-3 text-center text-lg font-mono tracking-wider"
                        placeholder="Scan atau ketik token"
                        value={data.token}
                        onChange={(e) => setData('token', e.target.value)}
                    />
                    <button
                        type="submit"
                        disabled={processing || !data.token}
                        className="w-full py-3 bg-blue-700 text-white rounded-xl text-lg hover:bg-blue-800 disabled:opacity-50"
                    >
                        Masuk
                    </button>
                </form>

                <p className="text-xs text-gray-400">
                    Belum punya token? Silakan hubungi petugas TPS untuk verifikasi identitas terlebih
                    dahulu.
                </p>
            </div>
        </div>
    );
}
