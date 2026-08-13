<?php

namespace App\Http\Controllers\Siswa;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends BaseSiswaController
{
    public function show()
    {
        $student = $this->student();
        return view('siswa.profile', compact('student'));
    }

    /**
     * Siswa hanya boleh mengubah kontak, alamat & foto. Data akademik
     * (nama, NIS, kelas, paket, program, kuota) tetap dikelola admin.
     */
    public function update(Request $request)
    {
        $student = $this->student();

        $validated = $request->validate([
            'phone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20|required_without:phone',
            'address' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|max:5120',
        ], [
            'whatsapp.required_without' => 'Isi nomor Telp/HP atau WhatsApp.',
        ]);

        if ($request->hasFile('photo')) {
            if ($student->photo) {
                Storage::disk('public')->delete($student->photo);
            }
            $validated['photo'] = $request->file('photo')->store('students', 'public');
        }

        $student->update($validated);

        return redirect()->route('siswa.profile')->with('success', 'Profil berhasil diperbarui.');
    }
}
