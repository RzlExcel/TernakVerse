<?php

namespace App\Http\Requests\Internal;

use Illuminate\Foundation\Http\FormRequest;

class FasilitasRphRequest extends FormRequest
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
            'kecamatan_id' => ['required', 'integer', 'exists:kecamatan,id'],
            'nama_rph' => ['required', 'string', 'max:150'],
            'alamat_lengkap' => ['required', 'string'],
            'jenis_pengelola' => ['required', 'string', 'in:Pemerintah,Swasta,Kelompok Tani'],
            'jenis_hewan_potong' => ['required', 'string', 'max:100'],
            'kapasitas_potong_ekor_hari' => ['required', 'integer', 'min:0'],
            'status_operasional' => ['required', 'string', 'in:Aktif,Tidak Aktif,Renovasi'],
            'latitude' => ['required', 'numeric', 'between:-7.6800,-7.1800'],
            'longitude' => ['required', 'numeric', 'between:108.9200,109.4500'],
            'nomor_kontak' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'kecamatan_id.required' => 'Kecamatan lokasi fasilitas RPH wajib dipilih.',
            'nama_rph.required' => 'Nama fasilitas RPH wajib diisi.',
            'jenis_pengelola.in' => 'Pengelola RPH harus berupa Pemerintah, Swasta, atau Kelompok Tani.',
            'status_operasional.in' => 'Status operasional harus berupa Aktif, Tidak Aktif, atau Renovasi.',
            'latitude.between' => 'Titik Latitude (:input) berada di luar batas geospasial Kabupaten Banyumas (-7.6800 s/d -7.1800).',
            'longitude.between' => 'Titik Longitude (:input) berada di luar batas geospasial Kabupaten Banyumas (108.9200 s/d 109.4500).',
        ];
    }
}
