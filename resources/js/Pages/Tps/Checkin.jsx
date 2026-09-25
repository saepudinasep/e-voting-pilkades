import { Head, router, useForm } from "@inertiajs/react";
import { QRCodeSVG } from "qrcode.react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";

export default function Checkin({ tps, voter, nikDicari, error, tokenBaru }) {
    const { data, setData } = useForm({ nik: nikDicari ?? "" });

    const cari = (e) => {
        e.preventDefault();
        router.get(route("tps.checkin"), { nik: data.nik });
    };

    const buatToken = () => {
        router.post(route("tps.checkin.token", voter.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold">
                    Check-in Pemilih — {tps.nama_lokasi} ({tps.election.nama})
                </h2>
            }
        >
            <Head title="Check-in Pemilih" />

            <div className="py-8 max-w-lg mx-auto px-4 space-y-6">
                <form
                    onSubmit={cari}
                    className="bg-white shadow rounded-lg p-4 flex gap-3 items-end"
                >
                    <div className="flex-1">
                        <label className="block text-sm font-medium mb-1">
                            NIK Pemilih
                        </label>
                        <input
                            type="text"
                            inputMode="numeric"
                            maxLength={16}
                            className="w-full border rounded-lg px-3 py-2 font-mono"
                            placeholder="16 digit NIK"
                            value={data.nik}
                            onChange={(e) => setData("nik", e.target.value)}
                        />
                    </div>
                    <button
                        type="submit"
                        className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        Cari
                    </button>
                </form>

                {error && (
                    <div className="bg-red-50 text-red-700 rounded-lg p-4 text-sm">
                        {error}
                    </div>
                )}

                {voter && !tokenBaru && (
                    <div className="bg-white shadow rounded-lg p-4 space-y-3">
                        <div>
                            <p className="font-medium text-lg">{voter.nama}</p>
                            <p className="text-sm text-gray-500">
                                {voter.alamat}
                            </p>
                            <p className="text-sm mt-1">
                                Status:{" "}
                                <span
                                    className={
                                        voter.status_verifikasi ===
                                        "terverifikasi"
                                            ? "text-green-600"
                                            : voter.status_verifikasi ===
                                                "ditolak"
                                              ? "text-red-600"
                                              : "text-yellow-600"
                                    }
                                >
                                    {voter.status_verifikasi}
                                </span>
                            </p>
                        </div>

                        {voter.status_verifikasi === "ditolak" ? (
                            <p className="text-red-600 text-sm">
                                Pemilih ini ditandai ditolak, tidak bisa diberi
                                token voting.
                            </p>
                        ) : (
                            <button
                                onClick={buatToken}
                                className="w-full py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                            >
                                Cocokkan Identitas &amp; Buat Token Voting
                            </button>
                        )}
                    </div>
                )}

                {tokenBaru && (
                    <div className="bg-white shadow rounded-lg p-6 text-center space-y-4">
                        <p className="font-medium">
                            Token untuk{" "}
                            <span className="text-blue-600">{voter.nama}</span>
                        </p>
                        <div className="flex justify-center">
                            <QRCodeSVG value={tokenBaru.plain} size={220} />
                        </div>

                        {/* Fallback kalau belum ada scanner QR fisik di Bilik Suara —
                            token bisa di-copy manual dan diketik/paste di field token. */}
                        <div>
                            <p className="text-xs text-gray-400 mb-1">
                                Belum ada scanner? Salin token ini manual:
                            </p>
                            <input
                                readOnly
                                value={tokenBaru.plain}
                                onClick={(e) => e.target.select()}
                                className="w-full border rounded-lg px-3 py-2 text-center font-mono text-sm bg-gray-50"
                            />
                        </div>

                        <p className="text-xs text-gray-400">
                            Berlaku sampai{" "}
                            {new Date(tokenBaru.kedaluwarsa).toLocaleTimeString(
                                "id-ID",
                            )}
                            . Kalau sudah ada scanner fisik di Bilik Suara,
                            cukup scan QR di atas.
                        </p>
                        <button
                            onClick={() => router.get(route("tps.checkin"))}
                            className="text-sm text-gray-500 hover:underline"
                        >
                            Selesai, kembali ke pencarian
                        </button>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
