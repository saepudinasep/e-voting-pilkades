import { Head, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Index({ position, candidates }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        nama: '',
        nomor_urut: candidates.length + 1,
        visi_misi: '',
        foto: null,
    });

    const tambah = (e) => {
        e.preventDefault();
        post(route('admin.positions.candidates.store', position.id), {
            forceFormData: true,
            onSuccess: () => reset(),
        });
    };

    const hapus = (candidate) => {
        if (!confirm(`Hapus kandidat "${candidate.nama}"?`)) return;
        router.delete(route('admin.candidates.destroy', candidate.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold">
                    Kandidat — {position.nama} ({position.election.nama})
                </h2>
            }
        >
            <Head title={`Kandidat — ${position.nama}`} />

            <div className="py-8 max-w-3xl mx-auto px-4 space-y-6">
                <form onSubmit={tambah} className="bg-white shadow rounded-lg p-4 space-y-3">
                    <div className="grid grid-cols-3 gap-3">
                        <div className="col-span-2">
                            <label className="block text-sm font-medium mb-1">Nama Kandidat</label>
                            <input
                                type="text"
                                className="w-full border rounded-lg px-3 py-2"
                                value={data.nama}
                                onChange={(e) => setData('nama', e.target.value)}
                            />
                            {errors.nama && <p className="text-red-600 text-sm mt-1">{errors.nama}</p>}
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">Nomor Urut</label>
                            <input
                                type="number"
                                min={1}
                                className="w-full border rounded-lg px-3 py-2"
                                value={data.nomor_urut}
                                onChange={(e) => setData('nomor_urut', e.target.value)}
                            />
                            {errors.nomor_urut && (
                                <p className="text-red-600 text-sm mt-1">{errors.nomor_urut}</p>
                            )}
                        </div>
                    </div>

                    <div>
                        <label className="block text-sm font-medium mb-1">Visi & Misi</label>
                        <textarea
                            className="w-full border rounded-lg px-3 py-2"
                            rows={3}
                            value={data.visi_misi}
                            onChange={(e) => setData('visi_misi', e.target.value)}
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-medium mb-1">Foto</label>
                        <input
                            type="file"
                            accept="image/*"
                            onChange={(e) => setData('foto', e.target.files[0])}
                        />
                        {errors.foto && <p className="text-red-600 text-sm mt-1">{errors.foto}</p>}
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                    >
                        Tambah Kandidat
                    </button>
                </form>

                <div className="grid grid-cols-2 gap-4">
                    {candidates.map((c) => (
                        <div key={c.id} className="bg-white shadow rounded-lg p-4">
                            {c.foto && (
                                <img
                                    src={`/storage/${c.foto}`}
                                    alt={c.nama}
                                    className="w-full h-40 object-cover rounded-lg mb-3"
                                />
                            )}
                            <p className="font-medium">
                                No. {c.nomor_urut} — {c.nama}
                            </p>
                            {c.visi_misi && (
                                <p className="text-sm text-gray-500 mt-1 line-clamp-3">{c.visi_misi}</p>
                            )}
                            <button
                                onClick={() => hapus(c)}
                                className="text-red-600 hover:underline text-sm mt-3"
                            >
                                Hapus
                            </button>
                        </div>
                    ))}
                    {candidates.length === 0 && (
                        <p className="col-span-2 text-center text-gray-400 py-6">Belum ada kandidat.</p>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
