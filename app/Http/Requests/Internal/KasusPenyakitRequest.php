<?php

namespace App\Http\Requests\Internal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Komoditas;
use App\Models\Penyakit;

class KasusPenyakitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'desa_id' => ['required', 'integer', 'exists:desa,id'],
            'penyakit_id' => ['required', 'integer', 'exists:penyakit,id'],
            'komoditas_id' => ['required', 'integer', 'exists:komoditas,id'],
            'tanggal_laporan' => ['required', 'date'],
            'tahun' => ['required', 'integer', 'between:2020,2035'],
            'triwulan' => ['required', 'integer', 'between:1,4'],
            'kasus_suspek' => ['nullable', 'integer', 'min:0'],
            'kasus_positif' => ['required', 'integer', 'min:0'],
            'kasus_sembuh' => ['nullable', 'integer', 'min:0'],
            'kasus_mati' => ['nullable', 'integer', 'min:0'],
            'potong_bersyarat' => ['nullable', 'integer', 'min:0'],
            'catatan_lapangan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Custom validation rules untuk kerentanan spesies terhadap penyakit PMK & LSD.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $data = $validator->getData();
                $penyakitId = $data['penyakit_id'] ?? null;
                $komoditasId = $data['komoditas_id'] ?? null;

                if ($penyakitId && $komoditasId) {
                    $penyakit = Penyakit::find($penyakitId);
                    $komoditas = Komoditas::find($komoditasId);

                    if (!$penyakit || !$komoditas) {
                        return;
                    }

                    $penyakitKode = strtoupper(trim($penyakit->kode));
                    $komoditasKode = strtoupper(trim($komoditas->kode));

                    // Aturan 1: PMK hanya menjangkiti hewan berkuku belah (Artiodactyla: Sapi, Kambing, Domba)
                    if ($penyakitKode === 'PMK') {
                        $rentanPmk = ['SAPI_POTONG', 'SAPI_PERAH', 'KAMBING', 'DOMBA'];
                        if (!in_array($komoditasKode, $rentanPmk)) {
                            $validator->errors()->add(
                                'komoditas_id',
                                "Wabah PMK (Penyakit Mulut dan Kuku) hanya menyerang hewan berkuku belah (Sapi Potong, Sapi Perah, Kambing, Domba). Komoditas {$komoditas->nama} tidak rentan terhadap PMK."
                            );
                        }
                    }

                    // Aturan 2: LSD (Lumpy Skin Disease) secara biologis HANYA menyerang sapi/kerbau (Bovine)
                    if ($penyakitKode === 'LSD') {
                        $rentanLsd = ['SAPI_POTONG', 'SAPI_PERAH'];
                        if (!in_array($komoditasKode, $rentanLsd)) {
                            $validator->errors()->add(
                                'komoditas_id',
                                "Wabah LSD (Lumpy Skin Disease) secara virologis HANYA menyerang sapi (Sapi Potong dan Sapi Perah). Komoditas {$komoditas->nama} tidak dapat terjangkit LSD."
                            );
                        }
                    }
                }
            }
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'desa_id.required' => 'Desa lokasi wabah wajib dipilih.',
            'penyakit_id.required' => 'Jenis penyakit menular wajib dipilih.',
            'komoditas_id.required' => 'Komoditas ternak yang terdampak wajib dipilih.',
            'kasus_positif.min' => 'Jumlah kasus positif tidak boleh bernilai negatif.',
            'tanggal_laporan.required' => 'Tanggal laporan surveilans wajib diisi.',
        ];
    }
}
