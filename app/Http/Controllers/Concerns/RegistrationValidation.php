<?php

namespace App\Http\Controllers\Concerns;

/**
 * Aturan validasi bersama untuk form registrasi/pendaftaran siswa
 * (dipakai baik dari sisi admin maupun user/publik).
 */
trait RegistrationValidation
{
    /** Nama field yang ramah dibaca untuk pesan validasi. */
    protected function registrationAttributes(): array
    {
        return [
            'full_name'          => 'Nama Lengkap',
            'birth_date'         => 'Tanggal Lahir',
            'gender'             => 'Jenis Kelamin',
            'grade'              => 'Kelas',
            'school_origin'      => 'Asal Sekolah',
            'email'              => 'Email',
            'phone'              => 'Nomor Telp/HP',
            'whatsapp'           => 'Nomor WhatsApp',
            'father_name'        => 'Nama Ayah',
            'mother_name'        => 'Nama Ibu',
            'guardian_name'      => 'Nama Wali',
            'program_id'         => 'Program Belajar',
            'grade_id'           => 'Jenjang',
            'duration'           => 'Durasi',
            'package_id'         => 'Paket',
            'program'            => 'Mata Pelajaran',
            'class_schedule_ids' => 'Pilihan Jadwal',
        ];
    }

    /** Pesan validasi berbahasa Indonesia. */
    protected function registrationMessages(): array
    {
        return [
            'required'             => ':attribute wajib diisi.',
            'required_without'     => ':attribute wajib diisi (isi Telp/HP atau WhatsApp).',
            'required_without_all' => ':attribute wajib diisi (minimal salah satu dari Ayah/Ibu/Wali).',
            'array'                => ':attribute wajib dipilih.',
            'min'                  => ':attribute wajib dipilih minimal :min.',
            'email'                => 'Format :attribute tidak valid.',
            'exists'               => ':attribute yang dipilih tidak valid.',
            'in'                   => ':attribute yang dipilih tidak valid.',
            'date'                 => 'Format :attribute tidak valid.',
        ];
    }

    /** Aturan field yang kini wajib diisi (di-merge ke aturan dasar masing-masing controller). */
    protected function registrationRequiredRules(): array
    {
        return [
            'birth_date'           => 'required|date',
            'gender'               => 'required|string|max:20',
            'grade'                => 'required|string|max:50',
            'school_origin'        => 'required|string|max:255',
            'email'                => 'nullable|email|max:255',
            'phone'                => 'nullable|string|max:20|required_without:whatsapp',
            'whatsapp'             => 'nullable|string|max:20|required_without:phone',
            'father_name'          => 'nullable|string|max:255|required_without_all:mother_name,guardian_name',
            'mother_name'          => 'nullable|string|max:255',
            'guardian_name'        => 'nullable|string|max:255',
            'program_id'           => 'required|exists:programs,id',
            'grade_id'             => 'required|exists:grades,id',
            'duration'             => 'required|integer|in:1,3,6,12',
            'package_id'           => 'required|exists:packages,id',
            'program'              => 'required|array|min:1',
            'program.*'            => 'string|max:100',
            'class_schedule_ids'   => 'required|array|min:1',
            'class_schedule_ids.*' => 'nullable|exists:class_schedules,id',
        ];
    }
}
