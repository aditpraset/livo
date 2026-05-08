<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\StudentRegistration;

class HomeController extends Controller
{
    public function index()
    {
        return view('website.index');
    }

    public function registration()
    {
        return view('website.registration');
    }

    public function storeRegistration(Request $request)
    {
        $validated = $request->validate([
            'full_name'            => 'required|string|max:255',
            'nickname'             => 'nullable|string|max:255',
            'registration_date'    => 'nullable|date',
            'birth_date'           => 'nullable|date',
            'religion'             => 'nullable|string|max:50',
            'gender'               => 'nullable|string|max:20',
            'grade'                => 'nullable|string|max:50',
            'school_origin'        => 'nullable|string|max:255',
            'father_name'          => 'nullable|string|max:255',
            'mother_name'          => 'nullable|string|max:255',
            'guardian_name'        => 'nullable|string|max:255',
            'address'              => 'nullable|string',
            'email'                => 'nullable|email|max:255',
            'phone'                => 'nullable|string|max:20',
            'whatsapp'             => 'nullable|string|max:20',
            'class_type'           => 'nullable|string|max:50',
            'kbm_process'          => 'nullable|string|max:100',
            'package'              => 'nullable|string|max:100',
            'program'              => 'nullable|string|max:100',
            'selected_days'        => 'nullable|string|max:100',
            'study_session'        => 'nullable|string|max:100',
            'school_curriculum'    => 'nullable|string|max:100',
            'learning_material'    => 'nullable|string|max:255',
            'promo_code'           => 'nullable|string|max:50',
            'registration_info'    => 'nullable|string|max:100',
            'marketing_pic'        => 'nullable|string|max:100',
            // 'g-recaptcha-response' => 'required',
        ], [
            'g-recaptcha-response.required' => 'Harap verifikasi bahwa Anda bukan robot.',
        ]);

        // Verify reCAPTCHA with Google
        // $recaptchaResponse = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
        //     'secret'   => config('services.recaptcha.secret_key'),
        //     'response' => $request->input('g-recaptcha-response'),
        //     'remoteip' => $request->ip(),
        // ]);

        // if (!$recaptchaResponse->json('success')) {
        //     return redirect()->back()
        //         ->withInput()
        //         ->withErrors(['g-recaptcha-response' => 'Verifikasi reCAPTCHA gagal. Silakan coba lagi.']);
        // }

        // // Remove reCAPTCHA field before saving
        // $data = $request->except(['_token', 'g-recaptcha-response']);
        // StudentRegistration::create($data);

        StudentRegistration::create($request->all());

        return redirect()->back()->with('success', 'Pendaftaran berhasil dikirim! Tim kami akan segera menghubungi Anda.');
    }
}
