import { Head, router, useForm } from "@inertiajs/react";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";

export default function Index({ election, tpsList }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        nama_lokasi: "",
    });

    const tambah = (e) => {
        e.preventDefault();
        post(route("admin.elections.tps.store", election.id), {
            onSuccess: () => reset(),
        });
    };

    const hapus = (tps) => {
        if (!confirm(`Hapus TPS "${tps.nama_lokasi}"?`)) return;
        router.delete(route("admin.tps.destroy", tps.id));
    };

    const generateDeviceToken = (tps) => {
        if (
            !confirm(
                `Generate device token baru untuk "${tps.nama_lokasi}"? Kalau device ini sudah pernah dipasang sebelumnya, token LAMA akan langsung mati.`,
            )
        )
            return;

        // Ini POST yang sengaja tidak redirect balik ke halaman ini — controllernya
        // langsung render halaman "DeviceToken" berisi token plaintext (cuma tampil sekali).
        router.post(route("admin.tps.device-token", tps.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold">TPS — {election.nama}</h2>
            }
        >
            <Head title={`TPS — ${election.nama}`} />

            <div className="py-8 max-w-3xl mx-auto px-4 space-y-6">
                <form
                    onSubmit={tambah}
                    className="bg-white shadow rounded-lg p-4 flex gap-3 items-end"
                >
                    <div className="flex-1">
                        <label className="block text-sm font-medium mb-1">
                            Nama Lokasi TPS
                        </label>
                        <input
                            type="text"
                            className="w-full border rounded-lg px-3 py-2"
                            placeholder="mis. Balai Desa Sukamaju"
                            value={data.nama_lokasi}
                            onChange={(e) =>
                                setData("nama_lokasi", e.target.value)
                            }
                        />
                        {errors.nama_lokasi && (
                            <p className="text-red-600 text-sm mt-1">
                                {errors.nama_lokasi}
                            </p>
                        )}
                    </div>
                    <button
                        type="submit"
                        disabled={processing}
                        className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                    >
                        Tambah TPS
                    </button>
                </form>

                <div className="bg-white shadow rounded-lg divide-y">
                    {tpsList.map((tps) => (
                        <div
                            key={tps.id}
                            className="flex items-center justify-between px-4 py-3"
                        >
                            <div>
                                <p className="font-medium">{tps.nama_lokasi}</p>
                                <p className="text-sm text-gray-500">
                                    Kode:{" "}
                                    <span className="font-mono">
                                        {tps.kode_tps}
                                    </span>{" "}
                                    · {tps.voters_count} DPT ·{" "}
                                    <span
                                        className={
                                            tps.status_koneksi === "online"
                                                ? "text-green-600"
                                                : "text-gray-400"
                                        }
                                    >
                                        {tps.status_koneksi}
                                    </span>
                                </p>
                            </div>
                            <div className="space-x-3 whitespace-nowrap">
                                <button
                                    onClick={() => generateDeviceToken(tps)}
                                    className="text-blue-600 hover:underline text-sm"
                                >
                                    Generate Device Token
                                </button>
                                <button
                                    onClick={() => hapus(tps)}
                                    className="text-red-600 hover:underline text-sm"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    ))}
                    {tpsList.length === 0 && (
                        <p className="px-4 py-6 text-center text-gray-400">
                            Belum ada TPS.
                        </p>
                    )}
                </div>

                <p className="text-xs text-gray-400">
                    Kode TPS ini dipakai untuk mendaftarkan aplikasi Bilik Suara
                    Digital (Electron) ke lokasi yang benar saat setup pertama
                    kali. Device Token dipakai di kolom "Device Token" pada
                    halaman Setup aplikasi Electron TPS ini.
                </p>
            </div>
        </AuthenticatedLayout>
    );
}
