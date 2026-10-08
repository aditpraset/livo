<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Admin\ClassScheduleController;
use App\Models\Module;
use Illuminate\Support\Facades\Storage;

/** Modul belajar: tutor hanya melihat daftar & mengunduh (admin yang mengelola). */
class ModuleController extends BaseTutorController
{
    public function index()
    {
        $modules = Module::with('subject')
            ->orderBy('nama_modul')
            ->get()
            ->groupBy('kelas')
            ->sortBy(fn ($items, $kelas) => array_search($kelas, ClassScheduleController::KELAS) === false
                ? PHP_INT_MAX
                : array_search($kelas, ClassScheduleController::KELAS));

        return view('tutor.modules.index', compact('modules'));
    }

    public function download(Module $module)
    {
        abort_unless($module->file && Storage::disk('public')->exists($module->file), 404);

        return Storage::disk('public')->download($module->file, $module->file_original_name ?: basename($module->file));
    }
}
