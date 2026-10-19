<?php

namespace App\Http\Requests\Internal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Komoditas;
use App\Models\KategoriProduksi;

class ProduksiTernakRequest extends FormRequest
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
            'komoditas_id' => ['required', 'integer', 'exists:komoditas,id'],
            'kategori_produksi_id' => ['required', 'integer', 'exists:kategori_produksi,id'],
            'tahun' => ['required', 'integer', 'between:2020,2035'],
            'triwulan' => ['required', 'integer', 'between:1,4'],
            'jumlah_produksi' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Custom validation rules untuk relasi biologis hasil produksi dan komoditas.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $data = $validator->getData();
                $komoditasId = $data['komoditas_id'] ?? null;
                $kategoriId = $data['kategori_produksi_id'] ?? null;

                if ($komoditasId && $kategoriId) {
                    $komoditas = Komoditas::find($komoditasId);
                    $kategori = KategoriProduksi::find($kategoriId);

                    if (!$komoditas || !$kategori) {
                        return;
                    }

                    $kategoriNama = strtoupper(trim($kategori->nama));
                    $komoditasKode = strtoupper(trim($komoditas->kode));

                    // Aturan 1: Daging hanya valid untuk SAPI_POTONG, SAPI_PERAH, KAMBING, DOMBA, AYAM_POTONG, AYAM_TELUR
                    if ($kategoriNama === 'DAGING') {
                        $validDaging = ['SAPI_POTONG', 'SAPI_PERAH', 'KAMBING', 'DOMBA', 'AYAM_POTONG', 'AYAM_TELUR'];
                        if (!in_array($komoditasKode, $validDaging)) {
                            $validator->errors()->add(
                                'komoditas_id',
                                "Kategori Daging tidak valid untuk komoditas {$komoditas->nama}. Hanya berlaku untuk sapi, kambing, domba, dan ayam."
                            );
                        }
                    }

                    // Aturan 2: Susu hanya valid untuk SAPI_PERAH, KAMBING, DOMBA
                    if ($kategoriNama === 'SUSU') {
                        $validSusu = ['SAPI_PERAH', 'KAMBING', 'DOMBA'];
                        if (!in_array($komoditasKode, $validSusu)) {
                            $validator->errors()->add(
                                'komoditas_id',
                                "Kategori Susu tidak valid untuk komoditas {$komoditas->nama}. Susu hanya dapat dihasilkan oleh Sapi Perah, Kambing, atau Domba."
                            );
                        }
                    }

                    // Aturan 3: Telur HANYA valid untuk AYAM_TELUR
                    if ($kategoriNama === 'TELUR') {
                        if ($komoditasKode !== 'AYAM_TELUR') {
                            $validator->errors()->add(
                                'komoditas_id',
                                "Kategori Telur hanya dapat dihasilkan oleh komoditas Ayam Ras Petelur (AYAM_TELUR)."
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
            'desa_id.required' => 'Desa wajib dipilih.',
            'desa_id.exists' => 'Desa tidak ditemukan dalam basis data.',
            'komoditas_id.required' => 'Komoditas ternak wajib dipilih.',
            'kategori_produksi_id.required' => 'Kategori produksi wajib dipilih.',
            'jumlah_produksi.min' => 'Jumlah produksi tidak boleh bernilai negatif.',
            'triwulan.between' => 'Triwulan harus berada pada rentang 1 hingga 4.',
        ];
    }
}
