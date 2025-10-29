<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $action === 'created' ? 'Yeni Təlim' : 'Təlim Yeniləndi' }}</title>
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
        .training-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .training-title {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .training-type {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 15px;
        }
        .type-online {
            background: #e3f2fd;
            color: #1976d2;
        }
        .type-offline {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        .type-video {
            background: #e8f5e8;
            color: #388e3c;
        }
        .training-info {
            margin: 15px 0;
        }
        .info-row {
            display: flex;
            margin: 8px 0;
        }
        .info-label {
            font-weight: bold;
            width: 120px;
            color: #555;
        }
        .info-value {
            flex: 1;
        }
        .offline-details {
            background: #f8f9fa;
            border-left: 4px solid #7b1fa2;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 5px 5px 0;
        }
        .google-meet {
            background: #e3f2fd;
            border-left: 4px solid #1976d2;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 5px 5px 0;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $action === 'created' ? '🎉 Yeni Təlim Yaratıldı!' : '📝 Təlim Yeniləndi!' }}</h1>
        <p>{{ $action === 'created' ? 'Yeni bir təlim əlavə edildi' : 'Mövcud təlim yeniləndi' }}</p>
    </div>

    <div class="content">
        <div class="training-card">
            <div class="training-title">{{ $training->title }}</div>
            
            <div class="training-type type-{{ $training->type }}">
                {{ $training->type === 'online' ? 'Online Təlim' : ($training->type === 'offline' ? 'Offline Təlim' : 'Video Təlim') }}
            </div>

            @if($training->description)
                <p style="color: #666; margin: 15px 0;">{{ $training->description }}</p>
            @endif

            <div class="training-info">
                <div class="info-row">
                    <span class="info-label">Kateqoriya:</span>
                    <span class="info-value">{{ $training->category ?? 'Təyin edilməyib' }}</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Çətinlik:</span>
                    <span class="info-value">
                        @switch($training->difficulty)
                            @case('beginner') Başlanğıc @break
                            @case('intermediate') Orta @break
                            @case('advanced') Qabaqcıl @break
                            @case('expert') Ekspert @break
                            @default {{ $training->difficulty }}
                        @endswitch
                    </span>
                </div>

                @if($training->start_date)
                    <div class="info-row">
                        <span class="info-label">Başlama tarixi:</span>
                        <span class="info-value">{{ \Carbon\Carbon::parse($training->start_date)->format('d.m.Y') }}</span>
                    </div>
                @endif

                @if($training->end_date)
                    <div class="info-row">
                        <span class="info-label">Bitmə tarixi:</span>
                        <span class="info-value">{{ \Carbon\Carbon::parse($training->end_date)->format('d.m.Y') }}</span>
                    </div>
                @endif

                @if($training->start_time && $training->end_time)
                    <div class="info-row">
                        <span class="info-label">Vaxt:</span>
                        <span class="info-value">{{ $training->start_time }} - {{ $training->end_time }}</span>
                    </div>
                @endif

                @if($training->timezone)
                    <div class="info-row">
                        <span class="info-label">Vaxt zonası:</span>
                        <span class="info-value">{{ $training->timezone }}</span>
                    </div>
                @endif
            </div>

            @if($training->type === 'offline' && isset($training->offline_details))
                <div class="offline-details">
                    <h4 style="margin-top: 0; color: #7b1fa2;">📍 Offline Təlim Detalları</h4>
                    @if(isset($training->offline_details['address']) && $training->offline_details['address'])
                        <div class="info-row">
                            <span class="info-label">Ünvan:</span>
                            <span class="info-value">{{ $training->offline_details['address'] }}</span>
                        </div>
                    @endif
                    @if(isset($training->offline_details['participant_size']) && $training->offline_details['participant_size'])
                        <div class="info-row">
                            <span class="info-label">İştirakçı sayı:</span>
                            <span class="info-value">{{ $training->offline_details['participant_size'] }}</span>
                        </div>
                    @endif
                    @if(isset($training->offline_details['coordinates']) && $training->offline_details['coordinates'])
                        <div class="info-row">
                            <span class="info-label">Koordinatlar:</span>
                            <span class="info-value">{{ $training->offline_details['coordinates'] }}</span>
                        </div>
                    @endif
                </div>
            @endif

            @if($training->type === 'online' && $googleMeetLink)
                <div class="google-meet">
                    <h4 style="margin-top: 0; color: #1976d2;">🔗 Google Meet Linki</h4>
                    <p><a href="{{ $googleMeetLink }}" style="color: #1976d2; text-decoration: none;">{{ $googleMeetLink }}</a></p>
                </div>
            @endif

            @if($training->has_certificate)
                <div style="background: #e8f5e8; border-left: 4px solid #388e3c; padding: 15px; margin: 15px 0; border-radius: 0 5px 5px 0;">
                    <h4 style="margin-top: 0; color: #388e3c;">🏆 Sertifikat</h4>
                    <p style="margin: 0;">Bu təlimi tamamladıqdan sonra sertifikat alacaqsınız!</p>
                </div>
            @endif

            <div style="text-align: center;">
                <a href="{{ url('/trainings/' . $training->id) }}" class="cta-button">
                    {{ $action === 'created' ? 'Təlimə Bax' : 'Yenilənmiş Təlimə Bax' }}
                </a>
            </div>
        </div>

        <div class="footer">
            <p>Bu email Aqrar Portal sistemindən avtomatik göndərilmişdir.</p>
            <p>Əgər bu emaili siz istəməmisinizsə, zəhmət olmasa bizə məlumat verin.</p>
        </div>
    </div>
</body>
</html>


