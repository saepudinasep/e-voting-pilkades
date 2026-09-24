import { Head, Link, router, useForm } from "@inertiajs/react";
import { useState } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";

const statusBadge = {
    belum: "bg-yellow-100 text-yellow-700",
    terverifikasi: "bg-green-100 text-green-700",
    ditolak: "bg-red-100 text-red-700",
};

export default function Index({
    election,
    voters,
    filterStatus,
    ringkasan,
    tpsOptions,
}) {
    const [rejectingId, setRejectingId] = useState(null);
    const [alasan, setAlasan] = useState("");
    const [tabForm, setTabForm] = useState("manual"); // 'manual' | 'import'

    const manualForm = useForm({
        nik: "",
        nama: "",
        alamat: "",
        tps_id: "",
    });

    const importForm = useForm({
        file: null,
    });

    const submitManual = (e) => {
        e.preventDefault();
        manualForm.post(route("admin.elections.voters.store", election.id), {
            onSuccess: () => manualForm.reset(),
        });
    };

    const submitImport = (e) => {
        e.preventDefault();
        importForm.post(route("admin.elections.voters.import", election.id), {
            forceFormData: true,
            onSuccess: () => importForm.reset(),
        });
    };

    const filterKe = (status) => {
        router.get(
            route("admin.elections.voters.index", election.id),
            status ? { status } : {},
            { preserveState: true },
        );
    };

    const verifikasi = (voter) => {
        router.patch(route("admin.voters.verify", voter.id));
    };

    const kirimTolak = (voter) => {
        router.patch(
            route("admin.voters.reject", voter.id),
            { alasan },
            {
                onSuccess: () => {
                    setRejectingId(null);
                    setAlasan("");
                },
            },
        );
    };

    const gantiTps = (voter, tpsId) => {
        router.patch(route("admin.voters.assign-tps", voter.id), {
            tps_id: tpsId,
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold">DPT — {election.nama}</h2>
            }
        >
            <Head title={`DPT — ${election.nama}`} />

            <div className="py-8 max-w-5xl mx-auto px-4 space-y-6">
                {/* Ringkasan */}
                <div className="grid grid-cols-4 gap-3">
                    {[
                        ["total", "Total DPT", ringkasan.total],
                        [
                            "terverifikasi",
                            "Terverifikasi",
                            ringkasan.terverifikasi,
                        ],
                        ["belum", "Belum Verifikasi", ringkasan.belum],
                        ["ditolak", "Ditolak", ringkasan.ditolak],
                    ].map(([key, label, jumlah]) => (
                        <button
                            key={key}
                            onClick={() =>
                                filterKe(key === "total" ? null : key)
                            }
                            className={`bg-white shadow rounded-lg p-4 text-left hover:ring-2 hover:ring-blue-300 ${
                                filterStatus === key
                                    ? "ring-2 ring-blue-500"
                                    : ""
                            }`}
                        >
                            <p className="text-2xl font-semibold">{jumlah}</p>
                            <p className="text-sm text-gray-500">{label}</p>
                        </button>
                    ))}
                </div>

                {/* Tab: Tambah Manual vs Import File */}
                <div className="bg-white shadow rounded-lg overflow-hidden">
                    <div className="flex border-b">
                        <button
                            onClick={() => setTabForm("manual")}
                            className={`flex-1 px-4 py-3 text-sm font-medium ${
                                tabForm === "manual"
                                    ? "border-b-2 border-blue-600 text-blue-600"
                                    : "text-gray-500"
                            }`}
                        >
                            Tambah Satu-satu
                        </button>
                        <button
                            onClick={() => setTabForm("import")}
                            className={`flex-1 px-4 py-3 text-sm font-medium ${
                                tabForm === "import"
                                    ? "border-b-2 border-blue-600 text-blue-600"
                                    : "text-gray-500"
                            }`}
                        >
                            Import Excel/CSV
                        </button>
                    </div>

                    {tabForm === "manual" ? (
                        <form onSubmit={submitManual} className="p-4 space-y-3">
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-sm font-medium mb-1">
                                        NIK
                                    </label>
                                    <input
                                        type="text"
                                        inputMode="numeric"
                                        maxLength={16}
                                        className="w-full border rounded-lg px-3 py-2 font-mono"
                                        placeholder="16 digit NIK"
                                        value={manualForm.data.nik}
                                        onChange={(e) =>
                                            manualForm.setData(
                                                "nik",
                                                e.target.value,
                                            )
                                        }
                                    />
                                    {manualForm.errors.nik && (
                                        <p className="text-red-600 text-sm mt-1">
                                            {manualForm.errors.nik}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium mb-1">
                                        Nama
                                    </label>
                                    <input
                                        type="text"
                                        className="w-full border rounded-lg px-3 py-2"
                                        value={manualForm.data.nama}
                                        onChange={(e) =>
                                            manualForm.setData(
                                                "nama",
                                                e.target.value,
                                            )
                                        }
                                    />
                                    {manualForm.errors.nama && (
                                        <p className="text-red-600 text-sm mt-1">
                                            {manualForm.errors.nama}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-sm font-medium mb-1">
                                        Alamat (opsional)
                                    </label>
                                    <input
                                        type="text"
                                        className="w-full border rounded-lg px-3 py-2"
                                        value={manualForm.data.alamat}
                                        onChange={(e) =>
                                            manualForm.setData(
                                                "alamat",
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium mb-1">
                                        TPS (opsional)
                                    </label>
                                    <select
                                        className="w-full border rounded-lg px-3 py-2"
                                        value={manualForm.data.tps_id}
                                        onChange={(e) =>
                                            manualForm.setData(
                                                "tps_id",
                                                e.target.value,
                                            )
                                        }
                                    >
                                        <option value="">Belum diatur</option>
                                        {tpsOptions.map((t) => (
                                            <option key={t.id} value={t.id}>
                                                {t.nama_lokasi}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <button
                                type="submit"
                                disabled={manualForm.processing}
                                className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                            >
                                Tambah ke DPT
                            </button>
                        </form>
                    ) : (
                        <form onSubmit={submitImport} className="p-4 space-y-3">
                            <div className="flex items-end gap-3">
                                <div className="flex-1">
                                    <label className="block text-sm font-medium mb-1">
                                        File Excel/CSV
                                    </label>
                                    <input
                                        type="file"
                                        accept=".xlsx,.csv"
                                        onChange={(e) =>
                                            importForm.setData(
                                                "file",
                                                e.target.files[0],
                                            )
                                        }
                                    />
                                    <p className="text-xs text-gray-400 mt-1">
                                        Kolom wajib: nik, nama. Kolom opsional:
                                        alamat, kode_tps.
                                    </p>
                                    {importForm.errors.file && (
                                        <p className="text-red-600 text-sm mt-1">
                                            {importForm.errors.file}
                                        </p>
                                    )}
                                </div>
                                <button
                                    type="submit"
                                    disabled={
                                        importForm.processing ||
                                        !importForm.data.file
                                    }
                                    className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                                >
                                    Upload
                                </button>
                            </div>

                            <a
                                href={route("admin.voters.template")}
                                className="inline-block text-sm text-blue-600 hover:underline"
                            >
                                ⬇ Download Template CSV
                            </a>
                        </form>
                    )}
                </div>

                {/* Tabel voter */}
                <div className="bg-white shadow rounded-lg overflow-hidden">
                    <table className="w-full text-sm text-left">
                        <thead className="bg-gray-50 text-gray-600">
                            <tr>
                                <th className="px-4 py-3">Nama</th>
                                <th className="px-4 py-3">Alamat</th>
                                <th className="px-4 py-3">TPS</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {voters.data.map((v) => (
                                <tr key={v.id} className="border-t align-top">
                                    <td className="px-4 py-3 font-medium">
                                        {v.nama}
                                    </td>
                                    <td className="px-4 py-3 text-gray-500">
                                        {v.alamat ?? "-"}
                                    </td>
                                    <td className="px-4 py-3">
                                        <select
                                            className="border rounded px-2 py-1 text-sm"
                                            value={v.tps_id ?? ""}
                                            onChange={(e) =>
                                                gantiTps(v, e.target.value)
                                            }
                                        >
                                            <option value="">
                                                Belum diatur
                                            </option>
                                            {tpsOptions.map((t) => (
                                                <option key={t.id} value={t.id}>
                                                    {t.nama_lokasi}
                                                </option>
                                            ))}
                                        </select>
                                    </td>
                                    <td className="px-4 py-3">
                                        <span
                                            className={`px-2 py-1 rounded text-xs ${statusBadge[v.status_verifikasi]}`}
                                        >
                                            {v.status_verifikasi}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                        {v.status_verifikasi !==
                                            "terverifikasi" && (
                                            <button
                                                onClick={() => verifikasi(v)}
                                                className="text-green-600 hover:underline"
                                            >
                                                Verifikasi
                                            </button>
                                        )}
                                        {v.status_verifikasi !== "ditolak" && (
                                            <button
                                                onClick={() =>
                                                    setRejectingId(v.id)
                                                }
                                                className="text-red-600 hover:underline"
                                            >
                                                Tolak
                                            </button>
                                        )}

                                        {rejectingId === v.id && (
                                            <div className="mt-2 flex gap-2 justify-end">
                                                <input
                                                    type="text"
                                                    placeholder="Alasan (opsional)"
                                                    className="border rounded px-2 py-1 text-xs w-40"
                                                    value={alasan}
                                                    onChange={(e) =>
                                                        setAlasan(
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <button
                                                    onClick={() =>
                                                        kirimTolak(v)
                                                    }
                                                    className="text-xs bg-red-600 text-white px-2 py-1 rounded"
                                                >
                                                    Kirim
                                                </button>
                                            </div>
                                        )}
                                    </td>
                                </tr>
                            ))}
                            {voters.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-4 py-6 text-center text-gray-400"
                                    >
                                        Belum ada data DPT. Tambah manual atau
                                        import file di atas.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination sederhana */}
                <div className="flex gap-2 justify-center">
                    {voters.links.map((link, i) => (
                        <Link
                            key={i}
                            href={link.url ?? "#"}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                            className={`px-3 py-1 rounded text-sm ${
                                link.active
                                    ? "bg-blue-600 text-white"
                                    : "bg-white text-gray-600"
                            } ${!link.url ? "opacity-40 pointer-events-none" : ""}`}
                        />
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
