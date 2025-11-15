# Real-Time Push Bildirişləri (Backend) Roadmap

## 0. Mövcud vəziyyət və tələblər
- Mail bildirişləri artıq mövcud job/controller axınlarından gedir və razı qalınan struktur saxlanacaq.
- `notifications` cədvəli və REST endpointi var, lakin real-time push və istifadəçi seçimləri yoxdur.
- İstifadəçi həm mail, həm də push kanallarını ayrıca deaktiv edə bilməlidir.

## 1. Məlumat bazası və model səviyyəsi
1. `users` cədvəlinə `email_notifications_enabled` və `push_notifications_enabled` (default `true`) sütunlarını əlavə edən migration.
2. `App\Models\User` daxilində `fillable` və `casts` massivlərinə bu sahələri əlavə et.
3. `notifications` cədvəlində title/message JSON strukturu artıq mövcuddur; lazım olarsa `data` (JSON) və `channel` sütunları ilə genişləndir.

## 2. NotificationService səviyyəsi
1. `App\Services\NotificationService` yaradaraq bütün mail/push çağırışlarını mərkəzləşdir.
2. Servisə `send(User $user, array $payload, array $channels = ['email','push'])` kimi API ver; daxilində:
   - İstifadəçi parametrlərinə əsasən kanalları filtrlə.
   - `notifications` cədvəlinə qeyd yarat.
   - Uyğun kanallar üçün job/event dispatch et.
3. Mövcud kontrollerlərdə (`TrainingController`, `InternshipProgramController`, job-lar) birbaşa `Mail::to` əvəzinə servisi çağır.

## 3. Real-time infrastruktur
1. Broadcasting driver seçimi:
   - On-prem: `beyondcode/laravel-websockets` + Redis queue.
   - Managed: Pusher (əgər infra hazırdırsa).
2. `BroadcastServiceProvider`-də `private-notifications.{userId}` kanalı üçün auth qaydası (`$user->id === (int)$userId`).
3. `NotificationCreated` event-ini `ShouldBroadcast` ilə qur və `NotificationResource` vasitəsilə JSON cavabı formalaşdır.

## 4. Queue və job axını
1. Mail/push göndərişini kuyruklara ötür (`SendNotificationEmailJob`, `BroadcastNotificationJob`).
2. `redis` queue driver-i aktivləşdir, `.env` konfiqurasiyasını yenilə, supervisor və ya horizon ilə işə sal.

## 5. REST API genişlənməsi
1. `PATCH /api/me/notification-preferences` endpointi (policy: yalnız öz profili) yaradaraq istifadəçi seçimlərini yenilə.
2. `NotificationController`-ə aşağıdakıları əlavə et:
   - `unreadCount`
   - `markAllRead`
   - `index`-ə filter parametrləri (type, is_read).
3. API cavablarını `NotificationResource` ilə standartlaşdır.

## 6. Frontend ilə inteqrasiya üçün backend tələbləri
- Laravel Echo üçün `broadcasting.php` və `services.php` konfiqurasiyasını hazırla.
- Authenticated user üçün `api/user` cavabına `email_notifications_enabled` və `push_notifications_enabled` sahələrini daxil et.
- Real-time badge sinxronu üçün `unreadCount` endpointini cache-lə.

## 7. Monitorinq və audit
- Push event və mail job-ları üçün strukturlaşdırılmış log formatı (`channel`, `user_id`, `notification_id`).
- `failed_jobs` üçün alerting strategiyası (Horizon notifications, Slack webhook və s.).
- Audit məqsədilə admin panelində notification preference dəyişikliklərini `audit_logs` cədvəlinə yaz (mövcudsa).

## 8. Test və mərhələli buraxılış
1. Unit testlər:
   - NotificationService kanalları düzgün filtrləyir?
   - Preference endpoint validation.
2. Feature testlər:
   - Auth edilmiş user push kanalını söndürdükdə event getmir.
   - Notification API-ləri.
3. QA mərhələsi:
   - Staging-də WebSocket load testi.
   - Progressive rollout: əvvəlcə adminlər üçün, sonra bütün user-lər.

## Açıq məsələlər
- Hansı WebSocket hosting modeli seçiləcək (öz server vs managed)?
- Frontend stack-də Laravel Echo artıq istifadə olunurmu?
- Push bildirişləri üçün əlavə mobil/desktop kanalları planlaşdırılırmı?

