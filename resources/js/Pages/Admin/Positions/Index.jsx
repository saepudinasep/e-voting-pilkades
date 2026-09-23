import { Head, Link, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Index({ election, positions }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        nama: '',
        urutan: positions.length,
    });

    const tambah = (e) => {
        e.preventDefault();
        post(route('admin.elections.positions.store', election.id), {
            onSuccess: () => reset(),
        });
    };

    const hapus = (position) => {
        if (!confirm(`Hapus posisi "${position.nama}"?`)) return;
        router.delete(route('admin.positions.destroy', position.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold">
                    Posisi — {election.nama}
                </h2>
            }
        >
            <Head title={`Posisi — ${election.nama}`} />

            <div className="py-8 max-w-3xl mx-auto px-4 space-y-6">
                <form onSubmit={tambah} className="bg-white shadow rounded-lg p-4 flex gap-3 items-end">
                    <div className="flex-1">
                        <label className="block text-sm font-medium mb-1">Nama Posisi</label>
                        <input
                            type="text"
                            className="w-full border rounded-lg px-3 py-2"
                            placeholder="mis. Kepala Desa, Ketua RW 02"
                            value={data.nama}
                            onChange={(e) => setData('nama', e.target.value)}
                        />
                        {errors.nama && <p className="text-red-600 text-sm mt-1">{errors.nama}</p>}
                    </div>
                    <button
                        type="submit"
                        disabled={processing}
                        className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                    >
                        Tambah
                    </button>
                </form>

                <div className="bg-white shadow rounded-lg divide-y">
                    {positions.map((pos) => (
                        <div key={pos.id} className="flex items-center justify-between px-4 py-3">
                            <div>
                                <p className="font-medium">{pos.nama}</p>
                                <p className="text-sm text-gray-500">{pos.candidates_count} kandidat</p>
                            </div>
                            <div className="space-x-3">
                                <Link
                                    href={route('admin.positions.candidates.index', pos.id)}
                                    className="text-blue-600 hover:underline text-sm"
                                >
                                    Kelola Kandidat
                                </Link>
                                <button
                                    onClick={() => hapus(pos)}
                                    className="text-red-600 hover:underline text-sm"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    ))}
                    {positions.length === 0 && (
                        <p className="px-4 py-6 text-center text-gray-400">Belum ada posisi.</p>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
