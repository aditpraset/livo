<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Modul belajar (materi) per mata pelajaran + kelas. Admin mengelola (upload/
 * edit/hapus), tutor hanya melihat & mengunduh (lihat Tutor\ModuleController).
 * Daftar ditampilkan dikelompokkan per kelas, bukan tabel datar — jumlahnya
 * relatif kecil (materi ajar, bukan data transaksional).
 */
class ModuleController extends Controller
{
    /** Urutan kelas yang dikenal, sama dengan Master Jadwal (ClassScheduleController::KELAS). */
    private const KELAS = ClassScheduleController::KELAS;

    public function index()
    {
        $modules = Module::with('subject')
            ->orderBy('nama_modul')
            ->get()
            ->groupBy('kelas')
            ->sortBy(fn ($items, $kelas) => array_search($kelas, self::KELAS) === false
                ? PHP_INT_MAX
                : array_search($kelas, self::KELAS));

        $subjects = Subject::orderBy('subject_name')->get(['id', 'subject_name']);

        return view('admin.modules.index', [
            'modules'  => $modules,
            'subjects' => $subjects,
            'kelasList' => self::KELAS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request, true);

        $file = $request->file('file');
        $validated['file'] = $file->store('modules', 'public');
        $validated['file_original_name'] = $file->getClientOriginalName();

        Module::create($validated);

        return response()->json(['success' => true, 'message' => 'Modul berhasil ditambahkan.']);
    }

    public function update(Request $request, Module $module)
    {
        $validated = $this->validateData($request, false);

        if ($request->hasFile('file')) {
            if ($module->file) {
                Storage::disk('public')->delete($module->file);
            }
            $file = $request->file('file');
            $validated['file'] = $file->store('modules', 'public');
            $validated['file_original_name'] = $file->getClientOriginalName();
        }

        $module->update($validated);

        return response()->json(['success' => true, 'message' => 'Modul berhasil diperbarui.']);
    }

    public function destroy(Module $module)
    {
        if ($module->file) {
            Storage::disk('public')->delete($module->file);
        }
        $module->delete();

        return response()->json(['success' => true, 'message' => 'Modul berhasil dihapus.']);
    }

    /** Unduh file modul dengan nama file aslinya (bukan nama hash di storage). */
    public function download(Module $module)
    {
        abort_unless($module->file && Storage::disk('public')->exists($module->file), 404);

        return Storage::disk('public')->download($module->file, $module->file_original_name ?: basename($module->file));
    }

    private function validateData(Request $request, bool $fileRequired): array
    {
        $validated = $request->validate([
            'subject_id' => 'nullable|exists:subjects,id',
            'kelas'      => 'required|string|in:' . implode(',', self::KELAS),
            'nama_modul' => 'required|string|max:150',
            'file'       => ($fileRequired ? 'required' : 'nullable') . '|file|mimes:xlsx,xls,pdf,doc,docx|max:20480',
        ], [], [
            'subject_id' => 'Mata Pelajaran',
            'kelas'      => 'Kelas',
            'nama_modul' => 'Nama Modul',
            'file'       => 'File Modul',
        ]);

        unset($validated['file']); // ditangani manual di store()/update()

        return $validated;
    }
}
