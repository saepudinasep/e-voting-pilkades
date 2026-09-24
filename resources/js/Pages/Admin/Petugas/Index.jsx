import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Index({ petugas, tpsOptions }) {
    const [mengedit, setMengedit] = useState(null); // id petugas yang lagi diedit, atau null

    const tambahForm = useForm({
        name: '',
        email: '',
        password: '',
        tps_id: '',
    });

    const editForm = useForm({
        name: '',
        email: '',
        password: '',
        tps_id: '',
    });

    const submitTambah = (e) => {
        e.preventDefault();
        tambahForm.post(route('admin.petugas.store'), {
            onSuccess: () => tambahForm.reset(),
        });
    };

    const mulaiEdit = (p) => {
        setMengedit(p.id);
        editForm.setData({
            name: p.name,
            email: p.email,
            password: '',
            tps_id: p.tps_id ?? '',
        });
    };

    const submitEdit = (e, id) => {
        e.preventDefault();
        editForm.patch(route('admin.petugas.update', id), {
            onSuccess: () => setMengedit(null),
        });
    };

    const hapus = (p) => {
        if (!confirm(`Hapus petugas "${p.name}"? Akun ini tidak akan bisa login lagi.`)) return;
        router.delete(route('admin.petugas.destroy', p.id));
    };

    // Ganti TPS langsung dari dropdown di tabel, tanpa masuk mode edit penuh
    const gantiTpsCepat = (p, tpsId) => {
        router.patch(route('admin.petugas.update', p.id), {
            name: p.name,
            email: p.email,
            tps_id: tpsId || null,
        });
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold">Kelola Petugas TPS</h2>}>
            <Head title="Kelola Petugas TPS" />

            <div className="py-8 max-w-4xl mx-auto px-4 space-y-6">
                {/* Form tambah petugas baru */}
                <form onSubmit={submitTambah} className="bg-white shadow rounded-lg p-4 space-y-3">
                    <h3 className="font-medium">Tambah Petugas Baru</h3>
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-sm font-medium mb-1">Nama</label>
                            <input
                                type="text"
                                className="w-full border rounded-lg px-3 py-2"
                                value={tambahForm.data.name}
                                onChange={(e) => tambahForm.setData('name', e.target.value)}
                            />
                            {tambahForm.errors.name && (
                                <p className="text-red-600 text-sm mt-1">{tambahForm.errors.name}</p>
                            )}
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">Email</label>
                            <input
                                type="email"
                                className="w-full border rounded-lg px-3 py-2"
                                value={tambahForm.data.email}
                                onChange={(e) => tambahForm.setData('email', e.target.value)}
                            />
                            {tambahForm.errors.email && (
                                <p className="text-red-600 text-sm mt-1">{tambahForm.errors.email}</p>
                            )}
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">Password</label>
                            <input
                                type="password"
                                className="w-full border rounded-lg px-3 py-2"
                                value={tambahForm.data.password}
                                onChange={(e) => tambahForm.setData('password', e.target.value)}
                            />
                            {tambahForm.errors.password && (
                                <p className="text-red-600 text-sm mt-1">{tambahForm.errors.password}</p>
                            )}
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">Tempatkan di TPS (opsional)</label>
                            <select
                                className="w-full border rounded-lg px-3 py-2"
                                value={tambahForm.data.tps_id}
                                onChange={(e) => tambahForm.setData('tps_id', e.target.value)}
                            >
                                <option value="">Belum ditempatkan</option>
                                {tpsOptions.map((t) => (
                                    <option key={t.id} value={t.id}>
                                        {t.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <button
                        type="submit"
                        disabled={tambahForm.processing}
                        className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                    >
                        Tambah Petugas
                    </button>
                </form>

                {/* Daftar petugas */}
                <div className="bg-white shadow rounded-lg overflow-hidden">
                    <table className="w-full text-sm text-left">
                        <thead className="bg-gray-50 text-gray-600">
                            <tr>
                                <th className="px-4 py-3">Nama</th>
                                <th className="px-4 py-3">Email</th>
                                <th className="px-4 py-3">TPS</th>
                                <th className="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {petugas.map((p) => (
                                <tr key={p.id} className="border-t align-top">
                                    {mengedit === p.id ? (
                                        <td colSpan={4} className="px-4 py-3">
                                            <form
                                                onSubmit={(e) => submitEdit(e, p.id)}
                                                className="grid grid-cols-2 gap-3 bg-gray-50 p-3 rounded-lg"
                                            >
                                                <input
                                                    type="text"
                                                    className="border rounded px-2 py-1"
                                                    value={editForm.data.name}
                                                    onChange={(e) => editForm.setData('name', e.target.value)}
                                                    placeholder="Nama"
                                                />
                                                <input
                                                    type="email"
                                                    className="border rounded px-2 py-1"
                                                    value={editForm.data.email}
                                                    onChange={(e) => editForm.setData('email', e.target.value)}
                                                    placeholder="Email"
                                                />
                                                <input
                                                    type="password"
                                                    className="border rounded px-2 py-1"
                                                    value={editForm.data.password}
                                                    onChange={(e) => editForm.setData('password', e.target.value)}
                                                    placeholder="Password baru (kosongkan jika tidak ganti)"
                                                />
                                                <select
                                                    className="border rounded px-2 py-1"
                                                    value={editForm.data.tps_id}
                                                    onChange={(e) => editForm.setData('tps_id', e.target.value)}
                                                >
                                                    <option value="">Belum ditempatkan</option>
                                                    {tpsOptions.map((t) => (
                                                        <option key={t.id} value={t.id}>
                                                            {t.label}
                                                        </option>
                                                    ))}
                                                </select>
                                                <div className="col-span-2 flex gap-2 justify-end">
                                                    <button
                                                        type="button"
                                                        onClick={() => setMengedit(null)}
                                                        className="text-sm text-gray-500"
                                                    >
                                                        Batal
                                                    </button>
                                                    <button
                                                        type="submit"
                                                        className="text-sm bg-blue-600 text-white px-3 py-1 rounded"
                                                    >
                                                        Simpan
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                    ) : (
                                        <>
                                            <td className="px-4 py-3 font-medium">{p.name}</td>
                                            <td className="px-4 py-3 text-gray-500">{p.email}</td>
                                            <td className="px-4 py-3">
                                                <select
                                                    className="border rounded px-2 py-1 text-sm"
                                                    value={p.tps_id ?? ''}
                                                    onChange={(e) => gantiTpsCepat(p, e.target.value)}
                                                >
                                                    <option value="">Belum ditempatkan</option>
                                                    {tpsOptions.map((t) => (
                                                        <option key={t.id} value={t.id}>
                                                            {t.label}
                                                        </option>
                                                    ))}
                                                </select>
                                            </td>
                                            <td className="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                                <button
                                                    onClick={() => mulaiEdit(p)}
                                                    className="text-gray-600 hover:underline"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    onClick={() => hapus(p)}
                                                    className="text-red-600 hover:underline"
                                                >
                                                    Hapus
                                                </button>
                                            </td>
                                        </>
                                    )}
                                </tr>
                            ))}
                            {petugas.length === 0 && (
                                <tr>
                                    <td colSpan={4} className="px-4 py-6 text-center text-gray-400">
                                        Belum ada petugas. Tambahkan lewat form di atas.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
