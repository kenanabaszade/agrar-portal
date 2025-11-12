<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <title>Təlim tamamlandı</title>
</head>
<body style="font-family: Arial, sans-serif; color:#1F2933; line-height:1.6; background:#f9fafb; padding:24px;">
    <div style="max-width:640px; margin:0 auto; background:#ffffff; border-radius:8px; padding:24px; box-shadow:0 2px 8px rgba(15,23,42,0.1);">
        <h2 style="margin-top:0; color:#047857;">Təbriklər!</h2>

        <p>
            Hörmətli {{ $user->first_name ?? $user->name ?? ($user->email ?? 'istifadəçi') }},
        </p>

        <p>
            "{{ $details['training_title'] ?? (is_array($training->title) ? ($training->title['az'] ?? reset($training->title)) : $training->title) }}"
            adlı təlimi uğurla tamamladığınız üçün sizi təbrik edirik.
        </p>

        <h3 style="color:#0f172a;">Təlim haqqında məlumat</h3>
        <ul style="padding-left:20px;">
            <li><strong>Kateqoriya:</strong> {{ $details['category'] ?? '—' }}</li>
            <li><strong>Təlimçi:</strong> {{ $details['trainer_name'] ?? '—' }}</li>
            <li><strong>Modul sayı:</strong> {{ $details['modules_count'] ?? 0 }}</li>
            <li><strong>Dərs sayı:</strong> {{ $details['lessons_count'] ?? 0 }}</li>
            <li><strong>Tamamlanan dərs sayı:</strong> {{ $details['completed_lessons_count'] ?? 0 }}</li>
            <li><strong>Təlimin ümumi müddəti:</strong> {{ isset($details['total_duration_minutes']) ? round(($details['total_duration_minutes'] ?? 0) / 60, 2) : '—' }} saat</li>
            <li><strong>Sertifikat:</strong> {{ !empty($details['has_certificate']) ? 'Bəli' : 'Xeyr' }}</li>
            @if(!empty($details['certificate_number']))
                <li><strong>Sertifikat nömrəsi:</strong> {{ $details['certificate_number'] }}</li>
            @endif
            <li><strong>Sertifikat üçün imtahan lazımdır:</strong> {{ !empty($details['certificate_requires_exam']) ? 'Bəli' : 'Xeyr' }}</li>
        </ul>

        @if(!empty($details['has_certificate']))
            @if(!empty($details['certificate_requires_exam']))
                <p>
                    Sertifikatın aktivləşməsi üçün bu təlimin imtahan mərhələsini uğurla tamamlamalısınız.
                    İmtahan nəticəniz təsdiqləndikdən sonra sertifikatınız avtomatik olaraq əlçatan olacaq.
                </p>
            @else
                @if(!empty($details['certificate_available']))
                    <p>
                        Sertifikatınız hazırdır! Aşağıdakı keçidlərdən istifadə edərək PDF faylını yükləyə və ya onlayn izləyə bilərsiniz:
                    </p>
                    <p>
                        @if(!empty($details['certificate_download_url']))
                            <a href="{{ $details['certificate_download_url'] }}" style="color:#047857; text-decoration:none;">PDF yüklə</a>
                        @endif
                        @if(!empty($details['certificate_download_url']) && !empty($details['certificate_preview_url']))
                            &nbsp;|&nbsp;
                        @endif
                        @if(!empty($details['certificate_preview_url']))
                            <a href="{{ $details['certificate_preview_url'] }}" style="color:#047857; text-decoration:none;">Onlayn bax</a>
                        @endif
                    </p>
                @else
                    <p>Sertifikatınız hazırlanma mərhələsindədir. Tezliklə sizə əlavə məlumat göndəriləcək.</p>
                @endif
            @endif
        @endif

        <p>
            Əlavə suallarınız olarsa, bizimlə əlaqə saxlamaqdan çəkinməyin.
        </p>

        <p style="margin-bottom:0;">
            Hörmətlə,<br>
            <strong>Aqrar Portal komandası</strong>
        </p>
    </div>
</body>
</html>

