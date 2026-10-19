<?php

namespace App\Http\Requests\Internal;

use Illuminate\Foundation\Http\FormRequest;

class PopulasiTernakRequest extends FormRequest
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
            'tahun' => ['required', 'integer', 'between:2020,2035'],
            'triwulan' => ['required', 'integer', 'between:1,4'],
            'jumlah_jantan' => ['required', 'integer', 'min:0'],
            'jumlah_betina' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Custom validation rules untuk mencegah duplikasi data periode.
     */
    public function after(): array
    {
        return [
            function (\Illuminate\Validation\Validator $validator) {
                $data = $validator->getData();
                $desaId = $data['desa_id'] ?? null;
                $komoditasId = $data['komoditas_id'] ?? null;
                $tahun = $data['tahun'] ?? null;
                $triwulan = $data['triwulan'] ?? null;

                if ($desaId && $komoditasId && $tahun && $triwulan) {
                    $id = $this->route('id') ?? $this->route('populasi');
                    $query = \App\Models\PopulasiTernak::where('desa_id', $desaId)
                        ->where('komoditas_id', $komoditasId)
                        ->where('tahun', $tahun)
                        ->where('triwulan', $triwulan);
                    if ($id) {
                        $query->where('id', '!=', $id);
                    }
                    if ($query->exists()) {
                        $validator->errors()->add(
                            'komoditas_id',
                            'Catatan populasi untuk desa, komoditas, dan periode tahun/triwulan ini sudah ada dalam sistem.'
                        );
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
            'desa_id.required' => 'Desa lokasi pencatatan populasi wajib dipilih.',
            'komoditas_id.required' => 'Jenis komoditas ternak wajib dipilih.',
            'jumlah_jantan.min' => 'Jumlah populasi jantan tidak boleh bernilai negatif.',
            'jumlah_betina.min' => 'Jumlah populasi betina tidak boleh bernilai negatif.',
            'tahun.between' => 'Tahun harus berada pada rentang 2020 hingga 2035.',
            'triwulan.between' => 'Triwulan harus berada pada rentang 1 hingga 4.',
        ];
    }
}
