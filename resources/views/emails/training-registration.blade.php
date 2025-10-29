<!DOCTYPE html>
<html>
<head>
    <title>Təlim Qeydiyyatı Təsdiqləndi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .success-badge {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            display: inline-block;
            margin: 20px 0;
            font-weight: bold;
        }
        .training-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .training-title {
            color: #495057;
            font-size: 24px;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .training-info {
            margin: 10px 0;
        }
        .info-label {
            font-weight: bold;
            color: #6c757d;
        }
        .offline-details {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .online-details {
            background: #f3e5f5;
            border-left: 4px solid #9c27b0;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .meet-link {
            background: #4caf50;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 6px;
            display: inline-block;
            margin: 10px 0;
            font-weight: bold;
        }
        .meet-link:hover {
            background: #45a049;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            color: #6c757d;
            font-size: 14px;
        }
        .cta-button {
            background: #007bff;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 6px;
            display: inline-block;
            margin: 20px 0;
            font-weight: bold;
        }
        .cta-button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎉 Təlim Qeydiyyatı Uğurla Təsdiqləndi!</h1>
        <p>Siz {{ $training->title }} təliminə qeydiyyatdan keçdiniz</p>
    </div>

    <div class="content">
        <div class="success-badge">
            ✅ Qeydiyyat Təsdiqləndi
        </div>

        <p>Salam <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>,</p>
        
        <p>Təbriklər! Siz aşağıdakı təlimə uğurla qeydiyyatdan keçdiniz:</p>

        <div class="training-card">
            <div class="training-title">{{ $training->title }}</div>
            
            <div class="training-info">
                <span class="info-label">Təsvir:</span> {{ $training->description }}
            </div>
            
            <div class="training-info">
                <span class="info-label">Kateqoriya:</span> {{ $training->category }}
            </div>
            
            <div class="training-info">
                <span class="info-label">Çətinlik:</span> 
                @if($training->difficulty === 'beginner')
                    Başlanğıc
                @elseif($training->difficulty === 'intermediate')
                    Orta
                @elseif($training->difficulty === 'advanced')
                    Qabaqcıl
                @else
                    {{ $training->difficulty }}
                @endif
            </div>
            
            @php
                $startDate = \Carbon\Carbon::parse($training->start_date);
                $endDate = \Carbon\Carbon::parse($training->end_date);
                $isSameDay = $startDate->isSameDay($endDate);
            @endphp
            
            @if($isSameDay)
                <div class="training-info">
                    <span class="info-label">Tarix:</span> {{ $startDate->format('d.m.Y') }}
                </div>
                @if($training->start_time && $training->end_time)
                    <div class="training-info">
                        <span class="info-label">Vaxt:</span> {{ $training->start_time }} - {{ $training->end_time }}
                    </div>
                @endif
            @else
                <div class="training-info">
                    <span class="info-label">Başlama Tarixi:</span> {{ $startDate->format('d.m.Y') }}
                </div>
                
                <div class="training-info">
                    <span class="info-label">Bitmə Tarixi:</span> {{ $endDate->format('d.m.Y') }}
                </div>
                
                @if($training->start_time && $training->end_time)
                    <div class="training-info">
                        <span class="info-label">Hər gün vaxtı:</span> {{ $training->start_time }} - {{ $training->end_time }}
                    </div>
                @endif
            @endif

            @if($training->type === 'offline')
                <div class="offline-details">
                    <h3>📍 Offline Təlim Məlumatları</h3>
                    @if(isset($training->offline_details['address']))
                        <div class="training-info">
                            <span class="info-label">Ünvan:</span> {{ $training->offline_details['address'] }}
                        </div>
                    @endif
                    @if(isset($training->offline_details['participant_size']))
                        <div class="training-info">
                            <span class="info-label">İştirakçı Sayı:</span> {{ $training->offline_details['participant_size'] }} nəfər
                        </div>
                    @endif
                    @if(isset($training->offline_details['coordinates']))
                        @php
                            $coordinates = $training->offline_details['coordinates'];
                            $googleMapsUrl = "https://www.google.com/maps?q=" . urlencode($coordinates);
                        @endphp
                        <div class="training-info">
                            <span class="info-label">Yerləşim:</span><br>
                            <a href="{{ $googleMapsUrl }}" target="_blank" class="meet-link" style="background: #ff6b6b;">
                                🗺️ Google Maps-də Aç
                            </a>
                        </div>
                    @endif
                </div>
            @elseif($training->type === 'online')
                <div class="online-details">
                    <h3>💻 Online Təlim Məlumatları</h3>
                    @if($training->google_meet_link)
                        <div class="training-info">
                            <span class="info-label">Google Meet Linki:</span><br>
                            <a href="{{ $training->google_meet_link }}" class="meet-link">
                                🎥 Google Meet-ə Qoşul
                            </a>
                        </div>
                    @endif
                    @if($training->start_time && $training->end_time)
                        <div class="training-info">
                            <span class="info-label">Vaxt:</span> {{ $training->start_time }} - {{ $training->end_time }} ({{ $training->timezone ?? 'UTC' }})
                        </div>
                    @endif
                </div>
            @endif

            @if($training->trainer)
                <div class="training-info">
                    <span class="info-label">Təlimçi:</span> {{ $training->trainer->first_name }} {{ $training->trainer->last_name }}
                </div>
                <div class="training-info">
                    <span class="info-label">Təlimçi Email:</span> {{ $training->trainer->email }}
                </div>
                @if($training->trainer->phone)
                    <div class="training-info">
                        <span class="info-label">Təlimçi Telefon:</span> {{ $training->trainer->phone }}
                    </div>
                @endif
            @endif
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ url('/trainings/' . $training->id) }}" class="cta-button">
                Təlimə Bax
            </a>
        </div>

        <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 6px; margin: 20px 0;">
            <strong>📝 Qeyd:</strong> Təlim başlamazdan əvvəl sizə xatırlatma email-i göndəriləcək. 
            @if($training->type === 'offline')
                Offline təlim üçün göstərilən ünvanda hazır olun.
            @elseif($training->type === 'online')
                Online təlim üçün Google Meet linkinə daxil olun.
            @endif
        </div>
    </div>

    <div class="footer">
        <p>Hörmətlə,<br><strong>Aqrar Portal Komandası</strong></p>
        <p>Bu email avtomatik olaraq göndərilmişdir. Zəhmət olmasa cavab verməyin.</p>
    </div>
</body>
</html>
