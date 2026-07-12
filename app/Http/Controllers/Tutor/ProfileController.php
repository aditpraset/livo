<?php

namespace App\Http\Controllers\Tutor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends BaseTutorController
{
    public function show()
    {
        $tutor = $this->tutor();
        return view('tutor.profile', compact('tutor'));
    }

    /** Tutor hanya boleh mengubah kontak, rekening & foto — data lain dikelola admin. */
    public function update(Request $request)
    {
        $tutor = $this->tutor();

        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'no_rekening' => 'nullable|string|max:50',
            'photo' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('photo')) {
            if ($tutor->photo) {
                Storage::disk('public')->delete($tutor->photo);
            }
            $validated['photo'] = $request->file('photo')->store('tutors', 'public');
        }

        $tutor->update($validated);

        return redirect()->route('tutor.profile')->with('success', 'Profil berhasil diperbarui.');
    }
}
