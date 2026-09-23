import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        nama: '',
        wilayah: '',
        tanggal_mulai: '',
        tanggal_selesai: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.elections.store'));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold">Pemilihan Baru</h2>}>
            <Head title="Pemilihan Baru" />

            <div className="py-8 max-w-xl mx-auto px-4">
                <form onSubmit={submit} className="bg-white shadow rounded-lg p-6 space-y-4">
                    <div>
                        <label className="block text-sm font-medium mb-1">Nama Pemilihan</label>
                        <input
                            type="text"
                            className="w-full border rounded-lg px-3 py-2"
                            placeholder="Pemilihan Kepala Desa Sukamaju 2026"
                            value={data.nama}
                            onChange={(e) => setData('nama', e.target.value)}
                        />
                        {errors.nama && <p className="text-red-600 text-sm mt-1">{errors.nama}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium mb-1">Wilayah</label>
                        <input
                            type="text"
                            className="w-full border rounded-lg px-3 py-2"
                            placeholder="Desa Sukamaju, Kec. ..."
                            value={data.wilayah}
                            onChange={(e) => setData('wilayah', e.target.value)}
                        />
                        {errors.wilayah && <p className="text-red-600 text-sm mt-1">{errors.wilayah}</p>}
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium mb-1">Tanggal Mulai</label>
                            <input
                                type="datetime-local"
                                className="w-full border rounded-lg px-3 py-2"
                                value={data.tanggal_mulai}
                                onChange={(e) => setData('tanggal_mulai', e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">Tanggal Selesai</label>
                            <input
                                type="datetime-local"
                                className="w-full border rounded-lg px-3 py-2"
                                value={data.tanggal_selesai}
                                onChange={(e) => setData('tanggal_selesai', e.target.value)}
                            />
                            {errors.tanggal_selesai && (
                                <p className="text-red-600 text-sm mt-1">{errors.tanggal_selesai}</p>
                            )}
                        </div>
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                    >
                        Simpan
                    </button>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
