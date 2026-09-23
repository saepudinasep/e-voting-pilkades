import { Head, Link, router } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";

const statusBadge = {
    draft: "bg-gray-200 text-gray-700",
    berjalan: "bg-green-100 text-green-700",
    selesai: "bg-blue-100 text-blue-700",
};

export default function Index({ elections }) {
    const hapus = (election) => {
        if (!confirm(`Hapus pemilihan "${election.nama}"?`)) return;
        router.delete(route("admin.elections.destroy", election.id));
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold">Kelola Pemilihan</h2>}
        >
            <Head title="Kelola Pemilihan" />

            <div className="py-8 max-w-5xl mx-auto px-4">
                <div className="flex justify-end mb-4">
                    <Link
                        href={route("admin.elections.create")}
                        className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        + Pemilihan Baru
                    </Link>
                </div>

                <div className="bg-white shadow rounded-lg overflow-hidden">
                    <table className="w-full text-sm text-left">
                        <thead className="bg-gray-50 text-gray-600">
                            <tr>
                                <th className="px-4 py-3">Nama</th>
                                <th className="px-4 py-3">Wilayah</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3">Posisi</th>
                                <th className="px-4 py-3">DPT</th>
                                <th className="px-4 py-3">TPS</th>
                                <th className="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {elections.data.map((el) => (
                                <tr key={el.id} className="border-t">
                                    <td className="px-4 py-3 font-medium">
                                        {el.nama}
                                    </td>
                                    <td className="px-4 py-3">{el.wilayah}</td>
                                    <td className="px-4 py-3">
                                        <span
                                            className={`px-2 py-1 rounded text-xs ${statusBadge[el.status]}`}
                                        >
                                            {el.status}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3">
                                        <Link
                                            href={route(
                                                "admin.elections.positions.index",
                                                el.id,
                                            )}
                                            className="text-blue-600 hover:underline"
                                        >
                                            {el.positions_count} posisi
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3">
                                        <Link
                                            href={route(
                                                "admin.elections.voters.index",
                                                el.id,
                                            )}
                                            className="text-blue-600 hover:underline"
                                        >
                                            {el.voters_count} dpt
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3">
                                        <Link
                                            href={route(
                                                "admin.elections.tps.index",
                                                el.id,
                                            )}
                                            className="text-blue-600 hover:underline"
                                        >
                                            {el.tps_list_count} TPS
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3 text-right space-x-2">
                                        <Link
                                            href={route(
                                                "admin.elections.edit",
                                                el.id,
                                            )}
                                            className="text-gray-600 hover:underline"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            onClick={() => hapus(el)}
                                            className="text-red-600 hover:underline"
                                        >
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            ))}
                            {elections.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="px-4 py-6 text-center text-gray-400"
                                    >
                                        Belum ada pemilihan. Buat yang pertama.
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
