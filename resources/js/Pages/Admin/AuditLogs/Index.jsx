import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Index({ logs, filterAksi, filterUserId, daftarAksi, daftarUser }) {
    const filter = (key, value) => {
        router.get(
            route('admin.audit-logs.index'),
            {
                aksi: key === 'aksi' ? value : filterAksi,
                user_id: key === 'user_id' ? value : filterUserId,
            },
            { preserveState: true }
        );
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold">Audit Log</h2>}>
            <Head title="Audit Log" />

            <div className="py-8 max-w-5xl mx-auto px-4 space-y-4">
                <div className="flex gap-3">
                    <select
                        className="border rounded-lg px-3 py-2 text-sm"
                        value={filterAksi ?? ''}
                        onChange={(e) => filter('aksi', e.target.value || null)}
                    >
                        <option value="">Semua aksi</option>
                        {daftarAksi.map((a) => (
                            <option key={a} value={a}>
                                {a}
                            </option>
                        ))}
                    </select>

                    <select
                        className="border rounded-lg px-3 py-2 text-sm"
                        value={filterUserId ?? ''}
                        onChange={(e) => filter('user_id', e.target.value || null)}
                    >
                        <option value="">Semua user</option>
                        {daftarUser.map((u) => (
                            <option key={u.id} value={u.id}>
                                {u.name}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="bg-white shadow rounded-lg overflow-hidden">
                    <table className="w-full text-sm text-left">
                        <thead className="bg-gray-50 text-gray-600">
                            <tr>
                                <th className="px-4 py-3">Waktu</th>
                                <th className="px-4 py-3">User</th>
                                <th className="px-4 py-3">Aksi</th>
                                <th className="px-4 py-3">Tabel</th>
                                <th className="px-4 py-3">IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            {logs.data.map((log) => (
                                <tr key={log.id} className="border-t align-top">
                                    <td className="px-4 py-3 whitespace-nowrap text-gray-500">
                                        {new Date(log.waktu).toLocaleString('id-ID')}
                                    </td>
                                    <td className="px-4 py-3">
                                        {log.user ? `${log.user.name} (${log.user.role})` : 'Sistem'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <span className="px-2 py-1 rounded bg-gray-100 text-xs font-mono">
                                            {log.aksi}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-gray-500">
                                        {log.tabel_terkait ? `${log.tabel_terkait}#${log.record_id}` : '-'}
                                    </td>
                                    <td className="px-4 py-3 text-gray-400 font-mono text-xs">
                                        {log.ip_address}
                                    </td>
                                </tr>
                            ))}
                            {logs.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-4 py-6 text-center text-gray-400">
                                        Tidak ada log yang cocok dengan filter ini.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="flex gap-2 justify-center">
                    {logs.links.map((link, i) => (
                        <Link
                            key={i}
                            href={link.url ?? '#'}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                            className={`px-3 py-1 rounded text-sm ${
                                link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-600'
                            } ${!link.url ? 'opacity-40 pointer-events-none' : ''}`}
                        />
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
